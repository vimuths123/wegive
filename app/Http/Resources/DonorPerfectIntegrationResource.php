<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonorPerfectIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $dp =  parent::toArray($request);

        $manuallyGenerated = [
            'activity_log' =>  ActivityResource::collection($this->actions->sortByDesc('created_at'))
        ];

        return array_merge($dp, $manuallyGenerated);
    }
}
