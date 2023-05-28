<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use App\Models\ScheduledDonation;
use App\Models\SalesforceIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessSalesforceRecurringDonationIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesforceIntegration = null;
    protected $scheduledDonation = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $neonIntegration, ScheduledDonation $scheduledDonation)
    {
        $this->salesforceIntegration = $neonIntegration;
        $this->scheduledDonation = $scheduledDonation;
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
            $response = $this->salesforceIntegration->syncRecurringDonation($this->scheduledDonation);
            if ($response && $response->successful()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->salesforceIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan failed");

            $this->fail($e);
        }
    }
}
