<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundraisersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fundraisers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('goal');
            $table->string('name');
            $table->text('description');
            $table->morphs('owner');
            $table->tinyInteger('publicity');
            $table->morphs('recipient');
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
        Schema::dropIfExists('fundraisers');
    }
}
