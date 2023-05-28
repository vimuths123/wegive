<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Fund;
use App\Models\Login;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SalesforceIntegrationController extends Controller
{
    public function isAuthorized(Request $request)
    {

        $apiKey = $request->headers->get('wegive-api-key');
        $organizationId = $request->headers->get('organization');

        $organization = Organization::find($organizationId);

        $token =  \Laravel\Sanctum\PersonalAccessToken::findToken($apiKey);

        return $token->tokenable()->is($organization);
    }

    public function handleAccountTrigger(Request $request)
    {
        return;
        $this->isAuthorized($request);

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $company = Company::where('salesforce_id', $salesforceId)->where('organization_id', $organization->id)->first();
        $salesforceRequest = $organization->salesforceIntegration->get("services/data/v54.0/sobjects/Account/{$salesforceId}", null);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_donors) return;

        if ($salesforceRequest->failed()) return;

        $salesforceData = $salesforceRequest->json();

        if ($company) {

            $company->name = $salesforceData['name'];
            $company->office_phone = $salesforceData['phone'];
            $company->fax = $salesforceData['fax'];
            $company->name = $salesforceData['name'];
        } else {
        }
    }

    public function handleContactTrigger(Request $request)
    {
        $this->isAuthorized($request);

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_donors) return;

        $salesforceRequest = $salesforceIntegration->get("services/data/v54.0/sobjects/Contact/{$salesforceId}", null);


        if ($salesforceRequest->failed()) return;

        $model = $salesforceRequest->json();

        $donor = Donor::where('email_1',  $model['Email'])->where('organization_id', $organization->id)->first();

        if ($donor) {
            $donor->first_name = $model['FirstName'] ? $model['FirstName'] : $donor->first_name;
            $donor->last_name = $model['LastName'] ? $model['LastName'] : $donor->last_name;
            $donor->name = "{$donor->first_name} {$donor->last_name}";
            $donor->mobile_phone = $model['MobilePhone'] ? $model['MobilePhone'] : $donor->mobile_phone;
            $donor->office_phone = $model['Phone'] ? $model['Phone'] : $donor->office_phone;
            $donor->fax = $model['Fax'] ? $model['Fax'] : $donor->fax;
            $donor->home_phone = $model['HomePhone'] ? $model['HomePhone'] : $donor->home_phone;
            $donor->other_phone = $model['OtherPhone'] ? $model['OtherPhone'] : $donor->other_phone;
            $donor->email_1 = $model['Email'] ? $model['Email'] : $donor->email_1;
            $donor->salesforce_id = $salesforceId;
            $donor->salesforce_account_id = $model['AccountId'];
            $donor->saveQuietly();
        } else {
            $user = User::where('email', $model['Email'])->first();

            if ($user) {

                $donorLogins = $user->logins()->where('loginable_type', 'donor')->get();

                $donor = null;

                foreach ($donorLogins as $login) {
                    if ($login->loginable->organization_id == $this->salesforceIntegration->organization_id) {
                        $donor = $login->loginable;
                        break;
                    }
                }

                if (!$donor) {
                    $donor = new Donor();
                    $donor->email_1 = $model['Email'];
                    $donor->first_name = $model['FirstName'];
                    $donor->last_name = $model['LastName'];
                    $donor->mobile_phone = $model['MobilePhone'];
                    $donor->organization()->associate($organization);
                    $donor->salesforce_id = $salesforceId;
                    $donor->salesforce_account_id = $model['AccountId'];
                    $donor->saveQuietly();
                    $login = new Login();
                    $login->user()->associate($user);
                    $login->loginable()->associate($donor);
                    $login->saveQuietly();
                } else {
                    $donor->salesforce_id = $salesforceId;
                    $donor->salesforce_account_id = $model['AccountId'];
                    $donor->saveQuietly();
                }
            } else {
                $userData = ['first_name' => $model['FirstName'], 'last_name' => $model['LastName'], 'email' => $model['Email'], 'phone' => $model['MobilePhone'], 'password' => Hash::make(Str::random())];

                if (!$model['FirstName'] || !$model['LastName'] || !$model['Email']) return;

                try {
                    $user = User::create($userData);
                } catch (Exception $e) {
                    return;
                }

                if (!$user) return;

                $donor = new Donor();
                $donor->first_name = $model['FirstName'];
                $donor->last_name = $model['LastName'];
                $donor->email_1 = $model['Email'];
                $donor->mobile_phone = $model['MobilePhone'];
                $donor->organization_id = $this->salesforceIntegration->organization_id;
                $donor->salesforce_id = $salesforceId;
                $donor->salesforce_account_id = $model['AccountId'];


                $donor->saveQuietly();
                $login = new Login();
                $login->loginable()->associate($donor);
                $user->logins()->save($login);

                $user->currentLogin()->associate($donor);
                $user->saveQuietly();
            }
        }
    }

    public function handleOpportunityTrigger(Request $request)
    {
        abort_unless($this->isAuthorized($request), 401, 'Unauthorized');

        Bugsnag::leaveBreadcrumb('opp trigger', null, $request->all());
        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_donations) return;


        $transaction = Transaction::where('salesforce_id', $salesforceId)->where('destination_id', $organization->id)->where('destination_type', 'organization')->first();


        $salesforceData = $request->all();

        if ($transaction) {
            $transaction->status =  $salesforceData['StageName'] === 'Closed Won' ? Transaction::STATUS_SUCCESS : Transaction::STATUS_PENDING;
            $transaction->saveQuietly();
        } else {
            $transaction = new Transaction();
            $transaction->source()->associate($organization);
            $transaction->destination()->associate($organization);
            $owner = Donor::where('salesforce_id', $salesforceData['npsp__Primary_Contact__c'])->where('organization_id', $organization->id)->first();
            $transaction->description = "Donation added in SF";
            $transaction->owner()->associate($owner);
            $transaction->amount = $salesforceData['Amount'] * 100;
            $transaction->created_at = $salesforceData['CreatedDate'];
            $transaction->status =  $salesforceData['StageName'] === 'Closed Won' ? Transaction::STATUS_SUCCESS : Transaction::STATUS_PENDING;
            $transaction->salesforce_id = $salesforceId;
            $transaction->saveQuietly();
        }
    }


    public function handleCampaignTrigger(Request $request)
    {
        $this->isAuthorized($request);

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_campaigns) return;

        $campaign = Campaign::where('salesforce_id', $salesforceId)->where('organization_id', $organization->id)->first();
        $salesforceRequest = $organization->salesforceIntegration->get("services/data/v54.0/sobjects/Campaign/{$salesforceId}", null);



        if ($salesforceRequest->failed()) return;

        $salesforceData = $salesforceRequest->json();

        if ($campaign) {
            $campaign->name = $salesforceData['Name'];
            $campaign->saveQuietly();
        } else {
            $campaign = new Campaign();
            $campaign->organization()->associate($organization);
            $campaign->name = $salesforceData['Name'];
            $campaign->salesforce_id = $salesforceId;
            $campaign->saveQuietly();
        }
    }

    public function handleGAUTrigger(Request $request)
    {

        $this->isAuthorized($request);

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_designations) return;


        $fund = Fund::where('salesforce_id', $salesforceId)->where('organization_id', $organization->id)->first();
        $salesforceRequest = $organization->salesforceIntegration->get("services/data/v54.0/sobjects/npsp__General_Accounting_Unit__c/{$salesforceId}", null);


        if ($salesforceRequest->failed()) return;

        $salesforceData = $salesforceRequest->json();




        if ($fund) {
            $fund->name = $salesforceData['Name'];
            $fund->saveQuietly();
        } else {
            $fund = new Fund();
            $fund->organization()->associate($organization);
            $fund->salesforce_id = $salesforceId;
            $fund->name = $salesforceData['Name'];
            $fund->saveQuietly();
        }
    }

    public function handleGAUAllocationTrigger(Request $request)
    {

        return;

        // untested

        $this->isAuthorized($request);

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $salesforceIntegration = $organization->salesforceIntegration;
        if (!$salesforceIntegration->track_donations) return;


        $salesforceRequest = $organization->salesforceIntegration->get("services/data/v54.0/sobjects/npsp__Allocation__c/{$salesforceId}", null);

        if ($salesforceRequest->failed()) return;


        $salesforceData = $salesforceRequest->json();


        $transaction = Transaction::where('salesforce_id', $salesforceData['npsp__Opportunity__c'])->where('destination_type', 'organization')->where('destination_id', $organization->id)->firstOrFail();


        if ($transaction) {
            $transaction->salesforce_allocation_id = $salesforceId;
            $fund = Fund::where('salesforce_id', $salesforceData['npsp__General_Accounting_Unit__c'])->where('organization_id', $organization->id)->firstOrFail();
            if ($fund) {
                $transaction->fund()->associate($fund);
            }
            $transaction->saveQuietly();
        }
    }


    public function handlePaymentTrigger(Request $request)
    {

        // What if multiple payments ????
        $this->isAuthorized($request);


        return;

        $salesforceId = $request->all()['Id'];
        $organizationId = $request->headers->get('organization');
        $organization = Organization::find($organizationId);

        $opportunityId = $request->all()['npe01__Opportunity__c'];
    }
}
