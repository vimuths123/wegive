<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $donorProfile = $this->owner;
        return [
            "id" => $this->id,
            "description" => $this->description,
            "amount" => $this->amount / 100,
            "tip" => $this->fee,
            "status" => $this->status,
            "payment_method_type" => $this->source_type,
            "payment_method_id" => $this->source_id,
            "donor_type" => $this->owner_type,
            "donor_id" => $this->owner_id,
            "donor_mobile_phone" => $donorProfile->mobile_phone,
            "donor_home_phone" => $donorProfile->home_phone,
            "donor_office_phone" => $donorProfile->office_phone,
            "donor_other_phone" => $donorProfile->other_phone,
            "addresses" => $donorProfile->addresses,
            "donor_fax" => $donorProfile->fax,
            "donor_email_1" => $donorProfile->email_1,
            "donor_email_2" => $donorProfile->email_2,
            "donor_email_3" => $donorProfile->email_3,
            "donor_name" => $donorProfile->name,
            "donor_first_name" => $donorProfile->first_name,
            "donor_last_name" => $donorProfile->last_name,
            "fund_id" => $this->fund_id,
            "fund_name" => $this->fund_id ? $this->fund->name : null,
            "fundraiser_id" => $this->fundraiser_id,
            "fundraiser_name" => $this->fundraiser_id ? $this->fundraiser->name : null,
            "scheduled_donation_id" => $this->scheduled_donation_id,
            "scheduled_donation_amount" => $this->scheduled_donation_id ? $this->scheduledDonation->amount : null,
            "scheduled_donation_frequency" => $this->scheduled_donation_id ? $this->scheduledDonation->frequency : null,
            "scheduled_donation_next_giving_date" => $this->scheduled_donation_id ? $this->scheduledDonation->start_date : null,
            "scheduled_donation_iteration" => $this->scheduled_donation_id ? $this->scheduledDonation->iteration : null,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "cover_fees" => $this->cover_fees,
            "fee_amount" => $this->fee_amount,
            "anonymous" => $this->anonymous,
            "tribute_email" => $this->tribute_email,
            "tribute_name" => $this->tribute_name,
            "tribute_message" => $this->tribute_message,
            "tribute" => $this->tribute,
        ];
    }
}
