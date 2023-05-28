<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaggableToPostDonorsAndImpactNumberDonors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('post_donor', function (Blueprint $table) {
            $table->morphs('taggable');
        });

        Schema::table('impact_number_donor', function (Blueprint $table) {
            $table->morphs('taggable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('post_donor', function (Blueprint $table) {
            $table->dropColumn(['taggable_id', 'taggable_type']);
        });

        Schema::table('impact_number_donor', function (Blueprint $table) {
            $table->dropColumn(['taggable_id', 'taggable_type']);
        });
    }
}
