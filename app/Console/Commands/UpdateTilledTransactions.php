<?php

namespace App\Console\Commands;

use App\Actions\Payments;
use App\Processors\Tilled;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class UpdateTilledTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update-tilled-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This takes money from our bank account and gives it to the recently onboarded organizations';

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
        Mail::raw("Processing Tilled Transactions", function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Cron Job Running');
        });

        $t = new Tilled();
        $t->checkTransactionStatus();
        return 0;
    }
}
