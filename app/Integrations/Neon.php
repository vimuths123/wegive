<?php

namespace App\Integrations;

use App\Models\User;
use App\Models\Donor;
use App\Models\Address;
use App\Actions\Intercom;
use App\Models\Fundraiser;
use App\Models\Transaction;
use App\Models\Organization;
use App\Models\ScheduledDonation;
use Illuminate\Support\Facades\Http;

class Neon
{
    public $base = 'https://api.neoncrm.com/v2/';
    public $organization = null;
    public $authString = null;

    public function __construct($organization = null)
    {
        if (!$organization || !$organization->neonIntegration) {
            return;
        }

        $this->organization = $organization;

        $this->authString = base64_encode("{$this->organization->neonIntegration->neon_id}:{$this->organization->neonIntegration->neon_api_key}");

        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $this->base = 'https://trial.z2systems.com/v2/';
        }
    }

    public function get($route, $params)
    {
        return Http::withHeaders(["Authorization" => "Basic {$this->authString}"])->get($this->base . $route, $params);
    }

    public function post($route, $params)
    {
        return Http::withHeaders(["Authorization" => "Basic {$this->authString}"])->post($this->base . $route, $params);
    }


    public function put($route, $params)
    {
        return Http::withHeaders(["Authorization" => "Basic {$this->authString}"])->put($this->base . $route, $params);
    }

    public function generateAccountParams($donorProfile)
    {


        return [
            "{$donorProfile->getMorphClass()}Account" => [
                // "accountCustomFields" => [
                //     [
                //         "id" => "1234",
                //         "name" => "Example custom field name",
                //         "optionValues" => [
                //             [
                //                 "id" => "1234",
                //                 "name" => "Example",
                //                 "status" => "ACTIVE"
                //             ]
                //         ],
                //         "status" => "ACTIVE",
                //         "value" => "string"
                //     ]
                // ],
                "accountId" => $donorProfile->neon_account_id,
                // "company" => [
                //     "id" => "1234",
                //     "name" => "Example",
                //     "status" => "ACTIVE"
                // ],
                "consent" => [
                    "dataSharing" => "GIVEN",
                    "email" => $donorProfile->email_notifications,
                    "mail" => "GIVEN",
                    "phone" => "GIVEN",
                    "sms" => $donorProfile->sms_notifications
                ],
                "facebookPage" => $donorProfile->facebook_link,
                // "individualTypes" => [
                //     [
                //         "id" => "1234",
                //         "name" => "Example",
                //         "status" => "ACTIVE"
                //     ]
                // ],
                "login" => [
                    "username" => $donorProfile->name,
                    "password" => 'WeGiveTest1234'
                ],
                "noSolicitation" => false,
                "origin" => [
                    "originDetail" => "WeGive"
                ],
                "primaryContact" => [
                    // "addresses" => [
                    //     [
                    //         "addressLine1" => "4545 Star Road",
                    //         "addressLine2" => "Apt. 123",
                    //         "addressLine3" => "",
                    //         "addressLine4" => "",
                    //         "city" => "Chicago",
                    //         "country" => [
                    //             "id" => "1234",
                    //             "name" => "Example",
                    //             "status" => "ACTIVE"
                    //         ],
                    //         "county" => "Cook County",
                    //         "endDate" => "2021-01-20",
                    //         "fax" => "+1 (555) 555-555",
                    //         "faxType" => "Home",
                    //         "isPrimaryAddress" => true,
                    //         "phone1" => "+1 (555) 555-555",
                    //         "phone1Type" => "Home",
                    //         "phone2" => "+1 (555) 555-555",
                    //         "phone2Type" => "Work",
                    //         "phone3" => "+1 (555) 555-555",
                    //         "phone3Type" => "Mobile",
                    //         "startDate" => "2021-01-20",
                    //         "stateProvince" => [
                    //             "code" => "CODE",
                    //             "name" => "Name",
                    //             "status" => "ACTIVE"
                    //         ],
                    //         "territory" => "",
                    //         "type" => [
                    //             "id" => "1234",
                    //             "name" => "Example",
                    //             "status" => "ACTIVE"
                    //         ],
                    //         "zipCode" => "60614",
                    //         "zipCodeSuffix" => ""
                    //     ]
                    // ],
                    "contactId" => $donorProfile->neon_id,
                    // "currentEmployer" => null,
                    // "deceased" => null,
                    // "department" => null,
                    // "dob" => [
                    //     "day" => null,
                    //     "month" => null,
                    //     "year" => null
                    // ],
                    "email1" => $donorProfile->email_1,
                    "email2" => $donorProfile->email_2,
                    "email3" => $donorProfile->email_3,
                    "endDate" => null,
                    "firstName" => $donorProfile->first_name,
                    // "gender" => [
                    //     "code" => "CODE",
                    //     "name" => "Name",
                    //     "status" => "ACTIVE"
                    // ],
                    "lastName" => $donorProfile->last_name,
                    // "middleName" => null,
                    // "preferredName" => null,
                    // "prefix" => null,
                    // "primaryContact" => true,
                    // "salutation" => null,
                    // "startDate" => null,
                    // "suffix" => null,
                    // "title" => null
                ],
                // "sendSystemEmail" => null,
                // "source" => [
                //     "id" => null,
                //     "name" => null,
                //     "status" => null
                // ],
                // "timestamps" => [
                //     "createdBy" => null,
                //     "createdDateTime" => null,
                //     "lastModifiedBy" => null,
                //     "lastModifiedDateTime" => null
                // ],
                "twitterPage" => $donorProfile->twitter_link,
            ]

        ];
    }

    public function generateDonationParams(Transaction $transaction)
    {
        $donorProfile = $transaction->owner;
        return  [
            "id" => $transaction->neon_id,
            "accountId" => $donorProfile->neon_account_id,
            "donorName" => $donorProfile->name,
            "amount" => $transaction->amount / 100,
            "date" => $transaction->created_at,
            // "campaign" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "fund" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "purpose" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "source" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "fundraiserAccountId" => "1234",
            // "solicitationMethod" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "tribute" => [
            //     "name" => "string",
            //     "type" => "Honor"
            // ],
            // "acknowledgee" => [
            //     "accountId" => "1234",
            //     "address" => [
            //         "addressLine1" => "4545 Star Road",
            //         "addressLine2" => "Apt. 123",
            //         "addressLine3" => "",
            //         "addressLine4" => "",
            //         "city" => "Chicago",
            //         "country" => [
            //             "id" => "1234",
            //             "name" => "Example",
            //             "status" => "ACTIVE"
            //         ],
            //         "county" => "Cook County",
            //         "endDate" => "2021-01-20",
            //         "fax" => "+1 (555) 555-555",
            //         "faxType" => "Home",
            //         "isPrimaryAddress" => true,
            //         "phone1" => "+1 (555) 555-555",
            //         "phone1Type" => "Home",
            //         "phone2" => "+1 (555) 555-555",
            //         "phone2Type" => "Work",
            //         "phone3" => "+1 (555) 555-555",
            //         "phone3Type" => "Mobile",
            //         "startDate" => "2021-01-20",
            //         "stateProvince" => [
            //             "code" => "CODE",
            //             "name" => "Name",
            //             "status" => "ACTIVE"
            //         ],
            //         "territory" => "",
            //         "type" => [
            //             "id" => "1234",
            //             "name" => "Example",
            //             "status" => "ACTIVE"
            //         ],
            //         "zipCode" => "60614",
            //         "zipCodeSuffix" => ""
            //     ],
            //     "email" => "jo@example.com",
            //     "name" => "Jo Person"
            // ],
            "anonymousType" => $transaction->anonymous,
            // "craInfo" => [
            //     "advantageAmount" => 12345,
            //     "advantageDescription" => "string"
            // ],
            "donorCoveredFee" => ($transaction->cover_fees ? $transaction->fee_amount : 0) / 100,
            "sendAcknowledgeEmail" => true,
            // "timestamps" => [
            //     "createdBy" => "string",
            //     "createdDateTime" => "2021-01-20 12:00:00",
            //     "lastModifiedBy" => "string",
            //     "lastModifiedDateTime" => "2021-01-20 12:00:00"
            // ],
            // "donationCustomFields" => [
            //     [
            //         "id" => "1234",
            //         "name" => "Example custom field name",
            //         "optionValues" => [
            //             [
            //                 "id" => "1234",
            //                 "name" => "Example",
            //                 "status" => "ACTIVE"
            //             ]
            //         ],
            //         "status" => "ACTIVE",
            //         "value" => "string"
            //     ]
            // ],
            "payments" => [
                [
                    "id" => $transaction->neon_payment_id,
                    // "ach" => [
                    //     "accountType" => "Checking",
                    //     "checkNumber" => "string",
                    //     "id" => 0,
                    //     "plaidAccountId" => "string",
                    //     "token" => "string"
                    // ],
                    "amount" => $transaction->amount / 100,
                    // "check" => [
                    //     "accountNumber" => "1234567890",
                    //     "accountOwner" => "Jo Person",
                    //     "accountType" => "Checking",
                    //     "checkNumber" => 123,
                    //     "institution" => "Agloe Federal Credit Union",
                    //     "routingNumber" => "123456"
                    // ],
                    // "creditCardOffline" => [
                    //     "billingAddress" => [
                    //         "addressLine1" => "4545 Star Road",
                    //         "addressLine2" => "Apt. 123",
                    //         "addressLine3" => "",
                    //         "addressLine4" => "",
                    //         "city" => "Chicago",
                    //         "countryId" => "string",
                    //         "stateProvinceCode" => "string",
                    //         "territory" => "",
                    //         "zipCode" => "60614",
                    //         "zipCodeSuffix" => ""
                    //     ],
                    //     "cardHolderEmail" => "string",
                    //     "cardHolderName" => "string",
                    //     "cardNumberLastFour" => "string",
                    //     "cardTypeCode" => "string",
                    //     "expirationMonth" => 0,
                    //     "expirationYear" => 0
                    // ],
                    // "creditCardOnline" => [
                    //     "billingAddress" => [
                    //         "addressLine1" => "4545 Star Road",
                    //         "addressLine2" => "Apt. 123",
                    //         "addressLine3" => "",
                    //         "addressLine4" => "",
                    //         "city" => "Chicago",
                    //         "countryId" => "string",
                    //         "stateProvinceCode" => "string",
                    //         "territory" => "",
                    //         "zipCode" => "60614",
                    //         "zipCodeSuffix" => ""
                    //     ],
                    //     "cardHolderEmail" => "string",
                    //     "id" => 0,
                    //     "token" => "string"
                    // ],
                    // "inKind" => [
                    //     "fairMarketValue" => 12345,
                    //     "nccDescription" => "string"
                    // ],
                    // "note" => "string",
                    // "receivedDate" => "2021-01-20 12:00:00",
                    "tenderType" => 1
                ]
            ]
        ];
    }

    public function generateRecurringDonationParams(ScheduledDonation $scheduledDonation)
    {

        $donorProfile = $scheduledDonation->source;

        return [
            "accountId" => $donorProfile->neon_account_id,
            "amount" => $scheduledDonation->chargeAmount,
            // "campaign" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "endDate" => "2021-01-20 12:00:00",
            // "fund" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "id" => "string",
            "nextDate" => $scheduledDonation->start_date,
            "payment" => [
                // "id" => "1234",
                // "ach" => [
                //     "accountType" => "Checking",
                //     "checkNumber" => "string",
                //     "id" => 0,
                //     "plaidAccountId" => "string",
                //     "token" => "string"
                // ],
                "amount" => $scheduledDonation->chargeAmount,
                // "check" => [
                //     "accountNumber" => "1234567890",
                //     "accountOwner" => "Jo Person",
                //     "accountType" => "Checking",
                //     "checkNumber" => 123,
                //     "institution" => "Agloe Federal Credit Union",
                //     "routingNumber" => "123456"
                // ],
                // "creditCardOffline" => [
                //     "billingAddress" => [
                //         "addressLine1" => "4545 Star Road",
                //         "addressLine2" => "Apt. 123",
                //         "addressLine3" => "",
                //         "addressLine4" => "",
                //         "city" => "Chicago",
                //         "countryId" => "string",
                //         "stateProvinceCode" => "string",
                //         "territory" => "",
                //         "zipCode" => "60614",
                //         "zipCodeSuffix" => ""
                //     ],
                //     "cardHolderEmail" => "string",
                //     "cardHolderName" => "string",
                //     "cardNumberLastFour" => "string",
                //     "cardTypeCode" => "string",
                //     "expirationMonth" => 0,
                //     "expirationYear" => 0
                // ],
                // "creditCardOnline" => [
                //     "billingAddress" => [
                //         "addressLine1" => "4545 Star Road",
                //         "addressLine2" => "Apt. 123",
                //         "addressLine3" => "",
                //         "addressLine4" => "",
                //         "city" => "Chicago",
                //         "countryId" => "string",
                //         "stateProvinceCode" => "string",
                //         "territory" => "",
                //         "zipCode" => "60614",
                //         "zipCodeSuffix" => ""
                //     ],
                //     "cardHolderEmail" => "string",
                //     "id" => 0,
                //     "token" => "string"
                // ],
                // "inKind" => [
                //     "fairMarketValue" => 12345,
                //     "nccDescription" => "string"
                // ],
                // "note" => "string",
                // "receivedDate" => "2021-01-20 12:00:00",
                "tenderType" => 1
            ],
            // "purpose" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            "recurringPeriod" => $scheduledDonation->iteration,
            "recurringPeriodType" => "LIFE",
            // "timestamps" => [
            //     "createdBy" => "string",
            //     "createdDateTime" => "2021-01-20 12:00:00",
            //     "lastModifiedBy" => "string",
            //     "lastModifiedDateTime" => "2021-01-20 12:00:00"
            // ]
        ];
    }

    public function generateCampaignParams(Fundraiser $fundraiser)
    {

        return [
            "campaignPageUrl" => "https://app.wegive.com/g/{$this->organization->slug}/fundraiser/{$fundraiser->id}",
            // "code" => "TREES",
            // "craInfo" => [
            //     "advantageAmount" => 12345,
            //     "advantageDescription" => "string"
            // ],
            "donationFormUrl" => "https://app.wegive.com/g/{$this->organization->slug}/fundraiser/{$fundraiser->id}/give",
            "endDate" => $fundraiser->expiration,
            // "fund" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            "goal" => $fundraiser->goal,
            "id" => $fundraiser->neon_id,
            "isDefault" => true,
            "name" => $fundraiser->name,
            // "pageContent" => "<html></html>",
            // "parentCampaign" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "purpose" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "socialFundraising" => [
            //     "createFundraiserUrl" => "string",
            //     "enabled" => true,
            //     "fundraiserListUrl" => "string",
            //     "fundraisersCount" => 0,
            //     "fundraisingPageContent" => "string",
            //     "statistics" => [
            //         "donationAmount" => 12345,
            //         "donationCount" => 12345,
            //         "eventRegistrationAmount" => 12345,
            //         "eventRegistrationCount" => 12345,
            //         "grandTotal" => 12345,
            //         "pledgeAmount" => 12345,
            //         "pledgeCount" => 12345
            //     ]
            // ],
            "startDate" => $fundraiser->start_date,
            "statistics" => [
                "donationAmount" => 12345,
                "donationCount" => 12345,
                "eventRegistrationAmount" => 12345,
                "eventRegistrationCount" => 12345,
                "grandTotal" => 12345,
                "pledgeAmount" => 12345,
                "pledgeCount" => 12345
            ],
            "status" => "ACTIVE"
        ];
    }

    public function generateAddressParams(Address $address)
    {
        $donorProfile = $address->addressable;

        return [
            "accountId" => $donorProfile->neon_account_id,
            "addressLine1" => $address->address_1,
            "addressLine2" => $address->address_2,
            // "addressLine3" => "",
            // "addressLine4" => "",
            "city" => $address->city,
            // "country" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            // "county" => null,
            // "endDate" => null,
            "fax" => $donorProfile->fax,
            "faxType" => "Home",
            "isPrimaryAddress" => $address->primary,
            "phone1" => $donorProfile->mobile_phone,
            "phone1Type" => "Mobile",
            "phone2" => $donorProfile->office_phone,
            "phone2Type" => "Work",
            "phone3" => $donorProfile->home_phone,
            "phone3Type" => "Home",
            // "startDate" => null,
            // "stateProvince" => [
            //     "code" => "CODE",
            //     "name" => "Name",
            //     "status" => "ACTIVE"
            // ],
            // "territory" => "",
            // "type" => [
            //     "id" => "1234",
            //     "name" => "Example",
            //     "status" => "ACTIVE"
            // ],
            "zipCode" => $address->zip,
            // "zipCodeSuffix" => null
        ];
    }

    public function createRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        return $this->post('recurring', $this->generateRecurringDonationParams($scheduledDonation));
    }

    public function updateRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        return $this->put("recurring/{$scheduledDonation->neon_id}", $this->generateRecurringDonationParams($scheduledDonation));
    }


    public function createDonation(Transaction $transaction)
    {
        return $this->post('donations', $this->generateDonationParams($transaction));
    }

    public function updateDonation(Transaction $transaction)
    {
        return $this->put("donations/{$transaction->neon_id}", $this->generateDonationParams($transaction));
    }

    public function createCampaign(Fundraiser $fundraiser)
    {
        return $this->post('campaigns', $this->generateCampaignParams($fundraiser));
    }

    public function updateCampaign(Fundraiser $fundraiser)
    {
        return $this->put("campaigns/{$fundraiser->neon_id}", $this->generateCampaignParams($fundraiser));
    }


    public function createAddress(Address $address)
    {
        return $this->post('addresses', $this->generateAddressParams($address));
    }

    public function updateAddress(Address $address)
    {
        return $this->put("addresses/{$address->neon_id}", $this->generateAddressParams($address));
    }

    public function createDonor($donorProfile)
    {
        return $this->post('accounts', $this->generateAccountParams($donorProfile));
    }

    public function updateDonor($donorProfile)
    {

        return $this->put("accounts/{$donorProfile->neon_account_id}", $this->generateAccountParams($donorProfile));
    }


    public function syncDonor($donorProfile)
    {

        if ($donorProfile->neon_id && $donorProfile->neon_account_id) {
            $response = $this->updateDonor($donorProfile);
        } else {
            $response = $this->createDonor($donorProfile);

            if ($response->successful()) {
                $accountId = $response->json()['id'];

                $account = $this->get("accounts/{$accountId}", []);

                $contactId = $account->json()["{$donorProfile->getMorphClass()}Account"]['primaryContact']['contactId'];

                $donorProfile->neon_id = intval($contactId);
                $donorProfile->neon_account_id = intval($accountId);

                $donorProfile->save();
            }
        }


        return $response;
    }

    public function syncFundraiser(Fundraiser $fundraiser)
    {

        if ($fundraiser->neon_id) {
            $response = $this->updateCampaign($fundraiser);
        } else {
            $response = $this->createCampaign($fundraiser);

            if ($response->successful()) {
                $campaignId = $response->json()['id'];

                $fundraiser->neon_id = $campaignId;
                $fundraiser->save();
            }
        }



        return $response;
    }

    public function syncAddress(Address $address)
    {
        if ($address->neon_id) {
            $response = $this->updateAddress($address);
        } else {
            $response = $this->createAddress($address);

            if ($response->successful()) {
                $addressId = $response->json()['id'];

                $address->neon_id = $addressId;
                $address->save();
            }
        }


        return $response;
    }

    public function syncDonation(Transaction $transaction)
    {
        if ($transaction->neon_id && $transaction->neon_payment_id) {
            $response = $this->updateDonation($transaction);
        } else {
            $response = $this->createDonation($transaction);

            if ($response->successful()) {
                $donationId = $response->json()['id'];
                $paymentId = $response->json()['paymentResponse']['id'];

                $transaction->neon_id = intval($donationId);
                $transaction->neon_payment_id = intval($paymentId);
                $transaction->save();
            }
        }


        return $response;
    }

    public function syncRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        if ($scheduledDonation->neon_id) {
            $response = $this->updateRecurringDonation($scheduledDonation);
        } else {
            $response = $this->createRecurringDonation($scheduledDonation);

            if ($response->successful()) {
                $recurringDonationId = $response->json()['id'];

                $scheduledDonation->neon_id = $recurringDonationId;
                $scheduledDonation->save();
            }
        }


        return $response;
    }
}
