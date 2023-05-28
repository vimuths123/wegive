<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeeAmountToScheduledDonations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_donations', function (Blueprint $table) {
            $table->unsignedInteger('fee_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduled_donations', function (Blueprint $table) {
            $table->dropColumn('fee_amount');
        });
    }
}
