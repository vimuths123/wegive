<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Givelist;
use App\Models\Organization;
use App\Models\ScheduledDonation;
use App\Http\Resources\BankResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\GivelistTableResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\OrganizationTableResource;

class GivelistScheduledDonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $destination = null;


        if ($this->resource->destination instanceof Organization) {
            $destination = new OrganizationTableResource($this->resource->destination);
        }

        if ($this->resource->destination instanceof Givelist) {
            $destination = new GivelistTableResource($this->resource->destination);
        }


        $payment_method = null;


        if ($this->resource->paymentMethod instanceof Card) {
            $payment_method = new CardResource($this->resource->paymentMethod);
        }

        if ($this->resource->paymentMethod instanceof Bank) {
            $payment_method = new BankResource($this->resource->paymentMethod);
        }

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->destination_type,
            'locked' => $this->locked,
            'destination' => $destination,
            'frequency' => ScheduledDonation::DONATION_FREQUENCY_MAP[$this->frequency] ?? null,
            'start_date' => $this->start_date,
            'paused_at' => $this->paused_at,
            'tip' => $this->tip,
            'cover_fees' => $this->cover_fees,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_type' => $this->payment_method_type,
            'payment_method' => $payment_method,

        ];
    }
}
