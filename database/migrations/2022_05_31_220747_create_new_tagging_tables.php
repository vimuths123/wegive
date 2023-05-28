<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTaggingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_individuals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('post_id');
            $table->foreignId('individual_id');
        });

        Schema::create('impact_number_individuals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('impact_number_id');
            $table->foreignId('individual_id');
        });

        Schema::create('post_companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('post_id');
            $table->foreignId('company_id');
        });

        Schema::create('impact_number_companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('impact_number_Id');
            $table->foreignId('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_tagging_tables');
    }
}
