<?php

namespace App\Http\Resources;

use App\Models\Checkout;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementTableResource extends JsonResource
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
            'campaign' => new CampaignTableResource($this->campaign()->withTrashed()->first()),
            'donation_count' => $this->donation_count,
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
