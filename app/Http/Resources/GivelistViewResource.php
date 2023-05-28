<?php

namespace App\Http\Resources;

use App\Models\Donor;
use Illuminate\Http\Resources\Json\JsonResource;

class GivelistViewResource extends JsonResource
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
            'description' => $this->description,
            'name' => $this->name,
            'categories' => CategoryTableResource::collection($this->categories),
            'creator' => $this->handleCreator(),
            'active' => $this->active,
            'is_public' => $this->is_public,
            'impact' => $this->impact,
            'organizations' => OrganizationSimpleResource::collection($this->organizations),
            'givers' => UserSimpleResource::collection($this->givers),
            'given_by_you' => $this->givenByDonor($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'given_this_year_by_you' => $this->givenThisYearByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'fundraised_by_you' => $this->fundraisedByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
            'thumbnail' => $this->getFirstMedia('thumbnail') ?  $this->getFirstMedia('thumbnail')->getUrl() : null,
            'type' => 'givelist'
        ];
    }

    public function handleCreator()
    {
        if ($this->creator instanceof Donor) {
            return new DonorSimpleResource($this->creator);
        }

        return null;
    }
}
