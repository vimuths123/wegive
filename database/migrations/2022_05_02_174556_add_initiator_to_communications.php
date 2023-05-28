<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInitiatorToCommunications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->morphs('initiator');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropColumn(['initiator_type', 'initiator_id']);
        });
    }
}
