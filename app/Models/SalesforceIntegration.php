<?php

namespace App\Models;

use App\Http\Resources\DonorWebhookResource;
use App\Http\Resources\TransactionWebhookResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonPath\JsonObject;
use Spatie\Activitylog\Traits\CausesActivity as TraitsCausesActivity;

class SalesforceIntegration extends Model
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
        'card' => SalesforceIntegration::CREDIT_CARD_OFFLINE,
        'bank' => SalesforceIntegration::CHECK,
        'donor' => SalesforceIntegration::CHECK,
    ];

    public $paymentMethodMap = [
        'card' => 'Credit Card',
        'bank' => 'ACH',
    ];

    public $cardTypeMap = [
        'visa' => 'V',
        'mastercard' => 'M',
        'amex' => 'A',
        'discover' => 'D',
    ];

    public $accessToken = null;

    public $frequencyMap = [ScheduledDonation::DONATION_FREQUENCY_DAILY => [
        'period' => 'Daily',
        'frequency' => 1
    ], ScheduledDonation::DONATION_FREQUENCY_WEEKLY => [
        'period' => 'Weekly',
        'frequency' => 1
    ], ScheduledDonation::DONATION_FREQUENCY_BIMONTHLY => [
        'period' => '1st and 15th',
        'frequency' => 1
    ], ScheduledDonation::DONATION_FREQUENCY_MONTHLY => [
        'period' => 'Monthly',
        'frequency' => 1
    ], ScheduledDonation::DONATION_FREQUENCY_QUARTERLY => [
        'period' => 'Monthly',
        'frequency' => 3
    ], ScheduledDonation::DONATION_FREQUENCY_YEARLY => [
        'period' => 'Yearly',
        'frequency' => 1
    ],];

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
                if ($model->enabled && !$model->getOriginal('enabled') && $model->two_way_sync && $model->client_id && $model->client_secret && $model->username && $model->password && $model->instance_url) {
                    $model->setupAllTriggers();
                } else if (!$model->enabled) {
                    $model->removeAllTriggers();
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
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'amount';
        $mapping->wegive_path = 'amount';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'date';
        $mapping->wegive_path = 'created_at';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'anonymousType';
        $mapping->wegive_path = 'anonymous';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'donorCoveredFee';
        $mapping->wegive_path = 'fee_amount';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'campaign.name';
        $mapping->wegive_path = 'fundraiser_name';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'campaign.id';
        $mapping->wegive_path = 'fundraiser_id';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::DONATION;
        $mapping->integration_path = 'tribute.name';
        $mapping->wegive_path = 'tribute_name';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        // Donor Rules

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'consent.email';
        $mapping->wegive_path = 'email_notifications';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'consent.sms';
        $mapping->wegive_path = 'sms_notifications';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'facebookPage';
        $mapping->wegive_path = 'facebook_link';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'twitterPage';
        $mapping->wegive_path = 'twitter_link';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email1';
        $mapping->wegive_path = 'email_1';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email2';
        $mapping->wegive_path = 'email_2';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.email3';
        $mapping->wegive_path = 'email_3';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.firstName';
        $mapping->wegive_path = 'first_name';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::ACCOUNT;
        $mapping->integration_path = 'primaryContact.lastName';
        $mapping->wegive_path = 'last_name';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();


        // Campaign
        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'endDate';
        $mapping->wegive_path = 'expiration';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'goal';
        $mapping->wegive_path = 'goal';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'name';
        $mapping->wegive_path = 'name';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'startDate';
        $mapping->wegive_path = 'start_date';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.donationAmount';
        $mapping->wegive_path = 'total_donated';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.donationCount';
        $mapping->wegive_path = 'number_of_donations';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.eventRegistrationAmount';
        $mapping->wegive_path = 'total_registration_collected';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.eventRegistrationCount';
        $mapping->wegive_path = 'number_of_registrations';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();

        $mapping = new NeonMappingRule();
        $mapping->organization()->associate($this->organization);
        $mapping->integration = NeonMappingRule::CAMPAIGN;
        $mapping->integration_path = 'statistics.grandTotal';
        $mapping->wegive_path = 'total_donated';
        $mapping->crm = NeonMappingRule::SALESFORCE;
        $mapping->save();
    }


    // Actual user who created
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function salesforceApexTriggers()
    {
        return $this->hasMany(SalesforceApexTrigger::class);
    }


    public function getBaseAttribute()
    {
        $base = $this->instance_url;

        return $base;
    }


    public function getAccessToken()
    {
        $response = Http::post($this->base . "services/oauth2/token?grant_type=password&client_id={$this->client_id}&client_secret={$this->client_secret}&username={$this->username}&password={$this->password}");

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        return false;
    }

    public function get($route, $params)
    {
        $accessToken = $this->getAccessToken();

        return Http::withHeaders(["Authorization" => "Bearer {$accessToken}"])->get($this->base . $route, $params);
    }

    public function remove($route, $params)
    {
        $accessToken = $this->getAccessToken();

        return Http::withHeaders(["Authorization" => "Bearer {$accessToken}"])->delete($this->base . $route, $params);
    }

    public function post($route, $params)
    {
        $accessToken = $this->getAccessToken();

        return Http::withHeaders(["Authorization" => "Bearer {$accessToken}"])->post($this->base . $route, $params);
    }

    public function patch($route, $params)
    {
        $accessToken = $this->getAccessToken();

        return Http::withHeaders(["Authorization" => "Bearer {$accessToken}"])->patch($this->base . $route, $params);
    }

    // Request Body Generators

    public function generateAccountParams($donorProfile)
    {
        $renderedData = new DonorWebhookResource($donorProfile);

        $renderedData = $renderedData->toResponse(app('request'))->getData();

        $renderedData = (array) $renderedData->data;

        $billingAddress = $donorProfile->addresses()->where('primary', true)->where('type', 'billing')->first();


        $mailingAddress = $donorProfile->addresses()->where('primary', true)->where('type', 'mailing')->first();

        $params = [
            "Id" => '',
            "IsDeleted" => '',
            "MasterRecordId" => '',
            "Name" => $donorProfile->name,
            "Type" => 'Customer',
            "RecordTypeId" => '',
            "ParentId" => '',
            "BillingStreet" => $billingAddress->address_1 . ' ' . $billingAddress->address_2,
            "BillingCity" => $billingAddress->city,
            "BillingState" => $billingAddress->state,
            "BillingPostalCode" => $billingAddress->zip,
            "BillingCountry" => '',
            "BillingLatitude" => '',
            "BillingLongitude" => '',
            "BillingGeocodeAccuracy" => '',
            "BillingAddress" => '',
            "ShippingStreet" => $mailingAddress->address_1 . ' ' . $mailingAddress->address_2,
            "ShippingCity" => $mailingAddress->city,
            "ShippingState" =>  $mailingAddress->state,
            "ShippingPostalCode" => $mailingAddress->zip,
            "ShippingCountry" => '',
            "ShippingLatitude" => '',
            "ShippingLongitude" => '',
            "ShippingGeocodeAccuracy" => '',
            "ShippingAddress" => '',
            "Phone" => $donorProfile->office_phone,
            "Fax" => $donorProfile->fax,
            "AccountNumber" => '',
            "Website" => '',
            "PhotoUrl" => '',
            "Sic" => '',
            "Industry" => '',
            "AnnualRevenue" => '',
            "NumberOfEmployees" => '',
            "Ownership" => '',
            "TickerSymbol" => '',
            "Description" => '',
            "Rating" => '',
            "Site" => '',
            "OwnerId" => '',
            "CreatedDate" => '',
            "CreatedById" => '',
            "LastModifiedDate" => '',
            "LastModifiedById" => '',
            "SystemModstamp" => '',
            "LastActivityDate" => '',
            "LastViewedDate" => '',
            "LastReferencedDate" => '',
            "Jigsaw" => '',
            "JigsawCompanyId" => '',
            "AccountSource" => '',
            "SicDesc" => '',
            "npe01__One2OneContact__c" => '',
            "npe01__SYSTEMIsIndividual__c" => '',
            "npe01__SYSTEM_AccountType__c" => '',
            "npo02__AverageAmount__c" => '',
            "npo02__Best_Gift_Year_Total__c" => '',
            "npo02__Best_Gift_Year__c" => '',
            "npo02__FirstCloseDate__c" => '',
            "npo02__Formal_Greeting__c" => '',
            "npo02__HouseholdPhone__c" => '',
            "npo02__Informal_Greeting__c" => '',
            "npo02__LargestAmount__c" => '',
            "npo02__LastCloseDate__c" => '',
            "npo02__LastMembershipAmount__c" => '',
            "npo02__LastMembershipDate__c" => '',
            "npo02__LastMembershipLevel__c" => '',
            "npo02__LastMembershipOrigin__c" => '',
            "npo02__LastOppAmount__c" => '',
            "npo02__MembershipEndDate__c" => '',
            "npo02__MembershipJoinDate__c" => '',
            "npo02__NumberOfClosedOpps__c" => '',
            "npo02__NumberOfMembershipOpps__c" => '',
            "npo02__OppAmount2YearsAgo__c" => '',
            "npo02__OppAmountLastNDays__c" => '',
            "npo02__OppAmountLastYear__c" => '',
            "npo02__OppAmountThisYear__c" => '',
            "npo02__OppsClosed2YearsAgo__c" => '',
            "npo02__OppsClosedLastNDays__c" => '',
            "npo02__OppsClosedLastYear__c" => '',
            "npo02__OppsClosedThisYear__c" => '',
            "npo02__SYSTEM_CUSTOM_NAMING__c" => '',
            "npo02__SmallestAmount__c" => '',
            "npo02__TotalMembershipOppAmount__c" => '',
            "npo02__TotalOppAmount__c" => '',
            "npsp__Batch__c" => '',
            "npsp__Number_of_Household_Members__c" => '',
            "npsp__Membership_Span__c" => '',
            "npsp__Membership_Status__c" => '',
            "npsp__Funding_Focus__c" => '',
            "npsp__Grantmaker__c" => '',
            "npsp__Matching_Gift_Administrator_Name__c" => '',
            "npsp__Matching_Gift_Amount_Max__c" => '',
            "npsp__Matching_Gift_Amount_Min__c" => '',
            "npsp__Matching_Gift_Annual_Employee_Max__c" => '',
            "npsp__Matching_Gift_Comments__c" => '',
            "npsp__Matching_Gift_Company__c" => '',
            "npsp__Matching_Gift_Email__c" => '',
            "npsp__Matching_Gift_Info_Updated__c" => '',
            "npsp__Matching_Gift_Percent__c" => '',
            "npsp__Matching_Gift_Phone__c" => '',
            "npsp__Matching_Gift_Request_Deadline__c" => '',
            "Level__c" => '',
            "Previous_Level__c" => '',
            "attributes" => '',
        ];

        $wegiveData = new JsonObject($renderedData);

        $salesforceData = new JsonObject($params);

        $fieldMappings = $this->organization->neonMappingRules()->where('integration', NeonMappingRule::ACCOUNT)->get();

        foreach ($fieldMappings as $map) {
            $salesforceData->set("$.{$map->integration_path}", $wegiveData->get("$.{$map->wegive_path}")[0]);
        }

        return array_filter($salesforceData->getValue());
    }

    public function generateContactParams($donorProfile, $salesforceId = null)
    {


        $renderedData = new DonorWebhookResource($donorProfile);

        $renderedData = $renderedData->toResponse(app('request'))->getData();

        $renderedData = (array) $renderedData->data;

        $billingAddress = $donorProfile->addresses()->where('primary', true)->where('type', 'billing')->first();


        $mailingAddress = $donorProfile->addresses()->where('primary', true)->where('type', 'mailing')->first();

        $existingData = null;

        if ($salesforceId) {
            $response = $this->get("services/data/v54.0/sobjects/Contact/{$salesforceId}", null);

            if ($response->successful()) {
                $existingData = $response->json();
            }
        }

        $params =  [
            "MailingStreet" => $mailingAddress ? $mailingAddress->address_1 . ' ' . $mailingAddress->address_2 : null,
            "MailingCity" => $mailingAddress ? $mailingAddress->city : null,
            "MailingState" => $mailingAddress ? $mailingAddress->state : null,
            "MailingPostalCode" => $mailingAddress ? $mailingAddress->zip : null,
            "Phone" => $donorProfile->office_phone,
            "Email" => $donorProfile->email_1,
            "LastName" => $donorProfile->last_name,
            "FirstName" => $donorProfile->first_name,
            // "OtherStreet" => $billingAddress ? $billingAddress->address_1 . ' ' . $billingAddress->address_2 : null,
            // "OtherCity" => $billingAddress ? $billingAddress->city : null,
            // "OtherState" => $billingAddress ? $billingAddress->state : null,
            // "OtherPostalCode" => $billingAddress ? $billingAddress->zip : null,
            // "Fax" => $donorProfile->fax,
            // "MobilePhone" =>  $donorProfile->mobile_phone,
            // "HomePhone" => $donorProfile->home_phone,
            // "OtherPhone" => $donorProfile->other_phone,
            // "IsDeleted" => null,
            // "MasterRecordId" => null,
            // "AccountId" => null,
            // "Salutation" => null,
            // "Name" => null,
            // "OtherCountry" => null,
            // "OtherLatitude" => null,
            // "OtherLongitude" => null,
            // "OtherGeocodeAccuracy" => null,
            // "OtherAddress" => null,
            // "MailingCountry" => null,
            // "MailingLatitude" => null,
            // "MailingLongitude" => null,
            // "MailingGeocodeAccuracy" => null,
            // "MailingAddress" => null,
            // "AssistantPhone" => null,
            // "ReportsToId" => null,
            // "Title" => null,
            // "Department" => null,
            // "AssistantName" => null,
            // "LeadSource" => null,
            // "Birthdate" => null,
            // "Description" => null,
            // "OwnerId" => null,
            // "HasOptedOutOfEmail" => null,
            // "HasOptedOutOfFax" => null,
            // "DoNotCall" => null,
            // "CreatedDate" => null,
            // "CreatedById" => null,
            // "LastModifiedDate" => null,
            // "LastModifiedById" => null,
            // "SystemModstamp" => null,
            // "LastActivityDate" => null,
            // "LastCURequestDate" => null,
            // "LastCUUpdateDate" => null,
            // "LastViewedDate" => null,
            // "LastReferencedDate" => null,
            // "EmailBouncedReason" => null,
            // "EmailBouncedDate" => null,
            // "IsEmailBounced" => null,
            // "PhotoUrl" => null,
            // "Jigsaw" => null,
            // "JigsawContactId" => null,
            // "IndividualId" => null,
            // "npe01__AlternateEmail__c" => null,
            // "npe01__HomeEmail__c" => null,
            // "npe01__Home_Address__c" => null,
            // "npe01__Organization_Type__c" => null,
            // "npe01__Other_Address__c" => null,
            // "npe01__PreferredPhone__c" => null,
            // "npe01__Preferred_Email__c" => null,
            // "npe01__Primary_Address_Type__c" => null,
            // "npe01__Private__c" => null,
            // "npe01__Secondary_Address_Type__c" => null,
            // "npe01__Type_of_Account__c" => null,
            // "npe01__WorkEmail__c" => null,
            // "npe01__WorkPhone__c" => null,
            // "npe01__Work_Address__c" => null,
            // "npo02__AverageAmount__c" => null,
            // "npo02__Best_Gift_Year_Total__c" => null,
            // "npo02__Best_Gift_Year__c" => null,
            // "npo02__FirstCloseDate__c" => null,
            // "npo02__Formula_HouseholdMailingAddress__c" => null,
            // "npo02__Formula_HouseholdPhone__c" => null,
            // "npo02__Household_Naming_Order__c" => null,
            // "npo02__Household__c" => null,
            // "npo02__LargestAmount__c" => null,
            // "npo02__LastCloseDateHH__c" => null,
            // "npo02__LastCloseDate__c" => null,
            // "npo02__LastMembershipAmount__c" => null,
            // "npo02__LastMembershipDate__c" => null,
            // "npo02__LastMembershipLevel__c" => null,
            // "npo02__LastMembershipOrigin__c" => null,
            // "npo02__LastOppAmount__c" => null,
            // "npo02__MembershipEndDate__c" => null,
            // "npo02__MembershipJoinDate__c" => null,
            // "npo02__Naming_Exclusions__c" => null,
            // "npo02__NumberOfClosedOpps__c" => null,
            // "npo02__NumberOfMembershipOpps__c" => null,
            // "npo02__OppAmount2YearsAgo__c" => null,
            // "npo02__OppAmountLastNDays__c" => null,
            // "npo02__OppAmountLastYearHH__c" => null,
            // "npo02__OppAmountLastYear__c" => null,
            // "npo02__OppAmountThisYearHH__c" => null,
            // "npo02__OppAmountThisYear__c" => null,
            // "npo02__OppsClosed2YearsAgo__c" => null,
            // "npo02__OppsClosedLastNDays__c" => null,
            // "npo02__OppsClosedLastYear__c" => null,
            // "npo02__OppsClosedThisYear__c" => null,
            // "npo02__SmallestAmount__c" => null,
            // "npo02__Soft_Credit_Last_Year__c" => null,
            // "npo02__Soft_Credit_This_Year__c" => null,
            // "npo02__Soft_Credit_Total__c" => null,
            // "npo02__Soft_Credit_Two_Years_Ago__c" => null,
            // "npo02__TotalMembershipOppAmount__c" => null,
            // "npo02__TotalOppAmount__c" => null,
            // "npo02__Total_Household_Gifts__c" => null,
            // "npsp__Batch__c" => null,
            // "npsp__Current_Address__c" => null,
            // "npsp__HHId__c" => null,
            // "npsp__Primary_Affiliation__c" => null,
            // "npsp__Soft_Credit_Last_N_Days__c" => null,
            // "npsp__is_Address_Override__c" => null,
            // "Gender__c" => null,
            // "npsp__Primary_Contact__c" => null,
            // "npsp__Address_Verification_Status__c" => null,
            // "npsp__Exclude_from_Household_Formal_Greeting__c" => null,
            // "npsp__Exclude_from_Household_Informal_Greeting__c" => null,
            // "npsp__Exclude_from_Household_Name__c" => null,
            // "npsp__Deceased__c" => null,
            // "npsp__Do_Not_Contact__c" => null,
            // "npsp__First_Soft_Credit_Amount__c" => null,
            // "npsp__First_Soft_Credit_Date__c" => null,
            // "npsp__Largest_Soft_Credit_Amount__c" => null,
            // "npsp__Largest_Soft_Credit_Date__c" => null,
            // "npsp__Last_Soft_Credit_Amount__c" => null,
            // "npsp__Last_Soft_Credit_Date__c" => null,
            // "npsp__Number_of_Soft_Credits_Last_N_Days__c" => null,
            // "npsp__Number_of_Soft_Credits_Last_Year__c" => null,
            // "npsp__Number_of_Soft_Credits_This_Year__c" => null,
            // "npsp__Number_of_Soft_Credits_Two_Years_Ago__c" => null,
            // "npsp__Number_of_Soft_Credits__c" => null,
            // "attributes" => null,

        ];

        if ($existingData) {

            $matchingFields = [
                'MailingStreet', 'MailingCity', 'MailingState', 'MailingPostalCode', 'Phone', "Email", "LastName", "FirstName",
            ];

            foreach ($matchingFields as $f) {
                if ($existingData[$f]) {
                    $params[$f] = null;
                }
            }
        }





        return array_filter($params);
    }

    public function generateCampaignParams(Campaign $campaign)
    {

        $params = [
            "Name"  => $campaign->name,
            // "Id"  => '',
            // "IsDeleted"  => '',
            // "ParentId"  => '',
            // "Type"  => 'Generic',
            // "RecordTypeId"  => '',
            // "Status"  => '',
            // "StartDate"  => '',
            // "EndDate"  => '',
            // "ExpectedRevenue"  => '',
            // "BudgetedCost"  => '',
            // "ActualCost"  => '',
            // "ExpectedResponse"  => '',
            // "NumberSent"  => '',
            // "IsActive"  => '',
            // "Description"  => '',
            // "NumberOfLeads"  => '',
            // "NumberOfConvertedLeads"  => '',
            // "NumberOfContacts"  => '',
            // "NumberOfResponses"  => '',
            // "NumberOfOpportunities"  => '',
            // "NumberOfWonOpportunities"  => '',
            // "AmountAllOpportunities"  => '',
            // "AmountWonOpportunities"  => '',
            // "HierarchyNumberOfLeads"  => '',
            // "HierarchyNumberOfConvertedLeads"  => '',
            // "HierarchyNumberOfContacts"  => '',
            // "HierarchyNumberOfResponses"  => '',
            // "HierarchyNumberOfOpportunities"  => '',
            // "HierarchyNumberOfWonOpportunities"  => '',
            // "HierarchyAmountAllOpportunities"  => '',
            // "HierarchyAmountWonOpportunities"  => '',
            // "HierarchyNumberSent"  => '',
            // "HierarchyExpectedRevenue"  => '',
            // "HierarchyBudgetedCost"  => '',
            // "HierarchyActualCost"  => '',
            // "OwnerId"  => '',
            // "CreatedDate"  => '',
            // "CreatedById"  => '',
            // "LastModifiedDate"  => '',
            // "LastModifiedById"  => '',
            // "SystemModstamp"  => '',
            // "LastActivityDate"  => '',
            // "LastViewedDate"  => '',
            // "LastReferencedDate"  => '',
            // "CampaignMemberRecordTypeId"  => '',
            // "Amount_to_Goal__c"  => '',
            // "attributes"  => '',
        ];

        return array_filter($params);
    }

    public function generateDonationParams(Transaction $transaction, $update = false)
    {
        $donorProfile = $transaction->owner;

        $renderedData = new TransactionWebhookResource($transaction);

        $renderedData = $renderedData->toResponse(app('request'))->getData();

        $renderedData = (array) $renderedData->data;

        $formattedDate = date("m-d", strtotime($transaction->created_at));

        if ($update) {
            return  [
                // "Id"  => $transaction->salesforce_id,
                // "IsDeleted"  => '',
                // "AccountId"  => $donor->type === 'company' ? $donorProfile->salesforce_id : null,
                // "RecordTypeId"  => '',
                // "IsPrivate"  => '',
                "Name"  => "{$transaction->owner->name} {$formattedDate}",
                // "Description"  => '',
                "StageName"  => 'Closed Won',
                "Amount"  => ($transaction->amount  - $transaction->fee) / 100,
                // "Probability"  => '',
                // "ExpectedRevenue"  => '',
                // "TotalOpportunityQuantity"  => '',
                "CloseDate"  => $transaction->created_at,
                // "Type"  => 'WeGive Donation',
                // "NextStep"  => '',
                // "LeadSource"  => '',
                // "IsClosed"  => '',
                // "IsWon"  => '',
                // "ForecastCategory"  => '',
                // "ForecastCategoryName"  => '',
                "CampaignId"  => $transaction->element_id ? $transaction->element->campaign->salesforce_id : null,
                // "HasOpportunityLineItem"  => '',
                // "Pricebook2Id"  => '',
                // "OwnerId"  => '',
                // "CreatedDate"  => $transaction->created_at,
                // "CreatedById"  => '',
                // "LastModifiedDate"  => '',
                // "LastModifiedById"  => '',
                // "SystemModstamp"  => '',
                // "LastActivityDate"  => '',
                // "PushCount"  => '',
                // "LastStageChangeDate"  => '',
                // "FiscalQuarter"  => '',
                // "FiscalYear"  => '',
                // "Fiscal"  => '',
                // "ContactId"  =>  $donor->type === 'individual' ? $donorProfile->salesforce_id : null,
                // "LastViewedDate"  => '',
                // "LastReferencedDate"  => '',
                // "ContractId"  => '',
                // "HasOpenActivity"  => '',
                // "HasOverdueTask"  => '',
                // "LastAmountChangedHistoryId"  => '',
                // "LastCloseDateChangedHistoryId"  => '',
                // "npe01__Amount_Outstanding__c"  => '',
                // "npe01__Contact_Id_for_Role__c"  => '',
                "npe01__Do_Not_Automatically_Create_Payment__c"  => true,
                // "npe01__Is_Opp_From_Individual__c"  => '',
                // "npe01__Member_Level__c"  => '',
                // "npe01__Membership_End_Date__c"  => '',
                // "npe01__Membership_Origin__c"  => '',
                // "npe01__Membership_Start_Date__c"  => '',
                // "npe01__Amount_Written_Off__c"  => '',
                // "npe01__Number_of_Payments__c"  => '',
                // "npe01__Payments_Made__c"  => '',
                // "npo02__CombinedRollupFieldset__c"  => '',
                // "npo02__systemHouseholdContactRoleProcessor__c"  => '',
                "npe03__Recurring_Donation__c"  => $transaction->scheduled_donation_id ? $transaction->scheduledDonation->salesforce_id : null,
                // "npsp__Batch__c"  => '',
                // "npsp__Acknowledgment_Date__c"  => '',
                // "npsp__Acknowledgment_Status__c"  => '',
                // "npsp__Recurring_Donation_Installment_Name__c"  => '',
                // "npsp__Recurring_Donation_Installment_Number__c"  => '',
                // "npsp__Primary_Contact__c"  => '',
                // "npsp__Grant_Contract_Date__c"  => '',
                // "npsp__Grant_Contract_Number__c"  => '',
                // "npsp__Grant_Period_End_Date__c"  => '',
                // "npsp__Grant_Period_Start_Date__c"  => '',
                // "npsp__Grant_Program_Area_s__c"  => '',
                // "npsp__Grant_Requirements_Website__c"  => '',
                // "npsp__Is_Grant_Renewal__c"  => '',
                // "npsp__Previous_Grant_Opportunity__c"  => '',
                // "npsp__Requested_Amount__c"  => '',
                // "npsp__Next_Grant_Deadline_Due_Date__c"  => '',
                // "npsp__Primary_Contact_Campaign_Member_Status__c"  => '',
                // "npsp__Honoree_Contact__c"  => '',
                // "npsp__Honoree_Name__c"  => '',
                // "npsp__Notification_Message__c"  => '',
                // "npsp__Notification_Preference__c"  => '',
                // "npsp__Notification_Recipient_Contact__c"  => '',
                // "npsp__Notification_Recipient_Information__c"  => '',
                // "npsp__Notification_Recipient_Name__c"  => '',
                // "npsp__Tribute_Type__c"  => '',
                // "npsp__Matching_Gift_Account__c"  => '',
                // "npsp__Matching_Gift_Employer__c"  => '',
                // "npsp__Matching_Gift_Status__c"  => '',
                // "npsp__Matching_Gift__c"  => '',
                // "npsp__Fair_Market_Value__c"  => '',
                // "npsp__In_Kind_Description__c"  => '',
                // "npsp__In_Kind_Donor_Declared_Value__c"  => '',
                // "npsp__In_Kind_Type__c"  => '',
                // "npsp__Ask_Date__c"  => '',
                // "npsp__Closed_Lost_Reason__c"  => '',
                // "npsp__Gift_Strategy__c"  => '',
                // "npsp__DisableContactRoleAutomation__c"  => '',
                // "npsp__Batch_Number__c"  => '',
                // "attributes"  => '',
            ];
        }

        $params =   [
            // "Id"  => $transaction->salesforce_id,
            // "IsDeleted"  => '',
            "AccountId"  => $donorProfile->salesforce_account_id,
            // "RecordTypeId"  => '',
            // "IsPrivate"  => '',
            "Name"  => "{$transaction->owner->name} {$formattedDate}",
            // "Description"  => '',
            "StageName"  => 'Closed Won',
            "Amount"  => ($transaction->amount - $transaction->fee) / 100,
            // "Probability"  => '',
            // "ExpectedRevenue"  => '',
            // "TotalOpportunityQuantity"  => '',
            "CloseDate"  => $transaction->created_at,
            // "Type"  => 'WeGive Donation',
            // "NextStep"  => '',
            // "LeadSource"  => '',
            // "IsClosed"  => '',
            // "IsWon"  => '',
            // "ForecastCategory"  => '',
            // "ForecastCategoryName"  => '',
            "CampaignId"  => $transaction->campaign_id ? $transaction->campaign->salesforce_id : null,
            // "HasOpportunityLineItem"  => '',
            // "Pricebook2Id"  => '',
            // "OwnerId"  => '',
            // "CreatedDate"  => $transaction->created_at,
            // "CreatedById"  => '',
            // "LastModifiedDate"  => '',
            // "LastModifiedById"  => '',
            // "SystemModstamp"  => '',
            // "LastActivityDate"  => '',
            // "PushCount"  => '',
            // "LastStageChangeDate"  => '',
            // "FiscalQuarter"  => '',
            // "FiscalYear"  => '',
            // "Fiscal"  => '',
            "ContactId"  => $donorProfile->salesforce_id,
            // "LastViewedDate"  => '',
            // "LastReferencedDate"  => '',
            // "ContractId"  => '',
            // "HasOpenActivity"  => '',
            // "HasOverdueTask"  => '',
            // "LastAmountChangedHistoryId"  => '',
            // "LastCloseDateChangedHistoryId"  => '',
            // "npe01__Amount_Outstanding__c"  => '',
            // "npe01__Contact_Id_for_Role__c"  => '',
            "npe01__Do_Not_Automatically_Create_Payment__c"  => true,
            // "npe01__Is_Opp_From_Individual__c"  => '',
            // "npe01__Member_Level__c"  => '',
            // "npe01__Membership_End_Date__c"  => '',
            // "npe01__Membership_Origin__c"  => '',
            // "npe01__Membership_Start_Date__c"  => '',
            // "npe01__Amount_Written_Off__c"  => '',
            // "npe01__Number_of_Payments__c"  => '',
            // "npe01__Payments_Made__c"  => '',
            // "npo02__CombinedRollupFieldset__c"  => '',
            // "npo02__systemHouseholdContactRoleProcessor__c"  => '',
            "npe03__Recurring_Donation__c"  => $transaction->scheduled_donation_id ? $transaction->scheduledDonation->salesforce_id : null,
            // "npsp__Batch__c"  => '',
            // "npsp__Acknowledgment_Date__c"  => '',
            // "npsp__Acknowledgment_Status__c"  => '',
            // "npsp__Recurring_Donation_Installment_Name__c"  => '',
            // "npsp__Recurring_Donation_Installment_Number__c"  => '',
            // "npsp__Primary_Contact__c"  => '',
            // "npsp__Grant_Contract_Date__c"  => '',
            // "npsp__Grant_Contract_Number__c"  => '',
            // "npsp__Grant_Period_End_Date__c"  => '',
            // "npsp__Grant_Period_Start_Date__c"  => '',
            // "npsp__Grant_Program_Area_s__c"  => '',
            // "npsp__Grant_Requirements_Website__c"  => '',
            // "npsp__Is_Grant_Renewal__c"  => '',
            // "npsp__Previous_Grant_Opportunity__c"  => '',
            // "npsp__Requested_Amount__c"  => '',
            // "npsp__Next_Grant_Deadline_Due_Date__c"  => '',
            // "npsp__Primary_Contact_Campaign_Member_Status__c"  => '',
            // "npsp__Honoree_Contact__c"  => '',
            // "npsp__Honoree_Name__c"  => '',
            // "npsp__Notification_Message__c"  => '',
            // "npsp__Notification_Preference__c"  => '',
            // "npsp__Notification_Recipient_Contact__c"  => '',
            // "npsp__Notification_Recipient_Information__c"  => '',
            // "npsp__Notification_Recipient_Name__c"  => '',
            // "npsp__Tribute_Type__c"  => '',
            // "npsp__Matching_Gift_Account__c"  => '',
            // "npsp__Matching_Gift_Employer__c"  => '',
            // "npsp__Matching_Gift_Status__c"  => '',
            // "npsp__Matching_Gift__c"  => '',
            // "npsp__Fair_Market_Value__c"  => '',
            // "npsp__In_Kind_Description__c"  => '',
            // "npsp__In_Kind_Donor_Declared_Value__c"  => '',
            // "npsp__In_Kind_Type__c"  => '',
            // "npsp__Ask_Date__c"  => '',
            // "npsp__Closed_Lost_Reason__c"  => '',
            // "npsp__Gift_Strategy__c"  => '',
            // "npsp__DisableContactRoleAutomation__c"  => '',
            // "npsp__Batch_Number__c"  => '',
            // "attributes"  => '',
        ];
        return array_filter($params);

        // $wegiveData = new JsonObject($renderedData);

        // $salesforceData = new JsonObject($params);

        // $fieldMappings = $this->organization->neonMappingRules()->where('integration', NeonMappingRule::DONATION)->get();

        // foreach ($fieldMappings as $map) {
        //     $salesforceData->set("$.{$map->integration_path}", $wegiveData->get("$.{$map->wegive_path}")[0]);
        // }

        // return $salesforceData->getValue();
    }

    public function generatePaymentParams(Transaction $transaction, $update = false)
    {
        if ($update) {
            $params = [
                // "Id" => $transaction->salesforce_payment_id,
                // "IsDeleted" => '',
                // "Name" => '',
                // "CreatedDate" => '',
                // "CreatedById" => '',
                // "LastModifiedDate" => '',
                // "LastModifiedById" => '',
                // "SystemModstamp" => '',
                // "LastViewedDate" => '',
                // "LastReferencedDate" => '',
                // "npe01__Opportunity__c" => $transaction->salesforce_id,
                // "npe01__Check_Reference_Number__c" => '',
                // "npe01__Custom_Payment_Field__c" => '',
                "npe01__Paid__c" => true,
                "npe01__Payment_Amount__c" => ($transaction->amount  - $transaction->fee) / 100,
                "npe01__Payment_Date__c" => $transaction->created_at,
                "npe01__Payment_Method__c" => $transaction->source_type,
                // "npe01__Scheduled_Date__c" => '',
                // "npe01__Written_Off__c" => '',
                // "npsp__Payment_Acknowledged_Date__c" => '',
                // "npsp__Payment_Acknowledgment_Status__c" => '',
                // "Payment_Status__c" => 'Paid',
                // "npsp__Batch_Number__c" => '',
                // "npsp__Elevate_Payment_API_Status__c" => '',
                // "attributes" => '',
            ];

            return array_filter($params);
        }
        $params =  [
            // "Id" => $transaction->salesforce_payment_id,
            // "IsDeleted" => '',
            // "Name" => '',
            // "CreatedDate" => '',
            // "CreatedById" => '',
            // "LastModifiedDate" => '',
            // "LastModifiedById" => '',
            // "SystemModstamp" => '',
            // "LastViewedDate" => '',
            // "LastReferencedDate" => '',
            "npe01__Opportunity__c" => $transaction->salesforce_id,
            // "npe01__Check_Reference_Number__c" => '',
            // "npe01__Custom_Payment_Field__c" => '',
            "npe01__Paid__c" => true,
            "npe01__Payment_Amount__c" => ($transaction->amount  - $transaction->fee_amount) / 100,
            "npe01__Payment_Date__c" => $transaction->created_at,
            "npe01__Payment_Method__c" => $transaction->source_type,
            // "npe01__Scheduled_Date__c" => '',
            // "npe01__Written_Off__c" => '',
            // "npsp__Payment_Acknowledged_Date__c" => '',
            // "npsp__Payment_Acknowledgment_Status__c" => '',
            // "Payment_Status__c" => 'Paid',
            // "npsp__Batch_Number__c" => '',
            // "npsp__Elevate_Payment_API_Status__c" => '',
            // "attributes" => '',
        ];

        return array_filter($params);
    }

    public function generateSoftCreditParams(Transaction $transaction, $update = false)
    {
        $referrer = $transaction->owner->referrer;
        $donorProfile = $referrer->donorProfile($this->organization);

        if ($update) {
            $params = [
                // "Id"  => $transaction->salesforce_soft_credit_id,
                // "IsDeleted"  => '',
                // "Name" => '',
                // "CreatedDate"  => $transaction->created_at,
                // "CreatedById"  => '',
                // "LastModifiedDate"  => '',
                // "LastModifiedById"  => '',
                // "SystemModstamp"  => '',
                // "npsp__Opportunity__c"  => $transaction->salesforce_id,
                // "npsp__Account__c"  => $donorProfile->salesforce_id,
                "npsp__Amount__c"  => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
                // "npsp__Role__c"  => '',
                // "attributes"  => '',
            ];

            return array_filter($params);
        }
        $params = [
            // "Id"  => $transaction->salesforce_soft_credit_id,
            // "IsDeleted"  => '',
            // "Name" => '',
            // "CreatedDate"  => $transaction->created_at,
            // "CreatedById"  => '',
            // "LastModifiedDate"  => '',
            // "LastModifiedById"  => '',
            // "SystemModstamp"  => '',
            "npsp__Opportunity__c"  => $transaction->salesforce_id,
            "npsp__Account__c"  => $donorProfile->salesforce_id,
            "npsp__Amount__c"  => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
            // "npsp__Role__c"  => '',
            // "attributes"  => '',
        ];

        return array_filter($params);
    }

    public function generatePartialSoftCreditParams(Transaction $transaction, $update = false)
    {
        $referrer = $transaction->owner->referrer;
        $donorProfile = $referrer->donorProfile($this->organization);

        if ($update) {
            $params = [
                // "Id"  => $transaction->salesforce_soft_credit_id,
                // "IsDeleted"  => '',
                // "Name" => '',
                // "CreatedDate"  => $transaction->created_at,
                // "CreatedById"  => '',
                // "LastModifiedDate"  => '',
                // "LastModifiedById"  => '',
                // "SystemModstamp"  => '',
                // "npsp__Opportunity__c"  => $transaction->salesforce_id,
                // "npsp__Contact__c"  => $donorProfile->salesforce_id,
                "npsp__Amount__c"  => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
                // "npsp__Role__c"  => '',
                // "attributes"  => '',
            ];

            return array_filter($params);
        }
        $params = [
            // "Id"  => $transaction->salesforce_soft_credit_id,
            // "IsDeleted"  => '',
            // "Name" => '',
            // "CreatedDate"  => $transaction->created_at,
            // "CreatedById"  => '',
            // "LastModifiedDate"  => '',
            // "LastModifiedById"  => '',
            // "SystemModstamp"  => '',
            "npsp__Opportunity__c"  => $transaction->salesforce_id,
            "npsp__Contact__c"  => $donorProfile->salesforce_id,
            "npsp__Amount__c"  => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
            // "npsp__Role__c"  => '',
            // "attributes"  => '',
        ];

        return array_filter($params);
    }

    public function generateRecurringDonationParams(ScheduledDonation $scheduledDonation)
    {

        $donorProfile = $scheduledDonation->source;

        $amount = $scheduledDonation->amount;

        if ($scheduledDonation->cover_fees) {
            $amount += $scheduledDonation->fee_amount;
        }



        $params =  [
            // "OwnerId"  => '',
            // "IsDeleted"  => '',
            // "Name"  => '',
            // "CreatedDate"  => '',
            // "CreatedById"  => '',
            // "LastModifiedDate"  => '',
            // "LastModifiedById"  => '',
            // "SystemModstamp"  => '',
            // "LastActivityDate"  => '',
            // "LastViewedDate"  => '',
            // "LastReferencedDate"  => '',
            "npe03__Amount__c"  => $amount  / 100,
            "npe03__Contact__c"  => $donorProfile->salesforce_id,
            // "npe03__Date_Established__c"  => '',
            // "npe03__Donor_Name__c"  => '',
            // "npe03__Installment_Amount__c"  => $amount,
            "npe03__Installment_Period__c"  => $this->frequencyMap[$scheduledDonation->frequency]['period'],
            // "npe03__Installments__c"  => '',
            // "npe03__Last_Payment_Date__c"  => '',
            "npe03__Next_Payment_Date__c"  => $scheduledDonation->start_date,
            // "npe03__Open_Ended_Status__c"  => '',
            // "npe03__Organization__c"  =>  null,
            // "npe03__Paid_Amount__c"  => '',
            "npe03__Recurring_Donation_Campaign__c"  => $scheduledDonation->campaign_id ? $scheduledDonation->campaign->salesforce_id : null,
            // "npe03__Schedule_Type__c"  => '',
            // "npe03__Total_Paid_Installments__c"  => '',
            // "npe03__Total__c"  => '',
            // "npsp__Always_Use_Last_Day_Of_Month__c"  => '',
            "npsp__Day_of_Month__c"  => $scheduledDonation->start_date->format('j'),
            "npsp__InstallmentFrequency__c"  => $this->frequencyMap[$scheduledDonation->frequency]['frequency'],
            "npsp__PaymentMethod__c"  => $this->paymentMethodMap[$scheduledDonation->payment_method_type],
            // "npsp__RecurringType__c"  => '',
            "npsp__StartDate__c"  => $scheduledDonation->created_at,
            // "npsp__Status__c"  => '',
            // "npsp__ClosedReason__c"  => '',
            // "npsp__CurrentYearValue__c"  => '',
            // "npsp__NextYearValue__c"  => '',
            // "npsp__CommitmentId__c"  => '',
            // "npsp__EndDate__c"  => '',
            "npsp__DisableFirstInstallment__c"  => true,
            // "npsp__CardExpirationMonth__c"  => '',
            // "npsp__CardExpirationYear__c"  => '',
            "npsp__CardLast4__c"  => $scheduledDonation->paymentMethod->last_four,
            // "npsp__LastElevateEventPlayed__c"  => '',
            "npsp__ACH_Last_4__c"  =>  $scheduledDonation->paymentMethod->last_four,
            // "attributes"  => '',
        ];

        return array_filter($params);
    }

    public function generateFundParams(Fund $fund)
    {

        $params = [
            "Name" => $fund->name,
            // "OwnerId" => '',
            // "IsDeleted" => '',
            // "CreatedDate" => '',
            // "CreatedById" => '',
            // "LastModifiedDate" => '',
            // "LastModifiedById" => '',
            // "SystemModstamp" => '',
            // "LastActivityDate" => '',
            // "LastViewedDate" => '',
            // "LastReferencedDate" => '',
            // "npsp__Active__c" => '',
            // "npsp__Average_Allocation__c" => '',
            // "npsp__Description__c" => '',
            // "npsp__First_Allocation_Date__c" => '',
            // "npsp__Largest_Allocation__c" => '',
            // "npsp__Last_Allocation_Date__c" => '',
            // "npsp__Number_of_Allocations_Last_N_Days__c" => '',
            // "npsp__Number_of_Allocations_Last_Year__c" => '',
            // "npsp__Number_of_Allocations_This_Year__c" => '',
            // "npsp__Number_of_Allocations_Two_Years_Ago__c" => '',
            // "npsp__Smallest_Allocation__c" => '',
            // "npsp__Total_Allocations_Last_N_Days__c" => '',
            // "npsp__Total_Allocations_Last_Year__c" => '',
            // "npsp__Total_Allocations_This_Year__c" => '',
            // "npsp__Total_Allocations_Two_Years_Ago__c" => '',
            // "npsp__Total_Allocations__c" => '',
            // "npsp__Total_Number_of_Allocations__c" => '',
            // "attributes" => '',
        ];

        return array_filter($params);
    }


    public function generateAllocationParams(Transaction $transaction, $update = false)
    {
        if ($update) {
            $params = [
                // "npsp__Amount__c" => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
                // "npsp__General_Accounting_Unit__c" => $transaction->fund_id ? $transaction->fund->salesforce_id : null,
                // "npsp__Opportunity__c" => $transaction->salesforce_id,
                "npsp__Percent__c" => 100,
                // "npsp__Recurring_Donation__c" =>  $transaction->scheduled_donation_id ? $transaction->scheduledDonation->salesforce_id : null,
                // "npsp__Campaign__c" => $transaction->element_id ? $transaction->element->campaign->salesforce_id : null,
                // "attributes" => null,
                // "OwnerId" => null,
                // "IsDeleted" => null,
                // "Name" => null,
                // "CreatedDate" => null,
                // "CreatedById" => null,
                // "LastModifiedDate" => null,
                // "LastModifiedById" => null,
                // "SystemModstamp" => null,
            ];

            return array_filter($params);
        }

        $params = [
            "npsp__Amount__c" => ($transaction->amount - $transaction->fee - $transaction->fee_amount) / 100,
            "npsp__General_Accounting_Unit__c" => $transaction->fund_id ? $transaction->fund->salesforce_id : null,
            "npsp__Opportunity__c" => $transaction->salesforce_id,
            "npsp__Percent__c" => 100,
            // "npsp__Recurring_Donation__c" =>  $transaction->scheduled_donation_id ? $transaction->scheduledDonation->salesforce_id : null,
            // "npsp__Campaign__c" => $transaction->element_id ? $transaction->element->campaign->salesforce_id : null,
            // "attributes" => null,
            // "OwnerId" => null,
            // "IsDeleted" => null,
            // "Name" => null,
            // "CreatedDate" => null,
            // "CreatedById" => null,
            // "LastModifiedDate" => null,
            // "LastModifiedById" => null,
            // "SystemModstamp" => null,
        ];

        return array_filter($params);
    }

    // Requests

    public function setupAllTriggers()
    {

        $appUrl = env('APP_URL') . '/api';

        $this->createRequestClass();

        $triggerObjects = [
            "Account" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "Account",
                'body' => "trigger AccountUpdatedOrCreated on Account(after insert, after update) {\n for(Account a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/account');\n }  \n }"
            ],
            "Contact" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "Contact",
                'body' => "trigger ContactUpdatedOrCreated on Contact(after insert, after update) {\n for(Contact a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/contact');\n }  \n }"
            ],
            "Opportunity" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "Opportunity",
                'body' => "trigger OpportunityUpdatedOrCreated on Opportunity(after insert, after update) {\n for(Opportunity a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/opportunity');\n }  \n }"
            ],
            "Payment" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "npe01__OppPayment__c",
                'body' => "trigger PaymentUpdatedOrCreated on npe01__OppPayment__c(after insert, after update) {\n for(npe01__OppPayment__c a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/npe01__OppPayment__c');\n }  \n }"
            ],
            "Campaign" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "Campaign",
                'body' => "trigger CampaignUpdatedOrCreated on Campaign(after insert, after update) {\n for(Campaign a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/campaign');\n }  \n }"
            ],
            "npsp__General_Accounting_Unit__c" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "npsp__General_Accounting_Unit__c",
                'body' => "trigger GAUUpdatedOrCreated on npsp__General_Accounting_Unit__c(after insert, after update) {\n for(npsp__General_Accounting_Unit__c a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/npsp__General_Accounting_Unit__c');\n }  \n }"
            ],
            "npsp__Allocation__c" => [
                "status" => "Active",
                "UsageAfterInsert" => true,
                "UsageAfterUpdate" => true,
                'TableEnumOrId' => "npsp__Allocation__c",
                'body' => "trigger GAUAllocationUpdatedOrCreated on npsp__Allocation__c(after insert, after update) {\n for(npsp__Allocation__c a : Trigger.New) {\n SendRequest.makePostCallout(JSON.serialize(a), '{$appUrl}/salesforce/npsp__Allocation__c');\n }  \n }"
            ],
        ];

        $responses = [];
        foreach ($triggerObjects as $object) {
            $responses[] = $this->createTrigger($object);
        }

        foreach ($responses as $response) {
            if ($response->successful()) {
                $trigger = new SalesforceApexTrigger();
                $trigger->salesforceIntegration()->associate($this);
                $trigger->salesforce_id = $response->json()['id'];
                $trigger->save();
            }
        }
    }

    public function removeAllTriggers()
    {
        $triggers = $this->salesforceApexTriggers;


        foreach ($triggers as $trigger) {
            $response = $this->remove("services/data/v54.0/sobjects/ApexTrigger/{$trigger->salesforce_id}", []);

            if ($response->successful()) {
                $trigger->delete();
            }
        }
    }

    public function createRequestClass()
    {
        $token =  $this->organization->createToken("salesforce_integration")->plainTextToken;

        $body = [
            "status" => "Active",
            "body" => "public class SendRequest {\n @future(callout=true)\n public static void makePostCallout(String serializedData, String endpoint) {\n Http http = new Http();\n HttpRequest request = new HttpRequest();\n request.setEndpoint(endpoint);\n  request.setMethod('POST');\n request.setHeader('Content-Type', 'application/json;charset=UTF-8');\n  request.setHeader('organization', '{$this->organization->id}');\n request.setHeader('wegive-api-key', '{$token}');\n request.setBody(serializedData);\n HttpResponse response = http.send(request);\n // Parse the JSON response\n if(response.getStatusCode() != 201) {\n System.debug('The status code returned was not expected: ' + response.getStatusCode() + ' ' + response.getStatus());} else {\n System.debug(response.getBody());\n }\n }\n }"
        ];

        return $this->post('services/data/v54.0/sobjects/ApexClass', $body);
    }

    public function createTrigger($params)
    {
        return $this->post('services/data/v54.0/sobjects/ApexTrigger', $params);
    }

    public function createRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        return $this->post('services/data/v54.0/sobjects/npe03__Recurring_Donation__c', $this->generateRecurringDonationParams($scheduledDonation));
    }

    public function updateRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        return $this->patch("services/data/v54.0/sobjects/npe03__Recurring_Donation__c/{$scheduledDonation->salesforce_id}", $this->generateRecurringDonationParams($scheduledDonation));
    }


    public function createOpportunity(Transaction $transaction)
    {
        return $this->post("services/data/v54.0/sobjects/Opportunity", $this->generateDonationParams($transaction));
    }

    public function updateOpportunity(Transaction $transaction)
    {
        return $this->patch("services/data/v54.0/sobjects/Opportunity/{$transaction->salesforce_id}", $this->generateDonationParams($transaction, true));
    }

    public function createPayment(Transaction $transaction)
    {
        return $this->post("services/data/v54.0/sobjects/npe01__OppPayment__c", $this->generatePaymentParams($transaction));
    }

    public function updatePayment(Transaction $transaction)
    {
        return $this->patch("services/data/v54.0/sobjects/npe01__OppPayment__c/{$transaction->salesforce_payment_id}", $this->generatePaymentParams($transaction, true));
    }

    public function createSoftCredit(Transaction $transaction)
    {
        if ($transaction->owner->type === 'individual') {
            return $this->post("services/data/v54.0/sobjects/npsp__Partial_Soft_Credit__c", $this->generatePartialSoftCreditParams($transaction));
        }

        return $this->post("services/data/v54.0/sobjects/npsp__Account_Soft_Credit__c", $this->generateSoftCreditParams($transaction));
    }

    public function updateSoftCredit(Transaction $transaction)
    {
        if ($transaction->owner->type === 'individual') {
            return $this->patch("services/data/v54.0/sobjects/npsp__Partial_Soft_Credit__c/{$transaction->salesforce_soft_credit_id}", $this->generatePartialSoftCreditParams($transaction, true));
        }

        return $this->patch("services/data/v54.0/sobjects/npsp__Account_Soft_Credit__c/{$transaction->salesforce_soft_credit_id}", $this->generateSoftCreditParams($transaction, true));
    }

    public function createCampaign(Campaign $campaign)
    {
        return $this->post('services/data/v54.0/sobjects/Campaign', $this->generateCampaignParams($campaign));
    }

    public function updateCampaign(Campaign $campaign)
    {
        return $this->patch("services/data/v54.0/sobjects/Campaign/{$campaign->salesforce_id}", $this->generateCampaignParams($campaign));
    }

    public function createDonor($donorProfile)
    {

        if ($donorProfile->type === 'individual') {

            $data = $this->generateContactParams($donorProfile);

            $response = $this->post('services/data/v54.0/sobjects/Contact', $data);
            return $response;
        }
        return $this->post('services/data/v54.0/sobjects/Account', $this->generateAccountParams($donorProfile));
    }

    public function updateDonor($donorProfile)
    {
        if ($donorProfile->type === 'individual') {
            $data = $this->generateContactParams($donorProfile, $donorProfile->salesforce_id);
            if (empty($data)) return;
            return $this->patch("services/data/v54.0/sobjects/Contact/{$donorProfile->salesforce_id}", $data);
        }
        return $this->patch("services/data/v54.0/sobjects/Account/{$donorProfile->salesforce_id}", $this->generateAccountParams($donorProfile));
    }


    public function createGeneralAccountingUnit(Fund $fund)
    {
        return $this->post('services/data/v54.0/sobjects/npsp__General_Accounting_Unit__c', $this->generateFundParams($fund));
    }

    public function updateGeneralAccountingUnit(Fund $fund)
    {
        return $this->patch("services/data/v54.0/sobjects/npsp__General_Accounting_Unit__c/{$fund->salesforce_id}", $this->generateFundParams($fund));
    }


    public function createAllocation(Transaction $transaction)
    {
        return $this->post('services/data/v54.0/sobjects/npsp__Allocation__c', $this->generateAllocationParams($transaction));
    }

    public function updateAllocation(Transaction $transaction)
    {
        return $this->patch("services/data/v54.0/sobjects/npsp__Allocation__c/{$transaction->salesforce_allocation_id}", $this->generateAllocationParams($transaction, true));
    }


    // Methods

    public function test()
    {
        return Http::post($this->base . "services/oauth2/token?grant_type=password&client_id={$this->client_id}&client_secret={$this->client_secret}&username={$this->username}&password={$this->password}");
    }

    public function syncDonor($donor)
    {

        if (!$this->track_donors || !$this->enabled || !$this->crm_sync) return;

        $donorProfile = $donor;

        if ($donorProfile->salesforce_id) {
            return $this->updateDonor($donor);
        } else {

            $url = "services/data/v54.0/query/?q=SELECT+id,AccountId,LastName,FirstName,Salutation,Name,OtherStreet,OtherCity,OtherState,OtherPostalCode,OtherCountry,MailingStreet,MailingCity,MailingState,MailingPostalCode,Phone,Fax,MobilePhone,HomePhone,OtherPhone,AssistantPhone,Email,Title,Department,Birthdate,Description,HasOptedOutOfEmail, DoNotCall,CreatedDate,LastModifiedDate,npo02__MembershipJoinDate__c, npe01__AlternateEmail__c,RecordType.Name+from+Contact+where+Email+=+'{$donor->email_1}'+ORDER+BY+RecordType.Name+DESC+,+LastModifiedDate+DESC";

            $secondCheck = $this->get($url, null);
            $secondCheckData = $secondCheck->json();

            if ($secondCheck->successful() && $secondCheckData['totalSize'] == 0) {
                $response = $this->createDonor($donor);


                if ($response->successful()) {
                    $contactId = $response->json()['id'];
                    $response = $this->get("services/data/v54.0/sobjects/Contact/{$contactId}", null);

                    if ($response->successful()) {
                        $existingData = $response->json();
                    }
                    $accountId = $existingData['AccountId'];
                    $donorProfile->salesforce_id = $contactId;
                    $donorProfile->salesforce_account_id = $accountId;
                    $donorProfile->saveQuietly();
                }
            } else if ($secondCheck->successful()) {

                $salesforceData = $secondCheckData["records"][0];

                $donorProfile->salesforce_id = $salesforceData['Id'];
                $donorProfile->saveQuietly();

                return $this->updateDonor($donor);
            } else {
                $response = $this->createDonor($donor);


                if ($response->successful()) {
                    $contactId = $response->json()['id'];
                    $response = $this->get("services/data/v54.0/sobjects/Contact/{$contactId}", null);
                    if ($response->successful()) {
                        $existingData = $response->json();
                    }
                    $accountId = $existingData['AccountId'];
                    $donorProfile->salesforce_id = $contactId;
                    $donorProfile->salesforce_account_id = $accountId;
                    $donorProfile->saveQuietly();
                }
            }
        }



        return $response;
    }

    public function syncCampaign(Campaign $campaign)
    {
        if (!$this->track_campaigns || !$this->enabled) return;

        if ($campaign->salesforce_id) {
            $response = $this->updateCampaign($campaign);
        } else {
            $response = $this->createCampaign($campaign);

            if ($response->successful()) {
                $campaignId = $response->json()['id'];

                $campaign->salesforce_id = $campaignId;
                $campaign->saveQuietly();
            }
        }



        return $response;
    }

    public function syncSoftCredit(Transaction $transaction)
    {
        if (!$this->track_donors || !$this->track_donations || !$this->enabled || !$this->crm_sync) return;


        if ($transaction->salesforce_soft_credit_id) {
            $response = $this->updateSoftCredit($transaction);
        } else {
            $response = $this->createSoftCredit($transaction);

            if ($response->successful()) {
                $id = $response->json()['id'];

                $transaction->salesforce_soft_credit_id = $id;
                $transaction->saveQuietly();
            }
        }


        return $response;
    }

    public function syncDonation(Transaction $transaction)
    {

        if (!$this->track_donors || !$this->track_donations || !$this->enabled || !$this->crm_sync) return;

        $response =  $this->syncDonor($transaction->owner);

        if ($response && $response->failed()) {
            return $response;
        };

        if ($transaction->element_id) {

            $campaignRequest = $this->syncCampaign($transaction->element->campaign);;

            if ($campaignRequest && $campaignRequest->failed()) {
                return $campaignRequest;
            }
        }
        if ($transaction->fund_id) {
            $fundRequest = $this->syncFund($transaction->fund);

            if ($fundRequest && $fundRequest->failed()) {
                return $fundRequest;
            }
        }

        if ($transaction->salesforce_id) {
            $opportunityRequest = $this->updateOpportunity($transaction);


            if ($opportunityRequest && $opportunityRequest->failed()) {
                return $opportunityRequest;
            }

            $paymentRequest = $this->updatePayment($transaction);

            if ($paymentRequest && $paymentRequest->failed()) {
                return $paymentRequest;
            }
        } else {
            $opportunityRequest = $this->createOpportunity($transaction);

            if ($opportunityRequest->successful()) {
                $salesforceId = $opportunityRequest->json()['id'];
                $transaction->salesforce_id = $salesforceId;
                $transaction->saveQuietly();

                $paymentRequest = $this->createPayment($transaction);

                if ($paymentRequest && $paymentRequest->failed()) {
                    return $paymentRequest;
                }

                $salesforcePaymentId = $paymentRequest->json()['id'];
                $transaction->salesforce_payment_id = $salesforcePaymentId;
                $transaction->saveQuietly();
            }
        }

        if ($transaction->fund_id) {
            $allocationRequest = $this->syncAllocation($transaction);

            if ($allocationRequest->failed()) {
                return $allocationRequest;
            }
        }

        if ($transaction->owner->referrer) {
            $referrerRequest = $this->syncDonor($transaction->owner->referrer);
            $softCreditRequest = $this->syncSoftCredit($transaction);

            if ($referrerRequest->failed()) {
                return $referrerRequest;
            }

            if ($softCreditRequest->failed()) {
                return $softCreditRequest;
            }
        }


        return $opportunityRequest;
    }

    public function syncAllocation(Transaction $transaction)
    {
        if (!$this->track_donors || !$this->track_donations || !$this->enabled || !$this->crm_sync) return;


        if ($transaction->salesforce_allocation_id) {
            $response = $this->updateAllocation($transaction);
        } else {
            $response = $this->createAllocation($transaction);

            if ($response->successful()) {
                $id = $response->json()['id'];

                $transaction->salesforce_allocation_id = $id;
                $transaction->saveQuietly();
            } else {
                Log::error($response->json());
            }
        }


        return $response;
    }

    public function syncRecurringDonation(ScheduledDonation $scheduledDonation)
    {
        if (!$this->track_donors || !$this->track_recurring_donations  || !$this->enabled || !$this->crm_sync) return;

        $response =  $this->syncDonor($scheduledDonation->source);

        if ($response && $response->failed()) return;

        if ($scheduledDonation->salesforce_id) {
            $response = $this->updateRecurringDonation($scheduledDonation);
        } else {
            $response = $this->createRecurringDonation($scheduledDonation);

            if ($response->successful()) {
                $recurringDonationId = $response->json()['id'];

                $scheduledDonation->salesforce_id = $recurringDonationId;
                $scheduledDonation->saveQuietly();
            }
        }


        return $response;
    }

    public function syncFund(Fund $fund)
    {

        if (!$this->track_designations || !$this->enabled || !$this->crm_sync) return;

        if ($fund->salesforce_id) {
            $response = $this->updateGeneralAccountingUnit($fund);
        } else {
            $response = $this->createGeneralAccountingUnit($fund);

            if ($response->successful()) {
                $id = $response->json()['id'];
                $fund->salesforce_id = $id;
                $fund->saveQuietly();
            }
        }

        return $response;
    }
}
