<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $orgId = $request->headers->get('organization');
        $organization = $orgId ? Organization::find($orgId) : $this->organization;

        return [
            'members' => DonorSimpleResource::collection($this->members),
            'active_recurring_giving' => ScheduledDonationResource::collection($this->activeRecurringGiving($organization)->get()),
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers($organization)->get()),
            'impact_numbers' => [],
            'impact_posts' => [],
            'recent_activity' => ActivityResource::collection($this->recentActivity($organization)->get()),
            'impact_graph' => $this->impactGraph($organization),
            'created_at' => $this->created_at,
            'name' => $this->name,
            'type' => $this->type,
            'id' => $this->id

        ];
    }
}
