<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignTableResource extends JsonResource
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
            'net_donation_volume' => $this->net_donation_volume,
            'recurring_donation_volume' => $this->recurring_donation_volume,
            'donation_count' => $this->donation_count,
            'donor_count' => $this->donor_count,

            'last_activity' => $this->donations()->latest()->first() ? $this->donations()->latest()->first()->created_at : null

        ];
    }
}
