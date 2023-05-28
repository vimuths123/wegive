<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMappingRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mapping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->integer('crm');
            $table->integer('integration');
            $table->string('integration_path');
            $table->string('wegive_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mapping_rules');
    }
}
