<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaderBoardandActivityToFundraisers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->boolean('show_leader_board')->default(true);
            $table->boolean('show_activity')->default(true);
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
            $table->dropColumn(['show_leader_board', 'show_activity']);
        });
    }
}
