<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignDonorPortalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $campaign = parent::toArray($request);
        return array_merge($campaign, [
            'goal' => $this->goal / 100,
            'elements' => ElementResource::collection($this->elements),
            'message_templates' => MessageTemplateViewResource::collection($this->messageTemplates()->get()),
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers),
            'net_donation_volume' => $this->net_donation_volume / 100,
            'gross_donation_volume' => $this->gross_donation_volume  / 100,
            'recurring_donation_volume' => $this->recurring_donation_volume / 100,
            'one_time_donation_volume' => $this->one_time_donation_volume / 100,
            'last_activity' => $this->donations()->latest()->first()->created_at ?? null,
            'checkout' => new CheckoutResource($this->checkout),
            'recent_donations' => $this->fundraiser_show_activity ? TransactionDonorPortalResource::collection($this->recentDonations()) : null,
            'leader_board' => $this->fundraiser_show_leader_board ? $this->leaderBoard() : null,
            'number_of_donors' => count($this->donorsWithDescendants()->get()),
            'fundraiser_show_child_fundraiser_campaign' => $this->fundraiser_show_child_fundraiser_campaign,
            'child_campaigns' => $this->fundraiser_show_child_fundraiser_campaign ? CampaignTableResource::collection(Campaign::whereIn('id', $this->getFamilyIds())->where('type', 1)->where('id', '!=', $this->id)->get()) : [],
        ]);
    }
}
