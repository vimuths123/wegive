<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NeonIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $neonIntegration =  parent::toArray($request);

        $manuallyGenerated = [
            'activity_log' =>  ActivityResource::collection($this->actions->sortByDesc('created_at'))
        ];

        return array_merge($neonIntegration, $manuallyGenerated);
    }
}
