<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettingsToDonors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->integer('profile_privacy')->default(User::PUBLIC);
            $table->integer('dollar_amount_privacy')->default(User::DONORS_ONLY);
            $table->boolean('include_name')->default(true);
            $table->boolean('include_profile_picture')->default(true);
            $table->boolean('desktop_notifications')->default(true);
            $table->boolean('mobile_notifications')->default(true);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(true);
            $table->boolean('general_communication')->default(true);
            $table->boolean('marketing_communication')->default(true);
            $table->boolean('donation_updates_receipts')->default(true);
            $table->boolean('impact_stories_use_of_funds')->default(true);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->integer('profile_privacy')->default(User::PUBLIC);
            $table->integer('dollar_amount_privacy')->default(User::DONORS_ONLY);
            $table->boolean('include_name')->default(true);
            $table->boolean('include_profile_picture')->default(true);
            $table->boolean('desktop_notifications')->default(true);
            $table->boolean('mobile_notifications')->default(true);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(true);
            $table->boolean('general_communication')->default(true);
            $table->boolean('marketing_communication')->default(true);
            $table->boolean('donation_updates_receipts')->default(true);
            $table->boolean('impact_stories_use_of_funds')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->dropColumn(['profile_privacy', 'dollar_amount_privacy', 'include_name', 'include_profile_picture', 'desktop_notifications', 'mobile_notifications', 'email_notifications', 'sms_notifications', 'general_communication', 'marketing_communication', 'donation_updates_receipts', 'impact_stories_use_of_funds']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['profile_privacy', 'dollar_amount_privacy', 'include_name', 'include_profile_picture', 'desktop_notifications', 'mobile_notifications', 'email_notifications', 'sms_notifications', 'general_communication', 'marketing_communication', 'donation_updates_receipts', 'impact_stories_use_of_funds']);
        });
    }
}
