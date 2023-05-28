<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationTableResource extends JsonResource
{
    /**
     * Transform the resource into an ar
     * ray.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->dba ? $this->dba : $this->legal_name,
            'categories' => CategoryTableResource::collection($this->categories),
            'id' => $this->id,
            'slug' => $this->slug,
            'given_this_year_by_you' => $this->givenThisYearByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'fundraised_by_you' => $this->fundraisedByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'thumbnail' => $this->getFirstMedia('thumbnail') ?  $this->getFirstMedia('thumbnail')->getUrl() : null,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
            'type' => 'organization',
            'legal_name' => $this->legal_name,
            'dba' => $this->dba,
            'url' => $this->url,
            'ein' => $this->formattedEin(),
        ];
    }
}
