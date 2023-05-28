<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'content' => $this->content,
            'created_at' => $this->created_at,
            'images' => $this->getMedia('media'),
            'youtube_url' => $this->youtube_link,
            'organization' => new OrganizationTableResource($this->organization),
            'comments' => CommentResource::collection($this->comments()->get()),
            'type' => 'post',
            'title' => $this->title,



        ];
    }
}
