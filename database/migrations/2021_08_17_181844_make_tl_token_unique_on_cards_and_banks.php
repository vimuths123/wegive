<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTlTokenUniqueOnCardsAndBanks extends Migration
{
       /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('tl_token')->unique()->change();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->string('tl_token')->unique()->change();
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
            $table->string('tl_token')->unique(false)->change();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->string('tl_token')->unique(false)->change();
        });
    }

}
