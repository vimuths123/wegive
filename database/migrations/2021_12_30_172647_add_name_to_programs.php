<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToPrograms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('name');
            $table->bigInteger('revenue')->nullable()->change();
            $table->bigInteger('grant_expense')->nullable()->change();
            $table->bigInteger('total_expense')->nullable()->change();
            $table->bigInteger('e_return_period')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->bigInteger('revenue')->nullable(false)->change();
            $table->bigInteger('grant_expense')->nullable(false)->change();
            $table->bigInteger('total_expense')->nullable(false)->change();
            $table->bigInteger('e_return_period')->nullable(false)->change();
        });
    }
}
