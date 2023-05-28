<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoginResource extends JsonResource
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
            'created_at' => $this->created_at,
            'phone' => $this->phone,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
        ];
    }

    private function handledonorSetting()
    {
        if ($this->donorSetting) {
            return $this->donorSetting;
        }

        $this->donorSetting()->create();
        $this->save();
        return $this->donorSetting;
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
