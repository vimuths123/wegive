<?php

namespace App\Http\Resources;

use App\Models\Givelist;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorPortalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $donorPortal =  parent::toArray($request);

        $manuallyGenerated = ['checkout' => new CheckoutResource($this->checkout), 'recipient' => $this->handleRecipient(), 'impact_number' => $this->impactNumber];

        return array_merge($donorPortal, $manuallyGenerated);
    }

    public function handleRecipient()
    {

        if ($this->recipient instanceof Organization) {
            return new OrganizationTableResource($this->recipient);
        }


        if ($this->recipient instanceof Givelist) {
            return new GivelistTableResource($this->recipient);
        }

        return null;
    }
}
