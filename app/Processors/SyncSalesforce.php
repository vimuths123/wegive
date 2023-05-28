<?php

namespace App\Processors;


use App\Jobs\ImportGau;
use App\Jobs\ImportAccount;
use App\Jobs\ImportContact;
use App\Jobs\ImportCampaign;
use App\Jobs\ImportOpportunity;
use App\Models\SalesforceIntegration;


class SyncSalesforce
{


    protected $salesforceIntegration = null;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesforceIntegration $salesforceIntegration)
    {
        $this->salesforceIntegration = $salesforceIntegration;
    }




    public function syncAccountsHandler($url = 'services/data/v54.0/query/?q=SELECT+id, name, type, BillingStreet, BillingState, BillingCity, BillingPostalCode, ShippingStreet, ShippingState, ShippingCity, ShippingPostalCode, phone, fax, website+from+Account')
    {
        $response = $this->salesforceIntegration->get($url, null);

        $data = $response->json();

        foreach ($data['records'] as $account) {
            ImportAccount::dispatch($this->salesforceIntegration, $account);
        }

        if ($data['nextRecordsUrl']) {
            $this->syncAccountsHandler($data['nextRecordsUrl']);
        } else {
            return;
        }
    }


    public function syncOpportunitiesHandler($url = 'services/data/v54.0/query/?q=SELECT+id,name,description, AccountId,ContactId, StageName, Amount, Probability,CloseDate,IsClosed,IsWon,CampaignId,CreatedDate, npe01__Is_Opp_From_Individual__c, npe03__Recurring_Donation__c, npsp__Recurring_Donation_Installment_Number__c, npsp__Tribute_Type__c+from+Opportunity')
    {

        // $url = "services/data/v54.0/query/?q=SELECT+id,name,description, AccountId,ContactId, StageName, Amount, Probability,CloseDate,IsClosed,IsWon,CampaignId,CreatedDate, npe01__Is_Opp_From_Individual__c, npe03__Recurring_Donation__c, npsp__Recurring_Donation_Installment_Number__c, npsp__Tribute_Type__c+from+Opportunity+WHERE+ContactId+=+'0033h000009y7FNAAY'"

        $response = $this->salesforceIntegration->get($url, null);

        $data = $response->json();

        foreach ($data['records'] as $opportunity) {
            ImportOpportunity::dispatch($this->salesforceIntegration, $opportunity);
        }

        if ($data['nextRecordsUrl']) {
            $this->syncOpportunitiesHandler($data['nextRecordsUrl']);
        } else {
            return;
        }
    }


    public function syncCampaigns($url = 'services/data/v54.0/query/?q=SELECT+id,name,type,status,StartDate,EndDate,IsActive,Description,CreatedDate+from+Campaign')
    {
        $response = $this->salesforceIntegration->get($url, null);

        $data = $response->json();

        foreach ($data['records'] as $campaign) {
            ImportCampaign::dispatch($this->salesforceIntegration, $campaign);
        }

        if ($data['nextRecordsUrl']) {
            $this->syncCampaigns($data['nextRecordsUrl']);
        } else {
            return;
        }
    }



    public function syncContacts($url = 'services/data/v54.0/query/?q=SELECT+id,AccountId,LastName,FirstName,Salutation,Name,OtherStreet,OtherCity,OtherState,OtherPostalCode,OtherCountry,MailingStreet,MailingCity,MailingState,MailingPostalCode,Phone,Fax,MobilePhone,HomePhone,OtherPhone,AssistantPhone,Email,Title,Department,Birthdate,Description,HasOptedOutOfEmail, DoNotCall,CreatedDate, npe01__AlternateEmail__c+from+Contact')
    {
        $response = $this->salesforceIntegration->get($url, null);

        $data = $response->json();

        foreach ($data['records'] as $contact) {
            ImportContact::dispatch($this->salesforceIntegration, $contact);
        }

        if ($data['nextRecordsUrl']) {
            $this->syncContacts($data['nextRecordsUrl']);
        } else {
            return;
        }
    }



    public function syncGaus($url = 'services/data/v54.0/query/?q=SELECT+id,name+from+npsp__General_Accounting_Unit__c')
    {
        $response = $this->salesforceIntegration->get($url, null);

        $data = $response->json();

        foreach ($data['records'] as $gau) {
            ImportGau::dispatch($this->salesforceIntegration, $gau);
        }

        if ($data['nextRecordsUrl']) {
            $this->syncGaus($data['nextRecordsUrl']);
        } else {
            return;
        }
    }
}
