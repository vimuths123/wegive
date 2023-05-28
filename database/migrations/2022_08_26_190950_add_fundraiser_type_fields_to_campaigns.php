<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFundraiserTypeFieldsToCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('type')->nullable();
            $table->string('fundraiser_name')->nullable();
            $table->text('fundraiser_description')->nullable();
            $table->boolean('fundraiser_donations_p2p_only')->default(false);
            $table->boolean('fundraiser_show_leader_board')->default(true);
            $table->boolean('fundraiser_show_activity')->default(true);
            $table->boolean('fundraiser_show_child_fundraiser_campaign')->default(true);
            $table->boolean('fundraiser_show_child_event_campaign')->default(true);
            $table->foreignId('checkout_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'fundraiser_name',
                'fundraiser_description',
                'fundraiser_donations_p2p_only',
                'fundraiser_show_leader_board',
                'fundraiser_show_activity',
                'fundraiser_show_child_fundraiser_campaign',
                'fundraiser_show_child_event_campaign',
                'type',
                'checkout_id'
            ]);
        });
    }
}
