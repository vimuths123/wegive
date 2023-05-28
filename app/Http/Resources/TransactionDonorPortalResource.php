<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDonorPortalResource extends JsonResource
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


            'amount' => round(($this->amount - $this->fee) / 100, 2),
            'created_at' => $this->created_at,
            'name' => $this->owner->name


        ];
    }
}
