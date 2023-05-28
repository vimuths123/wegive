<?php

use App\Models\ScheduledDonation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs('recipient');
            $table->boolean('informational_content')->default(true);
            $table->text('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('link_1')->nullable();
            $table->string('link_2')->nullable();
            $table->string('link_3')->nullable();
            $table->unsignedInteger('suggested_amount_1')->default(0);
            $table->unsignedInteger('suggested_amount_2')->default(0);
            $table->unsignedInteger('suggested_amount_3')->default(0);
            $table->unsignedInteger('suggested_amount_4')->default(0);
            $table->unsignedInteger('suggested_amount_5')->default(0);
            $table->unsignedInteger('suggested_amount_6')->default(0);
            $table->string('suggested_amount_1_description')->nullable();
            $table->string('suggested_amount_2_description')->nullable();
            $table->string('suggested_amount_3_description')->nullable();
            $table->string('suggested_amount_4_description')->nullable();
            $table->string('suggested_amount_5_description')->nullable();
            $table->string('suggested_amount_6_description')->nullable();
            $table->boolean('suggested_amount_descriptions')->default(true);
            $table->unsignedInteger('recurring_suggested_amount_1')->default(0);
            $table->unsignedInteger('recurring_suggested_amount_2')->default(0);
            $table->unsignedInteger('recurring_suggested_amount_3')->default(0);
            $table->unsignedInteger('recurring_suggested_amount_4')->default(0);
            $table->unsignedInteger('recurring_suggested_amount_5')->default(0);
            $table->unsignedInteger('recurring_suggested_amount_6')->default(0);
            $table->string('recurring_suggested_amount_1_description')->nullable();
            $table->string('recurring_suggested_amount_2_description')->nullable();
            $table->string('recurring_suggested_amount_3_description')->nullable();
            $table->string('recurring_suggested_amount_4_description')->nullable();
            $table->string('recurring_suggested_amount_5_description')->nullable();
            $table->string('recurring_suggested_amount_6_description')->nullable();
            $table->boolean('recurring_suggested_amount_descriptions')->default(true);
            $table->foreignId('impact_number_id')->nullable();
            $table->boolean('conversion_step')->default(true);
            $table->unsignedInteger('default_frequency')->default(ScheduledDonation::DONATION_FREQUENCY_MONTHLY);
            $table->boolean('allow_frequency_change')->default(true);
            $table->boolean('designation')->default(true);
            $table->boolean('tribute')->default(true);
            $table->boolean('credit_card')->default(true);
            $table->boolean('apple_pay')->default(true);
            $table->boolean('google_pay')->default(true);
            $table->boolean('ach')->default(true);
            $table->boolean('bank_login')->default(true);
            $table->boolean('crypto')->default(true);
            $table->boolean('show_savings')->default(true);
            $table->boolean('fee_pass')->default(true);
            $table->boolean('default_to_covered')->default(true);
            $table->boolean('tipping')->default(true);
            $table->boolean('donate_anonymously')->default(true);
            $table->boolean('anonymous_donations')->default(true);
            $table->boolean('require_verification')->default(true);
            $table->text('thank_you_headline')->nullable();
            $table->text('thank_you_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checkouts');
    }
}
