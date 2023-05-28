<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationViewResource extends JsonResource
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
            'color' => $this->color,
            'tagline' => $this->tagline,
            'slug' => $this->slug,
            'name' => $this->dba ? $this->dba : $this->legal_name,
            'categories' => $this->categories,
            'creator' => $this->user,
            'active' => $this->active,
            'designations' => $this->funds,
            'programs' => $this->programs,
            'impactNumbers' => $this->impactNumbers,
            'is_public' => $this->is_public,
            'impact' => $this->impact,
            'product_codes' => $this->productCodes,
            'donorPortal' => new DonorPortalResource($this->donorPortal),
            'givelists' => GivelistTableResource::collection($this->givelists),
            'givers' => UserSimpleResource::collection($this->givers),
            'given_by_you' => $this->givenByDonor($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'given_this_year_by_you' => $this->givenThisYearByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'fundraised_by_you' => $this->fundraisedByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'year_of_formation' => $this->year_of_formation,
            'mission_statement' => $this->mission_statement,
            'url' => $this->url ?? $this->open990_url,
            'financials' => $this->financials,
            'stats' => $this->userStats($request->user('sanctum')->currentLogin ?? $request->user('sanctum') ?? null),
            'thumbnail' => $this->getFirstMedia('thumbnail') ?  $this->getFirstMedia('thumbnail')->getUrl() : null,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
            'tagline' => $this->tagline,
            'program_expense' => $this->program_expense,
            'funraising_expense' => $this->funraising_expense,
            'total_revenue' => $this->total_revenue,
            'total_expenses' => $this->total_expenses,
            'general_expense' => $this->general_expense,
            'total_assets' => $this->total_assets,
            'total_liabilities' => $this->total_liabilities,
            'type' => 'organization',
            'fundraisers' => FundraiserTableResource::collection($this->myFundraisers($request->user('sanctum'))),
            'recent_activity' => $this->myActivity($request->user('sanctum')),
            'donor_level' => $this->donorLevel($request->user('sanctum')->currentLogin ?? $request->user('sanctum') ?? null),
            'google_tag_manager_container_id' => $this->google_tag_manager_container_id,
            'google_analytics_measurement_id' => $this->google_analytics_measurement_id,
            'facebook_pixel_id' => $this->facebook_pixel_id

        ];
    }
}
