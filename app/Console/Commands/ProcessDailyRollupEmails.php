<?php

namespace App\Console\Commands;

use App\Models\Login;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ProcessDailyRollupEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:process-daily-rollup-emails';

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
        $logins = Login::where('notification_frequency', Login::NOTIFICATION_FREQUENCY_DAILY)->where('loginable_type', 'organization')->get();

        foreach ($logins as $login) {

            if ($login->new_donor_email) {
                $this->sendDonorRollup($login->loginable, $login->user);
            }

            if ($login->new_donation_email) {
                $this->sendDonationRollup($login->loginable, $login->user);
            }

            if ($login->new_fundraiser_email) {
                $this->sendFundraiserRollup($login->loginable, $login->user);
            }
        }
    }


    public function sendFundraiserRollup(Organization $organization, User $user)
    {



        $fundraisers = $organization->fundraisers()->whereDate('created_at', Carbon::today())->get();

        $fundraiserCount = count($fundraisers);

        Mail::send('emails.rollup', ['sentence' => "You have had {$fundraiserCount} new fundraisers today."], function ($message) use ($user, $fundraiserCount) {
            $message->to($user->email)
                ->subject("{$fundraiserCount} New Fundraisers Today");
        });
    }

    public function sendDonorRollup(Organization $organization, User $user)
    {
        $donors = $organization->donors()->whereDate('created_at', Carbon::today())->get();

        $donorCount = count($donors);

        Mail::send('emails.rollup', ['sentence' => "You have had {$donorCount} new donations today."], function ($message) use ($user, $donorCount) {
            $message->to($user->email)
                ->subject("{$donorCount} New Donors Today");
        });
    }

    public function sendDonationRollup(Organization $organization, User $user)
    {
        $transactions = $organization->receivedTransactions()->whereDate('created_at', Carbon::today())->get();

        $transactionCount = count($transactions);

        Mail::send('emails.rollup', ['sentence' => "You have had {$transactionCount} new donations today."], function ($message) use ($user, $transactionCount) {
            $message->to($user->email)
                ->subject("{$transactionCount} New Donations Today");
        });
    }
}
