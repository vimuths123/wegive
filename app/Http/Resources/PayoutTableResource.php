<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutTableResource extends JsonResource
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
            'organization' => new OrganizationResource($this->organization),
            'transactions' => TransactionTableResource::collection($this->transactions),
            'status' => 'Succeeded',
            'amount' => round($this->transactions->sum('amount') / 100, 2),
            'date' => $this->created_at,
            'payment_method' => $this->transactions->count() === 1 ? $this->transactions->first()->source : 'Givelist Aggregate',


        ];
    }
}
