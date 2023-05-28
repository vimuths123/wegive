<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
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
            'name' => $this->name,
            'created_at' => $this->created_at,
            'last_four' => $this->last_four,
            'issuer' => $this->issuer,
            'expiration' => $this->expiration,
            'expires_at' => $this->expires_at,
            'owner_type' => $this->expires_at,
            'owner_id' => $this->owner_id,
            'type' => 'card'
        ];
    }
}
