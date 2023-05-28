<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Category $category */
        $category = $this->resource;

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'parent' => $this->parent,
            'icon' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'organizations' => OrganizationTableResource::collection($category->organizations()->limit(5)->get()),
            'givelists' => GivelistTableResource::collection($category->givelists()->get()),
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
        ];
    }
}
