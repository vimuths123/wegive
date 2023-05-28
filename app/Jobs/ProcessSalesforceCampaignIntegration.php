<?php

namespace App\Jobs;

use Exception;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use App\Models\SalesforceIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessSalesforceCampaignIntegration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesforceIntegration = null;
    protected $campaign = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $neonIntegration, Campaign $campaign)
    {
        $this->salesforceIntegration = $neonIntegration;
        $this->campaign = $campaign;
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
            $response = $this->salesforceIntegration->syncCampaign($this->campaign);
            if ($response &&  $response->successful()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->campaign)->log("Integration of campaign was processed");
                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->salesforceIntegration)->performedOn($this->campaign)->log("Integration of campaign failed");
                $this->fail();
            }
        } catch (Exception $e) {
            activity()->causedBy($this->salesforceIntegration)->performedOn($this->campaign)->log("Integration of campaign failed");
            $this->fail($e);
        }
    }
}
