<?php

namespace App\Jobs;

use App\Models\Donor;
use App\Models\Login;
use App\Models\SalesforceIntegration;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $salesforceIntegration = null;
    protected $model = null;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $salesforceIntegration, $model)
    {
        $this->salesforceIntegration = $salesforceIntegration;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $salesforceId = $this->model['Id'];
        $organization = $this->salesforceIntegration->organization;


        $individual = Donor::where('email_1',  $this->model['Email'])->where('organization_id', $organization->id)->first();


        if ($individual) {
            $individual->first_name = $this->model['FirstName'];
            $individual->last_name = $this->model['LastName'];
            $individual->mobile_phone = $this->model['MobilePhone'];
            $individual->office_phone = $this->model['Phone'];
            $individual->fax = $this->model['Fax'];
            $individual->home_phone = $this->model['HomePhone'];
            $individual->other_phone = $this->model['OtherPhone'];
            $individual->email_1 = $this->model['Email'];
            $individual->salesforce_id = $salesforceId;
            $individual->save();
        } else {

            $user = User::where('email', $this->model['Email'])->first();
            if ($user) {
                $user->last_name = $this->model['LastName'];
                $user->save();
            }

            if ($user) {

                $donorLogins = $user->logins()->where('loginable_type', 'individual')->get();

                $donor = null;


                foreach ($donorLogins as $login) {
                    if ($login->loginable->organization_id == $this->salesforceIntegration->organization_id) {
                        $donor = $login->loginable;
                        break;
                    }
                }

                if (!$donor) {
                    $individual = new Donor();
                    $individual->email_1 = $this->model['Email'];
                    $individual->first_name = $this->model['FirstName'];
                    $individual->last_name = $this->model['LastName'];
                    $individual->mobile_phone = $this->model['MobilePhone'];
                    $individual->organization->associate($organization);
                    $individual->salesforce_id = $salesforceId;
                    $individual->save();
                    $login = new Login();
                    $login->user()->associate($user);
                    $login->loginable()->associate($individual);
                    $login->save();
                } else {
                    $donor->salesforce_id = $salesforceId;
                    $donor->save();
                }
            } else {
                $userData = ['first_name' => $this->model['FirstName'], 'last_name' => $this->model['LastName'], 'email' => $this->model['Email'], 'phone' => $this->model['MobilePhone'], 'password' => Hash::make(Str::random())];

                if (!$this->model['FirstName'] || !$this->model['LastName'] || !$this->model['Email']) return;

                try {
                    $user = User::create($userData);
                } catch (Exception $e) {
                    return;
                }

                if (!$user) return;


                $individual = new Donor();
                $individual->email_1 = $this->model['Email'];
                $individual->first_name = $this->model['FirstName'];
                $individual->last_name = $this->model['LastName'];
                $individual->mobile_phone = $this->model['MobilePhone'];
                $individual->salesforce_id = null;
                $individual->organization_id = env('WEGIVE_DONOR_PROFILE');
                $individual->save();
                $login = new Login();
                $login->loginable()->associate($individual);
                $user->logins()->save($login);




                $individual = new Donor();
                $individual->first_name = $this->model['FirstName'];
                $individual->last_name = $this->model['LastName'];
                $individual->email_1 = $this->model['Email'];
                $individual->mobile_phone = $this->model['MobilePhone'];
                $individual->organization_id = $this->salesforceIntegration->organization_id;
                $individual->salesforce_id = $salesforceId;

                $individual->save();
                $login = new Login();
                $login->loginable()->associate($individual);
                $user->logins()->save($login);

                $user->currentLogin()->associate($individual);
                $user->save();
            }
        }
    }
}
