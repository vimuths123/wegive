<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Models\Organization;
use Illuminate\Http\Request;

class ZapierController extends Controller
{

    public function isAuthorized(Request $request)
    {
        $request->validate([
            'api_key' => 'required',
            'organization' => 'required',
        ]);

        $apiKey = $request->api_key;

        $organization = Organization::findOrFail($request->organization);

        $token =  \Laravel\Sanctum\PersonalAccessToken::findToken($apiKey);

        return $token->tokenable()->is($organization);
    }

    public function getAccessToken(Request $request, Organization $organization)
    {
        $user = auth()->user();

        if (!$user)
            abort(401, 'Unauthenticated');

        $login = $user->logins()->where('loginable_type', 'organization')->where('loginable_id', $organization->id)->first();
        if ($login) {
            $token =  $organization->createToken("zapier_{$user->first_name}_{$user->last_name}")->plainTextToken;

            return ['token' => $token];
        }

        abort(401, 'Unauthenticated');
    }

    public function testConnection(Request $request)
    {

        $request->validate([
            'api_key' => 'required',
            'organization' => 'required',
        ]);

        $apiKey = $request->api_key;

        $organization = Organization::findOrFail($request->organization);

        $token =  \Laravel\Sanctum\PersonalAccessToken::findToken($apiKey);

        return $token->tokenable()->is($organization);
    }


    public function donationSample(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $t = Organization::find($request->organization)->transactions()->first();

        return array([
            "id" => 1,
            "user_id" => null,
            "description" => "Seeded",
            "amount" => 25.13,
            "tip" => 0,
            "status" => "Success",
            "payment_method_type" => "card",
            "payment_method_id" => 1,
            "donor_type" => "individual",
            "donor_id" => 1,
            "donor_mobile_phone" => null,
            "donor_home_phone" => null,
            "donor_office_phone" => null,
            "donor_other_phone" => null,
            "donor_fax" => null,
            "donor_email_1" => null,
            "donor_email_2" => null,
            "donor_email_3" => null,
            "donor_name" => null,
            "donor_first_name" => null,
            "donor_last_name" => null,
            "fund_id" => null,
            "fund_name" => null,
            "fundraiser_id" => null,
            "fundraiser_name" => null,
            "scheduled_donation_id" => null,
            "scheduled_donation_amount" => null,
            "scheduled_donation_frequency" => null,
            "scheduled_donation_next_giving_date" => null,
            "scheduled_donation_iteration" => 1,
            "created_at" => null,
            "updated_at" => null,
            "cover_fees" => 0,
            "fee_amount" => 0,
            "anonymous" => 0,
            "tribute_email" => null,
            "tribute_name" => null,
            "tribute_message" => null,
            "tribute" => 0,
        ]);
    }

    public function newDonationSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::NEW_DONATION;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }

    public function newDonationUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::NEW_DONATION)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function updatedDonationUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::UPDATED_DONATION)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function updatedDonationSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::UPDATED_DONATION;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }

    public function donorSample(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');



        return array([
            "id" => 1,
            "handle" => "firstlast123",
            "facebook_link" => null,
            "twitter_link" => null,
            "linkedin_link" => null,
            "donor_profile_id" => 10,
            "created_at" => "2022-02-02T16:26:27.000000Z",
            "addresses" => [[
                "address_1" => null,
                "address_2" => null,
                "city" => null,
                "state" => null,
                "zip" => null,
                "type" => 'mailing',
                "primary" => true,

            ]],
            "mobile_phone" => 1111111111,
            "home_phone" => 1111111111,
            "office_phone" => 1111111111,
            "other_phone" => 1111111111,
            "fax" => 1111111111,
            "email_1" => "alden.veum@example.org",
            "email_2" => "alden.veum@example.org",
            "email_3" => "alden.veum@example.org",
            "name" => "Clifford Simonis",
            "short_name" => "Clifford",
            "first_name" => "Clifford",
            "last_name" => "Simonis",
            "wallet_balance" => 0,
            "type" => "donor",
            "donor_profile_type" => "individual",
            "avatar" => null,
            "profile_privacy" => 1,
            "dollar_amount_privacy" => 3,
            "include_name" => 1,
            "include_profile_picture" => 1,
            "desktop_notifications" => 1,
            "mobile_notifications" => 1,
            "email_notifications" => 1,
            "sms_notifications" => 1,
            "general_communication" => 1,
            "marketing_communication" => 1,
            "donation_updates_receipts" => 1,
            "impact_stories_use_of_funds" => 1,
        ]);
    }

    public function newDonorSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::NEW_DONOR;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }

    public function newDonorUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::NEW_DONOR)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function updatedDonorUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::UPDATED_DONOR)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function updatedDonorSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::UPDATED_DONOR;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }

    public function newOrUpdatedDonorUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::NEW_OR_UPDATED_DONOR)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function newOrUpdatedDonorSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::NEW_OR_UPDATED_DONOR;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }

    public function newOrUpdatedDonationUnsubscribe(Request $request)
    {
        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = Webhook::where('organization_id', $request->organization)->where('action', Webhook::NEW_OR_UPDATED_DONATION)->first();

        if ($webhook) {
            $webhook->delete();
        }

        return;
    }

    public function newOrUpdatedDonationSubscribe(Request $request)
    {

        if (!$this->isAuthorized($request)) abort(401, 'Unauthenticated');

        $webhook = new Webhook();
        $webhook->action = Webhook::NEW_OR_UPDATED_DONATION;
        $webhook->url = $request->hookUrl;
        $webhook->organization_id = $request->organization;
        $webhook->save();

        return $webhook;
    }
}
