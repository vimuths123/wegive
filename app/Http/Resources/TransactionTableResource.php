<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Donor;
use App\Models\Givelist;
use App\Models\Organization;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTableResource extends JsonResource
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

            'owner' => $this->handleOwner(),
            'destination' => $this->handleDestination(),
            'donor' => $this->handleOwner(),
            'source' => $this->handleSource(),
            'amount' => round($this->amount / 100, 2),
            'tip' => round($this->fee / 100, 2),
            'fee' => round($this->fee_amount / 100, 2),
            'cover_fees' => $this->cover_fees,

            'status' => Transaction::STATUS_MAP[$this->status],
            'description' => $this->description,
            'fund' => $this->fund,
            'id' => $this->id,
            'date' => $this->created_at,
            'scheduled_donation_iteration' => $this->scheduled_donation_iteration,
            'scheduled_donation_id' => $this->scheduled_donation_id,
            'campaign' => $this->campaign_id ? $this->campaign->name ??
                'Donor Portal' : 'Donor Portal',


        ];
    }

    public function handleOwner()
    {
        if ($this->anonymous) return (object) ['name' => 'Anonymous'];

        if ($this->owner instanceof Donor) {
            return new DonorSimpleResource($this->owner);
        }

        return ['name' => 'Guest User'];
    }

    public function handleSource()
    {
        if ($this->source()->withTrashed()->get()->first() instanceof Donor) {
            return new DonorSimpleResource($this->source()->withTrashed()->get()->first());
        }

        if ($this->source()->withTrashed()->get()->first() instanceof Bank) {
            return new BankResource($this->source()->withTrashed()->get()->first());
        }

        if ($this->source()->withTrashed()->get()->first() instanceof Card) {
            return new CardResource($this->source()->withTrashed()->get()->first());
        }

        return [];
    }

    public function handleDestination()
    {

        if ($this->destination instanceof Donor) {
            return new DonorSimpleResource($this->destination);
        }

        if ($this->destination instanceof Organization) {
            return new OrganizationTableResource($this->destination);
        }


        if ($this->destination instanceof Givelist) {
            return new GivelistTableResource($this->destination);
        }


        return null;
    }
}
