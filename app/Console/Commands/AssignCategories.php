<?php

namespace App\Console\Commands;


use App\Models\Category;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:categories {--parents} {--skip=0} {--single-import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns categories to organizations based on their NTEE code';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function initalize()
    {
        $this->info('Running a single insert query');

        $categories = Category::query()->where('ntees', '!=', '[]')->get();
        $cats = [];
        foreach ($categories as $category) {
            foreach ($category->ntees as $ntee) {
                $cats[$ntee] = $category->id;
            }
        }

        $ntees = array_keys($cats);
        $jsonCategories = json_encode($cats);
        $nteeArray = "'" . implode("','", $ntees) . "'";

        $this->info('This may take 10-15 minutes to execute!');

        DB::select("
            insert into category_organization (organization_uuid, category_id)
            select o.uuid, ifnull(json_extract(cats.kvs, concat('$.', o.ntee)), 1000) as cid from organizations o
            inner join (select '$jsonCategories' as kvs) cats
            where ntee is not null and ntee in ($nteeArray);
        ");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('single-import')) {
            $this->initalize();
            return;
        }

        $this->process();
    }

    public function process()
    {
        $this->info('Running one query per organization');

        $skipped = $this->option('skip');
        $query = DB::select('select count(name) as count from organizations where ntee is not null');
        $count = $query[0]->count - $skipped;
        $bar = $this->output->createProgressBar($count);

        if ($skipped > 0) {
            $this->info("Skipping $skipped number of rows");
        }

        if ($this->option('parents')) {
            $this->info('Including Parents and Grandparents');
        } else {
            $this->info('Skipping Parents and Grandparents');
        }

        Organization::query()
            ->whereNotNull('ntee')
            ->skip($skipped)
            ->chunk(10000, function ($orgs) use ($bar) {
                foreach ($orgs as $org) {
                    $category = Category::query()->whereJsonContains('ntees', $org->ntee)->first();

                    if (!$category) {
                        $bar->advance();
                        continue;
                    }

                    $categoryIds = [$category->id];

                    if ($this->option('parents')) {
                        for ($i = 0; $i < 5; $i++) {
                            $parentCategory = $category->parent;
                            if ($parentCategory) {
                                $categoryIds[] = $parentCategory->id;
                                $category = $parentCategory;
                            } else {
                                break;
                            }
                        }
                    }

                    $org->categories()->sync($categoryIds);
                    $bar->advance();
                }
            });
    }
}
