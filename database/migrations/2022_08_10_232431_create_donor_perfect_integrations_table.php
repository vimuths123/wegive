<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonorPerfectIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donor_perfect_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->boolean('crm_sync')->default(true);
            $table->boolean('two_way_sync')->default(true);
            $table->boolean('track_donations')->default(true);
            $table->boolean('track_donors')->default(true);
            $table->boolean('track_recurring_donations')->default(true);
            $table->boolean('track_campaigns')->default(true);
            $table->boolean('enabled')->default(false);
            $table->string('api_key')->nullable();
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
        Schema::dropIfExists('donor_perfect_integrations');
    }
}
