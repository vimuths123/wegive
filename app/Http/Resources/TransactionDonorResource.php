<?php

namespace App\Http\Resources;

use App\Models\Donor;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDonorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->handleOwner();
    }

    public function handleOwner()
    {
        if ($this->anonymous) return (object) ['name' => 'Anonymous'];

        if ($this->resource->owner instanceof Donor) {
            return new DonorSimpleResource($this->resource->owner);
        }
        return null;
    }
}
