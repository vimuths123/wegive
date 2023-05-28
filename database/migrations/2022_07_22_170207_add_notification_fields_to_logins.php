<?php

use App\Models\Login;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotificationFieldsToLogins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->integer('notification_frequency')->default(Login::NOTIFICATION_FREQUENCY_WEEKLY);
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
            $table->dropColumn('notification_frequency');
        });
    }
}
