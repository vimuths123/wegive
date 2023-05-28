<?php

namespace App\Jobs;

use Exception;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\Response;
use App\Models\SalesforceIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessSalesforceOpportunityIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesforceIntegration = null;
    protected $transaction = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $neonIntegration, Transaction $transaction)
    {
        $this->salesforceIntegration = $neonIntegration;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->salesforceIntegration->enabled) return;

        try {
            $response = $this->salesforceIntegration->syncDonation($this->transaction);
            if ($response && $response->successful()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->transaction)->log("Integration of donation processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->transaction)->log("Integration of donation failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->salesforceIntegration)->performedOn($this->transaction)->log("Integration of donation failed");

            $this->fail($e);
        }
    }
}
