<?php

namespace App\Jobs;

use Exception;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use App\Models\NeonIntegration;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessNeonIntegrationDonations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $neonIntegration = null;
    protected $transaction = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(NeonIntegration $neonIntegration, Transaction $transaction)
    {
        $this->neonIntegration = $neonIntegration;
        $this->transaction = $transaction;
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
            $response =  $this->neonIntegration->syncDonation($this->transaction);
            if ($response && $response->successful()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->transaction)->log("Integration of donation was processed");

                return $response;
            } else if ($response && $response->failed()) {
                activity()->causedBy($this->neonIntegration)->performedOn($this->transaction)->log("Integration of donation failed");

                $this->fail($response->throw());
            }
        } catch (Exception $e) {
            activity()->causedBy($this->neonIntegration)->performedOn($this->transaction)->log("Integration of donation failed");
            $this->fail($e);
        }
    }
}
