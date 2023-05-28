<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDonorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_donor_portal_configs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id');
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
        Schema::dropIfExists('user_donor_portal_configs');
    }
}
