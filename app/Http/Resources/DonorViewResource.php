<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class DonorViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'type' => $this->type,
            'name' => $this->name,
            'households' => $this->households ?? [],
            'email' => $this->email,
            'phone' => $this->phone,
            'created_at' =>  $this->created_at,
            'accounts' => $this->accounts,
            'scheduled_donations' => ScheduledDonationResource::collection($this ? $this->scheduledDonations->sortByDesc('created_at') : $this->scheduledDonations->sortByDesc('created_at')),
            'recent_donations' => TransactionTableResource::collection($this ? $this->donations->sortByDesc('created_at')->take(10) : $this->donations->sortByDesc('created_at')->take(10)),
            'stats' => $this->stats(),
            'events' => $this->activity(),
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'addresses' => $this->addresses,
            'mobile_phone' => $this->mobile_phone,
            'home_phone' => $this->home_phone,
            'office_phone' => $this->office_phone,
            'other_phone' => $this->other_phone,
            'fax' => $this->fax,
            'handle' => $this->handle,
            'email_1' => $this->email_1,
            'email_2' => $this->email_2,
            'email_3' => $this->email_3,
            'short_name' => $this->first_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'donor_profile_type' => $this->getMorphClass(),

            'profile_privacy' => $this->profile_privacy,
            'dollar_amount_privacy' => $this->dollar_amount_privacy,
            'include_name' => $this->include_name,
            'include_profile_picture' => $this->include_profile_picture,
            'desktop_notifications' => $this->desktop_notifications,
            'mobile_notifications' => $this->mobile_notifications,
            'email_notifications' => $this->email_notifications,
            'sms_notifications' => $this->sms_notifications,
            'general_communication' => $this->general_communication,
            'marketing_communication' => $this->marketing_communication,
            'donation_updates_receipts' => $this->donation_updates_receipts,
            'impact_stories_use_of_funds' => $this->impact_stories_use_of_funds,
            'total_given' => round($this->donations($this->organization)->sum('amount') / 100, 2),
            'total_given_this_year' => round($this->donations($this->organization)->where('created_at', '>=', date('Y-m-d', strtotime(date('Y-01-01'))))->sum('amount') / 100, 2),
            // 'communications' => CommunicationResource::collection($this->receivedCommunications->sortByDesc('created_at')) ?? [],
            'all_activity' => ActivityResource::collection($this->myActivity()->get()->sortByDesc('created_at')),
            'impact_numbers' => $this->impactNumbers,
            'enabled' => $this->enabled,
            'posts' => PostSimpleResource::collection($this->posts),
            'total_donated' => round($this->total_donated / 100, 2),
            'total_fundraised' => round($this->total_fundraised / 100, 2),
            'total_referrals' => $this->total_referrals,
            'donations_by_year' => $this->donations->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('Y');
            }),
            'total_lifetime_impact' => round($this->total_donated / 100, 2) + round($this->total_fundraised / 100, 2),


            'fundraisers' => FundraiserTableResource::collection($this->fundraisers->sortByDesc('created_at')),
            'first_gift' => $this->donations->sortBy('created_at')->first(),
            'primary_user' => $this->getPrimaryUser(),
            'users' => $this->getUsers(),
            'birthdate' => $this->birthdate,
            'notes' => $this->notes

        ];
    }

    private function getPrimaryUser()
    {
        $mostRecentLogin = $this->logins()->get()->sortByDesc('last_login_at')->first();
        if (!$mostRecentLogin) return null;
        $mostRecentUser = User::find($mostRecentLogin->user_id);
        if (!$mostRecentUser) return null;
        $userDonorResource = new UserDonorResource($mostRecentUser);
        return $userDonorResource->organization($this->organization);
    }

    private function getUsers()
    {
        $uniqueUserIds = User::find($this->logins()->pluck('user_id'));

        return UserSimpleOrganizationResource::collection($uniqueUserIds);
    }
}
