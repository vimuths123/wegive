<?php

use App\Models\DonorSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDonorSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('donor_settings', function (Blueprint $table) {
            $table->nullableMorphs('donor');
        });

        $donorSettings = DonorSetting::all();

        foreach ($donorSettings as &$donorSetting) {
            $donorSetting->donor()->associate($donorSetting->user);
            $donorSetting->save();
        }

        Schema::table('donor_settings', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
