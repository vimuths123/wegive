<?php

namespace App\Jobs;

use App\Models\Fund;
use Illuminate\Bus\Queueable;
use App\Models\SalesforceIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ImportGau implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salesforceIntegration = null;
    protected $model = null;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $salesforceIntegration, $model)
    {
        $this->salesforceIntegration = $salesforceIntegration;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $salesforceId = $this->model['Id'];

        $organization = $this->salesforceIntegration->organization;



        $fund = Fund::where('salesforce_id', $salesforceId)->where('organization_id', $organization->id)->first();


        if ($fund) {
            $fund->name = $this->model['Name'];
            $fund->save();
        } else {
            $fund = new Fund();
            $fund->organization()->associate($organization);
            $fund->salesforce_id = $salesforceId;
            $fund->name = $this->model['Name'];
            $fund->save();
        }
    }
}
