<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesforceIdToModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('individuals', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('households', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->string('salesforce_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('individuals', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('households', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('salesforce_id');
        });
    }
}
