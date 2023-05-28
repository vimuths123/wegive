<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Givelist;
use App\Models\Organization;
use App\Models\Interest;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $subject = null;


        if ($this->resource->subject instanceof Organization) {
            $subject = new OrganizationTableResource($this->resource->subject);
        }

        if ($this->resource->subject instanceof Givelist) {
            $subject = new GivelistTableResource($this->resource->subject);
        }



        return [
            'id' => $this->id,
            'subject' => $subject,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
        ];
    }
}
