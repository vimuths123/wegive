<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowRequestResource extends JsonResource
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
            'follower' => new UserSimpleResource($this->follower),
            'requested_at' => $this->requested_at

        ];
    }
}
