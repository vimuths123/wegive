<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryDescendantResource extends JsonResource
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
            'thumbnail' => $this->getFirstMedia('thumbnail') ?  $this->getFirstMedia('thumbnail')->getUrl() : null,
            'name' => $this->name,
            'color' => $this->color,
            'descendants' => CategoryTableResource::collection($this->getDescendantsHelper())
        ];
    }
}
