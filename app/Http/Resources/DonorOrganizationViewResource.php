<?php

namespace App\Http\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorOrganizationViewResource extends JsonResource
{


    public function toArray($request)
    {


        $donorProfile = $this->donorProfile($this->organization);

        $donations = $donorProfile ? $donorProfile->donations : $this->donations;

        return [
            'id' => $this->id,
            'type' => 'donor',
            'name' => $donorProfile->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_at' => $donorProfile ? $donorProfile->created_at : $this->created_at,
            'accounts' => $this->accounts,
            'scheduled_donations' => ScheduledDonationResource::collection($donorProfile ? $donorProfile->scheduledDonations->sortByDesc('created_at') : $this->scheduledDonations->sortByDesc('created_at')),
            'recent_donations' => TransactionTableResource::collection($donorProfile ? $donorProfile->donations->sortByDesc('created_at')->take(10) : $this->donations->sortByDesc('created_at')->take(10)),
            'stats' => $this->organizationStats($this->organization),
            'events' => $this->organizationActivity($this->organization),
            'avatar' =>  $this->getFirstMedia('avatar') ?  $this->getFirstMedia('avatar')->getUrl() : null,
            'addresses' => $donorProfile->addresses,
            'mobile_phone' => $donorProfile->mobile_phone,
            'home_phone' => $donorProfile->home_phone,
            'office_phone' => $donorProfile->office_phone,
            'other_phone' => $donorProfile->other_phone,
            'fax' => $donorProfile->fax,
            'handle' => $donorProfile->handle,
            'email_1' => $donorProfile->email_1,
            'email_2' => $donorProfile->email_2,
            'email_3' => $donorProfile->email_3,
            'short_name' => $donorProfile->first_name ?? $donorProfile->name,
            'first_name' => $donorProfile->first_name,
            'last_name' => $donorProfile->last_name,
            'donor_profile_type' => $donorProfile->getMorphClass(),
            'donor_profile_id' => $donorProfile->id,
            'profile_privacy' => $donorProfile->profile_privacy,
            'dollar_amount_privacy' => $donorProfile->dollar_amount_privacy,
            'include_name' => $donorProfile->include_name,
            'include_profile_picture' => $donorProfile->include_profile_picture,
            'desktop_notifications' => $donorProfile->desktop_notifications,
            'mobile_notifications' => $donorProfile->mobile_notifications,
            'email_notifications' => $donorProfile->email_notifications,
            'sms_notifications' => $donorProfile->sms_notifications,
            'general_communication' => $donorProfile->general_communication,
            'marketing_communication' => $donorProfile->marketing_communication,
            'donation_updates_receipts' => $donorProfile->donation_updates_receipts,
            'impact_stories_use_of_funds' => $donorProfile->impact_stories_use_of_funds,
            'total_given' => round($this->donations($this->organization)->sum('amount') / 100, 2),
            'total_given_this_year' => round($this->donations($this->organization)->where('created_at', '>=', date('Y-m-d', strtotime(date('Y-01-01'))))->sum('amount') / 100, 2),
            'communications' => CommunicationResource::collection($donorProfile->receivedCommunications->sortByDesc('created_at')),
            'all_activity' => ActivityResource::collection($this->myActivity($this->organization)->get()->sortByDesc('created_at')),
            'impact_numbers' => $donorProfile->impactNumbers,
            'enabled' => $donorProfile->enabled,
            'posts' => PostSimpleResource::collection($donorProfile->posts),
            'total_donated' => round($donorProfile->total_donated / 100, 2),
            'total_fundraised' => round($donorProfile->total_fundraised / 100, 2),
            'total_referrals' => $donorProfile->total_referrals,
            'donations_by_year' => $donations->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('Y');
            }),
            'fundraisers' => FundraiserTableResource::collection($this->fundraisers->sortByDesc('created_at')),
        ];
    }
}
