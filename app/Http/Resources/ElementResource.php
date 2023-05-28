<?php

namespace App\Http\Resources;

use App\Models\Checkout;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'slug' => $this->slug,
            'id' => $this->id,
            'name' => $this->name,
            'elementable_id' => $this->elementable_id,
            'elementable_type' => $this->elementable_type,
            'campaign_slug' => $this->campaign->slug,
            'donations' => TransactionTableResource::collection($this->donations),
            'templates' => MessageTemplateViewResource::collection($this->messageTemplates),
            'donors' => DonorSimpleResource::collection($this->donors),
            'elementable' => $this->handleElementable(),
            'donation_count' => $this->donation_count,
            'net_donation_volume' => $this->net_donation_volume,
            'recurring_donation_volume' => $this->recurring_donation_volume,
            'one_time_donation_volume' => $this->one_time_donation_volume,
        ];
    }


    public function handleElementable()
    {
        if ($this->resource->elementable instanceof Checkout) {
            return new CheckoutResource($this->resource->elementable);
        }


        return null;
    }
}
