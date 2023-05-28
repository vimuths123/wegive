<?php

namespace App\Processors;


use App\Models\Address;
use App\Models\Donor;
use App\Models\Login;
use App\Models\NeonIntegration;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncNeon
{


    protected $neonIntegration = null;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(NeonIntegration $neonIntegration)
    {
        $this->neonIntegration = $neonIntegration;
    }




    public function syncAccountsHandler($currentPage = 0)
    {
        $params = [
            "outputFields" => [
                "Account ID",
            ],
            "pagination" => [
                "currentPage" => $currentPage,
                "pageSize" => 200,
                "sortColumn" => "Account ID",
                "sortDirection" => "DESC"

            ],
            "searchFields" => [
                [
                    "field" => "Email 1",
                    "operator" => "NOT_BLANK",
                    "value" => "",
                    "valueList" => []
                ],
                [
                    "field" => "All Donation Count",
                    "operator" => "GREATER_THAN",
                    "value" => "0",
                    "valueList" => []
                ]
            ]

        ];

        $response = $this->neonIntegration->post('accounts/search', $params);

        $data = $response->json();

        foreach ($data['searchResults'] as $account) {

            $accountResponse = $this->neonIntegration->get("accounts/{$account['Account ID']}", []);

            $data = $accountResponse->json();

            $individualData = $data['individualAccount'];
            $companyData = $data['companyAccount'];

            if ($individualData) {
                $primaryContact = $individualData['primaryContact'];

                $user = User::where('email', $primaryContact['email1'])->first();

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
                        $individual = new Donor();
                        $individual->email_1 = $primaryContact['email1'];
                        $individual->email_2 = $primaryContact['email2'];
                        $individual->email_3 = $primaryContact['email3'];
                        $individual->first_name = $primaryContact['firstName'];
                        $individual->last_name = $primaryContact['lastName'];
                        $individual->organization()->associate($this->neonIntegration->organization);
                        $individual->created_at = $individualData['timestamps']['createdDateTime'];
                        $individual->neon_id = $primaryContact['contactId'];
                        $individual->neon_account_id = $individualData['accountId'];
                        $individual->save();

                        $login = new Login();
                        $login->user()->associate($user);
                        $login->loginable()->associate($individual);
                        $login->save();
                    }
                } else {
                    $userData = ['first_name' => $primaryContact['firstName'], 'last_name' => $primaryContact['lastName'], 'email' => $primaryContact['email1'], 'phone' => $primaryContact['addresses'][0]['phone1'] ?? null,  'password' => Hash::make(Str::random())];

                    if (!$userData['first_name'] || !$userData['last_name'] || !$userData['email']) continue;

                    try {
                        $user = User::create($userData);
                    } catch (Exception $e) {
                        continue;
                    }

                    if (!$user) continue;

                    $individual = new Donor();
                    $individual->first_name = $userData['first_name'];
                    $individual->last_name = $userData['last_name'];
                    $individual->email_1 = $userData['email'];
                    $individual->mobile_phone = $userData['phone'];
                    $individual->organization()->associate(Organization::find(env('WEGIVE_DONOR_PROFILE')));
                    $individual->save();

                    $login = new Login();
                    $login->loginable()->associate($individual);
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
                        $individual = new Donor();
                        $individual->first_name = $userData['first_name'];
                        $individual->last_name = $userData['last_name'];
                        $individual->email_1 = $userData['email'];
                        $individual->mobile_phone = $userData['phone'];
                        $individual->organization()->associate($this->neonIntegration->organization);
                        $individual->created_at = $individualData['timestamps']['createdDateTime'];

                        $individual->neon_id = $primaryContact['contactId'];
                        $individual->neon_account_id = $individualData['accountId'];
                        $individual->save();
                        $login = new Login();
                        $login->loginable()->associate($individual);
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

                    if (!$userData['first_name'] || !$userData['last_name'] || !$userData['email']) continue;

                    try {
                        $user = User::create($userData);
                    } catch (Exception $e) {
                        continue;
                    }

                    if (!$user) continue;

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

        if ($data['pagination'] && $data['pagination']['currentPage'] < $data['pagination']['totalPages']) {
            $this->syncAccountsHandler($currentPage + 1);
        } else {
            return;
        }
    }

    public function syncAccount($accountId)
    {

        $accountResponse = $this->neonIntegration->get("accounts/{$accountId}", []);

        $data = $accountResponse->json();

        $individualData = $data['individualAccount'];
        $companyData = $data['companyAccount'];

        if ($individualData) {
            $primaryContact = $individualData['primaryContact'];

            $user = User::whereNotNull('email')->where('email', $primaryContact['email1'])->first();

            if ($user) {
                $logins = $user->logins()->where('loginable_type', 'individual')->get();

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
                    $individual = new Donor();
                    $individual->email_1 = $primaryContact['email1'];
                    $individual->email_2 = $primaryContact['email2'];
                    $individual->email_3 = $primaryContact['email3'];
                    $individual->first_name = $primaryContact['firstName'];
                    $individual->last_name = $primaryContact['lastName'];
                    $individual->organization()->associate($this->neonIntegration->organization);
                    $individual->created_at = $individualData['timestamps']['createdDateTime'];
                    $individual->neon_id = $primaryContact['contactId'];
                    $individual->neon_account_id = $individualData['accountId'];
                    $individual->save();

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

                $individual = new Donor();
                $individual->first_name = $userData['first_name'];
                $individual->last_name = $userData['last_name'];
                $individual->email_1 = $userData['email'];
                $individual->mobile_phone = $userData['phone'];
                $individual->organization()->associate(Organization::find(env('WEGIVE_DONOR_PROFILE')));
                $individual->save();

                $login = new Login();
                $login->loginable()->associate($individual);
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
                    $individual = new Donor();
                    $individual->first_name = $userData['first_name'];
                    $individual->last_name = $userData['last_name'];
                    $individual->email_1 = $userData['email'];
                    $individual->mobile_phone = $userData['phone'];
                    $individual->organization()->associate($this->neonIntegration->organization);
                    $individual->created_at = $individualData['timestamps']['createdDateTime'];

                    $individual->neon_id = $primaryContact['contactId'];
                    $individual->neon_account_id = $individualData['accountId'];
                    $individual->save();
                    $login = new Login();
                    $login->loginable()->associate($individual);
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


    public function syncDonationsHandler($currentPage = 0)
    {
        $params = [
            "outputFields" => ["Donation Amount", "Donation ID", "Payment ID",  "Account ID", "Donation Created Date"],
            "pagination" => [
                "currentPage" => $currentPage,
                "pageSize" => 200,
                "sortColumn" => "Donation ID",
                "sortDirection" => "DESC"

            ],
            "searchFields" => [
                [
                    "field" => "Donation Amount",
                    "operator" => "NOT_BLANK",
                    "value" => "",
                    "valueList" => []
                ]
            ]

        ];
        $response = $this->neonIntegration->post('donations/search', $params);

        $data = $response->json();

        $missingAccounts = [];

        foreach ($data['searchResults'] as $donation) {
            $transaction = new Transaction();
            $transaction->amount = $donation['Donation Amount'] * 100;

            $this->syncAccount($donation['Account ID']);

            $owner = null;

            $owner = Donor::where('neon_account_id', $donation['Account ID'])->first();

            if (!$owner) {
                $missingAccounts[] = $donation["Account ID"];
                continue;
            }

            $transaction->owner()->associate($owner);
            $transaction->source()->associate($this->neonIntegration->organization);
            $transaction->destination()->associate($this->neonIntegration->organization);
            $transaction->neon_id = $donation['Donation ID'];
            $transaction->neon_payment_id = $donation['Payment ID'];
            $transaction->created_at = $donation["Donation Created Date"];
            $transaction->description = 'Neon Import';

            $transaction->save();
        }

        if ($data['pagination']['totalPages'] < $currentPage) {
            $this->syncDonationsHandler($currentPage + 1);
        } else {
        }

        dump(array_unique($missingAccounts));
    }
}
