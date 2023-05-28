<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTilledColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tl_token')->nullable();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->string('tl_token')->nullable();
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->string('tl_token')->nullable();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('tl_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tl_token');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn('tl_token');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('tl_token');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('tl_token');
        });
    }
}
