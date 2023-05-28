<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlackbaudIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blackbaud_integrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('organization_id');
            $table->string('subscription_key')->nullable();
            $table->string('access_token')->nullable();
            $table->boolean('crm_sync')->default(true);
            $table->boolean('two_way_sync')->default(true);
            $table->boolean('track_donations')->default(true);
            $table->boolean('track_accounts')->default(true);
            $table->boolean('track_donors')->default(true);
            $table->boolean('track_recurring_donations')->default(true);
            $table->boolean('track_campaigns')->default(true);
            $table->boolean('track_designations')->default(true);
            $table->boolean('enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blackbaud_integrations');
    }
}
