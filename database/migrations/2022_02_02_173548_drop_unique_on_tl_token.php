<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueOnTlToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {

            $table->dropUnique('cards_tl_token_unique');
        });

        Schema::table('banks', function (Blueprint $table) {

            $table->dropUnique('banks_tl_token_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('cards', function (Blueprint $table) {

            $table->string('tl_token')->unique(true)->change();
        });

        Schema::table('banks', function (Blueprint $table) {

            $table->string('tl_token')->unique(true)->change();
        });
    }
}
