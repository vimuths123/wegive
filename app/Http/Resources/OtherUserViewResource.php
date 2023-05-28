<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OtherUserViewResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => "{$this->first_name} {$this->last_name}",
            'giving_categories' => $this->givingCircle(),
            'public' => $this->is_public,
            'is_me' => $this->is_me,
            'stats' => $this->stats(),
            'followings' => UserSimpleResource::collection($this->followings()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
            'followers' => UserSimpleResource::collection($this->followers()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()),
            'categories' => $this->categories,
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers ?? []),

            'scheduled_donations' => ScheduledDonationResource::collection($this->scheduled_donations ?? []),
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'is_following' => $this->followers()->whereNull('accepted_at')->whereNull('requested_at')->orWhereNotNull('requested_at')->whereNotNull('accepted_at')->get()->contains(auth()->user()),
            'is_requested' => $this->followers()->whereNotNull('requested_at')->whereNull('accepted_at')->get()->contains(auth()->user())
        ];
    }
}
