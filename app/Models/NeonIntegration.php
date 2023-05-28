<?php

namespace App\Models;

use JsonPath\JsonObject;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\DonorWebhookResource;
use App\Http\Resources\TransactionWebhookResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\CausesActivity as TraitsCausesActivity;

class NeonIntegration extends Model
{

    use HasFactory;
    use TraitsCausesActivity;

    protected $guarded = ['id'];

    public const CASH = 1;
    public const CREDIT_CARD_OFFLINE = 2;
    public const CHECK = 3;
    public const CREDIT_CARD_ONLINE = 4;
    public const IN_KIND = 5;
    public const STOCK_SECURITY = 6;
    public const ART_ANTIQUE = 7;
    public const WIRE = 8;
    public const GIFT_CERTIFICATE = 9;
    public const OTHER = 10;
    public const PAYPAL = 11;
    public const ACH = 12;

    public $tenderTypeMap = [
        'card' => NeonIntegration::CREDIT_CARD_OFFLINE,
        'bank' => NeonIntegration::CHECK,
        'donor' => NeonIntegration::CHECK,
    ];

    public $cardTypeMap = [
        'visa' => 'V',
        'mastercard' => 'M',
        'amex' => 'A',
        'discover' => 'D',

    ];


    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->setupDefaultMappings();
        });


        self::updated(
            function ($model) {
                if ($model->enabled && !$model->getOriginal('enabled') && $model->two_way_sync) {
                    $model->setupWebhooks();
                } else if (!$model->enabled) {
                    $model->removeWebhooks();
                }
            }
        );
    }

    public function setupDefaultMappings()
    {
        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'donorName';
        $mapping->wegive_path = 'donor_name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'amount';
        $mapping->wegive_path = 'amount';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'date';
        $mapping->wegive_path = 'created_at';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'anonymousType';
        $mapping->wegive_path = 'anonymous';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'donorCoveredFee';
        $mapping->wegive_path = 'fee_amount';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'campaign.name';
        $mapping->wegive_path = 'fundraiser_name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'campaign.id';
        $mapping->wegive_path = 'fundraiser_id';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'tribute.name';
        $mapping->wegive_path = 'tribute_name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        // Donor Rules

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'consent.email';
        $mapping->wegive_path = 'email_notifications';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'consent.sms';
        $mapping->wegive_path = 'sms_notifications';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'facebookPage';
        $mapping->wegive_path = 'facebook_link';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'twitterPage';
        $mapping->wegive_path = 'twitter_link';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email1';
        $mapping->wegive_path = 'email_1';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email2';
        $mapping->wegive_path = 'email_2';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email3';
        $mapping->wegive_path = 'email_3';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.firstName';
        $mapping->wegive_path = 'first_name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.lastName';
        $mapping->wegive_path = 'last_name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();


        // Campaign
        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'endDate';
        $mapping->wegive_path = 'expiration';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'goal';
        $mapping->wegive_path = 'goal';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'name';
        $mapping->wegive_path = 'name';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'startDate';
        $mapping->wegive_path = 'start_date';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.donationAmount';
        $mapping->wegive_path = 'total_donated';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.donationCount';
        $mapping->wegive_path = 'number_of_donations';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.eventRegistrationAmount';
        $mapping->wegive_path = 'total_registration_collected';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.eventRegistrationCount';
        $mapping->wegive_path = 'number_of_registrations';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.grandTotal';
        $mapping->wegive_path = 'total_donated';
        $mapping->crm = NeonMappingRule::NEON;
        $mapping->save();
    }

    public function setupWebhooks()
    {
        $user = auth()->user();


        $token =  $this->organization->createToken("neon_crm_token")->plainTextToken;


        $base = 'https://super.givelist.app/api/';
        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $base = 'https://api.staging.givelist.app/api/';
        }

        $data = [
            "contentType" => "application/json",
            "customParameters" => [
                [
                    "name" => 'api_key',
                    "value" => $token
                ],
                [
                    "name" => 'organization',
                    "value" => $this->organization_id
                ]
            ],
            "id" => "string",
            "name" => "string",
            "trigger" => 'CREATE_ACCOUNT',
            "url" => $base . 'neon-integrations/create-donor'
        ];

        $this->post('webhooks', $data);

        $data = [
            "contentType" => "application/json",
            "customParameters" => [
                [
                    "name" => 'api_key',
                    "value" => $token
                ],
                [
                    "name" => 'organization',
                    "value" => $this->organization_id
                ]
            ],
            "id" => "string",
            "name" => "string",
            "trigger" => 'UPDATE_ACCOUNT',
            "url" => $base . 'neon-integrations/update-donor'
        ];

        $this->post('webhooks', $data);

        $data = [
            "contentType" => "application/json",
            "customParameters" => [
                [
                    "name" => 'api_key',
                    "value" => $token
                ],
                [
                    "name" => 'organization',
                    "value" => $this->organization_id
                ]
            ],
            "id" => "string",
            "name" => "string",
            "trigger" => 'CREATE_DONATION',
            "url" => $base . 'neon-integrations/create-donation'
        ];

        $this->post('webhooks', $data);


        $data = [
            "contentType" => "application/json",
            "customParameters" => [
                [
                    "name" => 'api_key',
                    "value" => $token
                ],
                [
                    "name" => 'organization',
                    "value" => $this->organization_id
                ]
            ],
            "id" => "string",
            "name" => "string",
            "trigger" => 'UPDATE_DONATION',
            "url" => $base . 'neon-integrations/update-donation'
        ];

        $this->post('webhooks', $data);
    }

    public function removeWebhooks()
    {

        $response = $this->get('webhooks', []);

        if ($response->successful()) {
            foreach ($response->json() as $webhook) {
                $this->remove("webhooks/{$webhook['id']}", []);
            }
        }
    }


    // Actual user who created
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function getBaseAttribute()
    {
        $base = 'https://api.neoncrm.com/v2/';
        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $base = 'https://trial.z2systems.com/v2/';
        }
        return $base;
    }


    public function getAuthStringAttribute()
    {
        $authString = base64_encode("{$this->neon_id}:{$this->neon_api_key}");

        return $authString;
    }

    public function get($route, $params)
    {
        return Http::withHeaders(["Authorization" => "Basic {$this->authString}"])->get($this->base . $route, $params);
    }

    public function remove($route, $params)
    {
        return Http::withHeaders(["Authorization" => "Basic {$this->authString}"])->delete($this->base . $route, $params);
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


        $renderedData = new DonorWebhookResource($donorProfile);

        $renderedData = $renderedData->toResponse(app('request'))->getData();

        $renderedData = (array) $renderedData->data;


        $params = [
            "{$donorProfile->type}Account" => [
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

        $wegiveData = new JsonObject($renderedData);

        $neonData = new JsonObject($params);

        $fieldMappings = $this->organization->neonMappingRules()->where('crm', NeonMappingRule::NEON)->where('integration', NeonMappingRule::ACCOUNT)->get();

        foreach ($fieldMappings as $map) {
            $neonData->set("$.{$donorProfile->type}Account.{$map->integration_path}", $wegiveData->get("$.{$map->wegive_path}")[0]);
        }

        return $neonData->getValue();
    }

    public function generateDonationParams(Transaction $transaction)
    {
        $donorProfile = $transaction->owner;

        $billingAddress = $donorProfile->addresses()->where('type', 'billing')->where('primary', true)->first();

        $renderedData = new TransactionWebhookResource($transaction);

        $renderedData = $renderedData->toResponse(app('request'))->getData();

        $renderedData = (array) $renderedData->data;

        $params =  [
            "id" => $transaction->neon_id,
            "accountId" => $donorProfile->neon_account_id,
            "donorName" => $donorProfile->name,
            "amount" => $transaction->amount / 100,
            "date" => $transaction->created_at,
            "campaign" => $this->fundraiser_id ? [
                "id" => $this->fundraiser_id,
                "name" => $this->fundraiser->name,
                "status" => "ACTIVE"
            ] : null,
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
            "tribute" => [
                "name" => $transaction->tribute_name,
                "type" => "Honor"
            ],
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
            "sendAcknowledgeEmail" => false,
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
            "payments" => $transaction->neon_payment_id ? null : [
                [
                    "id" => $transaction->neon_payment_id,
                    "check" => $transaction->source_type === 'bank' ? [
                        "accountType" => "Checking",
                        "accountNumber" => $transaction->source->last_four,
                        "institution" => $transaction->source->name,
                        "accountOwner" => $donorProfile->name

                    ] : null,
                    "amount" => $transaction->amount / 100,
                    "creditCardOffline" => $transaction->source_type === 'card' ? [
                        "billingAddress" => null,
                        "cardHolderEmail" => $donorProfile->email_1,
                        "cardHolderName" => $donorProfile->name,
                        "cardNumberLastFour" => $transaction->source->last_four,
                        "cardTypeCode" => $this->cardTypeMap[$transaction->source->issuer],
                        "expirationMonth" => explode('/', $transaction->source->expiration)[0],
                        "expirationYear" => explode('/', $transaction->source->expiration)[1],

                    ] : null,
                    // "inKind" => [
                    //     "fairMarketValue" => 12345,
                    //     "nccDescription" => "string"
                    // ],
                    "note" => "https://staging.wegive.com/dashboard/payments/donations/{$transaction->id}",
                    // "receivedDate" => "2021-01-20 12:00:00",
                    "tenderType" => $this->tenderTypeMap[$transaction->source_type]
                ]
            ]
        ];

        $wegiveData = new JsonObject($renderedData);

        $neonData = new JsonObject($params);

        $fieldMappings = $this->organization->neonMappingRules()->where('crm', NeonMappingRule::NEON)->where('integration', NeonMappingRule::DONATION)->get();

        foreach ($fieldMappings as $map) {
            $neonData->set("$.{$map->integration_path}", $wegiveData->get("$.{$map->wegive_path}")[0]);
        }

        return $neonData->getValue();
    }

    public function generateRecurringDonationParams(ScheduledDonation $scheduledDonation)
    {

        $donorProfile = $scheduledDonation->source;

        $params =  [
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

        if (!$this->track_donors || !$this->enabled || !$this->crm_sync) return;



        if ($donorProfile->neon_id && $donorProfile->neon_account_id) {
            $response = $this->updateDonor($donorProfile);

            foreach ($donorProfile->addresses as &$address) {
                $this->syncAddress($address);
            }
        } else {
            $response = $this->createDonor($donorProfile);

            if ($response->successful()) {
                $accountId = $response->json()['id'];

                $account = $this->get("accounts/{$accountId}", []);

                $contactId = $account->json()["{$donorProfile->type}Account"]['primaryContact']['contactId'];

                $donorProfile->neon_id = intval($contactId);
                $donorProfile->neon_account_id = intval($accountId);

                $donorProfile->save();

                foreach ($donorProfile->addresses as &$address) {
                    $this->syncAddress($address);
                }
            }
        }



        return $response;
    }

    public function syncFundraiser(Fundraiser $fundraiser)
    {
        if (!$this->track_campaigns || !$this->enabled) return;

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
        if (!$this->track_donors || !$this->enabled || !$this->crm_sync) return;


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

        if (!$this->track_donors || !$this->enabled || !$this->crm_sync) return;

        if ($transaction->fundraiser) $this->syncFundraiser($transaction->fundraiser);

        if (!$transaction->owner->neon_account_id) {
            // only sync donor if no id
            $response =  $this->syncDonor($transaction->owner);

            if ($response->failed()) return $response;
        }

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
        if (!$this->track_donors || !$this->enabled || !$this->crm_sync) return;

        $response =  $this->syncDonor($scheduledDonation->source);

        if ($response->failed()) return;

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
