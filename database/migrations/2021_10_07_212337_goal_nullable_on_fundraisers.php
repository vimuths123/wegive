<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GoalNullableOnFundraisers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->bigInteger('goal')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->bigInteger('goal')->nullable(false)->change();
        });
    }
}
