<?php

namespace App\Models;

use App\Http\Resources\BankResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\OrganizationSimpleResource;
use App\Notifications\EmailAuthCodeCreated;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Twilio\Rest\Client;

class User extends Authenticatable implements HasMedia, Auditable
{

    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia;
    use \OwenIt\Auditing\Auditable;

    public const DONATION_FREQUENCY_MONTHLY = 1;
    public const DONATION_FREQUENCY_WEEKLY = 2;
    public const DONATION_FREQUENCY_BIMONTHLY = 3;
    public const DONATION_FREQUENCY_MAP = [null, 'monthly', 'weekly', 'bimonthly',];
    public const DONATION_STRING_MAP = ['monthly' => 1, 'weekly' => 2, 'bimonthly' => 3,];

    public const STATUS_DISABLED = 0;
    public const STATUS_ACTIVE = 1;


    public const PUBLIC = 1;
    public const PRIVATE = 2;
    public const DONORS_ONLY = 3;


    protected $guarded = [];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'next_scheduled_donation_at' => 'date',
        // 'monthly_payment' => 'integer',
        // 'givelist_balance' => 'integer',
        // 'next_giving_date' 'datetime',
    ];

    protected $fillable = ['type', 'first_name', 'last_name', 'email', 'password', 'scheduled_donation_frequency', 'scheduled_donation_amount', 'next_scheduled_donation_at', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'is_public', 'handle'];

    protected $childTypes = [
        'admin' => Admin::class,
    ];





    public function getIsMeAttribute(): bool
    {
        $me = $this;

        return $me && $me->id === $this->id;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function donorSetting()
    {
        return $this->morphOne(DonorSetting::class, 'donor');
    }

    public function fundraisers()
    {
        return $this->morphMany(Fundraiser::class, 'owner');
    }

    public function preferredPayment()
    {
        return $this->morphTo();
    }

    public function currentLogin()
    {
        return $this->morphTo();
    }

    public function preferredCategories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function activity()
    {
        return Activity::where([['subject_type', 'user'], ['subject_id', $this->id]])->orWhere([['causer_type', 'user'], ['causer_id', $this->id]]);
    }



    public function followingActivity()
    {
        $followings =  $this->followings->pluck('id')->all();

        return Activity::where('causer_type', 'user')->whereIn('causer_id', $followings);
    }

    public function followingImpact()
    {
        $followings =  $this->followings->pluck('id')->all();

        $orgIds = Transaction::where('owner_type', 'user')->whereIn('owner_id', $followings)->where('destination_type', 'organization')->pluck('destination_id')->all();


        return Post::query()->whereIn('organization_id', array_unique($orgIds));
    }

    public function allImpact()
    {
        $allUsers =  User::all()->pluck('id')->all();

        $orgIds = Transaction::where('owner_type', 'user')->whereIn('owner_id', $allUsers)->where('destination_type', 'organization')->pluck('destination_id')->all();


        return Post::query()->whereIn('organization_id', array_unique($orgIds));
    }


    public function allActivity()
    {
        $allUsers =  User::all()->pluck('id')->all();

        return Activity::where('causer_type', 'user')->whereIn('causer_id', $allUsers);
    }

    public function organizationImpact($organizationId)
    {

        $firstGivenDate = Transaction::where('owner_type', 'user')->where('owner_id', $this->id)->where('destination_type', 'organization')->where('destination_id', $organizationId)->firstOrFail()->created_at;

        return Post::where('organization_id', $organizationId)->where('created_at', '>', $firstGivenDate);
    }

    public function impact()
    {
        $organizationIds = $this->scheduledDonations()->where('destination_type', 'organization')->pluck('destination_id');

        return Post::query()->whereIn('organization_id', $organizationIds);
    }

    public function getImpactAttribute()
    {
        return $this->impact()->get();
    }

    public function getAccountsAttribute()
    {
        return [
            'cards' => CardResource::collection($this->cards),
            'bank_accounts' => BankResource::collection($this->banks),
        ];
    }

    public function getGivingTotalsAttribute()
    {
        $orgTransactionsSum = $this->transactions->where('destination_type', Organization::class)->groupBy('destination_id')->select('*')->selectRaw('sum(amount) as sum')->get();
        $givelistTransactionsSum = $this->transactions->where('destination_type', Givelist::class)->groupBy('destination_id')->select('*')->selectRaw('sum(amount) as sum')->get();

        return [
            'organizations' => $orgTransactionsSum,
            'givelists' => $givelistTransactionsSum
        ];
    }

    public function loadIsFollowing()
    {
        if (!auth()->check()) {
            return false;
        }

        $usersFollowingMe = $this->followings;
        $this->is_following = $usersFollowingMe->pluck('id')->contains($this->id);

        return $this->is_following;
    }

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function loadFollowsMe()
    {
        if (!auth()->check()) {
            return false;
        }

        $usersFollowers = $this->followers;
        $userFollowerUuids = $usersFollowers->pluck('id')->all();
        $this->follows_me = in_array($this->id, $userFollowerUuids);

        return $this->follows_me;
    }

    public function givingCircle()
    {
        $categories = [];
        $categories['uncategorized'] = ['name' => 'Uncategorized', 'amount' => 0, 'count' => 0];
        $organizationsScheduled = $this->scheduledDonations->where('destination_type', 'organization');
        $givelistsScheduled = $this->scheduledDonations->where('destination_type', 'givelist');

        if ($organizationsScheduled->count()) {
            foreach ($organizationsScheduled as &$scheduledDonation) {
                if (!$scheduledDonation->destination->categories) {
                    $categories['uncategorized']['amount'] += $scheduledDonation->amount;
                    $categories['uncategorized']['count']++;
                    continue;
                }

                $amount = $scheduledDonation->amount;
                if ($scheduledDonation->destination->categories->count()) {
                    $amount = $scheduledDonation->amount / $scheduledDonation->destination->categories->count();
                }

                foreach ($scheduledDonation->destination->categories as &$category) {
                    if (isset($categories[$category->id])) {
                        $categories[$category->id]['amount'] += $amount;
                        $categories[$category->id]['count']++;
                        continue;
                    }

                    $categories[$category->id] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'color' => $category->color,
                        'amount' => $amount,
                        'slug' => $category->slug,
                        'count' => 1,
                    ];
                }
            }
        }

        if ($givelistsScheduled->count()) {
            foreach ($givelistsScheduled as &$scheduledDonation) {
                $givelist = $scheduledDonation->destination;
                $organizations = $givelist->organizations;
                $amountForOrganization = $scheduledDonation->amount / $organizations->count();

                foreach ($organizations as &$organization) {
                    if (!$organization->categories) {
                        $categories['uncategorized']['amount'] += $scheduledDonation->amount;
                        continue;
                    }
                    $amount = $amountForOrganization / $organization->categories->count();

                    foreach ($organization->categories as &$category) {
                        if (isset($categories[$category->id])) {
                            $categories[$category->id]['amount'] += $amount;
                            continue;
                        }
                        $categories[$category->id] = ['id' => $category->id, 'name' => $category->name, 'color' => $category->color, 'amount' => $amount];
                    }
                }
            }
        }




        return array_values($categories);
    }


    public function followings()
    {
        return $this->belongsToMany(User::class, 'follower_user', 'follower_id', 'user_id')->withPivot('requested_at', 'accepted_at')->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follower_user', 'user_id', 'follower_id')->withPivot('requested_at', 'accepted_at')->withTimestamps();
    }

    public function followRequests()
    {
        return FollowerUser::where('user_id', $this->id)->whereNotNull('requested_at')->whereNull('accepted_at')->get();
    }

    public function givelists()
    {
        return $this->morphMany(Givelist::class, 'creator');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function banks()
    {
        return $this->morphMany(Bank::class, 'owner');
    }

    public function cards()
    {
        return $this->morphMany(Card::class, 'owner');
    }

    public function accessTokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function userTransactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function interests()
    {
        return $this->morphMany(Interest::class, 'enthusiast');
    }

    public function logins()
    {
        return $this->hasMany(Login::class);
    }

    public function fundTransactions()
    {
        return $this->transactions()->where([['source_type', 'user'], ['source_id', $this->id]])->orWhere([['destination_type', 'user'], ['destination_id', $this->id]]);
    }

    public function sentTransactions()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function donationsMade()
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    public function receivedTransactions()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }

    public function scheduledDonations()
    {
        return $this->morphMany(ScheduledDonation::class, 'source');
    }

    public function organizationStats(Organization $organization)
    {
        $transactions = $this->donationsMade()->where('destination_id', $organization->id)->where('destination_type', 'organization')->get();

        $date = new DateTime(); //Today
        $lastDay = $date->format("Y-m-t"); //Get last day
        $dateMinus12 = $date->modify("-12 months");

        $yms = array();
        $now = date('Y-m');
        for ($x = 12; $x >= 1; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $transactionsLast12 = $transactions->where('created_at', '>', $dateMinus12)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($transactionsLast12 as $key => $value) {
            $yms[$key] = $value;
        }

        return [
            "total" => round($transactions->sum('amount') / 100, 2),
            "count" => $transactions->count(),
            "history" => $yms
        ];
    }

    public function organizationActivity(Organization $organization)
    {
        return collect([['title' => 'Joined Givelist', 'icon' => 'mdi-cake', 'date' => $this->created_at]]);
    }

    public function getAddressAttribute()
    {
        return [
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,

        ];
    }

    public function getAddressStringAttribute()
    {
        return implode(", ", array_filter([
            $this->address1,
            $this->address2,
            $this->city,
            $this->state,
            $this->zip,

        ]));
    }

    public function walletBalance()
    {
        $received = 0;
        $sent = 0;


        $received = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->sum('amount');
        $sent = $this->sentTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->sum('amount');


        $balance = $received - $sent;

        // TODO: Cache $balance and break cache with new transactions

        return $balance;
    }

    public function clearedWalletBalance()
    {
        $received = 0;
        $sent = 0;


        $received = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS])->sum('amount');
        $sent = $this->sentTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS])->sum('amount');


        $balance = $received - $sent;

        // TODO: Cache $balance and break cache with new transactions

        return $balance;
    }

    public function donateToOrganization(Organization $organization, $amount)
    {
        $transaction = new Transaction();
        $transaction->source = $this;
        $transaction->destination = $organization;

        $this->transactions()->save($transaction);
    }

    public function totalGiven()
    {
        return $this->donationsMade->sum('amount');
    }

    public function totalGivenByMonth($year = null)
    {

        if ($year === 'all') {
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

            $transactions = $this->donationsMade;


            $transactionsLast12 = $transactions->where('created_at', '>', $this->created_at)->where('created_at', '<', now())->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });



            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }

        if (!$year) $year = date("Y");

        $transactions = $this->donationsMade;

        $date = new DateTime(); //Today
        $date->setDate($year, 12, 31);





        $yms = array();
        $now = $date->format('Y-m');
        $dateMinus12 = $date->modify("-12 months");



        for ($x = 11; $x >= 0; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $transactionsLast12 = $transactions->where('created_at', '>', $dateMinus12)->where('created_at', '<', $now)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($transactionsLast12 as $key => $value) {
            $yms[$key] = $value;
        }

        return $yms;
    }



    public function totalFundraisedByMonth($year = null)
    {

        if ($year === 'all') {
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

            $fundraiserIds = $this->fundraisers->pluck('id')->all();



            $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->get();


            $transactionsLast12 = $transactions->where('created_at', '>', $this->created_at)->where('created_at', '<', now())->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });



            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }


        if (!$year) $year = date("Y");

        $fundraiserIds = $this->fundraisers->pluck('id')->all();



        $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->get();

        $date = new DateTime(); //Today
        $date->setDate($year, 12, 31);



        $yms = array();
        $now = $date->format('Y-m');

        $dateMinus12 = $date->modify("-12 months");

        for ($x = 11; $x >= 0; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $transactionsLast12 = $transactions->where('created_at', '>', $dateMinus12)->where('created_at', '<', $now)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($transactionsLast12 as $key => $value) {
            $yms[$key] = $value;
        }

        return $yms;
    }

    public function totalFundraised()
    {
        return $this->sentTransactions()->whereNotNull('fundraiser_id')->sum('amount');
    }

    public function impactedOrganizations()
    {
        return $this->sentTransactions()->where('destination_type', 'organization')->groupBy(['destination_id'])->get('destination_id')->pluck('destination_id')->toArray();
    }

    public function topOrganizations()
    {
        $orgIds = $this->sentTransactions()->where('destination_type', 'organization')->whereNull('givelist_id')->groupBy(['destination_id'])->get('destination_id');


        $orgs = [];
        foreach ($orgIds as $key => $value) {
            $organization = Organization::find($value)->first();
            $orgs[] = [
                'organization' => new OrganizationSimpleResource($organization),
                'total_given' => $organization->givenByDonor($this) / 100
            ];
        }

        return $orgs;
    }


    public function setPreferredPayment($type, $id)
    {
        $model = null;
        if ($type === 'card') {
            $model = Card::find($id);
        }

        if ($type === 'bank') {
            $model = Bank::find($id);
        }

        $this->preferredPayment()->associate($model);
        $this->save();
        return $this->preferredPayment;
    }

    public function stats()
    {
        $transactions = $this->transactions;
        return [
            'total_given' => round($this->totalGiven() / 100, 2),
            'first_given_date' => $transactions->first()->created_at ?? now(),
            'count' => count($transactions)
        ];
    }

    public function isFollowing(User $user)
    {
        return $this->followings->contains($user);
    }

    public function createAuthCode()
    {
        return random_int(100000, 999999);
    }

    public function sendEmailAuthCode()
    {

        $this->verification_code = $this->createAuthCode();

        $this->verification_code_set_at = now();

        $this->save();

        $this->notify(new EmailAuthCodeCreated($this->verification_code));
    }

    public function sendPhoneAuthCode()
    {

        $this->verification_code = $this->createAuthCode();

        $this->verification_code_set_at = now();

        $this->save();


        $client = new Client(config('services.twilio.account'), config('services.twilio.token'));
        $client->messages->create($this->phone, [
            'from' => config('services.twilio.number'),
            'body' => "Your WeGive Verification Code is {$this->verification_code}"
        ]);
    }


    public function twoPaymentMethodsUsed($paymentMethod)
    {
        $transactions = $this->transactions()->where([['created_at', '>=',  new DateTime('today midnight')]])->get();


        $cards = [];
        $banks = [];

        foreach ($transactions as $transaction) {

            if ($transaction->source instanceof Card) {
                array_push($cards, $transaction->source->id);
            }


            if ($transaction->source instanceof Bank) {
                array_push($banks, $transaction->source->id);
            }
        }

        $cards = array_unique($cards);
        $banks = array_unique($banks);


        if ($paymentMethod instanceof Bank) {
            if (in_array($paymentMethod->id, $banks)) {
                return false;
            }
        }

        if ($paymentMethod instanceof Card) {
            if (in_array($paymentMethod->id, $cards)) {
                return false;
            }
        }


        if ($paymentMethod instanceof Bank || $paymentMethod instanceof Card) {
            return (count($cards) + count($banks)) >= 10;
        }

        return false;
    }

    public function passedProbationaryPeriod($paymentMethod)
    {

        if ($paymentMethod instanceof Card) {

            $successfulTransactions = $this->transactions()->where([['source_type', 'card'], ['source_id', $paymentMethod->id], ['status', Transaction::STATUS_SUCCESS]])->get();

            if (count($successfulTransactions) > 0) {
                return true;
            }

            $allTransactions = $this->transactions()->where([['source_type', 'card'], ['source_id', $paymentMethod->id],])->get();

            if (count($allTransactions) >= 3) {
                return false;
            }
        }


        if ($paymentMethod instanceof Bank) {
            $successfulTransactions = $this->transactions()->where([['source_type', 'bank'], ['source_id', $paymentMethod->id], ['status', Transaction::STATUS_SUCCESS]])->get();

            if (count($successfulTransactions) > 0) {
                return true;
            }

            $allTransactions = $this->transactions()->where([['source_type', 'bank'], ['source_id', $paymentMethod->id],])->get();

            if (count($allTransactions) >= 3) {
                return false;
            }
        }

        return true;
    }


    public function hasFailedTransaction($paymentMethod)
    {


        if ($paymentMethod instanceof Card) {

            $transactions = Transaction::where([['source_type', 'card'], ['source_id', $paymentMethod->id]])->get();

            $sequence = 0;

            foreach ($transactions as $transaction) {
                if ($sequence === 3) {
                    break;
                }
                if ($transaction->status === Transaction::STATUS_FAILED) {
                    $sequence += 1;
                } else {
                    $sequence = 0;
                }
            }


            if ($sequence === 3) {
                return true;
            }
        }


        if ($paymentMethod instanceof Bank) {
            $transactions = Transaction::where([['source_type', 'bank'], ['source_id', $paymentMethod->id]])->get();

            $sequence = 0;

            foreach ($transactions as $transaction) {
                if ($sequence === 3) {
                    break;
                }
                if ($transaction->status === Transaction::STATUS_FAILED) {
                    $sequence += 1;
                } else {
                    $sequence = 0;
                }
            }

            if ($sequence === 3) {
                return true;
            }
        }

        return false;
    }

    public function hasThreeFailedTransactions()
    {
        $failed = $this->transactions()->where([['status', Transaction::STATUS_FAILED], ['created_at', '>',  new DateTime('today midnight')]])->get();

        if (count($failed) >= 3) {
            return true;
        }
    }


    public function isAbleToUsePaymentMethod($paymentMethod)
    {
        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            return;
        }

        abort_if($this->twoPaymentMethodsUsed($paymentMethod), 400, 'You have reached your limit of 10 payment methods used in a day.');

        abort_unless($this->passedProbationaryPeriod($paymentMethod), 400, 'Has Not Passed Probationary Period.');

        abort_if($this->hasFailedTransaction($paymentMethod), 400, 'This Payment Method Has Incorrect Details, Please ReAdd and Try Again.');

        abort_if($this->hasThreeFailedTransactions(), 400, 'Your account has more than three failed transactions today. Please try again tomorrow.');
    }
}
