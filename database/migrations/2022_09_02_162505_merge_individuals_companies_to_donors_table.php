<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeIndividualsCompaniesToDonorsTable extends Migration
{
    /**
     * This is a big one. Merges a lot of data together and clean up polymorphic links across multiple tables.
     *
     * @return void
     */
    public function up()
    {
        // Add new columns to the current "individuals" table
        Schema::table('individuals', function (Blueprint $table) {
            if (Schema::hasColumn('individuals', 'tmp_company_id') === false) {
                $table->string('tmp_company_id')->nullable();
            }

            if (Schema::hasColumn('individuals', 'type') === false) {
                $table->enum('type', ['individual', 'company'])->default('individual')->after('updated_at');
            }

            $table->string('first_name')->nullable()->change();

            $table->string('last_name')->nullable()->change();
        });

        // Clear out all the legacy tables that are deprecated (not "companies" yet)
        foreach ($this->legacyTables() as $table) {
            if (Schema::hasTable($table)) {
                Schema::rename($table, 'backup_' . $table);
            }
        }

        // Create new pivot tables (data don't need to be migrated though)
        Schema::create('impact_number_donors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('impact_number_id');
            $table->foreignId('donor_id');
        });

        Schema::create('post_donors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('post_id');
            $table->foreignId('donor_id');
        });

        // Merge companies to individuals table
        $id = 0;

        do {
            // Pull batch of companies without using the Company model. Similar logic than Companies::batch()
            $companies = DB::table('companies')->take(100)->orderBy('id')->where('id', '>', $id)->get();

            // Grab all the companies ids of this batch that have already been migrated to not create duplicates
            $alreadyMigrated = DB::table('individuals')->select('tmp_company_id')->whereIn('tmp_company_id', $companies->pluck('id'))->get();

            foreach ($companies as $companyData) {

                // If company is already migrated, skip it
                if (in_array($companyData->id, (array)$alreadyMigrated)) {
                    continue;
                }

                // List of company attributes that are not merged
                $attributesToInsert = Arr::except((array)$companyData, [
                    'id',
                    'mission_statement',
                    'last_login_at',
                    'ein',
                    'matching',
                    'matching_percent',
                    'max_match_amount'
                ]);

                // Merge extra information before inserting it. tmp_company_id is used to keep track of the merge process
                $attributesToInsert += [
                    'type'           => 'company',
                    'tmp_company_id' => $companyData->id
                ];

                // Insert new record without using the Individual model to do so
                DB::table('individuals')->insert($attributesToInsert);
            }

            // Prepare next batch: If no companies were returned, we are done with the migration
            $id = optional($companies->last())->id;
        } while (!is_null($id));

        // Merge is completed: Change final names
        Schema::rename('companies', 'backup_companies');

        Schema::rename('individuals', 'donors');

        // Update polymorphic contents
        $this->updatePolymorphics('action_events', 'actionable');

        $this->updatePolymorphics('activity_log', 'causer');

        $this->updatePolymorphics('addresses', 'addressable');

        $this->updatePolymorphics('audits', 'auditable');

        $this->updatePolymorphics('banks', 'owner');

        $this->updatePolymorphics('communications', 'receiver');

        $this->updatePolymorphics('fundraisers', 'owner');

        $this->updatePolymorphics('interests', 'enthusiast');

        $this->updatePolymorphics('logins', 'loginable');

        $this->updatePolymorphics('media', 'model');

        $scheduleDonationsToDelete = DB::table('scheduled_donations')->where('source_type', 'donor')->pluck('id');

        DB::table('transactions')->whereIn('scheduled_donation_id', $scheduleDonationsToDelete)->delete();

        $this->updatePolymorphics('scheduled_donations', 'source');

        $this->updatePolymorphics('transactions', 'owner');

        DB::table('users')->where('current_login_type', 'donor')->update(['current_login_type' => null, 'current_login_id' => null]);

        $this->updatePolymorphics('users', 'current_login');
        // TODO : Script to remove donor_id and tmp_company_id
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('donors', 'individuals');

        Schema::rename('backup_companies', 'companies');

        DB::table('individuals')->whereNotNull('tmp_company_id')->delete();

        Schema::drop('impact_number_donors');

        Schema::drop('post_donors');

        foreach ($this->legacyTables() as $table) {
            if (Schema::hasTable('backup_' . $table)) {
                Schema::rename('backup_' . $table, $table);
            }
        }

        Schema::table('individuals', function (Blueprint $table) {
            if (Schema::hasColumn('individuals', 'tmp_company_id')) {
                $table->dropColumn('tmp_company_id');
            }

            if (Schema::hasColumn('individuals', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    protected function updatePolymorphics($table, $key)
    {
        DB::table($table)->where($key . '_type', 'donor')->delete();

        DB::table($table)->where($key . '_type', 'individual')->update([$key . '_type' => 'donor']);

        $tableElements = DB::table($table)->where($key . '_type', 'company')->get();

        $chunks = $tableElements->chunk(300);

        foreach ($chunks as $chunk) {
            $companiesIds = $chunk->pluck($key . '_id');

            $associatedDonors = DB::table('donors')->where('type', 'company')->whereIn('tmp_company_id', $companiesIds)->get()->keyBy('tmp_company_id');

            foreach ($chunk as $item) {
                $companyId = $item->{$key . '_id'};

                if (isset($associatedDonors[$companyId])) {
                    DB::table($table)->where('id', $item->id)->update([
                        $key . '_type' => 'donor',
                        $key . '_id'   => $associatedDonors[$companyId]->id
                    ]);
                }
            }
        }
    }

    protected function legacyTables()
    {
        return [
            'company_donor',
            'company_user',
            'donor_settings',
            'donors',
            'impact_number_companies',
            'impact_number_donors',
            'impact_number_individuals',
            'post_companies',
            'post_individuals',
            'post_donors',
            'organization_donor',
            'organization_donor_portal_configs',
            'organization_user',
            'user_donor_portal_configs'
        ];
    }
}
