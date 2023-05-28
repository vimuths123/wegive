<?php

namespace App\Models;

use App\Actions\Payments;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\DonorSimpleResource;
use App\Http\Resources\FundraiserTableResource;
use App\Http\Resources\FundResource;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Organization extends Model implements HasMedia, Auditable
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;

    use \OwenIt\Auditing\Auditable;
    use HasApiTokens;

    public const DONOR_PAYS_FEES = 1;

    public const ORGANIZATION_PAYS_FEES = 2;

    public const DONOR_DECIDES_FEES = 3;

    public const DONOR_DECIDES_DONATION_PRIVACY = 1;

    public const DONATION_ACTIVITY_HIDDEN = 2;

    public const DONATION_ACTIVITY_PUBLIC = 3;

    public const DONATION_ACTIVITY_AND_AMOUNTS_PUBLIC = 4;

    protected $fillable = [
        "uuid", 'legal_name', 'dba', 'ein', 'year_of_formation', 'phone', 'url', 'mission_statement', 'tagline', 'program_expense', 'fundraising_expense', 'total_revenue', 'total_expenses', 'general_expense', 'total_assets', 'total_liabilities', 'color', 'google_tag_manager_container_id',
        'google_analytics_measurement_id',
        'facebook_pixel_id'
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'general_expense'         => 'integer',
        'present_in_bmf'          => 'boolean',
        'present_in_pub78'        => 'boolean',
        'manually_managed_fields' => 'array',
        'total_expenses'          => 'integer',
        'year_of_formation'       => 'integer',
        'given_by_user'           => 'integer',
        'onboarded'               => 'date'
    ];

    protected $dates = [
        'pub78_update_date' => 'date',
        'tax_end_dt'        => 'date',
        'tax_start_dt'      => 'date',
        'bmf_update_date'   => 'date',
        'onboarded'         => 'date'
    ];

    public static function boot()
    {
        parent::boot();



        self::updated(function ($model) {
            if ($model->onboarded && !$model->getOriginal('onboarded')) {
                $model->setupDefaultMessageTemplates();
            }
        });
    }

    public function setupDefaultMessageTemplates()
    {
        $receiptMessageTemplate = new MessageTemplate();
        $receiptMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
        $receiptMessageTemplate->enabled = true;
        $receiptMessageTemplate->owner()->associate($this);
        $receiptMessageTemplate->trigger = MessageTemplate::TRIGGER_RECEIPT;
        $receiptMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_RECEIPT];
        $receiptMessageTemplate->content = " ";
        $receiptMessageTemplate->save();

        $recurringDonationCreatedMessageTemplate = new MessageTemplate();
        $recurringDonationCreatedMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
        $recurringDonationCreatedMessageTemplate->enabled = true;
        $recurringDonationCreatedMessageTemplate->owner()->associate($this);
        $recurringDonationCreatedMessageTemplate->trigger = MessageTemplate::TRIGGER_RECURRING_DONATION_CREATED;
        $recurringDonationCreatedMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_RECURRING_DONATION_CREATED];
        $recurringDonationCreatedMessageTemplate->content = " ";
        $recurringDonationCreatedMessageTemplate->save();

        $donationFailedMessageTemplate = new MessageTemplate();
        $donationFailedMessageTemplate->type = MessageTemplate::TYPE_EMAIL;
        $donationFailedMessageTemplate->enabled = true;
        $donationFailedMessageTemplate->owner()->associate($this);
        $donationFailedMessageTemplate->trigger = MessageTemplate::TRIGGER_DONATION_FAILED;
        $donationFailedMessageTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_DONATION_FAILED];
        $donationFailedMessageTemplate->content = " ";
        $donationFailedMessageTemplate->save();

        $thankYouTextTemplate = new MessageTemplate();
        $thankYouTextTemplate->type = MessageTemplate::TYPE_TEXT;
        $thankYouTextTemplate->enabled = true;
        $thankYouTextTemplate->owner()->associate($this);
        $thankYouTextTemplate->trigger = MessageTemplate::TRIGGER_THANK_YOU;
        $thankYouTextTemplate->subject = MessageTemplate::TRIGGER_MAP[MessageTemplate::TRIGGER_THANK_YOU];
        $thankYouTextTemplate->content = " ";
        $thankYouTextTemplate->save();
    }

    public function elements()
    {
        return $this->hasManyThrough(Element::class, Campaign::class);
    }

    public function toSearchableArray()
    {
        return [
            'dba'        => $this->dba,
            'legal_name' => $this->legal_name,
            'ein'        => preg_replace('~\D~', '', $this->ein)

        ];
    }

    public function donorPortal()
    {
        return $this->morphOne(DonorPortal::class, 'recipient');
    }

    public function checkouts()
    {
        return $this->morphMany(Checkout::class, 'recipient');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('legal_name')
            ->saveSlugsTo('slug');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function neonIntegration()
    {
        return $this->hasOne(NeonIntegration::class);
    }

    public function donorPerfectIntegration()
    {
        return $this->hasOne(DonorPerfectIntegration::class);
    }

    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function customEmailDomains()
    {
        return $this->hasMany(CustomEmailDomain::class);
    }

    public function domainAliases()
    {
        return $this->hasMany(DomainAlias::class);
    }

    public function salesforceIntegration()
    {
        return $this->hasOne(SalesforceIntegration::class);
    }

    public function blackbaudIntegration()
    {
        return $this->hasOne(BlackbaudIntegration::class);
    }

    public function neonMappingRules()
    {
        return $this->hasMany(NeonMappingRule::class);
    }

    public function donorSetting()
    {
        return $this->hasOne(OrganizationDonorPortalConfig::class);
    }

    public function funds()
    {
        return $this->hasMany(Fund::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function impactNumbers()
    {
        return $this->hasMany(ImpactNumber::class);
    }

    public function fundraisers()
    {
        return $this->morphMany(Fundraiser::class, 'recipient');
    }

    public function products()
    {
        return $this->morphMany(Product::class, 'owner');
    }

    public function invites()
    {
        return $this->morphMany(Invite::class, 'inviter');
    }

    public function messageTemplates()
    {
        return $this->morphMany(MessageTemplate::class, 'owner');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function givelists()
    {
        return $this->belongsToMany(Givelist::class)->withTimestamps();
    }

    // TODO: original called this programs, will renaming make conventions better
    public function organizationPrograms()
    {
        return $this->hasMany(OrganizationProgram::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function donors()
    {
        return $this->hasMany(Donor::class);
    }

    public function productCodes()
    {
        return $this->belongsToMany(ProductCode::class)->withTimestamps();
    }

    public function getIconUrlAttribute()
    {
        return $this->getAvatarUrlAttribute();
    }

    public function getAvatarUrlAttribute()
    {
        return $this->getFirstMediaUrl('avatar');
    }

    public function formattedEin()
    {
        return sprintf("%09s", $this->ein);
    }

    public function teamMembers()
    {

        $logins = Login::where('loginable_type', 'organization')->where('loginable_id', $this->id)->get()->unique('user_id')->pluck('user_id')->all();

        return User::whereIn('id', $logins)->get();
    }

    public function recurringDonors()
    {
        return $this->donors()->whereHas('scheduledDonations', function ($query) {
            $query->where('destination_type', 'organization')->where('destination_id', $this->id);
        });
    }

    public function newDonors()
    {
        $orgId = $this->id;

        $donors = $this->donors();

        return $donors->whereHas('transactions', function ($query) use ($orgId) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', date('c', strtotime('-30 days')));
        })->whereHas('transactions', function ($query) use ($orgId) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '<=', date('c', strtotime('-30 days')));
        }, 0);
    }

    public function slippingDonors()
    {
        $orgId = $this->id;
        $donors = $this->donors();

        return $donors->whereHas('transactions', function ($query) use ($orgId) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '<=', date('c', strtotime('-30 days')));
        })->whereHas('transactions', function ($query) use ($orgId) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', date('c', strtotime('-30 days')));
        }, 0);
    }

    public function returningDonorsByTimePeriod($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;
        $orgId = $this->id;

        $currentPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($orgId, $start, $end) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', $start->format('Y-m-d'))->where('created_at', '<=', $end->format('Y-m-d'));
        }, '>', 1)->count();

        $previousPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($orgId, $start, $end, $differenceInDays) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<=', $end->modify("-$differenceInDays days")->format('Y-m-d'));
        }, '>', 1)->count();

        return ['current' => $currentPeriodCount, 'previous' => $previousPeriodCount];
    }

    public function firstTimeDonorsByTimePeriod($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;
        $orgId = $this->id;

        $currentPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($orgId, $start) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '<', $start->format('Y-m-d'));
        }, 0)->whereHas('transactions', function ($query) use ($orgId, $start, $end) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', $start->format('Y-m-d'))->where('created_at', '<=', $end->format('Y-m-d'));
        })->count();

        $previousPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($orgId, $start, $differenceInDays) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '<', $start->modify("-$differenceInDays days")->format('Y-m-d'));
        }, 0)->whereHas('transactions', function ($query) use ($orgId, $start, $end, $differenceInDays) {
            $query->where('destination_type', 'organization')->where('destination_id', $orgId)->where('created_at', '>=', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<=', $end->modify("-$differenceInDays days")->format('Y-m-d'));
        })->count();

        return ['current' => $currentPeriodCount, 'previous' => $previousPeriodCount];
    }

    public function recurringGiversByTimePeriod($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;

        $currentUsers = $this->receivedTransactions()
            ->with('owner')
            ->whereNotNull('scheduled_donation_id')
            ->where('created_at', '>=', $start->format('Y-m-d'))
            ->where('created_at', '<=', $end->format('Y-m-d'))
            ->get()
            ->pluck('owner')
            ->unique();

        $previousUsers = $this->receivedTransactions()
            ->with('owner')
            ->whereNotNull('scheduled_donation_id')
            ->where('created_at', '>=', $start->modify("-$differenceInDays days")->format('Y-m-d'))
            ->where('created_at', '<=', $end->modify("-$differenceInDays days")->format('Y-m-d'))
            ->get()
            ->pluck('owner')
            ->unique();

        return ['current' => count($currentUsers), 'previous' => count($previousUsers)];
    }

    public function nonRecurringGiversByTimePeriod($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;

        $orgId = $this->id;

        $currentPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($start, $end, $orgId) {
            $query->where('scheduled_donation_id', null)
                ->where('owner_type', 'donor')
                ->where('created_at', '>=', $start->format('Y-m-d'))
                ->where('created_at', '<=', $end->format('Y-m-d'))
                ->where('destination_id', $orgId)
                ->where('destination_type', 'organization');
        })->count();

        $previousPeriodCount = $this->donors()->whereHas('transactions', function ($query) use ($start, $end, $differenceInDays, $orgId) {
            $query->where('scheduled_donation_id', null)
                ->where('owner_type', 'donor')
                ->where('created_at', '>=', $start->modify("-$differenceInDays days")->format('Y-m-d'))
                ->where('created_at', '<=', $end->modify("-$differenceInDays days")->format('Y-m-d'))
                ->where('destination_id', $orgId)
                ->where('destination_type', 'organization');
        })->count();

        return ['current' => $currentPeriodCount, 'previous' => $previousPeriodCount];
    }

    public function giversByTimePeriod($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;
        $currentPeriodUsers = $this->receivedTransactions()->with('owner')->whereIn('owner_type', ['donor'])
            ->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>=', $start->format('Y-m-d'))->where('created_at', '<=', $end->format('Y-m-d'))->orderBy('created_at', 'desc')->get()->pluck('owner')->unique();

        $previousPeriodUsers = $this->receivedTransactions()->with('owner')->whereIn('owner_type', ['donor'])
            ->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>=', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<=', $end->modify("-$differenceInDays days")->format('Y-m-d'))->orderBy('created_at', 'desc')->get()->pluck('owner')->unique();

        return ['current' => DonorSimpleResource::collection($currentPeriodUsers), 'previous' => DonorSimpleResource::collection($previousPeriodUsers)];
    }

    public function disputes()
    {
        return Transaction::where('status', Transaction::STATUS_DISPUTED)->where('destination_id', $this->id)->where('destination_type', Organization::class);
    }

    public function transactions()
    {
        return Transaction::where('destination_id', $this->id)->orWhere('source_id', $this->id)->where('destination_type', Organization::class)->orWhere('source_type', Organization::class);
    }

    public function myFundraisers($user)
    {

        if (!$user) {
            return [];
        }
        $currentLogin = $user->currentLogin ?? $user;

        return $currentLogin->fundraisers()->where([['recipient_type', 'organization'], ['recipient_id', $this->id]])->get();
    }

    public function myActivity($user)
    {
        if (!$user) {
            return;
        }
        $currentLogin = $user->currentLogin;
        if (!$currentLogin) {
            return;
        }

        $actions = $currentLogin->actions;

        if ($actions) {
            $actions = $actions->sortByDesc('created_at');
        }

        return ActivityResource::collection($actions ?? []);
    }

    public function donorGroupOptions()
    {
        $funds = FundResource::collection($this->funds);
        $fundraisers = FundraiserTableResource::collection($this->fundraisers);

        return $funds->merge($fundraisers);
    }

    public function grossDonationVolumeGraph($start_date, $end_date)
    {
        $transactions = $this->receivedTransactions()->where('created_at', '>', $start_date)->where('created_at', '<', $end_date)->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->get();

        $transactionsGrouped = $transactions->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        return $transactionsGrouped;
    }

    public function give($source, $amount, $description = 'None', $scheduledDonationId = null, $tip = 0, $givelistId = null, $fundraiserId = null, $fundId = null, $anonymous = true, $coverFees = false, $feeAmount = 0, $elementId = null, $saveQuietly = false, $overrideUser = null, $campaignId = null)
    {
        abort_unless($amount >= 500, 400, 'Amount must be positive.');

        $currentLogin = auth()->user()->currentLogin ?? $source->owner ?? $source;

        if (auth()->user()) {
            auth()->user()->isAbleToUsePaymentMethod($source);
        }

        if ($currentLogin === null) {
            if ($source instanceof User) {
                $currentLogin = $source;
            } else {
                if ($source instanceof Bank || $source instanceof Card) {
                    $currentLogin = $source->owner;
                }
            }
        }

        $transaction = new Transaction();
        $transaction->owner()->associate($currentLogin);

        if ($givelistId) {
            $transaction->givelist_id = $givelistId;
        }
        if ($fundraiserId) {
            $fundraiser = Fundraiser::where('slug', $fundraiserId)->first();;
            $campaign = $fundraiser->campaign;
            $transaction->fundraiser()->associate($fundraiser);
            if ($campaign) {
                $transaction->campaign()->associate($campaign);
            }
        }
        if ($elementId) {
            $element = Element::find($elementId);
            $transaction->element_id = $elementId;
            $transaction->campaign_id = $element->campaign_id;
        }
        if ($campaignId) {
            $transaction->campaign_id = $campaignId;
        }
        if ($fundId) {
            $transaction->fund_id = $fundId;
        }
        if ($scheduledDonationId) {
            $transaction->scheduled_donation_id = $scheduledDonationId;
            $scheduledDonation = ScheduledDonation::find($scheduledDonationId);
            $user = $scheduledDonation->user;
            $transaction->owner()->associate($scheduledDonation->source);
        }
        $transaction->user()->associate(auth()->user() ?? $currentLogin ?? $user);
        $transaction->source()->associate($source);
        $transaction->amount = round($amount);
        $transaction->description = $description;
        $transaction->destination()->associate($this);
        $transaction->fee = round($tip);
        $transaction->cover_fees = $coverFees;
        $transaction->fee_amount = $feeAmount;

        $transaction->anonymous = $anonymous;
        $transaction->direct_deposit = $this->onboarded ? true : false;

        $transaction = Payments::processTransaction($transaction);

        if ($saveQuietly) {
            $transaction->saveQuietly();
        } else {
            $transaction->save();
        }

        if ($transaction->status === Transaction::STATUS_FAILED) {
            abort(400, 'The transaction has failed');
        }

        return $transaction;
    }

    public function scheduledDonations()
    {
        return $this->morphMany(ScheduledDonation::class, 'destination');
    }

    public function receivedTransactions()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function sentTransactions()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function getPostsCountAttribute()
    {
        return $this->posts()->count();
    }

    public function getGiversCountAttribute()
    {
        return $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->groupBy(['source_id', 'source_type'])->count();
    }

    public function getGiversAttribute()
    {
        $userIds = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->groupBy(['owner_id'])->where('owner_type', 'user')->pluck('owner_id');

        return User::query()->find($userIds);
    }

    public function recurringDonationsAmount($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;

        $currentPeriod = $this->receivedTransactions()->whereNotNull('scheduled_donation_id')->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->format('Y-m-d'))->where('created_at', '<', $end->format('Y-m-d'))->get()->sum('amount');

        $previousPeriod = $this->receivedTransactions()->whereNotNull('scheduled_donation_id')->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<', $end->modify("-$differenceInDays days")->format('Y-m-d'))->get()->sum('amount');

        return ['current' => $currentPeriod / 100, "previous" => $previousPeriod / 100];
    }

    public function grossDonationsVolume($start_date, $end_date)
    {

        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;

        $currentPeriod = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->format('Y-m-d'))->where('created_at', '<', $end->format('Y-m-d'))->get()->sum('amount');

        $previousPeriod = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<', $end->modify("-$differenceInDays days")->format('Y-m-d'))->get()->sum('amount');

        return ['current' => $currentPeriod / 100, "previous" => $previousPeriod / 100];
    }

    public function netDonationsVolume($start_date, $end_date)
    {
        $start = date_create($start_date);
        $end = date_create($end_date);

        $differenceInDays = date_diff($end, $start)->days;

        $currentTransactions =
            $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->format('Y-m-d'))->where('created_at', '<', $end->format('Y-m-d'))->get();

        $currentPeriod = $currentTransactions->sum('amount') - $currentTransactions->sum('fee_amount') -  $currentTransactions->sum('fee');

        $previousTransactions = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', $start->modify("-$differenceInDays days")->format('Y-m-d'))->where('created_at', '<', $end->modify("-$differenceInDays days")->format('Y-m-d'))->get();

        $previousPeriod = $previousTransactions->sum('amount') - $previousTransactions->sum('fee_amount') - $previousTransactions->sum('fee');

        return ['current' => $currentPeriod / 100, "previous" => $previousPeriod / 100];
    }

    public function givenByDonor($donor)
    {
        if (!$donor) {
            return 0;
        }

        return $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where([['owner_id', $donor->id], ['owner_type', $donor->getMorphClass()]])->sum('amount') / 100;
    }

    public function givenThisYearByUser($user)
    {
        if (!$user) {
            return 0;
        }

        return $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', date('Y-m-d', strtotime(date('Y-01-01'))))->where([['owner_id', $user->id], ['owner_type', $user->getMorphClass()]])->sum('amount') / 100;
    }

    public function fundraisedByUser($user)
    {
        if (!$user) {
            return 0;
        }
        $fundraisers = $user->fundraisers()->pluck('id');

        $transactions = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->whereIn('fundraiser_id', $fundraisers)->get();

        return $transactions->sum('amount') / 100;
    }

    public function fundraiserStats($user)
    {
        if (!$user) {
            return null;
        }
        $fundraisers = $user->fundraisers()->pluck('id');

        $transactions = $this->receivedTransactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->whereIn('fundraiser_id', $fundraisers)->get();

        $date = new DateTime(); //Today
        $lastDay = $date->format("Y-m-t"); //Get last day
        $dateMinus12 = $date->modify("-12 months");

        $yms = [];
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
            "total"   => round(($transactions->sum('amount') / 100), 2),
            "count"   => $transactions->count(),
            "history" => $yms,
        ];
    }

    public function userStats($user)
    {
        if (!$user) {
            return null;
        }

        $transactions = $this->receivedTransactions()->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->where('owner_id', $user->id)->where('owner_type', $user->getMorphClass())->get();

        $lastYear = now()->subYear();

        $yms = [];
        $now = date('Y-m');
        for ($x = 12; $x >= 1; $x--) {
            $ym = date('m-Y', strtotime($now . " -$x month"));
            $yms[$ym] = [];
        }

        $oneYearOfTransactions = $transactions->where('created_at', '>', $lastYear)->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        foreach ($oneYearOfTransactions as $key => $value) {
            $yms[$key] = $value;
        }

        return [
            "total"            => round($transactions->sum('amount') / 100, 2),
            "count"            => $transactions->count(),
            "history"          => $yms,
            "fundraiser"       => $this->fundraiserStats($user),
            "first_given_date" => $transactions->sortBy('created_at')->first() ?? null
        ];
    }

    public function userImpactGraph($year)
    {
        return ['donated' => $this->userDonationStats($year), 'fundraised' => $this->userFundraiserStats($year)];
    }

    public function userDonationStats($year)
    {

        $currentLogin = auth()->user()->currentLogin;

        if (!$year || $year === 'all') {
            $endDate = now()->getTimestamp();
            $createdAt = $currentLogin->created_at;
            $startDate = strtotime($createdAt);

            $yms = [];

            $totalSecondsDiff = abs($endDate - $startDate);
            $totalMonthsDiff = round($totalSecondsDiff / 60 / 60 / 24 / 30);

            $now = now()->format('Y-m');

            for ($x = $totalMonthsDiff; $x >= 0; $x--) {
                $ym = date('m-Y', strtotime($now . " -$x month"));
                $yms[$ym] = [];
            }

            $transactions = auth()->user()->currentLogin->donations()->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

            $transactionsLast12 = $transactions->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });

            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }

        $transactions = auth()->user()->currentLogin->donations()->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

        $startDate = new DateTime(); // Today
        $startDate->setDate($year, 12, 31);

        $endDate = new DateTime(); // Today
        $endDate->setDate($year, 12, 31)->modify("-12 months");

        $yms = [];
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

    public function userFundraiserStats($year)
    {

        $currentLogin = auth()->user()->currentLogin;

        if (!$year || $year === 'all') {
            $endDate = now()->getTimestamp();
            $createdAt = $currentLogin->created_at;
            $startDate = strtotime($createdAt);

            $yms = [];

            $totalSecondsDiff = abs($endDate - $startDate);
            $totalMonthsDiff = round($totalSecondsDiff / 60 / 60 / 24 / 30);

            $now = now()->format('Y-m');

            for ($x = $totalMonthsDiff; $x >= 0; $x--) {
                $ym = date('m-Y', strtotime($now . " -$x month"));
                $yms[$ym] = [];
            }

            $fundraiserIds = $currentLogin->fundraisers->pluck('id')->all();

            $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

            $transactionsLast12 = $transactions->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m-Y');
            });

            foreach ($transactionsLast12 as $key => $value) {
                $yms[$key] = $value;
            }

            return $yms;
        }

        $fundraiserIds = $currentLogin->fundraisers->pluck('id')->all();

        $transactions = Transaction::whereIn('fundraiser_id', $fundraiserIds)->whereNotIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED])->get();

        $startDate = new DateTime(); // Today
        $startDate->setDate($year, 12, 31);

        $endDate = new DateTime(); // Today
        $endDate->setDate($year, 12, 31)->modify("-12 months");

        $yms = [];
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

    public function scopeVisible($query)
    {
        return $query->where(function ($query) {
            $query->where('visible', true);
        });
    }

    public function totalFundraised($donor)
    {
        $fundraiserIds = $this->fundraisers()->where('owner_type', $donor->getMorphClass())->where('owner_id', $donor->id)->get()->pluck('id')->all();

        return Transaction::whereIn('fundraiser_id', $fundraiserIds)->sum('amount');
    }

    public function totalGiven($donor)
    {
        return $this->receivedTransactions()->where('owner_type', $donor->getMorphClass())->where('owner_id', $donor->id)->sum('amount');
    }

    public function totalGivenThisYear($donor)
    {
        return $this->receivedTransactions()->where('owner_type', $donor->getMorphClass())->where('owner_id', $donor->id)->where('created_at', '>=', date('Y-01-01'))->where('created_at', '<=', date('Y-12-31'))->sum('amount');
    }

    public function totalRecurringGiving($donor)
    {
        return $this->receivedTransactions()->whereNotNull('scheduled_donation_id')->where('owner_type', $donor->getMorphClass())->where('owner_id', $donor->id)->sum('amount');
    }

    public function lifetimeImpact($donor)
    {
        return $this->totalFundraised($donor) + $this->totalGiven($donor);
    }

    public function getNameAttribute()
    {
        return $this->dba ? $this->dba : $this->legal_name;
    }

    public function donorLevel($currentLogin)
    {

        if (!$currentLogin) {
            return null;
        }

        $qualifier = $this->donorPortal->donor_ranking_qualifier;

        $donors = $this->donors()->has('donations')->get();

        if ($qualifier === DonorPortal::LIFETIME_IMPACT_QUALIFIER) {

            foreach ($donors as $donor) {
                $donor["qualifier"] = $this->lifetimeImpact($donor);
            }
        }

        if ($qualifier === DonorPortal::TOTAL_GIVEN_QUALIFIER) {
            foreach ($donors as $donor) {
                $donor["qualifier"] = $this->totalGiven($donor);
            }
        }

        if ($qualifier === DonorPortal::GIVEN_THIS_YEAR_QUALIFIER) {
            foreach ($donors as $donor) {
                $donor["qualifier"] = $this->givenThisYear($donor);
            }
        }

        if ($qualifier === DonorPortal::TOTAL_FUNDRAISED_QUALIFIER) {
            foreach ($donors as $donor) {
                $donor["qualifier"] = $this->totalFundraised($donor);
            }
        }

        if ($qualifier === DonorPortal::RECURRING_DONATION_AMOUNT_QUALIFIER) {
            foreach ($donors as $donor) {
                $donor["qualifier"] = $this->totalRecurringGiving($donor);
            }
        }

        $sorted = $donors->sortBy(function ($donor) {
            return (float)$donor['qualifier'];
        });

        $index = null;

        $count = count($donors);

        if ($count === 0) {
            return [];
        }

        $sortedArray = array_values($sorted->toArray());

        for ($i = 0; $i < $count; $i++) {
            if ($sortedArray[$i]['id'] === $currentLogin->id) {
                $index = $i + 1;
                break;
            }
        }

        $level = null;

        $percentile = (($count - $index) / $count) * 100;
        $topPercent = 100 - $percentile;
        $ranking = null;

        if (!$level && $this->donorPortal->percentile_1 === DonorPortal::TOP_1_PERCENT) {
            if ($percentile <= 1) {
                $ranking = 1;
                $level = 'Level 1';
            }
        }

        if (!$level && $this->donorPortal->percentile_1 === DonorPortal::TOP_5_PERCENT) {
            if ($percentile <= 5) {
                $ranking = 5;

                $level = 'Level 1';
            }
        }
        if (!$level && $this->donorPortal->percentile_1 === DonorPortal::TOP_10_PERCENT) {
            if ($percentile <= 10) {
                $percentile = 10;
                $level = 'Level 1';
            }
        }
        if (!$level && $this->donorPortal->percentile_1 === DonorPortal::TOP_20_PERCENT) {
            if ($percentile <= 20) {
                $ranking = 20;
                $level = 'Level 1';
            }
        }
        if (!$level && $this->donorPortal->percentile_1 === DonorPortal::TOP_40_PERCENT) {
            if ($percentile <= 40) {
                $ranking = 40;
                $level = 'Level 1';
            }
        }

        if (!$level && $this->donorPortal->percentile_2 === DonorPortal::TOP_1_PERCENT) {
            if ($percentile <= 1) {
                $ranking = 1;
                $level = 'Level 2';
            }
        }

        if (!$level && $this->donorPortal->percentile_2 === DonorPortal::TOP_5_PERCENT) {
            if ($percentile <= 5) {
                $ranking = 4;
                $level = 'Level 2';
            }
        }
        if (!$level && $this->donorPortal->percentile_12 === DonorPortal::TOP_10_PERCENT) {
            if ($percentile <= 10) {
                $ranking = 10;
                $level = 'Level 2';
            }
        }
        if (!$level && $this->donorPortal->percentile_2 === DonorPortal::TOP_20_PERCENT) {
            if ($percentile <= 20) {
                $ranking = 20;
                $level = 'Level 2';
            }
        }
        if (!$level && $this->donorPortal->percentile_2 === DonorPortal::TOP_40_PERCENT) {
            if ($percentile <= 40) {
                $ranking = 40;
                $level = 'Level 2';
            }
        }

        if (!$level && $this->donorPortal->percentile_3 === DonorPortal::TOP_1_PERCENT) {
            if ($percentile <= 1) {
                $ranking = 1;
                $level = 'Level 3';
            }
        }

        if (!$level && $this->donorPortal->percentile_3 === DonorPortal::TOP_5_PERCENT) {
            if ($percentile <= 5) {
                $ranking = 5;
                $level = 'Level 3';
            }
        }
        if (!$level && $this->donorPortal->percentile_3 === DonorPortal::TOP_10_PERCENT) {
            if ($percentile <= 10) {
                $ranking = 10;
                $level = 'Level 3';
            }
        }
        if (!$level && $this->donorPortal->percentile_3 === DonorPortal::TOP_20_PERCENT) {
            if ($percentile <= 20) {
                $ranking = 20;
                $level = 'Level 3';
            }
        }
        if (!$level && $this->donorPortal->percentile_3 === DonorPortal::TOP_40_PERCENT) {
            if ($percentile <= 40) {
                $ranking = 40;
                $level = 'Level 3';
            }
        }

        if (!$level && $this->donorPortal->percentile_4 === DonorPortal::TOP_1_PERCENT) {
            if ($percentile <= 1) {
                $ranking = 1;
                $level = 'Level 4';
            }
        }

        if (!$level && $this->donorPortal->percentile_4 === DonorPortal::TOP_5_PERCENT) {
            if ($percentile <= 5) {
                $ranking = 5;
                $level = 'Level 4';
            }
        }
        if (!$level && $this->donorPortal->percentile_4 === DonorPortal::TOP_10_PERCENT) {
            if ($percentile <= 10) {
                $ranking = 10;
                $level = 'Level 4';
            }
        }
        if (!$level && $this->donorPortal->percentile_4 === DonorPortal::TOP_20_PERCENT) {
            if ($percentile <= 20) {
                $ranking = 20;
                $level = 'Level 4';
            }
        }
        if (!$level && $this->donorPortal->percentile_4 === DonorPortal::TOP_40_PERCENT) {
            if ($percentile <= 40) {
                $ranking = 40;
                $level = 'Level 4';
            }
        }

        if (!$level && $this->donorPortal->percentile_5 === DonorPortal::TOP_1_PERCENT) {
            if ($percentile <= 1) {
                $ranking = 1;
                $level = 'Level 5';
            }
        }

        if (!$level && $this->donorPortal->percentile_5 === DonorPortal::TOP_5_PERCENT) {
            if ($percentile <= 5) {
                $ranking = 5;
                $level = 'Level 5';
            }
        }
        if (!$level && $this->donorPortal->percentile_5 === DonorPortal::TOP_10_PERCENT) {
            if ($percentile <= 10) {
                $ranking = 10;
                $level = 'Level 5';
            }
        }
        if (!$level && $this->donorPortal->percentile_5 === DonorPortal::TOP_20_PERCENT) {
            if ($percentile <= 20) {
                $ranking = 20;
                $level = 'Level 5';
            }
        }
        if (!$level && $this->donorPortal->percentile_5 === DonorPortal::TOP_40_PERCENT) {
            if ($percentile <= 40) {
                $ranking = 40;
                $level = 'Level 5';
            }
        }

        return [
            'rank'       => $index,
            'total'      => $count,
            'percentile' => $percentile,
            'level'      => $level,
            'topPercent' => $ranking,
            'donors'     => $sortedArray
        ];
    }
}
