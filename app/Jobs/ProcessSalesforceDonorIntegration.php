<?php

namespace App\Jobs;

use App\Models\SalesforceIntegration;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSalesforceDonorIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesforceIntegration = null;
    protected $donor = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $neonIntegration, $donor)
    {
        $this->salesforceIntegration = $neonIntegration;
        $this->donor = $donor;
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
            if ($this->donor->type === 'company') {
                return;
            }

            $response = $this->salesforceIntegration->syncDonor($this->donor);
            if ($response && $response->successful()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->donor)->log("Integration of donor processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->donor)->log("Integration of donor failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->salesforceIntegration)->performedOn($this->donor)->log("Integration of donor failed");

            $this->fail($e);
        }
    }
}
