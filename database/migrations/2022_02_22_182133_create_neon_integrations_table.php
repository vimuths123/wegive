<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNeonIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('neon_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->boolean('crm_sync')->default(true);
            $table->boolean('two_way_sync')->default(true);
            $table->boolean('track_donations')->default(true);
            $table->boolean('track_donors')->default(true);
            $table->boolean('track_recurring_donations')->default(true);
            $table->boolean('track_campaigns')->default(true);
            $table->boolean('enabled')->default(false);
            $table->string('neon_id')->nullable();
            $table->string('neon_api_key')->nullable();
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
        Schema::dropIfExists('neon_integrations');
    }
}
