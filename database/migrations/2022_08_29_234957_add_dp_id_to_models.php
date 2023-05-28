<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDpIdToModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('dp_id')->nullable();
        });

        Schema::table('individuals', function (Blueprint $table) {
            $table->integer('dp_id')->nullable();
        });

        Schema::table('scheduled_donations', function (Blueprint $table) {
            $table->integer('dp_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('dp_id');
        });

        Schema::table('individuals', function (Blueprint $table) {
            $table->dropColumn('dp_id');
        });

        Schema::table('scheduled_donations', function (Blueprint $table) {
            $table->dropColumn('dp_id');
        });
    }
}
