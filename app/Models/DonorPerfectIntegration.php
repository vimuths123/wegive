<?php

namespace App\Models;

use DateTime;
use Exception;
use DateTimeZone;
use Illuminate\Support\Str;
use Gaarf\XmlToPhp\Convertor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\CausesActivity as TraitsCausesActivity;

class DonorPerfectIntegration extends Model
{
    use HasFactory;
    use TraitsCausesActivity;

    protected $guarded = ['id'];

    // Actual user who created
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function getBaseUrlAttribute($token = null)
    {

        return "https://www.donorperfect.net/prod/xmlrequest.asp?apikey={$this->api_key}";
    }



    public function makeRequest($params)
    {
        return Http::get($this->base_url  . $params);
    }

    public function convertXmlToArray($xml)
    {
        $array = [];
        foreach ($xml->children() as $items) {
            $subArray = [];

            foreach ($items->children() as $item) {
                $subArray[strval($item['name'])] = strval($item['value']);
            }

            $array[] = $subArray;
        }

        return $array;
    }

    public function convertXmlResponseToArray($xml)
    {
        $array = [];
        foreach ($xml->children() as $items) {
            foreach ($items->children() as $item) {
                $array['id'] = strval($item['value']);
            }
        }


        return $array;
    }

    public function importTodaysGifts()
    {

        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('America/Los_Angeles'));
        $formattedDate = $dt->format("n/d/Y");

        $params = "&action=select * from dpgift where gift_date = '{$formattedDate}'";

        $response = $this->makeRequest($params);

        if ($response->failed()) return $response;

        $gifts =  simplexml_load_string($response->body());

        $giftsArray = $this->convertXmlToArray($gifts);

        foreach ($giftsArray as $gift) {
            $this->importGift($gift);
        }
    }



    public function importGifts($donorId)
    {

        $params = "&action=dp_gifts&params=@donor_id={$donorId}";

        $response = $this->makeRequest($params);

        if ($response->failed()) return $response;

        $gifts =  simplexml_load_string($response->body());

        $giftsArray = $this->convertXmlToArray($gifts);


        foreach ($giftsArray as $gift) {
            $this->importGift($gift);
        }
    }

    public function importGift($gift)
    {

        $donorProfile = Donor::where('dp_id', $gift['donor_id'])->where('organization_id', $this->organization_id)->get()->first();


        if (!$donorProfile) return;

        if ($donorProfile->transactions()->whereNotNull('dp_id')->where('dp_id', $gift['gift_id'])->first()) return;


        $transaction = new Transaction();
        $organization = Organization::find($this->organization_id);
        $transaction->amount = $gift['amount'] * 100;
        $transaction->description = 'Donor Perfect Import';
        $transaction->created_at = $gift['gift_date2'] ?? $gift['gift_date'];
        $transaction->owner()->associate($donorProfile);
        $transaction->source()->associate($organization);
        $transaction->destination()->associate($organization);
        $transaction->status = Transaction::STATUS_SUCCESS;
        $transaction->cover_fees = $gift['donorCoveredFee'] ?? false;
        $transaction->dp_id = $gift['gift_id'];
        $transaction->saveQuietly();
    }

    public function importTodaysDonors()
    {
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('America/Los_Angeles'));
        $formattedDate = $dt->format("n/d/Y");

        $params = "&action=select * from dp where created_date = '{$formattedDate}'";

        $response = $this->makeRequest($params);

        if ($response->failed()) return $response;


        $donors =  simplexml_load_string($response->body());

        $donorsArray = $this->convertXmlToArray($donors);


        foreach ($donorsArray as $donor) {
            $this->importDonor($donor);
        }

        return;
    }

    public function importDonors()
    {
        $params = "&action=select * from dp";

        $response = $this->makeRequest($params);

        if ($response->failed()) return $response;


        $donors =  simplexml_load_string($response->body());

        $donorsArray = $this->convertXmlToArray($donors);


        foreach (array_slice($donorsArray, 0, 100) as $donor) {
            $this->importDonor($donor);
        }

        return;
    }

    public function importDonor($donor)
    {

        if (!array_key_exists('email', $donor)) return;

        if (!$donor['email'] || $donor["email"] == "" || !filter_var($donor["email"], FILTER_VALIDATE_EMAIL)) return;

        $user = User::where('email', $donor['email'])->get()->first();


        if (!$user) {
            try {
                $user = new User();
                $user->first_name = $donor['first_name'];
                $user->last_name = $donor['last_name'];
                $user->email = $donor['email'];
                $user->phone = $donor['mobile_phone'];
                $user->password = Hash::make(Str::random());
                $user->save();
            } catch (Exception $e) {
                dump($e);
                return;
            }
        }

        // check if donor profile by id exists yet

        $logins = $user->logins()->where('loginable_type', 'donor')->get();

        $donorProfile = null;

        foreach ($logins as $login) {
            if ($login->loginable->organization()->is(Organization::find($this->organization_id))) {
                $donorProfile = $login->loginable;
                break;
            }
        }

        if ($donorProfile) {
            // add neon id to donor profile,sync donations
            $donorProfile->dp_id = $donor['donor_id'];
            $donorProfile->name = $donor['first_name'] . ' ' . $donor['last_name'];

            $donorProfile->saveQuietly();
        } else {
            $donorModel = new Donor();
            $donorModel->first_name = $donor['first_name'];
            $donorModel->last_name = $donor['last_name'];
            $donorModel->name = $donor['first_name'] . ' ' . $donor['last_name'];
            $donorModel->email_1 = $donor['email'];
            $donorModel->home_phone = $donor['home_phone'];
            $donorModel->fax = $donor['fax_phone'];
            $donorModel->office_phone = $donor['business_phone'];
            $donorModel->mobile_phone = $donor['mobile_phone'];
            $donorModel->dp_id = $donor['donor_id'];

            $donorModel->organization_id = $this->organization_id;
            $donorModel->saveQuietly();

            $login = new Login();
            $login->user()->associate($user);
            $login->loginable()->associate($donorModel);
            $login->save();
        }


        $this->importGifts($donor['donor_id']);
    }




    public function createGift(Transaction $transaction)
    {

        $transactionAmount = ($transaction->amount - $transaction->fee) / 100;

        $formattedDate = date("m/d/Y", strtotime($transaction->created_at));
        $pledgePayment = $transaction->scheduled_donation_id ? 'Y' : 'N';
        $pledgeId = $transaction->scheduled_donation_id ? $transaction->scheduledDonation->dp_id : null;

        $params = "&action=dp_savegift&params=@gift_id=0, @donor_id='{$transaction->owner->dp_id}', @record_type='G',@gift_date='{$formattedDate}', @amount={$transactionAmount}, @gl_code=NULL,@solicit_code=NULL, @sub_solicit_code=NULL, @campaign=NULL,@gift_type=NULL, @split_gift='N', @pledge_payment='{$pledgePayment}', @reference=NULL, @transaction_id=NULL, @memory_honor=NULL,@gfname=NULL, @glname=NULL, @fmv=0, @batch_no=0,@gift_narrative='Gift narrative text', @ty_letter_no=NULL, @glink=NULL,@plink={$pledgeId}, @nocalc='N', @old_amount=NULL, @receipt='N', @user_id='WeGive', @gift_aid_date=NULL, @gift_aid_amt=NULL,@gift_aid_eligible_g=NULL, @currency='USD'";

        return $this->makeRequest($params);
    }

    public function updateGift(Transaction $transaction)
    {
        $transactionAmount = ($transaction->amount - $transaction->fee) / 100;
        $formattedDate = date("m/d/Y", strtotime($transaction->created_at));

        $pledgePayment = $transaction->scheduled_donation_id ? 'Y' : 'N';
        $pledgeId = $transaction->scheduled_donation_id ? $transaction->scheduledDonation->dp_id : null;

        $params = "&action=dp_savegift&params=@gift_id={$transaction->dp_id}, @donor_id='{$transaction->owner->dp_id}', @record_type='G',@gift_date='{$formattedDate}', @amount={$transactionAmount}, @gl_code=NULL,@solicit_code=NULL, @sub_solicit_code=NULL, @campaign=NULL,@gift_type=NULL, @split_gift='N', @pledge_payment='{$pledgePayment}', @reference=NULL, @transaction_id=NULL, @memory_honor=NULL,@gfname=NULL, @glname=NULL, @fmv=0, @batch_no=0,@gift_narrative='Gift narrative text', @ty_letter_no=NULL, @glink=NULL,@plink={$pledgeId}, @nocalc='N', @old_amount=NULL, @receipt='N', @user_id='WeGive', @gift_aid_date=NULL, @gift_aid_amt=NULL,@gift_aid_eligible_g=NULL, @currency='USD'";



        return $this->makeRequest($params);
    }


    public function syncGift(Transaction $transaction)
    {

        if ($transaction->dp_id) {
            return $this->updateGift($transaction);
        }


        $this->syncDonor($transaction->owner);
        $response = $this->createGift($transaction);

        if ($response->successful()) {
            $xml =  simplexml_load_string($response->body());

            $responseObject = $this->convertXmlResponseToArray($xml);

            $transaction->dp_id = $responseObject['id'];
            $transaction->saveQuietly();
        }

        return $response;
    }


    public function createDonor($donor)
    {

        $params = "&action=dp_savedonor&params=@donor_id=0,@first_name='{$donor->first_name}',@last_name='{$donor->last_name}',@middle_name=null,@suffix=null,@title=null,@salutation=null,@prof_title=null,@opt_line=null,@address=null,@address2=null,@city=null,@state=null,@zip=null,@country=null,@address_type=null,@home_phone='{$donor->home_phone}',@business_phone='{$donor->office_phone}',@fax_phone='{$donor->fax}',@mobile_phone='{$donor->mobile_phone}',@email='{$donor->email_1}',@org_rec='N',@donor_type='IN',@nomail='N',@nomail_reason=null,@narrative=null,@user_id='WeGive'";

        return $this->makeRequest($params);
    }

    public function updateDonor($donor)
    {

        $params = "&action=dp_savedonor&params=@donor_id='{$donor->dp_id}',@first_name='{$donor->first_name}',@last_name='{$donor->last_name}',@middle_name=null,@suffix=null,@title=null,@salutation=null,@prof_title=null,@opt_line=null,@address=null,@address2=null,@city=null,@state=null,@zip=null,@country=null,@address_type=null,@home_phone='{$donor->home_phone}',@business_phone='{$donor->office_phone}',@fax_phone='{$donor->fax}',@mobile_phone='{$donor->mobile_phone}',@email='{$donor->email_1}',@org_rec='N',@donor_type='IN',@nomail='N',@nomail_reason=null,@narrative=null,@user_id='WeGive'";

        return $this->makeRequest($params);
    }


    public function syncDonor($donorProfile)
    {
        if ($donorProfile->dp_id) {
            return $this->updateDonor($donorProfile);
        }

        $response = $this->createDonor($donorProfile);

        if ($response->successful()) {
            $xml =  simplexml_load_string($response->body());

            $responseObject = $this->convertXmlResponseToArray($xml);

            $donorProfile->dp_id = $responseObject['id'];
            $donorProfile->saveQuietly();
        }
        return $response;
    }


    public function updateScheduledDonation(ScheduledDonation $scheduledDonation)
    {
        $donor = $scheduledDonation->source;
        $donationAmount = $scheduledDonation->amount;

        if ($scheduledDonation->cover_fees) {
            $donationAmount += $scheduledDonation->fee_amount;
        }

        $donationAmount = $donationAmount / 100;

        $params = "&action=dp_savepledge&params=@gift_id='{$scheduledDonation->dp_id}',@donor_id='{$donor->dp_id}',@gift_date='{$scheduledDonation->created_at}',@start_date='{$scheduledDonation->created_at}',@total=0,@bill='{$donationAmount}',@frequency='M',@reminder='N',@gl_code=null,@solicit_code=null,@initial_payment='N',@sub_solicit_code=null,@writeoff_amount=0.00,@writeoff_date=null,@user_id='WeGive',@campaign=null,@membership_type=NULL,@membership_level=NULL,@membership_enr_date=NULL,@membership_exp_date=NULL,@membership_link_id=NULL,@address_id=NULL,@gift_narrative=null,@ty_letter_no=null,@vault_id=NULL,@receipt_delivery_g=null,@contact_id=NULL";

        return $this->makeRequest($params);
    }

    public function createScheduledDonation(ScheduledDonation $scheduledDonation)
    {
        $donor = $scheduledDonation->source;

        $donationAmount = $scheduledDonation->amount;

        if ($scheduledDonation->cover_fees) {
            $donationAmount += $scheduledDonation->fee_amount;
        }

        $donationAmount = $donationAmount / 100;


        $params = "&action=dp_savepledge&params=@gift_id=0,@donor_id='{$donor->dp_id}',@gift_date='{$scheduledDonation->created_at}',@start_date='{$scheduledDonation->created_at}',@total=0,@bill='{$donationAmount}',@frequency='M',@reminder='N',@gl_code=null,@solicit_code=null,@initial_payment='N',@sub_solicit_code=null,@writeoff_amount=0.00,@writeoff_date=null,@user_id='WeGive',@campaign=null,@membership_type=NULL,@membership_level=NULL,@membership_enr_date=NULL,@membership_exp_date=NULL,@membership_link_id=NULL,@address_id=NULL,@gift_narrative=null,@ty_letter_no=null,@vault_id=NULL,@receipt_delivery_g=null,@contact_id=NULL";

        return $this->makeRequest($params);
    }


    public function sycnScheduledDonation(ScheduledDonation $scheduledDonation)
    {
        if ($scheduledDonation->dp_id) {
            return $this->updateScheduledDonation($scheduledDonation);
        }

        $this->syncDonor($scheduledDonation->source);

        $response = $this->createScheduledDonation($scheduledDonation);

        if ($response->successful()) {
            $xml =  simplexml_load_string($response->body());

            $responseObject = $this->convertXmlResponseToArray($xml);

            $scheduledDonation->dp_id = $responseObject['id'];
            $scheduledDonation->saveQuietly();
        }

        return $response;
    }
}
