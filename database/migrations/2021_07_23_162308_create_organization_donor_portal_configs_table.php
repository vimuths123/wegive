<?php

use App\Models\Organization;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrganizationDonorPortalConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_donor_portal_configs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('organization_id');
            $table->string('primary_color')->default('#28aae2');
            $table->string('friendly_name')->nullable();
            $table->boolean('card')->default(true);
            $table->boolean('bank')->default(true);
            $table->boolean('echeck')->default(true);
            $table->integer('processing_fee_handler')->default(Organization::DONOR_DECIDES_FEES);
            $table->integer('donation_activity_privacy')->default(Organization::DONOR_DECIDES_DONATION_PRIVACY);
            $table->boolean('wegive_tipping')->default(true);
            $table->boolean('wegive_branding')->default(true);
            $table->boolean('organization_fundraisers')->default(true);
            $table->boolean('donor_fundraisers')->default(true);
            $table->boolean('fund_or_program_specific_fundraiser')->default(true);
            $table->boolean('fundraiser_donation_cap')->default(true);
            $table->boolean('donor_fundraiser_events')->default(true);
            $table->boolean('giving_page')->default(true);
            $table->boolean('fundraiser_page')->default(true);
            $table->boolean('organization_page')->default(true);
            $table->boolean('donor_page')->default(true);
            $table->boolean('profile_donors_visible')->default(true);
            $table->boolean('profile_donation_amounts_visible')->default(true);
            $table->boolean('profile_mission_statement')->default(true);
            $table->boolean('profile_financials')->default(true);
            $table->boolean('profile_programs')->default(true);
            $table->boolean('profile_non_donor_visible')->default(true);
            $table->boolean('profile_impact_stories')->default(true);
            $table->boolean('profile_impact_numbers')->default(true);
            $table->boolean('show_donors_rank')->default(true);
            $table->boolean('show_donors_impact')->default(true);
            $table->boolean('show_donors_impact_numbers')->default(true);
            $table->boolean('show_donors_donation_spending_breakdown')->default(true);
            $table->boolean('show_donors_donation_fund_program_breakdown')->default(true);
            $table->boolean('show_badges')->default(true);
            $table->boolean('add_payment_method_badge')->default(true);
            $table->boolean('made_donation_badge')->default(true);
            $table->boolean('recurring_giving_badge')->default(true);
            $table->boolean('has_had_recurring_giving_badge')->default(true);
            $table->boolean('started_fundraiser_badge')->default(true);
            $table->boolean('completed_fundraising_goal_badge')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_donor_portal_configs');
    }
}
