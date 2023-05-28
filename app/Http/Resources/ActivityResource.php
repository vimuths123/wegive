<?php

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function handleSubject()
    {
        switch ($this->subject_type) {
            case 'bank':
                return new BankResource($this->subject()->withTrashed()->get()->first());
            case 'card':
                return new CardResource($this->subject()->withTrashed()->get()->first());

            case 'comment':
                return new CommentResource($this->subject()->withTrashed()->get()->first());

            case 'fund':
                return new FundraiserTableResource($this->subject()->withTrashed()->get()->first());

            case 'fundraiser':
                return new FundraiserTableResource($this->subject()->withTrashed()->get()->first());

            case 'givelist':
                return new GivelistTableResource($this->subject()->withTrashed()->get()->first());

            case 'organization':
                return new OrganizationTableResource($this->subject()->withTrashed()->get()->first());

            case 'post':
                return new PostResource($this->subject()->withTrashed()->get()->first());

            case 'purchase':
                return new PurchaseResource($this->subject()->withTrashed()->get()->first());

            case 'scheduled_donation':
                return new ScheduledDonationResource($this->subject()->withTrashed()->get()->first());

            case 'transaction':
                return new TransactionTableResource($this->subject()->withTrashed()->get()->first());
            case 'user':
                return new UserSimpleResource($this->subject()->withTrashed()->get()->first());
        }
    }

    public function handleCauser()
    {
        switch ($this->causer_type) {
            case 'user':
                return ['name' => $this->causer->name];
        }
        return $this->causer;
    }

    public function toArray($request)
    {
        $createdAt = new DateTime($this->created_at);
        return [
            'description' => $this->description,
            'subject' => $this->handleSubject(),
            'causer' => $this->handleCauser(),
            'subject_id' => $this->subject_id,
            'subject_type' => $this->subject_type,
            'causer_id' => $this->causer_id,
            'causer_type' => $this->causer_type,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'properties' => $this->properties,
            'type' => 'activity'
        ];
    }
}
