<?php

namespace App\Console\Commands;

use App\Processors\Tilled;
use Illuminate\Console\Command;

class ImportAndRelatePayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-payouts';

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

        $tilled = new Tilled();



        return 0;
    }
}
