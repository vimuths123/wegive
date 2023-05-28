<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class Household extends Model
{
    use HasFactory;

    public const FAMILY = 1;
    public const COMPANY = 2;
    public const FUNDRAISING_TEAM = 3;
    public const CHURCH = 4;


    protected $fillable = ['name', 'type'];


    public function members()
    {
        return $this->belongsToMany(Donor::class, 'household_donor')->withTimestamps();
    }

    public function activeRecurringGiving(Organization $organization)
    {

        $donorIds = $this->members->pluck('id');

        return ScheduledDonation::where('source_type', 'donor')->whereIn('source_id', $donorIds)->where('destination_type', 'organization')->where('destination_id', $organization->id);
    }

    public function fundraisers(Organization $organization)
    {

        $donorIds = $this->members->pluck('id');

        return Fundraiser::where('owner_type', 'donor')->whereIn('owner_id', $donorIds)->where('recipient_type', 'organization')->where('recipient_id', $organization->id);
    }

    public function impactNumbers(Organization $organization)
    {

        $donorIds = $this->members->pluck('id');

        $impactNumberIds = DB::table('impact_number_donors')->whereIn('donor_id', $donorIds)->get()->pluck('impact_number_id');

        $impactNumbers = ImpactNumber::whereIn('id', $impactNumberIds)->where('organization_id', $organization->id);

        return $impactNumbers;
    }

    public function impactPosts(Organization $organization)
    {

        $donorIds = $this->members->pluck('id');

        $postIds = DB::table('post_donors')->whereIn('donor_id', $donorIds)->get()->pluck('impact_number_id');

        $posts = Post::whereIn('id', $postIds)->where('organization_id', $organization->id);

        return $posts;
    }

    public function recentActivity(Organization $organization)
    {
        $donorIds = $this->members->pluck('id');


        return Activity::where('causer_type', 'donor')->whereIn('causer_id', $donorIds)->where('subject_type', 'organization')->where('subject_id', $organization->id);
    }

    public function impactGraph(Organization $organization , $year = 2022)
    {
        return ['donated' => $this->donationStats($organization, $year), 'fundraised' => $this->fundraiserStats($organization, $year)];
    }



    public function donationStats(Organization $organization, $year = 2022)
    {
        $donorIds = $this->members->pluck('id');


        if (!$year || $year === 'all') {
            $endDate = now()->getTimestamp();
            $createdAt = $this->created_at;
            $startDate = strtotime($createdAt);



            $yms = array();

            $totalSecondsDiff = abs($endDate - $startDate);
            $totalMonthsDiff  = round($totalSecondsDiff / 60 / 60 / 24 / 30);


            $now = now()->format('Y-m');

            for ($x = $totalMonthsDiff; $x >= 0; $x--) {
                $ym = date('m-Y', strtotime($now . " -$x month"));
                $yms[$ym] = [];
            }

            $transactions = $organization->receivedTransactions()->where('owner_type', 'donor')->whereIn('owner_id', $donorIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();


            $transactionsLast12 = $transactions->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });



            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }


        $transactions = $organization->receivedTransactions()->where('owner_type', 'donor')->whereIn('owner_id', $donorIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

        $startDate = new DateTime(); // Today
        $startDate->setDate($year, 12, 31);

        $endDate = new DateTime(); // Today
        $endDate->setDate($year, 12, 31)->modify("-12 months");

        $yms = array();
        $now = $startDate->format('Y-m');

        for ($x = 11; $x >= 0; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $transactionsLast12 = $transactions->where('created_at', '>=', $endDate)->where('created_at', '<=', $startDate)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($transactionsLast12 as $key => $value) {
            $yms[$key] = $value;
        }

        return $yms;
    }


    public function fundraiserStats(Organization $organization, $year = 2022)
    {

        $donorIds = $this->members->pluck('id');


        if (!$year || $year === 'all') {
            $endDate = now()->getTimestamp();
            $createdAt = $this->created_at;
            $startDate = strtotime($createdAt);



            $yms = array();

            $totalSecondsDiff = abs($endDate - $startDate);
            $totalMonthsDiff  = round($totalSecondsDiff / 60 / 60 / 24 / 30);


            $now = now()->format('Y-m');

            for ($x = $totalMonthsDiff; $x >= 0; $x--) {
                $ym = date('m-Y', strtotime($now . " -$x month"));
                $yms[$ym] = [];
            }

            $fundraiserIds = $organization->fundraisers()->where('owner_type', 'donor')->whereIn('owner_id', $donorIds)->pluck('id')->all();



            $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();


            $transactionsLast12 = $transactions->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });



            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }


        $fundraiserIds = $organization->fundraisers()->where('owner_type', 'donor')->whereIn('owner_id', $donorIds)->pluck('id')->all();



        $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

        $startDate = new DateTime(); // Today
        $startDate->setDate($year, 12, 31);

        $endDate = new DateTime(); // Today
        $endDate->setDate($year, 12, 31)->modify("-12 months");

        $yms = array();
        $now = $startDate->format('Y-m');

        for ($x = 11; $x >= 0; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $transactionsLast12 = $transactions->where('created_at', '>=', $endDate)->where('created_at', '<=', $startDate)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($transactionsLast12 as $key => $value) {
            $yms[$key] = $value;
        }

        return $yms;
    }
}
