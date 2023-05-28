<?php

namespace App\Jobs;

use Exception;
use App\Models\Fundraiser;
use Illuminate\Bus\Queueable;
use App\Models\NeonIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessNeonIntegrationCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $neonIntegration = null;
    protected $fundraiser = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(NeonIntegration $neonIntegration, Fundraiser $fundraiser)
    {
        $this->neonIntegration = $neonIntegration;
        $this->fundraiser = $fundraiser;
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
            $response = $this->neonIntegration->syncFundraiser($this->fundraiser);
            if ( $response && $response->successful()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->campaign)->log("Integration of campaign was processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->campaign)->log("Integration of campaign failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->neonIntegration)->performedOn($this->campaign)->log("Integration of campaign failed");

            $this->fail($e);
        }
    }
}
