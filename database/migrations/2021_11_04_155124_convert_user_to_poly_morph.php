<?php

use App\Models\User;
use App\Models\Givelist;
use App\Models\Interest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertUserToPolyMorph extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('interests', function (Blueprint $table) {
            $table->morphs('enthusiast');
        });

        Schema::table('givelists', function (Blueprint $table) {
            $table->morphs('creator');
        });

        $givelists = Givelist::all();

        foreach ($givelists as &$givelist) {
            $givelist->creator()->associate($givelist->user ?? User::find(2));
            $givelist->save();
        }

        $interests = Interest::all();

        foreach ($interests as &$interest) {
            $interest->enthusiast()->associate($interest->user ?? User::find(2));
            $interest->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('interests', function (Blueprint $table) {
            $table->dropColumn(['enthusiast_id', 'enthusiast_type']);
        });

        Schema::table('givelists', function (Blueprint $table) {
            $table->dropColumn(['creator_id', 'creator_type']);
        });
    }
}
