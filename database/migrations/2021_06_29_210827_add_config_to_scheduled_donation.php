<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigToScheduledDonation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_donations', function (Blueprint $table) {
            $table->unsignedInteger('frequency')->nullable()->default(null);
            $table->date('start_date')->nullable()->index();
            $table->nullableMorphs('payment_method');
            $table->string('platform')->default('givelist');


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
            $table->dropColumn('payment_method_id');
            $table->dropColumn('payment_method_type');
            $table->dropColumn('frequency');
            $table->dropColumn('start_date');
            $table->dropColumn('platform');

        });
    }
}
