<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use App\Models\NeonIntegration;
use App\Models\ScheduledDonation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessNeonIntegrationRecurringDonations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $neonIntegration = null;
    protected $scheduledDonation = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(NeonIntegration $neonIntegration, ScheduledDonation $scheduledDonation)
    {
        $this->neonIntegration = $neonIntegration;
        $this->scheduledDonation = $scheduledDonation;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->neonIntegration->enabled) return;

        try {
            $response = $this->neonIntegration->syncRecurringDonation($this->scheduledDonation);
            if ($response && $response->successful()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan succeeded");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->neonIntegration)->performedOn($this->scheduledDonation)->log("Integration of recurring plan failed");

            $this->fail($e);
        }
    }
}
