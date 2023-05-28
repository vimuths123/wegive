<?php

namespace App\Http\Resources;

use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'new_donation_email' => $this->new_donation_email,
            'new_donor_email' => $this->new_donor_email,
            'new_fundraiser_email' => $this->new_fundraiser_email,
            'notification_frequency' => $this->notification_frequency,
            'loginable' => $this->processLoginable(),
            'donor_profile_type' => $this->loginable->type
        ];
    }

    private function processLoginable()
    {
        if ($this->loginable instanceof Donor) {
            return new DonorSimpleResource($this->loginable);
        }

        if ($this->loginable instanceof Organization) {
            return new OrganizationTableResource($this->loginable);
        }
    }
}
