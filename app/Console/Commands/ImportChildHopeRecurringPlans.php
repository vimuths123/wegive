<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\ScheduledDonation;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportChildHopeRecurringPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-child-hope-recurring-plans {organization_id}';


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
        ini_set('memory_limit', '-1');

        if (!$this->argument('organization_id')) return 0;
        $rows = $this->import();
        $keys = array_values($this->map); // $keys = $rows->current();
        $i = 0;

        $recurringPlansCreated = 0;
        $usersNotFound = 0;
        $cardNotFound = 0;
        $validPlans = 0;

        foreach ($rows as $rawRow) {

            if ($i <= 0) {
                $i++;

                continue;
            }


            $row = array_combine($keys, $rawRow);

            if (!$row['email'] || !$row['amount']) continue;


            $user = User::where('email', $row['email'])->first();

            if ($user && !$user->tl_token) {
                $user->tl_token = $row['correct_tilled_id'];
                $user->save();
            }




            $user = User::where('tl_token', trim($row['correct_tilled_id']))->first();


            if (!$user) {
                $usersNotFound += 1;

                continue;
            };

            $card = Card::where('tl_token', trim($row['tilled_pm']))->where('owner_id', $user->id)->where('owner_type', 'user')->first();


            if (!$card) {
                $card = new Card();
                $card->owner()->associate($user);
                $card->name = $row['name'] ?? $user->first_name . $user->last_name;
                $card->last_four = $row['last_four'];
                $card->issuer = $row['issuer'] ?? 'Unknown';
                $card->expiration = $row['exp_month'] . '/' . $row['exp_year'];
                $card->tl_token = trim($row['tilled_pm']);
                try {
                    $card->save();
                } catch (Exception $e) {
                    dump($e, $row);
                    $cardNotFound += 1;
                }
            }

            $logins = $user->logins()->where('loginable_type', 'donor')->get();

            $orgId = $this->argument('organization_id');

            $availableLogins = $logins->filter(function ($item) use ($orgId) {
                return $item->loginable->organization_id === (int)  $orgId;
            })->values();

            if (count($availableLogins) > 1) {
                dump('More than one login', $row, $user->id);
                continue;
            }

            $donor = $availableLogins->first()->loginable;

            if (!$donor) continue;

            if ($card->owner()->is($user)) {
                $validPlans += 1;
            } else {
                dump($user, $card->owner);
                continue;
            }



            if ($card->owner()->is($user)) {
                $date = Carbon::parse($row['last_successful_transaction']);
                $date->month = 6;
                $date->year = 2022;

                $columns = ['source_id' => $donor->id, 'source_type' => $donor->getMorphClass(), 'destination_id' => $this->argument('organization_id'), 'destination_type' => 'organization', 'amount' => $row['amount'] * 100, 'frequency' => ScheduledDonation::DONATION_FREQUENCY_MONTHLY, 'start_date' => $date, 'payment_method_type' => 'card', 'payment_method_id' => $card->id, 'cover_fees' => 0, 'tip' => 0, 'iteration' => 1, 'fee_amount' => 0, 'user_id' => $user->id];

                try {
                    DB::table('scheduled_donations')->insert($columns);
                    $recurringPlansCreated += 1;
                } catch (Exception $e) {
                    dump('here', $e, $row);
                }
            }
        }

        dump('Recurring Plans Created: ' . $recurringPlansCreated, 'Users Not Found: ' . $usersNotFound, 'Valid Plans: ' . $validPlans);

        return 0;
    }


    private function fileHandle()
    {
        return fopen(storage_path('recurringplanimport.csv'), 'r');
    }

    private function lineCount()
    {
        $count = 0;
        $handle = $this->fileHandle();
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }

        fclose($handle);
        return $count;
    }

    private function import()
    {
        $file = $this->fileHandle();
        while (($line = fgetcsv($file)) !== false) {
            yield $line;
        }

        fclose($file);
    }

    private $map = [
        "Email" => 'email',
        "Amount" => 'amount',
        "First Payment" => 'first_payment',
        "Last Payment Date" => 'first_payment_date',
        "Last Payment Status" => 'first_payment_status',
        "Last Successful Transaction" => 'last_successful_transaction',
        "End Date" => 'end_date',
        "Billing Cycle" => 'billing_cycle',
        "Is Active?" => 'is_active',
        "URL" => 'url',
        "Credit Card Expiration Date" => 'exp_date',
        "Form Name" => 'form_name',
        "Processor" => 'processor',
        "LGL Charge ID" => 'lgl_charge_id',
        "Stripe Charge ID" => 'stripe_charge_id',
        "Stripe Card ID" => 'stripe_card_id',
        "Stripe Card Fingerprint" => 'stripe_card_fingerprint',
        "Stripe Customer ID " => 'stripe_customer_id',
        "Tilled ID" => 'tilled_id',
        "Correct Tilled PM" => 'tilled_pm',
        "Correct Tilled ID" => 'correct_tilled_id',
        "Same?" => 'same',
        "LGL ID" => 'lgl_id',
        "Problem" => 'problem',
        "Found Alternate Payment Method (stripe card id) " => 'alt_payment_bool',
        "Card Last4" => 'last_four',
        "Card Brand" => 'issuer',
        "Card Funding" => 'funding',
        "Card Exp Month" => 'exp_month',
        "Card Exp Year" => 'exp_year',
        "Card Name" => 'card_name',
        "Card2 Last4" => 'last_four_2',
        "Card Brand 2" => 'issuer_2',
        "Card Funding 2" => 'funding_2',
        "Card Exp Month 2" => 'exp_month_2',
        "Card Exp Year 2" => 'exp_year_2',
        "Card Name 2" => 'card_name_2'

    ];
}
