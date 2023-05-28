<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
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
            'stats' => $this->stats(),
            'followers' => UserSimpleResource::collection($this->followers()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
            'followings' => UserSimpleResource::collection($this->followings()->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
            'impact' => PostSimpleResource::collection($this->impact),
            'categories' => $this->categories,
            'scheduled_donations' => $this->scheduled_donations,
            'avatar' => $this->avatar,
            'follows_me' => $this->loadFollowsMe(),
            'is_following' => $this->loadIsFollowing(),
        ];
    }
}
