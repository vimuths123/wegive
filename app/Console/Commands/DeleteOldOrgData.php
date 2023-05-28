<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteOldOrgData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delete-old-org-data {--dry-run}';

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

    public function handle()
    {
        $this->info('Organizations to keep:');

        $giveListIds = Arr::pluck(DB::select('SELECT DISTINCT destination_id FROM transactions WHERE destination_type = "givelist"'), 'destination_id');

        $fromGiveLists = Arr::pluck(DB::select('SELECT DISTINCT organization_id FROM givelist_organization WHERE givelist_id IN (' . implode(',', $giveListIds) . ')'), 'organization_id');

        $fromGiveLists = array_unique($fromGiveLists);

        $this->info('- From Givelist: ' . count($fromGiveLists));

        $usedIds = Arr::pluck(DB::select('SELECT DISTINCT destination_id FROM transactions WHERE destination_type = "organization"'), 'destination_id');

        $usedIds = array_unique($usedIds);

        $this->info('- With Transactions: ' . count($usedIds));

        $withTL = Organization::whereNotNull('tl_token')->pluck('id')->all();

        $this->info('- With TL Token: ' . count($withTL));

        $manuals = [1584770];

        $idsToKeep = array_unique(array_merge($fromGiveLists, $usedIds, $withTL, $manuals));

        $this->info('Total: ' . count($idsToKeep));

        dump(implode(',', $idsToKeep));

        if ($this->option('dry-run')) {
            return ;
        }

        // Checkouts table
        $this->warn('Deleting checkouts ...');

        if (Schema::hasTable('backup_checkouts') === false) {
            if (Schema::hasTable('checkouts_new')) {
                DB::statement('DROP TABLE checkouts_new');
            }

            DB::statement('CREATE TABLE checkouts_new LIKE checkouts');

            DB::statement('INSERT checkouts_new SELECT * FROM checkouts WHERE recipient_type = "organization" AND recipient_id IN (' . implode(',', $idsToKeep) . ')');

            Schema::rename('checkouts', 'backup_checkouts');

            Schema::rename('checkouts_new', 'checkouts');
        } else {
            $this->info('(Skipped)');
        }

        // Donor_Portals table
        $this->warn('Deleting Donor Portals ...');

        if (Schema::hasTable('backup_donor_portals') === false) {
            if (Schema::hasTable('donor_portals_new')) {
                DB::statement('DROP TABLE donor_portals_new');
            }

            DB::statement('CREATE TABLE donor_portals_new LIKE donor_portals');

            DB::statement('INSERT donor_portals_new SELECT * FROM donor_portals WHERE recipient_type = "organization" AND recipient_id IN (' . implode(',', $idsToKeep) . ')');

            Schema::rename('donor_portals', 'backup_donor_portals');

            Schema::rename('donor_portals_new', 'donor_portals');
        } else {
            $this->info('(Skipped)');
        }

        // Organizations table
        if (Schema::hasTable('backup_organizations') === false) {
            if (Schema::hasTable('organizations_new')) {
                DB::statement('DROP TABLE organizations_new');
            }

            DB::statement('CREATE TABLE organizations_new LIKE organizations');

            DB::statement('INSERT organizations_new SELECT * FROM organizations WHERE id IN (' . implode(',', $idsToKeep) . ')');

            Schema::rename('organizations', 'backup_organizations');

            Schema::rename('organizations_new', 'organizations');
        } else {
            $this->info('(Skipped)');
        }
    }
}
