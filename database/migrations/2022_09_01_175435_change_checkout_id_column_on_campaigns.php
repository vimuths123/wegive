<?php

use App\Models\Campaign;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCheckoutIdColumnOnCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function ($table) {
            $table->foreignId('checkout_id')->nullable()->change();
        });

        $campaigns = Campaign::where('checkout_id', 1)->get();

        foreach ($campaigns as &$campaign) {
            $checkout = $campaign->organization->checkouts()->create();

            $campaign->checkout()->associate($checkout);
            $campaign->saveQuietly();
        }
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
