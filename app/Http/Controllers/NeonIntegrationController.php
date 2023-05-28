<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Donor;
use App\Models\Fundraiser;
use App\Models\Login;
use App\Models\NeonIntegration;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NeonIntegrationController extends Controller
{
    public function isAuthorized(Request $request)
    {

        $data = $request->all();

        $apiKey = $data['customParameters']['api_key'];

        $organization = Organization::findOrFail($data['customParameters']['organization']);

        $token =  \Laravel\Sanctum\PersonalAccessToken::findToken($apiKey);

        return $token->tokenable()->is($organization);
    }

    public function donorCreated(Request $request)
    {
        return;

        Mail::raw($request->getContent(), function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Webhook: Donor Created');
        });

        abort_unless($this->isAuthorized($request), 401, 'Unauthenticated');

        $data = $request->all()['data'];

        $individualData = $data['individualAccount'];
        $companyData = $data['companyAccount'];

        if ($individualData) {
            $primaryContact = $individualData['primaryContact'];

            $user = User::whereNotNull('email')->where('email', $primaryContact['email1'])->first();

            if ($user) {
                $logins = $user->logins()->where('loginable_type', 'donor')->get();

                $donor = null;

                foreach ($logins as $login) {
                    if ($login->loginable->organization_id === $this->neonIntegration->organization_id) {
                        $donor = $login->loginable;
                        break;
                    }
                }

                if ($donor) {
                    $donor->neon_id = $primaryContact['contactId'];
                    $donor->neon_account_id = $individualData['accountId'];
                    $donor->save();
                } else {
                    $donor = new \App\Models\Donor();
                    $donor->email_1 = $primaryContact['email1'];
                    $donor->email_2 = $primaryContact['email2'];
                    $donor->email_3 = $primaryContact['email3'];
                    $donor->first_name = $primaryContact['firstName'];
                    $donor->last_name = $primaryContact['lastName'];
                    $donor->organization()->associate($this->neonIntegration->organization);
                    $donor->created_at = $individualData['timestamps']['createdDateTime'];
                    $donor->neon_id = $primaryContact['contactId'];
                    $donor->neon_account_id = $individualData['accountId'];
                    $donor->save();

                    $login = new Login();
                    $login->user()->associate($user);
                    $login->loginable()->associate($individual);
                    $login->save();
                }
            } else {
                $userData = ['first_name' => $primaryContact['firstName'], 'last_name' => $primaryContact['lastName'], 'email' => $primaryContact['email1'], 'phone' => $primaryContact['addresses'][0]['phone1'] ?? null,  'password' => Hash::make(Str::random())];

                if (!$userData['first_name'] || !$userData['last_name'] || !$userData['email']) {
                    return;
                };

                try {
                    $user = User::create($userData);
                } catch (Exception $e) {
                    return;
                }

                if (!$user) {
                    return;
                };

                $donor = new Donor();
                $donor->first_name = $userData['first_name'];
                $donor->last_name = $userData['last_name'];
                $donor->email_1 = $userData['email'];
                $donor->mobile_phone = $userData['phone'];
                $donor->organization()->associate(Organization::find(env('WEGIVE_DONOR_PROFILE')));
                $donor->save();

                $login = new Login();
                $login->loginable()->associate($donor);
                $user->logins()->save($login);


                foreach ($primaryContact['addresses'] as $address) {
                    try {
                        $address = new Address();
                        $address->address_1 = $address['addressLine1'];
                        $address->address_2 = $address['addressLine2'];
                        $address->city = $address['city'];
                        $address->state = $address['state'];
                        $address->zip = $address['zip'];
                        $address->type = $address['type']['name'] === 'mailing' ? 'mailing' : 'billing';
                        $address->primary = $address['isPrimaryAddress'];
                        $address->neon_id = $address['addressId'];
                        $address->addressable()->associate($individual);
                        $address->save();
                    } catch (Exception $e) {
                    }
                }



                $orgId = $this->neonIntegration->organization_id;


                if ($orgId !== "null" && $orgId && $orgId !== 'undefined') {
                    $donor = new Donor();
                    $donor->first_name = $userData['first_name'];
                    $donor->last_name = $userData['last_name'];
                    $donor->email_1 = $userData['email'];
                    $donor->mobile_phone = $userData['phone'];
                    $donor->organization()->associate($this->neonIntegration->organization);
                    $donor->created_at = $individualData['timestamps']['createdDateTime'];

                    $donor->neon_id = $primaryContact['contactId'];
                    $donor->neon_account_id = $individualData['accountId'];
                    $donor->save();
                    $login = new Login();
                    $login->loginable()->associate($donor);
                    $user->logins()->save($login);

                    foreach ($primaryContact['addresses'] as $address) {

                        try {
                            $address = new Address();
                            $address->address_1 = $address['addressLine1'];
                            $address->address_2 = $address['addressLine2'];
                            $address->city = $address['city'];
                            $address->state = $address['state'];
                            $address->zip = $address['zip'];
                            $address->type = $address['type']['name'] === 'mailing' ? 'mailing' : 'billing';
                            $address->primary = $address['isPrimaryAddress'];
                            $address->neon_id = $address['addressId'];
                            $address->addressable()->associate($individual);
                            $address->save();
                        } catch (Exception $e) {
                        }
                    }
                }



                $user->currentLogin()->associate($individual);
                $user->save();
            }
        } else if ($companyData) {
            $primaryContact = $companyData['primaryContact'];

            $user = User::where('email', $primaryContact['email1'])->first();

            if ($user) {
                $logins = $user->logins()->where('loginable_type', 'company')->get();

                $donor = null;

                foreach ($logins as $login) {
                    if ($login->loginable->organization_id === $this->neonIntegration->organization_id) {
                        $donor = $login->loginable;
                        break;
                    }
                }

                if ($donor) {
                    $donor->neon_id = $primaryContact['contactId'];
                    $donor->neon_account_id = $companyData['accountId'];
                    $donor->save();
                } else {
                    $company = new Donor();
                    $company->type = 'company';
                    $company->email_1 = $primaryContact['email1'];
                    $company->email_2 = $primaryContact['email2'];
                    $company->email_3 = $primaryContact['email3'];
                    $company->name = $companyData["name"];
                    $company->organization()->associate($this->neonIntegration->organization);
                    $company->created_at = $individualData['timestamps']['createdDateTime'];

                    $company->neon_id = $primaryContact['contactId'];
                    $company->neon_account_id = $companyData['accountId'];
                    $company->save();

                    $login = new Login();
                    $login->user()->associate($user);
                    $login->loginable()->associate($company);
                    $login->save();
                }
            } else {
                $userData = ['first_name' => $primaryContact['firstName'], 'last_name' => $primaryContact['lastName'], 'email' => $primaryContact['email1'], 'phone' => $primaryContact['addresses'][0]['phone1'] ?? null,  'password' => Hash::make(Str::random())];

                if (!$userData['first_name'] || !$userData['last_name'] || !$userData['email']) {
                    return;
                };

                try {
                    $user = User::create($userData);
                } catch (Exception $e) {
                    return;
                }

                if (!$user) {
                    return;
                };

                $company = new Donor();
                $company->type = 'company';
                $company->name = $companyData["name"];
                $company->email_1 = $userData['email'];
                $company->email_2 = $primaryContact['email2'];
                $company->email_3 = $primaryContact['email3'];
                $company->mobile_phone = $userData['phone'];
                $company->organization()->associate(Organization::find(env('WEGIVE_DONOR_PROFILE')));
                $company->save();

                $login = new Login();
                $login->loginable()->associate($company);
                $user->logins()->save($login);


                foreach ($primaryContact['addresses'] as $address) {
                    try {
                        $address = new Address();
                        $address->address_1 = $address['addressLine1'];
                        $address->address_2 = $address['addressLine2'];
                        $address->city = $address['city'];
                        $address->state = $address['state'];
                        $address->zip = $address['zip'];
                        $address->type = $address['type']['name'] === 'mailing' ? 'mailing' : 'billing';
                        $address->primary = $address['isPrimaryAddress'];
                        $address->neon_id = $address['addressId'];
                        $address->addressable()->associate($company);
                        $address->save();
                    } catch (Exception $e) {
                    }
                }



                $orgId = $this->neonIntegration->organization_id;


                if ($orgId !== "null" && $orgId && $orgId !== 'undefined') {
                    $company = new Donor();
                    $company->type = 'company';
                    $company->name = $companyData["name"];
                    $company->email_1 = $userData['email'];
                    $company->email_2 = $primaryContact['email2'];
                    $company->email_3 = $primaryContact['email3'];
                    $company->mobile_phone = $userData['phone'];
                    $company->organization()->associate($this->neonIntegration->organization);
                    $company->created_at = $individualData['timestamps']['createdDateTime'];
                    $company->neon_id = $primaryContact['contactId'];
                    $company->neon_account_id = $companyData['accountId'];
                    $company->save();
                    $login = new Login();
                    $login->loginable()->associate($company);
                    $user->logins()->save($login);

                    foreach ($primaryContact['addresses'] as $address) {

                        try {
                            $address = new Address();
                            $address->address_1 = $address['addressLine1'];
                            $address->address_2 = $address['addressLine2'];
                            $address->city = $address['city'];
                            $address->state = $address['state'];
                            $address->zip = $address['zip'];
                            $address->type = $address['type']['name'] === 'mailing' ? 'mailing' : 'billing';
                            $address->primary = $address['isPrimaryAddress'];
                            $address->neon_id = $address['addressId'];
                            $address->addressable()->associate($company);
                            $address->save();
                        } catch (Exception $e) {
                        }
                    }
                }



                $user->currentLogin()->associate($company);
                $user->save();
            }
        }
    }

    public function donorUpdated(Request $request)
    {
        return;

        Mail::raw($request->getContent(), function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Webhook: Donor Updated');
        });

        $organization = Organization::findOrFail($data['customParameters']['organization']);


        abort_unless($this->isAuthorized($request), 401, 'Unauthenticated');

        return;

        $data = $request->all()['data'];

        if (array_key_exists('individualAccount ', $data)) {

            $individual = Individual::whereNotNull('neon_account_id')->where('neon_account_id', $data['individualAccount']['accountId'])->where('organization_id', $organization->id)->firstOrFail();
            $primaryContact = $data['individualAccount']['primaryContact'];
            $individual->email_1 = $primaryContact['email1'];
            $individual->email_2 = $primaryContact['email2'];
            $individual->email_3 = $primaryContact['email3'];
            $individual->first_name = $primaryContact['firstName'];
            $individual->last_name = $primaryContact['lastName'];
            $individual->save();
        } else if (array_key_exists('companyAccount ', $data)) {
            $company = Company::whereNotNull('neon_account_id')->where('neon_account_id', $data['individualAccount']['accountId'])->firstOrFail();
        }
    }

    public function donationCreated(Request $request)
    {
        return;

        Mail::raw($request->getContent(), function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Webhook: Donation created');
        });

        abort_unless($this->isAuthorized($request), 401, 'Unauthenticated');

        $data = $request->all()['data'];

        $transaction = new Transaction();
        $transaction->owner->associate(Individual::where('neon_acount_id', $data['accountId'])->first()->donor);
        $transaction->amount = $data['amount'];
        $transaction->cover_fees = $data['donorCoveredFeeFlag'];
        $transaction->fee_amount = $data['donorCoveredFee'];
        $transaction->direct_depost = true;
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->destination->associate(Organization::find($request->organization));

        $source = null;
        switch ($data['payments'][0]['tenderType']) {
            case NeonIntegration::CASH:
            case NeonIntegration::CREDIT_CARD_OFFLINE:
            case NeonIntegration::CHECK:
            case NeonIntegration::CREDIT_CARD_ONLINE:
            case NeonIntegration::IN_KIND:
            case NeonIntegration::WIRE:
            case NeonIntegration::PAYPAL:
            case NeonIntegration::ACH:
            case NeonIntegration::OTHER:
            default:
                $source = Organization::find($request->organization);
        }

        $transaction->source->associate($source);



        $fundraiser = Fundraiser::where('neon_id', $data['campaign']['id'])->first();
        $transaction->fundraiser->associate($fundraiser);
        $transaction->neon_id = $data['id'];
        $transaction->save();
    }

    public function donationUpdated(Request $request)
    {

        return;

        Mail::raw($request->getContent(), function ($message) {
            $message->to('Charlie@givelistapp.com')
                ->subject('Webhook: Donation Updated');
        });

        abort_unless($this->isAuthorized($request), 401, 'Unauthenticated');

        return;

        $data = $request->all()['data'];

        $transaction = Transaction::whereNotNull('neon_id')->where('neon_id', $data['id'])->firstOrFail();
        if (!$transaction) $transaction = new Transaction();
        $transaction->owner->associate(Individual::where('neon_acount_id', $data['accountId'])->first()->donor);
        $transaction->amount = $data['amount'];
        $transaction->cover_fees = $data['donorCoveredFeeFlag'];
        $transaction->fee_amount = $data['donorCoveredFee'];
        $transaction->direct_depost = true;
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->destination->associate(Organization::find($request->organization));

        $source = null;
        switch ($data['payments'][0]['tenderType']) {
            case NeonIntegration::CASH:
            case NeonIntegration::CREDIT_CARD_OFFLINE:
            case NeonIntegration::CHECK:
            case NeonIntegration::CREDIT_CARD_ONLINE:
            case NeonIntegration::IN_KIND:
            case NeonIntegration::WIRE:
            case NeonIntegration::PAYPAL:
            case NeonIntegration::ACH:
            case NeonIntegration::OTHER:
            default:
                $source = Organization::find($request->organization);
        }

        $transaction->source->associate($source);



        $fundraiser = Fundraiser::where('neon_id', $data['campaign']['id'])->first();
        $transaction->fundraiser->associate($fundraiser);
        $transaction->neon_id = $data['id'];
        $transaction->save();
    }
}
