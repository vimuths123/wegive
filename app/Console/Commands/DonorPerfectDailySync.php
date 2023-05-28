<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DonorPerfectIntegration;

class DonorPerfectDailySync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:donor-perfect-daily-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dpIntegrations = DonorPerfectIntegration::where('enabled', true)->get();

        foreach ($dpIntegrations as $dp) {
            $dp->importTodaysDonors();
            $dp->importTodaysGifts();
        }
    }
}
