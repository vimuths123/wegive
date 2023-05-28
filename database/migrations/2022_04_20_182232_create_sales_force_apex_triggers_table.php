<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesForceApexTriggersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesforce_apex_triggers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('salesforce_integration_id');
            $table->string('salesforce_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesforce_apex_triggers');
    }
}
