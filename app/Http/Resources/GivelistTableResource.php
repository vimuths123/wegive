<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GivelistTableResource extends JsonResource
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
            'user' => new PublicUserSimpleResource($this->user),
            'is_public' => $this->is_public,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'name' => $this->name,
            'given_by_you' => $this->givenByDonor($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'given_this_year_by_you' => $this->givenThisYearByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'fundraised_by_you' => $this->fundraisedByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'organizations' => OrganizationSimpleResource::collection($this->organizations),
            'type' => 'givelist'
        ];
    }
}
