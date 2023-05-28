<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationSimpleResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->dba ? $this->dba : $this->legal_name,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,

        ];
    }
}
