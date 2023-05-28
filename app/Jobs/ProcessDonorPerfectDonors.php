<?php

namespace App\Jobs;

use App\Models\Donor;
use App\Models\DonorPerfectIntegration;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDonorPerfectDonors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $donorPerfectIntegration = null;
    protected $donor = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DonorPerfectIntegration $donorPerfectIntegration, Donor $donor)
    {
        $this->donorPerfectIntegration = $donorPerfectIntegration;
        $this->donor = $donor;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->donorPerfectIntegration->enabled || !$this->donorPerfectIntegration->track_donors) {
            return;
        }

        try {
            if ($this->donor->type === 'company') {
                return;
            }
            $response = $this->donorPerfectIntegration->syncDonor($this->donor);
            if ($response && $response->successful()) {
                activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->donor)->log("Integration of donor processed");

                return $response;
            } else {
                if ($response && $response->failed()) {
                    activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->donor)->log("Integration of donor failed");

                    $this->fail($response->throw());
                }
            }
        }
        catch (Exception $e) {
            activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->donor)->log("Integration of donor failed");

            $this->fail($e);
        }
    }
}
