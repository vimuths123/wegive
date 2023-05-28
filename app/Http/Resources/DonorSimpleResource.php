<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonorSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'type' => $this->type,
            'mobile_phone' => $this->mobile_phone,
            'home_phone' => $this->home_phone,
            'office_phone' => $this->office_phone,
            'other_phone' => $this->other_phone,
            'fax' => $this->fax,
            'email_1' => $this->email_1,
            'email_2' => $this->email_2,
            'email_3' => $this->email_3,
            'name' => $this->name,
            'total_given' => round($this->donations($this->organization)->sum('amount') / 100, 2),
            'total_given_this_year' => round($this->donations($this->organization)->where('created_at', '>=', date('Y-m-d', strtotime(date('Y-01-01'))))->sum('amount') / 100, 2),
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null
        ];
    }
}
