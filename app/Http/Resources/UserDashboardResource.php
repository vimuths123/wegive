<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDashboardResource extends JsonResource
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
            'accounts' => $this->accounts,
            'preferred_payment' => $this->processPreferredPayment(),

            'id' => $this->id,
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'created_at' => $this->created_at,
            'phone' => $this->phone,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'first_name' => $this->first_name,
            'handle' => $this->handle,
            'last_name' => $this->last_name,
            'name' => "{$this->first_name} {$this->last_name}",
            'email' => $this->email,
            'organizations' => OrganizationDashboardResource::collection($this->organizations),

        ];
    }

    private function processPreferredPayment()
    {
        if ($this->resource->preferredPayment instanceof Card) {
            return new CardResource($this->resource->preferredPayment);
        }

        if ($this->resource->preferredPayment instanceof Bank) {
            return new BankResource($this->resource->preferredPayment);
        }

        return [];
    }
}
