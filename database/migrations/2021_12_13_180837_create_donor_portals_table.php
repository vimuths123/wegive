<?php

use App\Models\DonorPortal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonorPortalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donor_portals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs('recipient');
            $table->foreignId('checkout_id')->nullable();
            $table->boolean('badges_enabled')->default(true);
            $table->boolean('badge_1_enabled')->default(true);
            $table->boolean('badge_2_enabled')->default(true);
            $table->boolean('badge_3_enabled')->default(true);
            $table->boolean('badge_4_enabled')->default(true);
            $table->boolean('badge_5_enabled')->default(true);
            $table->unsignedInteger('badge_1')->default(DonorPortal::ADD_PAYMENT_BADGE);
            $table->unsignedInteger('badge_2')->default(DonorPortal::MADE_DONATION_BADGE);
            $table->unsignedInteger('badge_3')->default(DonorPortal::RECURRING_GIVING_BADGE);
            $table->unsignedInteger('badge_4')->default(DonorPortal::FUNDRAISING_BADGE);
            $table->unsignedInteger('badge_5')->default(DonorPortal::MET_FUNDRAISING_GOAL_BADGE);
            $table->boolean('donor_ranking_enabled')->default(true);
            $table->unsignedInteger('donor_ranking_qualifier')->default(DonorPortal::TOTAL_GIVEN_QUALIFIER);
            $table->unsignedInteger('percentile_1')->default(DonorPortal::TOP_1_PERCENT);
            $table->unsignedInteger('percentile_2')->default(DonorPortal::TOP_5_PERCENT);
            $table->unsignedInteger('percentile_3')->default(DonorPortal::TOP_10_PERCENT);
            $table->unsignedInteger('percentile_4')->default(DonorPortal::TOP_20_PERCENT);
            $table->unsignedInteger('percentile_5')->default(DonorPortal::TOP_40_PERCENT);
            $table->boolean('impact_stories')->default(true);
            $table->boolean('impact_numbers')->default(true);
            $table->boolean('fundraising')->default(true);
            $table->boolean('lifetime_impact_graph')->default(true);
            $table->boolean('lifetime_impact')->default(true);
            $table->foreignId('impact_number_id')->nullable();
            $table->boolean('donors_manage_visibility')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('donor_portals');
    }
}
