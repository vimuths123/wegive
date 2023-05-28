<?php

namespace App\Console\Commands;

use App\Actions\Payments;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Console\Command;

class ProcessPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:payouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This takes money from our bank account and gives it to the recently onboarded organizations';

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

        $transactions = Transaction::where('status', Transaction::STATUS_PENDING)->get()->groupBy(['destination_type', 'destination_id']);

        $organizationIds = array_keys($transactions['organization']->all());

        $organizations = Organization::query()->whereIn('id', $organizationIds)->whereNotNull('onboarded')->get();

        foreach ($organizations as $organization) {
            $pendingTransactions = $organization->receivedTransactions->where('status', Transaction::STATUS_PENDING)->where('source_type', 'user');

            foreach ($pendingTransactions as $key => $transaction) {
                if ($transaction->source->walletBalance() < $transaction->amount) {
                    $pendingTransactions->forget($key);
                }
            }

            $orgSum = $pendingTransactions->sum('amount');
            $transactionIds = $pendingTransactions->pluck('id')->all();



            $service = new Payments();
            $response = $service->processLumpSum($orgSum, $organization->tl_token, $transactionIds);

            $details = $response->json();



            if ($response->successful()) {
                foreach ($pendingTransactions as $transaction) {

                    $transaction->correlation_id = $details['id'] ?? null;

                    if ($details['status'] === 'succeeded' || $details['status'] === 'processing') {
                        $transaction->status = Transaction::STATUS_SUCCESS;
                    }

                    $transaction->save();
                }
            }
        }

        return 0;
    }
}
