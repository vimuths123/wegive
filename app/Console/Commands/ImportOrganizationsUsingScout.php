<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Scout\Events\ModelsImported;

class ImportOrganizationsUsingScout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:organizations {--skip=0} {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Dispatcher $events)
    {
        $skip = $this->option('skip') ?? 0;
        $chunk = $this->option('chunk') ?? 1000;
        $count = Organization::count() - $skip;

        $this->info("Importing $count Organizations, $chunk at a time, skipping $skip initial records.");
        $model = new Organization();

        $events->listen(ModelsImported::class, function ($event) use (&$skip) {
            $key = $event->models->last()->getScoutKey();
            $this->line('<comment>Imported Organizations up to ID:</comment> ' . $key);
            $skip = $key;
        });

        while ($skip <= $count) {
            try {
                $model->query()->where('id', '>', $skip)->searchable($chunk);
            } catch (\Exception $exception) {
                $this->info($exception);
            }
        }

        return 0;
    }
}
