<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeImpactNumberFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('impact_numbers', function (Blueprint $table) {

            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('impact_numbers', function (Blueprint $table) {

            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
}
