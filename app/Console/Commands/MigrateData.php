<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Donor;
use App\Models\Login;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;

class MigrateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:migrate-data';

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
        $users = User::all();

        foreach ($users as $user) {

            // Create Donor For User
            $donor = new Donor();
            $donor->handle = $user->handle;
            $donor->type = 'donor';
            $donor->tl_token = $user->tl_token ?? null;
            $donor->user_id = $user->id;
            $donor->created_at = $user->created_at;
            $donor->save();


            // Create Login for New Donor
            $login = new Login();
            $login->loginable()->associate($donor);
            $user->logins()->save($login);

            // Set Current Login
            $user->currentLogin()->associate($donor);

            // Relate Organization to Donor
            $orgs = $user->donationsMade()->where('destination_type', 'organization')->get()->unique('destination_id')->pluck('destination_id')->all();


            // Only Make Donor if user has first and last name
            if ($user->first_name && $user->last_name) {

                $mailingAddress = new Address();
                $mailingAddress->address_1 = $user->address1;
                $mailingAddress->address_2 = $user->address2;
                $mailingAddress->city = $user->city;
                $mailingAddress->state = $user->state;
                $mailingAddress->zip = $user->zip;
                $mailingAddress->primary = true;
                $mailingAddress->type = 'mailing';

                $billingAddress = new Address();
                $billingAddress->address_1 = $user->address1;
                $billingAddress->address_2 = $user->address2;
                $billingAddress->city = $user->city;
                $billingAddress->state = $user->state;
                $billingAddress->zip = $user->zip;
                $billingAddress->primary = true;
                $billingAddress->type = 'billing';

                // Wegive Donor Profile
                $donor = new Donor();
                $donor->first_name = $user->first_name;
                $donor->last_name = $user->last_name;
                $donor->email_1 = $user->email;
                $donor->mobile_phone = $user->phone;
                $donor->is_public = $user->is_public;
                $donor->donor_id = $donor->id;
                $donor->organization_id = env('WEGIVE_DONOR_PROFILE');
                $donor->preferredPayment()->associate($user->preferred_payment);
                $donor->save();

                try {
                    $donor->addresses()->save($mailingAddress);
                    $donor->addresses()->save($billingAddress);
                } catch (Exception $e) {
                }

                // Generate Donor Profiles
                foreach ($orgs as $org) {
                    $mailingAddress = new Address();
                    $mailingAddress->address_1 = $user->address1;
                    $mailingAddress->address_2 = $user->address2;
                    $mailingAddress->city = $user->city;
                    $mailingAddress->state = $user->state;
                    $mailingAddress->zip = $user->zip;
                    $mailingAddress->primary = true;
                    $mailingAddress->type = 'mailing';

                    $billingAddress = new Address();
                    $billingAddress->address_1 = $user->address1;
                    $billingAddress->address_2 = $user->address2;
                    $billingAddress->city = $user->city;
                    $billingAddress->state = $user->state;
                    $billingAddress->zip = $user->zip;
                    $billingAddress->primary = true;
                    $billingAddress->type = 'billing';

                    $donor = new Donor();
                    $donor->first_name = $user->first_name;
                    $donor->last_name = $user->last_name;
                    $donor->email_1 = $user->email;
                    $donor->mobile_phone = $user->phone;
                    $donor->is_public = $user->is_public;
                    $donor->donor_id = $donor->id;
                    $donor->organization_id = $org;
                    $donor->preferredPayment()->associate($user->preferred_payment);
                    $donor->save();

                    try {
                        $donor->addresses()->save($mailingAddress);
                        $donor->addresses()->save($billingAddress);
                    } catch (Exception $e) {
                    }
                }
            }



            // Convert User Data to Donor Data
            $this->convertFundraisers($user, $donor);
            $this->convertBanks($user, $donor);
            $this->convertCards($user, $donor);
            $this->convertTransactions($user, $donor);
            $this->convertInterests($user, $donor);
            $this->convertScheduledDonations($user, $donor);
            $this->convertGivelists($user, $donor);
        }
    }


    public function convertFundraisers($user, $donor)
    {

        $fundraisers = $user->fundraisers;

        foreach ($fundraisers as $fundraiser) {
            $fundraiser->owner()->associate($donor);
            $fundraiser->save();
        }
    }

    public function convertBanks($user, $donor)
    {

        $banks = $user->banks;

        foreach ($banks as $bank) {
            $bank->owner()->associate($donor);
            $bank->save();
        }
    }

    public function convertCards($user, $donor)
    {

        $cards = $user->cards;

        foreach ($cards as $card) {
            $card->owner()->associate($donor);
            $card->save();
        }
    }


    public function convertTransactions($user, $donor)
    {

        $donationsMade = $user->donationsMade;

        foreach ($donationsMade as $donations) {
            $donations->owner()->associate($donor);
            $donations->save();
        }

        $sentTransactions = $user->sentTransactions;

        foreach ($sentTransactions as $transaction) {
            $transaction->source()->associate($donor);
            $transaction->save();
        }

        $receivedTransactions = $user->receivedTransactions;

        foreach ($receivedTransactions as $transaction) {
            $transaction->destination()->associate($donor);
            $transaction->save();
        }
    }

    public function convertInterests($user, $donor)
    {

        $interests = $user->interests;

        foreach ($interests as $interest) {
            $interest->enthusiast()->associate($donor);
            $interest->save();
        }
    }

    public function convertGivelists($user, $donor)
    {

        $givelists = $user->givelists;

        foreach ($givelists as $givelist) {
            $givelist->creator()->associate($donor);
            $givelist->save();
        }
    }

    public function convertScheduledDonations($user, $donor)
    {

        $scheduledDonations = $user->scheduledDonations;

        foreach ($scheduledDonations as $donation) {
            $donation->source()->associate($donor);
            $donation->save();

            if ($donation->paymentMethod instanceof User) {
                $donation->paymentMethod()->associate($donor);
                $donation->save();
            }
        }
    }
}
