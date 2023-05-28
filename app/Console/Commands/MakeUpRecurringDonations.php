<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Bank;
use App\Models\Card;
use Illuminate\Console\Command;
use App\Models\ScheduledDonation;
use Illuminate\Support\Facades\Mail;

class MakeUpRecurringDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make-up-recurring-donations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    public function processRecurringGift($donation)
    {
        $amount = $donation->amount;
        if ($donation->cover_fees) {
            $amount += $donation->fee_amount;
        }

        $amount += $donation->tip;


        return $donation->destination->give($donation->paymentMethod, $amount, 'Recurring Donation', $donation->id, $donation->tip,  null, null, null, false, $donation->cover_fees, $donation->fee_amount, null, true);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        Mail::raw("Processing Make Up Recurring Donations", function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Cron Job Running');
        });

        $from = date('2022-08-09');
        $to = date('2022-08-17');

        $scheduledDonations = ScheduledDonation::whereBetween('start_date', [$from, $to])->get();

        foreach ($scheduledDonations as &$donation) {

            if ($donation->amount === 0) continue;

            if ($donation->paused_until > now()) continue;

            if ($donation->paused_at && $donation->paused_type === ScheduledDonation::PAUSE_TYPE_INDEFINITE) continue;

            if ($donation->paused_until < now()) {
                $donation->paused_at = null;
                $donation->paused_type = null;
                $donation->paused_until = null;
            };


            $transaction = $this->processRecurringGift($donation);
            $transaction->created_at = $donation->start_date;
            $transaction->owner()->associate($donation->source);
            if ($donation->user) {
                $transaction->user()->associate($donation->user);
            } else if ($donation->paymentMethod instanceof Card || $donation->paymentMethod instanceof Bank) {
                $transaction->user()->associate($donation->paymentMethod->owner);
                $donation->user()->associate($donation->paymentMethod->owner);
            }
            if ($donation->tribute) {
                $transaction->tribute = true;
                $transaction->tribute_name = $donation->tribute_name;
                $transaction->tribute_message = $donation->tribute_message;
                $transaction->tribute_email = $donation->tribute_email;
                if ($donation->tribute_email) {
                    Mail::send('emails.tribute', ['tributeName' => $donation->tribute_name, 'donorName' => $transaction->owner->name(), 'destinationName' => $transaction->destination->name, 'tributeMessage' => $donation->tribute_message, 'logo' => $transaction->destination->getFirstMedia('avatar') ?  $transaction->destination->getFirstMedia('avatar')->getUrl() : null], function ($message) use ($donation) {
                        $message->to($donation->tribute_email)
                            ->subject('Someone has donated in your honor');
                    });
                }
            }




            $transaction->scheduled_donation_iteration = $donation->iteration + 1;
            $transaction->cover_fees = $donation->cover_fees;
            $transaction->fee_amount = $donation->fee_amount;
            $transaction->save();
            $donation->iteration += 1;
            $donation->save();
            $startDate = new DateTime($donation->start_date);
            $modifiedDate = null;


            if ($donation->frequency == ScheduledDonation::DONATION_FREQUENCY_MONTHLY) {
                $modifiedDate = $startDate->modify('+ 1 month');
            }

            if ($donation->frequency === ScheduledDonation::DONATION_FREQUENCY_WEEKLY) {
                $modifiedDate = $startDate->modify('+ 1 week');
            }

            if ($donation->frequency === ScheduledDonation::DONATION_FREQUENCY_BIMONTHLY) {
                $modifiedDate = $startDate->modify('+ 15 days');
            }

            if ($donation->frequency === ScheduledDonation::DONATION_FREQUENCY_YEARLY) {
                $modifiedDate = $startDate->modify('+ 1 year');
            }

            if ($donation->frequency === ScheduledDonation::DONATION_FREQUENCY_DAILY) {
                $modifiedDate = $startDate->modify('+ 1 day');
            }

            if ($donation->frequency === ScheduledDonation::DONATION_FREQUENCY_QUARTERLY) {
                $modifiedDate = $startDate->modify('+ 3 months');
            }

            $donation->start_date = $modifiedDate;
            $donation->save();
        }

        return 0;
    }
}
