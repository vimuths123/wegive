<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyAdminViewResource extends JsonResource
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
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'handle' => $this->handle,
            'name' => $this->name,
            'is_public' => $this->is_public,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'accounts' => $this->accounts,
            'wallet_balance' => $this->walletBalance() / 100,
            'scheduled_donations' => ScheduledDonationResource::collection($this->scheduledDonations()->with(['destination', 'paymentMethod'])->get()),
            'preferred_payment' => $this->processPreferredPayment(),
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers),
            'givelists' => GivelistTableResource::collection($this->givelists),
            'top_organizations' => $this->topOrganizations(),
            'interests' => InterestResource::collection($this->interests),
            'recent_activity' => ActivityResource::collection($this->activity()->orderByDesc('created_at')->take(4)->get()),
            'type' => 'company',
            'stats' => $this->stats(),
            'employees' => UserSimpleResource::collection($this->employees),
            'donor_portal_config' => $this->donorSetting,
            'matching' => $this->matching,
            'matching_percent' => $this->matching_percent,
            'max_match_amount' => $this->max_match_amount / 100



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
