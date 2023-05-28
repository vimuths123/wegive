<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNeonFieldsToTransactionsFundraisers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('neon_id')->nullable();
        });

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->string('neon_id')->nullable();
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
            $table->dropColumn('neon_id');
        });

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->dropColumn('neon_id');
        });
    }
}
