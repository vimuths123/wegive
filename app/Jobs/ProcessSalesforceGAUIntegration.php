<?php

namespace App\Jobs;

use Exception;
use App\Models\Fund;
use Illuminate\Bus\Queueable;
use App\Models\SalesforceIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessSalesforceGAUIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $salesforceIntegration = null;
    protected $fund = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $neonIntegration, Fund $fund)
    {
        $this->salesforceIntegration = $neonIntegration;
        $this->fund = $fund;
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
            $response = $this->salesforceIntegration->syncFund($this->fund);
            if ($response && $response->successful()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->fund)->log("Integration of GAU processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->fund)->log("Integration of GAU failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->salesforceIntegration)->performedOn($this->fund)->log("Integration of GAU failed");

            $this->fail($e);
        }
    }
}
