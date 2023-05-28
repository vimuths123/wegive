<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'product' => new ProductResource($this->product),
            'transaction' => new TransactionTableResource($this->transaction),
            'user' => new UserSimpleResource($this->user),
            'purchased_for' => [
                'name' => "{$this->first_name} {$this->last_name}", 'email' => $this->email, 'phone' => $this->phone
            ],
            'purchased_at' => $this->created_at


        ];
    }
}
