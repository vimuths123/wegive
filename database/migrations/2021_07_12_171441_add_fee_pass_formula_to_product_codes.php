<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeePassFormulaToProductCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_codes', function (Blueprint $table) {
            $table->text('fee_pass_formula');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_codes', function (Blueprint $table) {
            $table->dropColumn('fee_pass_formula');
        });
    }
}
