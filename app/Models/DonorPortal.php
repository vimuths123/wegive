<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DonorPortal extends Model
{
    use HasFactory;

    public const ADD_PAYMENT_BADGE = 1;
    public const MADE_DONATION_BADGE = 2;
    public const RECURRING_GIVING_BADGE = 3;
    public const FUNDRAISING_BADGE = 4;
    public const MET_FUNDRAISING_GOAL_BADGE = 5;

    public const STATUS_MAP = [null, 'added_payment_method', 'made_donation', 'recurring_giving', 'fundraising', 'met_fundraising_goal'];

    public const LIFETIME_IMPACT_QUALIFIER = 1;
    public const TOTAL_GIVEN_QUALIFIER = 2;
    public const GIVEN_THIS_YEAR_QUALIFIER = 3;
    public const TOTAL_FUNDRAISED_QUALIFIER = 4;
    public const RECURRING_DONATION_AMOUNT_QUALIFIER = 5;

    public const TOP_1_PERCENT = 1;
    public const TOP_5_PERCENT = 2;
    public const TOP_10_PERCENT = 3;
    public const TOP_20_PERCENT = 4;
    public const TOP_40_PERCENT = 5;

    public function recipient()
    {
        return $this->morphTo();
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function impactNumber()
    {
        return $this->belongsTo(ImpactNumber::class);
    }
}
