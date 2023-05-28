<?php

namespace App\Console\Commands;

use App\Models\Login;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ProcessWeeklyRollupEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:process-weekly-rollup-emails';

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
        $logins = Login::where('notification_frequency', Login::NOTIFICATION_FREQUENCY_WEEKLY)->where('loginable_type', 'organization')->get();

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



        $fundraisers = $organization->fundraisers()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $fundraiserCount = count($fundraisers);

        Mail::send('emails.rollup', ['sentence' => "You have had {$fundraiserCount} new fundraisers this week."], function ($message) use ($user, $fundraiserCount) {
            $message->to($user->email)
                ->subject("{$fundraiserCount} New Fundraisers This Week");
        });
    }

    public function sendDonorRollup(Organization $organization, User $user)
    {
        $donors = $organization->donors()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $donorCount = count($donors);

        Mail::send('emails.rollup', ['sentence' => "You have had {$donorCount} new donations this week."], function ($message) use ($user, $donorCount) {
            $message->to($user->email)
                ->subject("{$donorCount} New Donors This Week");
        });
    }

    public function sendDonationRollup(Organization $organization, User $user)
    {

        $transactions = $organization->receivedTransactions()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $transactionCount = count($transactions);


        Mail::send('emails.rollup', ['sentence' => "You have had {$transactionCount} new donations this week."], function ($message) use ($user, $transactionCount) {
            $message->to($user->email)
                ->subject("{$transactionCount} New Donations This Week");
        });
    }
}
