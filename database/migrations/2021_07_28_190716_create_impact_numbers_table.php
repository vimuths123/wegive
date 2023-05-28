<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImpactNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('impact_numbers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean('static');
            $table->string('name');
            $table->bigInteger('number');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('include_on_organization');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('impact_numbers');
    }
}
