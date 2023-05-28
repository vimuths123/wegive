<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\User;
use App\Actions\Payments;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Console\Command;
use App\Models\ScheduledDonation;
use Illuminate\Support\Facades\Mail;

class SendBrianEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send-brian-email';

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

        $array = array(['First Name', 'Last Name', 'Email']);



        $transactionsOne = Transaction::whereIn('givelist_id', [72, 73, 75])->where('status', '!=',  Transaction::STATUS_FAILED)->get();

        $transactionsTwo = Transaction::whereIn('destination_id', [72, 73, 75])->where([['destination_type', 'givelist'], ['status', '!=',  Transaction::STATUS_FAILED]])->get();


        $transactions = $transactionsOne->merge($transactionsTwo)->unique('user_id');

        foreach ($transactions as $t) {
            array_push($array, ['first_name' => $t->user->first_name, 'last_name' => $t->user->last_name, 'email' => $t->user->email]);
        }


        // Open a file in write mode ('w')
        $fp = tmpfile();

        // Loop through file pointer and a line
        foreach ($array as $fields) {
            fputcsv($fp, $fields);
        }

        Mail::send('emails.brian', [], function ($message) use ($fp) {
            $message->to('brian@forpurpose.com')
                ->subject('Donor List');

            $message->attach(stream_get_meta_data($fp)['uri'], ['as' => 'DonorList.csv', 'mime' => 'text/csv']);
        });

        fclose($fp);

        return 0;
    }
}
