<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpactNumberResource extends JsonResource
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
            'id'                      => $this->id,
            'static'                  => $this->static,
            'name'                    => $this->name,
            'number'                  => $this->number,
            'start_date'              => $this->start_date,
            'end_date'                => $this->end_date,
            'include_on_organization' => $this->include_on_organization,
            'individuals'             => DonorSimpleResource::collection($this->donors->where('type', 'individual')),
            'companies'               => DonorSimpleResource::collection($this->donors->where('type', 'company'))
        ];
    }
}
