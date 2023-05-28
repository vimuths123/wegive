<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNeonIdToDonors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->integer('neon_account_id')->nullable();
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('neon_account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->dropColumn('neon_account_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('neon_account_id');
        });
    }
}
