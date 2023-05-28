<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrgEmailSettingsToLogins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->boolean('new_donation_email')->default(true);
            $table->boolean('new_donor_email')->default(true);
            $table->boolean('new_fundraiser_email')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->dropColumn([
                'new_donation_email',
                'new_donor_email',
                'new_fundraiser_email'
            ]);
        });
    }
}
