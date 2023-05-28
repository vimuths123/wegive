<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSimpleOrganizationResource extends JsonResource
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
            'name' => "{$this->first_name} {$this->last_name}",
            'email' => $this->email,
            'id' => $this->id,
            'accounts' => $this->accounts,


        ];
    }
}
