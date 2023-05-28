<?php

namespace App\Jobs;

use Exception;
use App\Nova\Company;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Models\DonorPerfectIntegration;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessDonorPerfectDonations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $donorPerfectIntegration = null;
    protected $transaction = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DonorPerfectIntegration $donorPerfectIntegration, Transaction $transaction)
    {
        $this->donorPerfectIntegration = $donorPerfectIntegration;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->donorPerfectIntegration->enabled || !$this->donorPerfectIntegration->track_donations) return;


        try {
            $response = $this->donorPerfectIntegration->syncGift($this->transaction);
            if ($response && $response->successful()) {
                activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->transaction)->log("Integration of donation processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->transaction)->log("Integration of donation failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->donorPerfectIntegration)->performedOn($this->transaction)->log("Integration of donation failed");

            $this->fail($e);
        }
    }
}
