<?php

namespace App\Http\Resources;

use App\Models\Donor;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $transaction = parent::toArray($request);
        $otherValues =  [
            'scheduledDonation' => new ScheduledDonationResource($this->scheduledDonation()->withTrashed()->get()->first()),
            'amount' => round($this->amount / 100, 2),
            'fee_amount' => round($this->fee_amount / 100, 2),
            'fee' => round($this->fee / 100, 2),
            'owner' => $this->handleOwner(),
            'status' => Transaction::STATUS_MAP[$this->status],
            'tribute_name' => $this->tribute_name,
            'tribute' => $this->tribute,
            'tribute_message' => $this->tribute_message,
            'tribute_email' => $this->tribute_email,
            'campaign' => $this->campaign_id ? new CampaignTableResource($this->campaign) : null,
            'communications' =>  CommunicationResource::collection($this->communications),
            'fund' => $this->fund
        ];



        return array_merge($transaction, $otherValues);
    }

    public function handleOwner()
    {
        if ($this->anonymous) return (object) ['name' => 'Anonymous'];
        if ($this->owner instanceof Donor) {
            return new DonorSimpleResource($this->owner);
        }
        return null;
    }
}
