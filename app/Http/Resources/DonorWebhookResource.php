<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class DonorWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {


        return [
            'id' => $this->id,
            'handle' => null,
            'facebook_link' => null,
            'twitter_link' => null,
            'linkedin_link' => null,
            'created_at' => $this->created_at,
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers),
            'scheduled_donations' => ScheduledDonationResource::collection($this->scheduledDonations()->with(['destination', 'paymentMethod'])->get()),
            'addresses' => $this->addresses,
            'mobile_phone' => $this->mobile_phone,
            'home_phone' => $this->home_phone,
            'office_phone' => $this->office_phone,
            'other_phone' => $this->other_phone,
            'fax' => $this->fax,
            'email_1' => $this->email_1,
            'email_2' => $this->email_2,
            'email_3' => $this->email_3,
            'name' => $this->name,
            'employer_name' => null,
            'short_name' => $this->first_name ?? $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'wallet_balance' => $this->walletBalance,
            'preferred_payment' =>  $this->preferredPayment ? new AccountResource($this->preferredPayment) : [],
            'type' => 'donor',
            'donor_profile_type' => $this->getMorphClass(),
            'donor_profile_id' => $this->id,
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'profile_privacy' => $this->profile_privacy,
            'dollar_amount_privacy' => $this->dollar_amount_privacy,
            'include_name' => $this->include_name,
            'include_profile_picture' => $this->include_profile_picture,
            'desktop_notifications' => $this->desktop_notifications,
            'mobile_notifications' => $this->mobile_notifications,
            'email_notifications' => $this->email_notifications,
            'sms_notifications' => $this->sms_notifications,
            'general_communication' => $this->general_communication,
            'marketing_communication' => $this->marketing_communication,
            'donation_updates_receipts' => $this->donation_updates_receipts,
            'impact_stories_use_of_funds' => $this->impact_stories_use_of_funds,

        ];
    }
}
