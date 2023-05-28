<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\User;
use App\Actions\Payments;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Console\Command;
use App\Models\ScheduledDonation;

class ProcessGivelistRecurringDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:givelist-donations';

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

    public function processRecurringGift($donation, $user)
    {
        $donation->destination->give($donation->paymentMethod ?? $user->preferredPayment ?? $user, $donation->amount, 'Recurring Donation', $donation->id);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userIds = ScheduledDonation::where('source_type', 'user')->where('platform', 'givelist')->get()->groupBy(['source_type', 'source_id']);


        if (empty($userIds->all())) {return 0; };


        $users = User::query()->whereIn('id', array_keys($userIds['user']->all()))->get();

        foreach ($users as $key => $user) { if ($user->next_scheduled_donation_at < today()) {
            $users->forget($key);
        } }


        foreach ($users as $key => $user) {
            $scheduledDonations = $user->scheduledDonations;

            foreach ($scheduledDonations as $donation) {
                if ($user->scheduled_donation_frequency == User::DONATION_FREQUENCY_MONTHLY) {
                    $startDate = new DateTime($user->next_scheduled_donation_at);

                    $today = today();
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $today->format('m'), $today->format('y'));

                    if ($daysInMonth == 30 && $startDate->format('d') == 31 && $today->format('d') == 30) {
                        $this->processRecurringGift($donation, $user);
                        continue;
                    }

                    if ($startDate->format('d') === today()->format('d')) {
                        $this->processRecurringGift($donation, $user);
                        continue;
                    }
                }
                if ($user->scheduled_donation_frequency === User::DONATION_FREQUENCY_WEEKLY) {
                    if (date('D') == 'Sun') {
                        $this->processRecurringGift($donation, $user);
                        continue;
                    }
                }

                if ($user->scheduled_donation_frequency === User::DONATION_FREQUENCY_BIMONTHLY) {
                    $fDay = date('01-m-Y');
                    $hDay = date('d-m-Y', (strtotime($fDay) + (86400 * 14)));
                    $fifteenth = new DateTime($hDay);
                    if (today() == new DateTime('first day of this month') || today() == $fifteenth) {
                        $this->processRecurringGift($donation, $user);
                        continue;
                    }
                }
            }
        }
        return 0;
    }
}
