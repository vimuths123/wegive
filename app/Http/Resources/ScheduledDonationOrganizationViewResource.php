<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Givelist;
use App\Models\Organization;
use App\Models\ScheduledDonation;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledDonationOrganizationViewResource extends JsonResource
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


        if ($this->destination()->withTrashed()->get()->first() instanceof Organization) {
            $destination = new OrganizationTableResource($this->destination()->withTrashed()->get()->first());
        }

        if ($this->destination()->withTrashed()->get()->first() instanceof Givelist) {
            $destination = new GivelistTableResource($this->destination()->withTrashed()->get()->first());
        }


        $payment_method = null;


        if ($this->paymentMethod()->withTrashed()->get()->first() instanceof Card) {
            $payment_method = new CardResource($this->paymentMethod()->withTrashed()->get()->first());
        }

        if ($this->paymentMethod()->withTrashed()->get()->first() instanceof Bank) {
            $payment_method = new BankResource($this->paymentMethod()->withTrashed()->get()->first());
        }

        if ($this->paymentMethod()->withTrashed()->get()->first() instanceof User) {
            $payment_method = new UserSimpleResource($this->paymentMethod()->withTrashed()->get()->first());
        }

        return [
            'id' => $this->id,
            'amount' => $this->amount / 100,
            'type' => $this->destination_type,
            'locked' => $this->paymentMethod instanceof User ? !$this->paymentMethod()->is($request->user('sanctum')) : !$this->paymentMethod->owner()->is($request->user('sanctum')),
            'paused_at' => $this->paused_at,
            'destination' => $destination,
            'frequency' => ScheduledDonation::DONATION_FREQUENCY_MAP[$this->frequency] ?? null,
            'start_date' => date_format($this->start_date, 'Y-m-d'),
            'tip' => $this->tip / 100,
            'cover_fees' => $this->cover_fees,
            'fee_amount' => $this->fee_amount / 100,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_type' => $this->payment_method_type,
            'payment_method' => $payment_method ?? [],
            'created_at' => $this->created_at,
            'iteration' => $this->iteration,
            'last_processed' => $this->lastDateProcessed(),
            'tribute_email' => $this->tribute_email,
            'tribute_name' => $this->tribute_name,
            'tribute_message' => $this->tribute_message,
            'paused_type' => $this->paused_type,
            'paused_until' => $this->paused_until,
            'donor' => new DonorSimpleResource($this->source),
            'user' => (new UserDonorResource($this->user))->organization($this->source->organization),
            'donations' => $this->transactions
        ];
    }
}
