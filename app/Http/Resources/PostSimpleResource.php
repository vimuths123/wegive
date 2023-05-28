<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostSimpleResource extends JsonResource
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
            'media' => $this->getMedia('media')->map(function ($image) {
                return $image->getUrl() ?? null;
            }),
            'organization' => new OrganizationTableResource($this->organization),
            'comments' => CommentResource::collection($this->comments),
            'content' => $this->content,
            'posted_at' => $this->posted_at,
            'created_at' => $this->created_at,

            'title' => $this->title,

        ];
    }
}
