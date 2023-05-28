<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ClearExpiredVerificationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:clear-verification-codes';

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
        $users = User::where('verification_code_set_at', '<', date('Y-m-d H:i:s', strtotime('-5 minutes')));

        $users->update(['verification_code' => null, 'verification_code_set_at' => null]);

        return 0;
    }
}
