<?php

namespace App\Jobs;

use App\Models\Donor;
use App\Models\SalesforceIntegration;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportOpportunity implements ShouldQueue
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


        $transaction = Transaction::whereNotNull('salesforce_id')->where('salesforce_id', $salesforceId)->where('destination_id', $organization->id)->where('destination_type', 'organization')->first();


        if ($transaction) {

            $transaction->status =  Transaction::STATUS_SUCCESS;
            $transaction->save();
        } else {

            $transaction = new Transaction();
            $transaction->source()->associate($organization);
            $transaction->destination()->associate($organization);
            $owner = Donor::where('salesforce_id', $this->model['ContactId'])->where('organization_id', $organization->id)->first();

            if (!$owner) return;

            $transaction->owner()->associate($owner);
            $transaction->amount = $this->model['Amount'] * 100;
            $transaction->created_at = $this->model['CreatedDate'];
            $transaction->status = Transaction::STATUS_SUCCESS;
            $transaction->salesforce_id = $salesforceId;
            $transaction->description = 'Salesforce Import';
            $transaction->save();
        }
    }
}
