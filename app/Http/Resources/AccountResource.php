<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->handleAccount();
    }

    private function handleAccount()
    {
        if ($this->resource instanceof Card) {
            return new CardResource($this);
        }

        if ($this->resource instanceof Bank) {
            return new BankResource($this);
        }
    }
}
