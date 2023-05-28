<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Fundraiser;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewingGroupResource extends JsonResource
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
            'destination' => $this->handleDestination()
        ];
    }

    public function handleDestination()
    {

        if ($this->destination instanceof Fund) {
            return new FundResource($this->destination);
        }

        if ($this->destination instanceof Fundraiser) {
            return new FundraiserTableResource($this->destination);
        }

        return null;
    }
}
