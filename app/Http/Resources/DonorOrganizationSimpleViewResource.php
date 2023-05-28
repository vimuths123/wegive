<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonorOrganizationSimpleViewResource extends JsonResource
{


    public function toArray($request)
    {


        $donorProfile = $this->donorProfile($this->organization);

        return [
            'id' => $this->id,
            'type' => $donorProfile->getMorphClass(),
            'mobile_phone' => $donorProfile->mobile_phone,
            'home_phone' => $donorProfile->home_phone,
            'office_phone' => $donorProfile->office_phone,
            'other_phone' => $donorProfile->other_phone,
            'fax' => $donorProfile->fax,
            'email_1' => $donorProfile->email_1,
            'email_2' => $donorProfile->email_2,
            'email_3' => $donorProfile->email_3,
            'name' => $donorProfile->name,
            'total_given' => round($this->donations($this->organization)->sum('amount') / 100, 2),
            'total_given_this_year' => round($this->donations($this->organization)->where('created_at', '>=', date('Y-m-d', strtotime(date('Y-01-01'))))->sum('amount') / 100, 2),


        ];
    }
}
