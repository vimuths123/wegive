<?php

namespace App\Console\Commands;

use App\Models\Donor;
use App\Models\Login;
use App\Models\NeonIntegration;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportNeonDonors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-neon-donors {organization_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $neonIntegration = null;
    protected $accountsProcessed = 0;
    protected $accountsAttempted = 0;

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
        if (!$this->argument('organization_id')) return 0;

        $this->neonIntegration = NeonIntegration::where('organization_id', $this->argument('organization_id'))->firstOrFail();

        $this->processIndividualAccounts();
        $this->processCompanyAccounts();
        // $array = [
        //     17,
        //     373,
        //     434,
        //     557,
        //     728,
        //     959,
        //     1154,
        //     1156,
        //     1398,
        //     1995,
        //     2023,
        //     2170,
        //     3119,
        //     3250,
        //     3355,
        //     3668,
        //     4006,
        //     4045,
        //     4111,
        //     4118,
        //     4136,
        //     4169,
        //     4353,
        //     4526,
        //     4534,
        //     4541,
        //     4559,
        //     4563,
        //     4564,
        // ];

        // // dump($this->accountsAttempted, $this->accountsProcessed);

        // $accountsProcessed = 0;

        // foreach ($array as $id) {
        //     $accountRequest = $this->neonIntegration->get('accounts/' . $id, []);
        //     if ($accountRequest->failed()) continue;

        //     $accountsProcessed += 1;

        //     $data = $accountRequest->json();

        //     $individual = $data['individualAccount'];
        //     $company = $data['companyAccount'];

        //     if ($individual && $individual['primaryContact']) {
        //         $this->processIndividualAccount(['accountId' => $individual['accountId'], 'firstName' => $individual['primaryContact']['firstName'], 'lastName' => $individual['primaryContact']['lastName'], 'email' => $individual['primaryContact']['email1']]);
        //     } else if ($company && $company['primaryContact']) {
        //         $this->processCompanyAccount(['accountId' => $company['accountId'], 'firstName' => $company['primaryContact']['firstName'], 'lastName' => $company['primaryContact']['lastName'], 'email' => $company['primaryContact']['email1'], 'companyName' => $company['name']]);
        //     }
        // }

        // dump($accountsProcessed);
        return 0;
    }

    public function processIndividualAccounts($page = 0)
    {
        if ($page > 0) return;
        dump('Processing Individual Accounts Page' . $page);
        $accountsRequest = $this->neonIntegration->get('accounts', ['currentPage' => $page, 'userType' => 'INDIVIDUAL', 'pageSize' => 500]);

        $accounts = $accountsRequest->json()['accounts'];
        $pagination = $accountsRequest->json()['pagination'];

        foreach ($accounts as $account) {

            if ($account['email'] == null || $account['email'] == "" || $account['firstName'] == null ||  $account['firstName'] == "" || $account['userType'] == 'COMPANY') continue;
            $this->processIndividualAccount($account);
        }

        if ($pagination['totalPages'] > $page) {
            $this->processIndividualAccounts($page + 1);
        }

        if ($pagination['totalPages'] === $page) return;
    }

    public function processIndividualAccount($account)
    {
        dump('Processing: ' . $account['accountId']);
        $this->accountsAttempted += 1;


        $user = User::where('email', $account['email'])->get()->first();


        if (!$user) {
            try {
                $user = new User();
                $user->first_name = $account['firstName'];
                $user->last_name = $account['lastName'];
                $user->email = $account['email'];
                $user->password = Hash::make(Str::random());
                $user->save();
            } catch (Exception $e) {
                dump($e);
                return;
            }
        }

        // check if donor profile by id exists yet

        $logins = $user->logins()->where('loginable_type', 'individual')->get();

        $donorProfile = null;

        foreach ($logins as $login) {
            if ($login->loginable->organization()->is(Organization::find($this->argument('organization_id')))) {
                $donorProfile = $login->loginable;
                break;
            }
        }

        if ($donorProfile) {
            // add neon id to donor profile, sync donations
            $donorProfile->neon_account_id = $account['accountId'];
            $donorProfile->saveQuietly();
        } else {
            $individual = new Donor();
            $individual->first_name = $account['firstName'];
            $individual->last_name = $account['lastName'];
            $individual->email_1 = $account['email'];

            $individual->neon_account_id = $account['accountId'];

            $individual->organization_id = $this->argument('organization_id');
            $individual->saveQuietly();

            $login = new Login();
            $login->user()->associate($user);
            $login->loginable()->associate($individual);
            $login->save();

            // foreach ($primaryContact['addresses'] as $neonAddress) {

            //     $address = new Address();
            //     $address->addressable()->associate($individual);
            //     $address->city = $neonAddress['city'];
            //     $address->state = $neonAddress['stateProvince']['code'];
            //     $address->zip = $neonAddress['zipCode'];
            //     $address->address_1 = $neonAddress['addressLine1'];
            //     $address->address_2 = $neonAddress['addressLine2'];
            //     $individual->mobile_phone = $neonAddress['phone1'];
            //     $individual->save();
            //     $address->save();
            // }
        }


        $this->processDonations(0, $account['accountId']);
    }

    public function processCompanyAccounts($page = 0)
    {
        dump('Processing Company Accounts Page' . $page);

        $accountsRequest = $this->neonIntegration->get('accounts', ['currentPage' => $page, 'userType' => 'COMPANY', 'pageSize' => 500]);

        $accounts = $accountsRequest->json()['accounts'];
        $pagination = $accountsRequest->json()['pagination'];

        foreach ($accounts as $account) {
            if ($account['email'] == null || $account['email'] == "" || $account['companyName'] == null ||  $account['companyName'] == "" || $account['userType'] == 'INDIVIDUAL' || $account['firstName'] == null ||  $account['firstName'] == ""  || $account['lastName'] == null ||  $account['lastName'] == "") continue;

            $this->processCompanyAccount($account);
        }

        if ($pagination['totalPages'] > $page) {
            $this->processCompanyAccounts($page + 1);
        }

        if ($pagination['totalPages'] === $page) return;
    }

    public function processCompanyAccount($account)
    {
        dump('Processing: ' . $account['accountId']);
        $this->accountsAttempted += 1;



        $user = User::where('email', $account['email'])->get()->first();


        if (!$user) {
            try {
                $user = new User();
                $user->first_name = $account['firstName'];
                $user->last_name = $account['lastName'];
                $user->email = $account['email'];
                $user->password = Hash::make(Str::random());
                $user->save();
            } catch (Exception $e) {
                dump($e);
                return;
            }
        }



        $logins = $user->logins()->where('loginable_type', 'company')->get();

        $donorProfile = null;

        foreach ($logins as $login) {
            if ($login->loginable->organization()->is(Organization::find($this->argument('organization_id')))) {
                $donorProfile = $login->loginable;
                break;
            }
        }


        if ($donorProfile) {
            // add neon id to donor profile, sync donations
            $donorProfile->neon_account_id = $account['accountId'];
            $donorProfile->saveQuietly();
        } else {
            $company = new Donor();
            $company->type = 'company';
            $company->name = $account['companyName'];
            $company->email_1 = $account['email'];

            $company->neon_account_id = $account['accountId'];

            $company->organization_id = $this->argument('organization_id');
            $company->saveQuietly();

            $login = new Login();
            $login->user()->associate($user);
            $login->loginable()->associate($company);
            $login->save();

            // foreach ($primaryContact['addresses'] as $neonAddress) {
            //     $address = new Address();
            //     $address->addressable()->associate($company);
            //     $address->city = $neonAddress['city'];
            //     $address->state = $neonAddress['stateProvince']['code'];
            //     $address->zip = $neonAddress['zipCode'];
            //     $address->address_1 = $neonAddress['addressLine1'];
            //     $address->address_2 = $neonAddress['addressLine2'];
            //     $company->mobile_phone = $neonAddress['phone1'];
            //     $company->save();
            //     $address->save();
            // }

            // create donor profile, sync donations
        }

        $this->accountsProcessed += 1;

        $this->processDonations(0, $account['accountId']);
    }


    public function processDonations($page = 0, $accountId)
    {
        dump('Processing Donations for: ' . $accountId);

        $donationsRequest = $this->neonIntegration->get("accounts/{$accountId}/donations", ['currentPage' => $page, 'pageSize' => '20']);


        if ($donationsRequest->failed()) return;
        $donations = $donationsRequest->json()['donations'];
        $pagination = $donationsRequest->json()['pagination'];

        foreach ($donations as $donation) {
            $this->processDonation($donation, $accountId);
        }

        if ($pagination['totalPages'] > $page) {
            $this->processDonations($page + 1, $accountId);
        }

        if ($pagination['totalPages'] === $page) return;
    }

    public function processDonation($donation, $accountId)
    {

        $donorProfile = Donor::where('neon_account_id', $accountId)->where('organization_id', $this->argument('organization_id'))->get()->first();

        if (!$donorProfile) return;

        if ($donorProfile->transactions()->whereNotNull('neon_id')->where('neon_id', $donation['id'])->first()) return;

        dump('new donation found');

        $transaction = new Transaction();
        $organization = Organization::find($this->argument('organization_id'));
        $transaction->amount = $donation['amount'] * 100;
        $transaction->description = 'Neon Import';
        $transaction->created_at = $donation['timestamps']['createdDateTime'];
        $transaction->owner()->associate($donorProfile);
        $transaction->source()->associate($organization);
        $transaction->destination()->associate($organization);
        $transaction->neon_id = $donation['id'];
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->cover_fees = $donation['donorCoveredFee'] ?? false;
        $transaction->neon_payment_id = $donation['payments'][0]['id'] ?? null;
        $transaction->saveQuietly();
    }
}
