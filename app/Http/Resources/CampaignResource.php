<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'donations' => TransactionTableResource::collection($this->donationsWithDescendants()->get()),
            'donors' => DonorSimpleResource::collection($this->donorsWithDescendants()->get()),
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers),
            'net_donation_volume' => $this->net_donation_volume,
            'gross_donation_volume' => $this->gross_donation_volume,
            'recurring_donation_volume' => $this->recurring_donation_volume,
            'one_time_donation_volume' => $this->one_time_donation_volume,
            'last_activity' => $this->donationsWithDescendants()->latest()->first()->created_at ?? null,
            'checkout' => new CheckoutResource($this->checkout),
            'parent_campaign' => $this->campaign_id ? new CampaignTableResource($this->parentCampaign) : null,
            'banner' => $this->checkout && $this->checkout->getFirstMedia('banner') ?  $this->checkout->getFirstMedia('banner')->getUrl() : null,

            'child_campaigns' => CampaignTableResource::collection($this->childCampaigns)

        ]);
    }
}
