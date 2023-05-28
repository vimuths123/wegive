<?php

namespace App\Jobs;

use Exception;
use App\Models\Donor;
use Illuminate\Bus\Queueable;
use App\Models\NeonIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessNeonIntegrationDonors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $neonIntegration = null;
    protected $donor = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(NeonIntegration $neonIntegration, $donor)
    {
        $this->neonIntegration = $neonIntegration;
        $this->donor = $donor;
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
            $response =  $this->neonIntegration->syncDonor($this->donor);
            if ($response && $response->successful()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->donor)->log("Integration of donor succeeded");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->donor)->log("Integration of donor failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->neonIntegration)->performedOn($this->donor)->log("Integration of donor failed");

            $this->fail($e);
        }
    }
}
