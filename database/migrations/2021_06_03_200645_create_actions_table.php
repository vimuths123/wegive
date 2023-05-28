<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();

            // owner or parent actioning
            $table->uuid('actionable_id');
            $table->string('actionable_type');

            // object or child being actioned upon
            $table->uuid('objectable_id')->nullable();
            $table->string('objectable_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions');
    }
}
