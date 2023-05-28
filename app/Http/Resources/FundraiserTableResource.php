<?php

namespace App\Http\Resources;

use App\Models\Donor;
use App\Models\Givelist;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class FundraiserTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'             => $this->slug,
            'slug'           => $this->slug,
            'percent_raised' => $this->goal ? round($this->totalRaised() / 100, 2) / round($this->goal / 100, 2) : 0,
            'goal'           => round($this->goal / 100, 2),
            'total_raised'   => round($this->totalRaised() / 100, 2),
            'description'    => $this->description,
            'name'           => $this->name,
            'type'           => 'fundraiser',
            'expiration_at'  => $this->expiration,
            'owner'          => $this->handleOwner(),
            'recipient'      => $this->processRecipient(),
            'publicity'      => $this->publicity,
            'active'         => $this->active,
            'thumbnail'      => $this->getFirstMedia('thumbnail') ? $this->getFirstMedia('thumbnail')->getUrl() : null

        ];
    }

    private function processRecipient()
    {
        if ($this->resource->recipient instanceof Donor) {
            return new DonorSimpleResource($this->resource->recipient);
        }

        if ($this->resource->recipient instanceof Organization) {
            return new OrganizationTableResource($this->resource->recipient);
        }

        if ($this->resource->recipient instanceof Givelist) {
            return new GivelistTableResource($this->resource->recipient);
        }

        return null;
    }

    public function handleOwner()
    {
        if ($this->resource->owner instanceof Donor) {
            return new DonorSimpleResource($this->resource->owner);
        }

        if ($this->resource->owner instanceof Household) {
            return $this->owner;
        }

        if ($this->resource->owner instanceof Organization) {
            return new OrganizationSimpleResource($this->resource->owner);
        }

        return null;
    }
}
