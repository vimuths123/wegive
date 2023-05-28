<?php

namespace App\Http\Resources;

use App\Http\Resources\ActivityResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesforceIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $salesforceIntegration =  parent::toArray($request);

        $manuallyGenerated = [
            'activity_log' =>  ActivityResource::collection($this->actions->sortByDesc('created_at'))
        ];

        return array_merge($salesforceIntegration, $manuallyGenerated);
    }
}
