<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDonorResource extends JsonResource
{
    protected $organization;

    public function organization($value)
    {
        $this->organization = $value;
        return $this;
    }
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
            'type' => 'user',
            'name' => "{$this->first_name} {$this->last_name}",
            'email' => $this->email,
            'phone' => $this->phone,
            'created' => $this->created_at,
            'accounts' => $this->accounts,
            'address' => $this->addressString,
            'scheduled_donations' => ScheduledDonationResource::collection($this->scheduledDonations()->where([['destination_id', $this->organization->id], ['destination_type', 'organization']])->get()),
            'stats' => $this->organizationStats($this->organization),
            'events' => $this->organizationActivity($this->organization),
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null
        ];
    }
}
