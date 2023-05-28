<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostViewResource extends JsonResource
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
            'id'           => $this->id,
            'media'        => $this->getMedia('media')->map(function ($image) {
                return ['url' => $image->getUrl() ?? null, 'id' => $image->id];
            }),
            'organization' => new OrganizationTableResource($this->organization),
            'comments'     => CommentResource::collection($this->comments),
            'individuals'  => DonorSimpleResource::collection($this->donors->where('type', 'individual')),
            'companies'    => DonorSimpleResource::collection($this->donors->where('type', 'company')),
            'title'        => $this->title,
            'content'      => $this->content,
            'created_at'   => $this->created_at,
        ];
    }
}
