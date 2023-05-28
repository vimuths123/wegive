<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\NeonIntegration;
use Illuminate\Console\Command;

class FixFeeCoverFieldRestorationHouse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:fix-fee-cover-field-resoration-house {organization_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $organization = Organization::find($this->argument('organization_id'));
        $neonIntegration = NeonIntegration::where('organization_id', $this->argument('organization_id'))->firstOrFail();

        $donations = $organization->receivedTransactions()->whereNotNull('neon_id')->get();

        foreach ($donations as $donation) {
            $this->updateDonation($donation, $neonIntegration);
        }
    }

    public function updateDonation($transaction, $neonIntegration)
    {
        $neonDonationRequest = $neonIntegration->get("donations/{$transaction->neon_id}", null);

        if ($neonDonationRequest->failed()) return;

        $donation = $neonDonationRequest->json();



        if ($donation['donorCoveredFeeFlag']) {
            $transaction->cover_fees = $donation['donorCoveredFeeFlag'];
            $transaction->fee_amount = $donation['donorCoveredFee'] * 100;
            dump('donor covered fee');
        }

        if ($donation['campaign']) {
            $campaign = Campaign::whereNotNull('neon_id')->where('neon_id', $donation['campaign']['id'])->get()->first();

            if ($campaign) {
                $transaction->campaign()->associate($campaign);
                dump('campaign attached');

            }
        }

        $transaction->saveQuietly();
    }
}
