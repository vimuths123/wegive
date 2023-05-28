<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesforceIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesforce_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->boolean('crm_sync')->default(true);
            $table->boolean('two_way_sync')->default(true);
            $table->boolean('track_donations')->default(true);
            $table->boolean('track_accounts')->default(true);
            $table->boolean('track_donors')->default(true);
            $table->boolean('track_recurring_donations')->default(true);
            $table->boolean('track_campaigns')->default(true);
            $table->boolean('track_designations')->default(true);
            $table->boolean('enabled')->default(false);
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('instance_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesforce_integrations');
    }
}
