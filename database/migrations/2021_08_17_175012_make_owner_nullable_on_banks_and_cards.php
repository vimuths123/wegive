<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeOwnerNullableOnBanksAndCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->bigInteger('owner_id')->nullable()->change();
            $table->string('owner_type')->nullable()->change();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->bigInteger('owner_id')->nullable()->change();
            $table->string('owner_type')->nullable()->change();
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
            $table->bigInteger('owner_id')->nullable(false)->change();
            $table->string('owner_type')->nullable(false)->change();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->bigInteger('owner_id')->nullable(false)->change();
            $table->string('owner_type')->nullable(false)->change();
        });
    }
}
