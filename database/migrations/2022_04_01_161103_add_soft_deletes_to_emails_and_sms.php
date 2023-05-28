<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToEmailsAndSms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::table('sms', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('sms', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
}
