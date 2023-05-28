<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationDashboardResource extends JsonResource
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
            'legal_name' => $this->legal_name,
            'ein' => $this->formattedEin(),
            'dba' => $this->dba,
            'year_of_formation' => $this->year_of_formation,
            'phone' => $this->phone,
            'url' => $this->url,
            'mission_statement' => $this->mission_statement,
            'program_expense' => $this->program_expense,
            'fundraising_expense' => $this->fundraising_expense,
            'total_revenue' => $this->total_revenue,
            'total_expenses' => $this->total_expenses,
            'general_expense' => $this->general_expense,
            'total_assets' => $this->total_assets,
            'total_liabilities' => $this->total_liabilities,
            'slug' => $this->slug,
            'name' => $this->dba ? $this->dba : $this->legal_name,
            'categories' => $this->categories,
            'campaigns' => $this->campaigns,
            'thumbnail' => $this->getFirstMedia('thumbnail') ?  $this->getFirstMedia('thumbnail')->getUrl() : null,
            'avatar' => $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'banner' => $this->getFirstMedia('banner') ?  $this->getFirstMedia('banner')->getUrl() : null,
            'onboarded' => $this->onboarded,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'funds' => $this->funds,
            'zip' => $this->zip,
            'tagline' => $this->tagline,
            'operation' => $this->operation,
            // 'admins' => UserSimpleResource::collection($this->users),
            'tl_token' => $this->tl_token,
            'donor_group_options' => $this->donorGroupOptions(),
            'donorPortal' => new DonorPortalResource($this->donorPortal),
            'checkout' => new CheckoutResource($this->donorPortal->checkout),
            'color' => $this->color,
            'creator' => $this->user,
            'active' => $this->active,
            'designations' => FundResource::collection($this->funds),
            'posts' => PostSimpleResource::collection($this->posts),
            'programs' => $this->programs,
            'impactNumbers' => $this->impactNumbers,
            'is_public' => $this->is_public,
            'impact' => $this->impact,
            'product_codes' => $this->productCodes,
            'givelists' => GivelistTableResource::collection($this->givelists),
            'givers' => UserSimpleResource::collection($this->givers),
            'given_this_year_by_you' => $this->givenThisYearByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'fundraised_by_you' => $this->fundraisedByUser($request->user('sanctum')->currentLogin ?? $request->user('sanctum')),
            'financials' => $this->financials,
            'stats' => $this->userStats($request->user('sanctum')->currentLogin ?? $request->user('sanctum') ?? null),
            'type' => 'organization',
            'fundraisers' => FundraiserTableResource::collection($this->myFundraisers($request->user('sanctum'))),
            'recent_activity' => $this->myActivity($request->user('sanctum')),
            'team_members' => UserSimpleResource::collection($this->teamMembers()),
            'donors' => [],
            'neon_integration' => new NeonIntegrationResource($this->neonIntegration),
            'salesforce_integration' => new SalesforceIntegrationResource($this->salesforceIntegration),
            'donor_perfect_integration' => new DonorPerfectIntegrationResource($this->donorPerfectIntegration),
            'neon_mapping_rules' => $this->neonMappingRules,
            'invites' => $this->invites,
            'google_tag_manager_container_id' => $this->google_tag_manager_container_id,
            'google_analytics_measurement_id' => $this->google_analytics_measurement_id,
            'facebook_pixel_id' => $this->facebook_pixel_id

        ];
    }
}
