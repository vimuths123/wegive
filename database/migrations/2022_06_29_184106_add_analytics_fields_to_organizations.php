<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnalyticsFieldsToOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('google_tag_manager_container_id')->nullable();
            $table->string('google_analytics_measurement_id')->nullable();
            $table->string('facebook_pixel_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'google_tag_manager_container_id',
                'google_analytics_measurement_id',
                'facebook_pixel_id'
            ]);
        });
    }
}
