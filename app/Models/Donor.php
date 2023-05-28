<?php

namespace App\Models;

use App\Http\Resources\BankResource;
use App\Http\Resources\CardResource;
use App\Jobs\ProcessDonorPerfectDonors;
use App\Jobs\ProcessNeonIntegrationDonors;
use App\Jobs\ProcessSalesforceDonorIntegration;
use Aws\S3\S3MultiRegionClient;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\CausesActivity as TraitsCausesActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Donor extends Model implements Auditable, HasMedia
{
    use \OwenIt\Auditing\Auditable;

    use HasFactory;
    use InteractsWithMedia;
    use TraitsCausesActivity;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean'
    ];

    public const SEARCH_ARRAY = ['{{donorName}}', '{{totalGiven}}', '{{totalFundraised}}', '{{numberOfFundraisers}}', '{{lastGivenDate}}', '{{firstGivenDate}}', '{{mobilePhone}}', '{{email}}'];

    /**
     * @return array
     */
    public function replaceArray()
    {
        return [$this->name, "$" . number_format($this->totalDonated / 100, 2, ".", ","), "$" . number_format($this->totalFundraised / 100, 2, ".", ","), count($this->fundraisers), $this->donations()->first() ? $this->donations()->first()->created_at : null, $this->donations()->latest()->first() ? $this->donations()->latest()->first()->created_at : null, $this->mobile_phone, $this->email_1];
    }

    /**
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->name) && (!empty($model->first_name) || !empty($model->last_name))) {
                $model->name = trim($model->first_name . ' ' . $model->last_name);
            }
        });

        self::updating(function ($model) {
            if (($model->isDirty('first_name') || $model->isDirty('last_name')) && $model->isDirty('name') === false) {
                $model->name = trim($model->first_name . ' ' . $model->last_name);
            }
        });

        self::created(function ($model) {
            if (!$model->organization) {
                return;
            }

            if ($n = $model->organization->neonIntegration) {
                ProcessNeonIntegrationDonors::dispatch($n, $model);
            }

            if (($s = $model->organization->salesforceIntegration) && $s->enabled && !$model->salesforce_id) {
                ProcessSalesforceDonorIntegration::dispatch($s, $model);
            }

            $dp = $model->organization->donorPerfectIntegration;
            if ($dp && $dp->enabled) {
                ProcessDonorPerfectDonors::dispatch($dp, $model);
            }

            $webhooks = $model->organization->webhooks()->whereIn('action', [Webhook::NEW_DONOR, Webhook::NEW_OR_UPDATED_DONOR])->get();

            foreach ($webhooks as $webhook) {
                $webhook->trigger($model);
            }
        });

        self::updated(function ($model) {
            if (!$model->organization) {
                return;
            }

            if ($n = $model->organization->neonIntegration) {
                ProcessNeonIntegrationDonors::dispatch($n, $model);
            }

            if (($s = $model->organization->salesforceIntegration) && $s->enabled) {
                ProcessSalesforceDonorIntegration::dispatch($s, $model);
            }

            $dp = $model->organization->donorPerfectIntegration;
            if ($dp && $dp->enabled) {
                ProcessDonorPerfectDonors::dispatch($dp, $model);
            }

            $webhooks = $model->organization->webhooks()->whereIn('action', [Webhook::UPDATED_DONOR, Webhook::NEW_OR_UPDATED_DONOR])->get();

            foreach ($webhooks as $webhook) {
                $webhook->trigger($model);
            }
        });
    }

    /**
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_donors');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function impactNumbers()
    {
        return $this->belongsToMany(ImpactNumber::class, 'impact_number_donors');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function preferredPayment()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function logins()
    {
        return $this->morphMany(Login::class, 'loginable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function donations()
    {
        return $this->transactions();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function receivedCommunications()
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function scheduledDonations()
    {
        return $this->morphMany(ScheduledDonation::class, 'source');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function fundraisers()
    {
        return $this->morphMany(Fundraiser::class, 'owner');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activeFundraisers()
    {
        return $this->fundraisers()->where('expiration', '>', now());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function expiredFundraisers()
    {
        return $this->fundraisers()->where('expiration', '<', now());
    }

    /**
     * @return int|mixed
     */
    public function getTotalDonatedAttribute()
    {
        return $this->donations()->sum('amount');
    }

    /**
     * @return int
     */
    public function getTotalFundraisedAttribute()
    {
        $fundraisers = $this->fundraisers()->pluck('id');

        return $fundraisers->count() ? Transaction::whereIn('fundraiser_id', $fundraisers)->sum('amount') : 0;
    }

    /**
     * @return array
     */
    public function getAccountsAttribute()
    {
        $usedCards = $this->donations()->where('source_type', 'card')->pluck('source_id')->unique()->all();
        $usedBanks = $this->donations()->where('source_type', 'bank')->pluck('source_id')->unique()->all();

        return [
            'cards'         => CardResource::collection(Card::whereIn('id', $usedCards)->get()->sortByDesc('created_at')),
            'bank_accounts' => BankResource::collection(Bank::whereIn('id', $usedBanks)->get()->sortByDesc('created_at')),
        ];
    }

    /**
     * @return int
     */
    public function getTotalReferralsAttribute()
    {
        return 0;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function activity()
    {
        return collect([['title' => 'Joined ' . $this->organization->name, 'icon' => 'mdi-cake', 'date' => $this->created_at]]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function myActivity()
    {
        return $this->actions();
    }

    /**
     * @return array
     */
    public function stats()
    {
        $transactionsBuilder = $this->donations()->where(['destination_id' => $this->organization_id, 'destination_type' => 'organization']);

        $totalTransactions = $transactionsBuilder->sum('amount');

        $countTransactions = $transactionsBuilder->count();

        $historyTransactions = $transactionsBuilder->where('created_at', '>', now()->subMonths(12))->get()->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('m-Y');
        });

        $history = [];
        for ($x = 12; $x >= 1; $x--) {
            $ym = now()->subMonths($x)->format('m-Y');
            $history[$ym] = data_get($historyTransactions, $ym, []);
        }

        return [
            'total'   => round($totalTransactions / 100, 2),
            'count'   => $countTransactions,
            'history' => $history
        ];
    }

    /**
     * @param $year
     * @return string
     */
    public function taxDocument($year)
    {
        [$startDate, $endDate] = [Carbon::parse($year . '-01-01'), Carbon::parse($year . '-12-31')];

        $givelistTransactions = $this->transactions()->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('destination_type', ['organization', 'givelist', 'donor'])
            ->where('direct_deposit', 0)
            ->whereNotNull('correlation_id')
            ->where('source_type', '!=', 'donor')
            ->get()->unique('id');

        $orgsTransactions = $this->transactions()->whereBetween('created_at', [$startDate, $endDate])
            ->where('destination_type', 'organization')
            ->where('source_type', '!=', 'donor')
            ->get()->groupBy('destination_id')->unique('id');

        $array = [['Name', 'EIN', 'Amount']];

        if (count($givelistTransactions) > 0) {
            $array[] = ['name' => 'Givelist Foundation', 'ein' => '84-2054638', 'amount' => '$' . round($givelistTransactions->sum('amount') / 100, 2)];
        }

        foreach ($orgsTransactions as $orgTransactions) {
            $array[] = ['name' => $orgTransactions->first()->destination->name, 'ein' => $orgTransactions->first()->destination->ein, 'amount' => '$' . round($orgTransactions->sum('amount') / 100, 2)];
        }

        $pdf = PDF::loadView('emails.taxdocument', ['ownerName' => $this->name, 'year' => $year, 'writeoffs' => $array, 'logo' => null]);

        $request = $this->storePDF($pdf);

        return (string)$request->getUri();
    }

    public function households()
    {
        return $this->belongsToMany(Household::class, 'household_donor', 'donor_id')->withTimestamps();
    }

    public function givingHistoryDocument($year)
    {
        $organization = $this->organization;

        [$startDate, $endDate] = [Carbon::parse($year . '-01-01'), Carbon::parse($year . '-12-31')];

        $transactionsByUser = $this->transactions()->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('destination_type', ['organization', 'givelist', 'donor'])
            ->where('source_type', '!=', 'donor')
            ->get()->unique('id')->groupBy('user_id');

        $array = [];

        foreach ($transactionsByUser as $userId => $transactions) {
            $array[] = ['email' => optional(User::find($userId))->email ?: 'Offline', 'donations' => $transactions];
        }

        $pdf = PDF::loadView('emails.givinghistory', [
            'donorName'        => $this->name,
            'year'             => $year,
            'donationsByUser'  => $array,
            'logo'             => null,
            'organizationName' => $organization->name,
            'organizationDba'  => $organization->dba,
            'ein'              => $organization->ein
        ]);

       $request = $this->storePDF($pdf);

        return (string)$request->getUri();
    }

    /**
     * @param $pdf
     * @param $name
     * @return \Psr\Http\Message\RequestInterface
     */
    private function storePDF($pdf, $name = null)
    {
        $name = $name ?: Str::random(32);

        $client = new S3MultiRegionClient([
            'credentials' => ['key' => config('services.aws.key'), 'secret' => config('services.aws.secret')],
            'version'     => 'latest',
        ]);

        $adapter = new AwsS3Adapter($client, 'wegivelist-private');

        $adapter->write($name, $pdf->output(), new Config());

        $result = $client->getCommand('GetObject', ['Bucket' => 'wegivelist-private', 'Key' => $name]);

        return $client->createPresignedRequest($result, '+20 minutes');
    }
}
