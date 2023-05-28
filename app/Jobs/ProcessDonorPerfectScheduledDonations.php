<?php

namespace App\Jobs;

use App\Models\DonorPerfectIntegration;
use App\Models\ScheduledDonation;
use Doctrine\DBAL\SQL\Parser\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDonorPerfectScheduledDonations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $donorPerfectIntegration = null;
    protected $scheduledDonation = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DonorPerfectIntegration $donorPerfectIntegration, ScheduledDonation $scheduledDonation)
    {
        $this->donorPerfectIntegration = $donorPerfectIntegration;
        $this->scheduledDonation = $scheduledDonation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->donorPerfectIntegration->enabled || !$this->donorPerfectIntegration->track_recurring_donations) return;

        try {

            $response = $this->donorPerfectIntegration->sycnScheduledDonation($this->scheduledDonation);
            if ($response && $response->successful()) {
                activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->scheduledDonation)->log("Integration of scheduled donation processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->scheduledDonation)->log("Integration of scheduled donation failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->scheduledDonation)->log("Integration of scheduled donation failed");

            $this->fail($e);
        }
    }
}
