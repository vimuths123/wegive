<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrivateUserResource extends JsonResource
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
            'name' => $this->name,
            'is_public' => $this->is_public,
            'is_me' => $this->is_me,
            'avatar' => $this->avatar,
            'follows_me' => $this->loadFollowsMe(),
            'is_following' => $this->loadIsFollowing(),
        ];
    }
}
