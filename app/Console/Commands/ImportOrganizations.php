<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportOrganizations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:organizations {--skip-previous}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Organizations from nonprofit export main.csv';

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
    public function handle()
    {
        $bufferLimit = 1000;
        ini_set('memory_limit', '-1');

        Organization::unguard();

        $eins = Organization::query()->select('ein')->get()->pluck('ein')->all();

        if ($this->option('skip-previous')) {

            $existingOrgCount = count($eins);
            $this->info("Skipping $existingOrgCount existing organizations.");

            if ($existingOrgCount > 10000) {
                $this->warn("There are already more than 10k organizations in the database, this script may run slow with this skip flag...");
            }
        }

        $rows = $this->import();
        $keys = array_values($this->map); // $keys = $rows->current();
        $i = 0;
        $buffer = [];

        $einsToUpdate = [];
        $orgCount = $this->lineCount() - 1;
        $bar = $this->output->createProgressBar($orgCount);
        foreach ($rows as $rawRow) {
            if ($i <= 0) {
                $i++;
                $bar->advance();
                continue;
            }

            $row = array_combine($keys, str_getcsv($rawRow));
            unset($row['primary_exempt_purpose']); // these database columns don't exist

            // skip already existing eins


            // convert '' to null
            foreach ($row as $key => $value) {
                if ($row[$key] === '') {
                    $row[$key] = null;
                }
            }

            $name = $row['dba'] ?? $row['name'];
            $row['slug'] = Str::slug($name) ?? $row['ein'] ?? Str::random();

            $stringToBoolColumns = [
                'present_in_bmf',
                'present_in_pub78',
                'is_school',
                'grants_to_govt',
                'has_controlled_entity',
                'operates_hospital_facilities',
                'transacted_with_controlled_entity',
                'engaged_in_lobbying',
            ];
            foreach ($stringToBoolColumns as $column) {
                if ($row[$column] === 'True') {
                    $row[$column] = true;
                }

                if ($row[$column] === 'False') {
                    $row[$column] = false;
                }
            }

            if (strlen($row['e_return_period']) === 6 && Str::startsWith($row['e_return_period'], '20')) {
                $row['e_return_period'] .= '01';
            }

            if (in_array($row['ein'], $eins)) {
                Organization::where('ein', $row['ein'])->first()->update($row);

                $i++;
                $bar->advance();
                continue;
            }

            $buffer[] = $row;
            if (count($buffer) > $bufferLimit) {
                // unique slugs
                $slugs = [];
                foreach ($buffer as $row) {
                    $slugs[] = $row['slug'];
                }

                $orgs = Organization::query()->whereIn('slug', $slugs)->get(['slug']);
                if ($orgs) {
                    $existingSlugs = $orgs->pluck('slug')->all();
                    foreach ($buffer as &$row) {
                        if (in_array($row['slug'], $existingSlugs)) {
                            $row['slug'] .= '-' . $row['ein'];
                        }
                    }
                }

                if (self::duplicates_exist($slugs)) {
                    $existingSlugs = [];
                    foreach ($buffer as &$row) {
                        if (isset($existingSlugs[$row['slug']])) {
                            $row['slug'] .= '-' . $row['ein'];
                        }
                        $existingSlugs[$row['slug']] = true;
                    }
                }

                Organization::insert($buffer);
                $bar->advance(count($buffer));
                $buffer = [];
            }
            $i++;
        }
        $bar->finish();

        $this->info("Finished importing organizations");

        return 0;
    }

    public static function duplicates_exist(array $array)
    {
        return count($array) !== count(array_flip($array));
    }

    private function fileHandle()
    {
        return fopen(storage_path('main.csv'), 'r');
    }

    private function lineCount()
    {
        $count = 0;
        $handle = $this->fileHandle();
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }

        fclose($handle);
        return $count;
    }

    private function import()
    {
        $file = $this->fileHandle();
        while (($line = fgets($file)) !== false) {
            yield $line;
        }

        fclose($file);
    }

    // csv column => db column
    private $map = [
        'EIN' => 'ein',
        'e-Return period' => 'e_return_period',
        'Name' => 'name',
        'DBA' => 'dba',
        'Organization type' => 'organization_type',
        'Subsection' => 'subsection',
        'Year of formation' => 'year_of_formation',
        'Present in BMF' => 'present_in_bmf',
        'BMF update date' => 'bmf_update_date',
        'Present in Pub78' => 'present_in_pub78',
        'Pub78 update date' => 'pub78_update_date',
        'Street' => 'address',
        'City' => 'city',
        'State' => 'state',
        'ZIP' => 'post_code',
        'Country' => 'country',
        'Phone' => 'phone',
        'Website' => 'url',
        'NTEE code' => 'ntee',
        'NTEE description' => 'ntee_description',
        'Mission' => 'mission_statement',
        'Mission or significant activities' => 'mission_statement',
        'Primary exempt purpose' => 'primary_exempt_purpose', // this doesn't exist in the database
        'Total assets' => 'total_assets',
        'Total liabilities' => 'total_liabilities',
        'Total revenue' => 'total_revenue',
        'Fundraising expense' => 'fundraising_expense',
        'General expense' => 'general_expense',
        'Program expense' => 'program_expense',
        'Total expense' => 'total_expenses',
        'Engaged in lobbying' => 'engaged_in_lobbying',
        'Grants to govt or domestic org' => 'grants_to_govt',
        'Has controlled entity' => 'has_controlled_entity',
        'Is a school' => 'is_school',
        'Operates hospital facilities' => 'operates_hospital_facilities',
        'Transacted with controlled entity' => 'transacted_with_controlled_entity',
        'e-Return type' => 'e_return_type',
        'Tax period start date' => 'tax_start_dt',
        'Tax period end date' => 'tax_end_dt',
        'Date e-file submitted' => 'date_efile_submitted',
        'Date e-file published' => 'date_efile_published',
        'IRS e-file ID' => 'irs_efile_id',
        'IRS e-file schema version' => 'efile_schema_version',
        'e-file XML' => 'efile_xml',
        'e-file PDF' => 'efile_pdf',
        'Open990 URL' => 'open990_url',
    ];
}
