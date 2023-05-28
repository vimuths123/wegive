<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Models\ScheduledDonation;
use Exception;
use Illuminate\Support\Facades\DB;

class ImportChildHopeTilledMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-child-hope-tilled-migration {organization_id}';


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

        $cardsCreated = 0;
        $recurringPlansCreated = 0;
        $usersNotFound = 0;
        $validPlans = 0;

        foreach ($rows as $rawRow) {

            if ($i <= 0) {
                $i++;

                continue;
            }


            $row = array_combine($keys, $rawRow);

            $user = User::where('tl_token', trim($row['tilled_customer_id']))->first();


            if (!$user) {
                $usersNotFound += 1;
                $stripeId = trim($row['stripe_customer_ids']);
                $newId = str_replace(['{', '}'], "", $stripeId);

                dump($newId);
                continue;
            };

            $card = Card::where('tl_token', trim($row['tilled_payment_method_id']))->first();


            if (!$card) {
                $card = new Card();

                $card->owner()->associate($user);
                $card->name = $row['name'] ?? $user->first_name . $user->last_name;
                $card->last_four = $row['last_four'];
                $card->issuer = $row['issuer'] ?? 'Unknown';
                $card->expiration = $row['exp_month'] . '/' . $row['exp_year'];
                $card->tl_token = $row['tilled_payment_method_id'];
                try {
                    $card->save();
                    $cardsCreated += 1;
                } catch (Exception $e) {
                    dump($e, $row);
                }
            }



            $logins = $user->logins()->where('loginable_type', 'donor')->get();

            if (count($logins) > 1) {
                dump('More than one login', $row, $user->id);
                continue;
            }

            $donor = $logins->first()->loginable;

            if (!$donor) continue;

            dump($card && $row['active'] === 'TRUE' && $row['frequency'] === 'Monthly' && $row['last_payment_status'] === 'Paid' && $card->owner()->is($user));

            if ($card && $row['active'] === 'TRUE' && $row['frequency'] === 'Monthly' && $row['last_payment_status'] === 'Paid' && $card->owner()->is($user)) {
                $validPlans += 1;
            }

            continue;

            if ($card && $row['active'] === 'TRUE' && $row['frequency'] === 'Monthly' && $row['last_payment_status'] === 'Paid' && $card->owner()->is($user)) {
                $date = Carbon::parse($row['last_processed']);
                $date->month = 6;
                $date->year = 2022;

                $columns = ['source_id' => $donor->id, 'source_type' => 'donor', 'destination_id' => $this->argument('organization_id'), 'destination_type' => 'organization', 'amount' => $row['amount'] * 100, 'frequency' => ScheduledDonation::DONATION_FREQUENCY_MONTHLY, 'start_date' => $date, 'payment_method_type' => 'card', 'payment_method_id' => $card->id, 'cover_fees' => 0, 'tip' => 0, 'iteration' => 1, 'user_id' => $user->id];

                try {
                    DB::table('scheduled_donations')->insert($columns);
                    $recurringPlansCreated += 1;
                } catch (Exception $e) {
                    dump($e, $row);
                }
            }
        }

        dump('Cards Created: ' . $cardsCreated, 'Recurring Plans Created: ' . $recurringPlansCreated, 'Users Not Found: ' . $usersNotFound, 'Valid Plans: ' . $validPlans);

        return 0;
    }


    private function fileHandle()
    {
        return fopen(storage_path('stripetilledimportfile.csv'), 'r');
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
        'tilled_payment_method_id' => 'tilled_payment_method_id',
        'stripe_card_id' => 'stripe_card_id',
        'tilled_customer_id' => 'tilled_customer_id',
        'stripe_customer_ids' => 'stripe_customer_ids',
        'Card Last4' => 'last_four',
        'Card Brand' => 'issuer',
        'Card Funding' => 'card_funding',
        'Card Exp Month' => 'exp_month',
        'Card Exp Year' => 'exp_year',
        'Name' => 'name',
        'Amount' => 'amount',
        'First Payment' => 'first_payment',
        'Last Payment Date' => 'last_payment',
        'Last Payment Status' => 'last_payment_status',
        'Last Successful Transaction' => 'last_success',
        'Billing Cycle' => 'frequency',
        'Is Active?' => 'active',
    ];
}
