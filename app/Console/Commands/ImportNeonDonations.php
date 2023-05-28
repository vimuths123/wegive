<?php

namespace App\Console\Commands;

use App\Models\Donor;
use App\Models\NeonIntegration;
use App\Models\Organization;
use App\Models\Transaction;
use Illuminate\Console\Command;

class ImportNeonDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-neon-donations {organization_id}';

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
        if (!$this->argument('organization_id')) return 0;
        $organization = Organization::find($this->argument('organization_id'));

        $this->neonIntegration = NeonIntegration::where('organization_id', $this->argument('organization_id'))->firstOrFail();

        $donors = $organization->donors()->whereNotNull('neon_account_id')->get();

        foreach ($donors as $i) {
            $this->processDonations(0, $i->neon_account_id);
        }
    }

    public function processDonations($page = 0, $accountId)
    {
        dump('Processing Donations for: ' . $accountId);

        $donationsRequest = $this->neonIntegration->get("accounts/{$accountId}/donations", ['currentPage' => $page, 'pageSize' => '20']);

        if ($donationsRequest->failed()) return;
        $donations = $donationsRequest->json()['donations'];
        $pagination = $donationsRequest->json()['pagination'];

        foreach ($donations as $donation) {
            $this->processDonation($donation, $accountId);
        }

        if ($pagination['totalPages'] > $page) {
            $this->processDonations($page + 1, $accountId);
        }

        if ($pagination['totalPages'] === $page) return;
    }

    public function processDonation($donation, $accountId)
    {

        $donorProfile = Donor::where('neon_account_id', $accountId)->where('organization_id', $this->argument('organization_id'))->get()->first();

        if (!$donorProfile) return;

        if ($donorProfile->transactions()->whereNotNull('neon_id')->where('neon_id', $donation['id'])->first()) return;

        dump('new donation found');

        $transaction = new Transaction();
        $organization = Organization::find($this->argument('organization_id'));
        $transaction->amount = $donation['amount'] * 100;
        $transaction->description = 'Neon Import';
        $transaction->created_at = $donation['timestamps']['createdDateTime'];
        $transaction->owner()->associate($donorProfile);
        $transaction->source()->associate($organization);
        $transaction->destination()->associate($organization);
        $transaction->neon_id = $donation['id'];
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->cover_fees = $donation['donorCoveredFee'] ?? false;
        $transaction->neon_payment_id = $donation['payments'][0]['id'] ?? null;
        $transaction->saveQuietly();
    }
}
