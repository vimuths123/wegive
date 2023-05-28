<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductViewResource extends JsonResource
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
            'quantity' => 0,
            'name' => $this->name,
            'id' => $this->id,

            'description' => $this->description,
            'remaining_availability' => $this->remaining_availability,
            'owner' => $this->owner,
            'price' => $this->price,
            'purchasers' => UserSimpleResource::collection($this->purchasers()),

        ];
    }
}
