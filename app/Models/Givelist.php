<?php

namespace App\Models;

use App\Actions\Payments;
use Illuminate\Http\Request;
use Spatie\Sluggable\HasSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class Givelist extends Model implements HasMedia, Auditable
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['name'];

    protected $casts = [
        'active' => 'boolean',
        'is_public' => 'boolean',
        'given_by_user' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function scopePublic($query)
    {
        return $query->where(function ($query) {
            $query->where('is_public', true);
        });
    }

    public function getIconUrlAttribute()
    {
        return $this->getAvatarUrlAttribute();
    }

    public function getAvatarUrlAttribute()
    {
        return $this->getFirstMediaUrl('avatar');
    }

    public function fundraisers()
    {
        return $this->morphMany(Fundraiser::class, 'recipient');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function receivedTransactions()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function organizations()
    {
        // TODO: create pivot table model and use softdeletes, this is for the transaction stuff
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function scheduledDonations()
    {
        return $this->morphMany(ScheduledDonation::class, 'destination');
    }

    public function getCategoriesAttribute()
    {
        $this->load('organizations.categories');

        return $this->organizations->pluck('categories')->flatten()->unique('id');
    }

    // checkIfUserCanEdit => admin true, self true

    public function give($source, $amount, $description = 'None', $scheduledDonationId = null, $tip = 0, $givelistId = null, $fundraiserId = null, $fundId = null, $anonymous = true, $coverFees = false, $feeAmount = 0)
    {
        abort_unless($amount > 0, 400, 'Amount must be positive.');
        abort_unless($amount < 10000000, 'Amount must be reasonable.');


        $currentLogin = auth()->user()->currentLogin;


        if ($currentLogin === null) {
            if ($source instanceof User) {
                $currentLogin = $source;
            } else if ($source instanceof Card || $source instanceof Bank) {
                $currentLogin = $source->owner;
            }
        }

        auth()->user()->isAbleToUsePaymentMethod($source);

        // if (!$currentLogin->interests()->where([['subject_type', 'givelist'], ['subject_id', $this->id]])->first() && !$this->creator()->is($currentLogin)) {
        //     $interest = new Interest();
        //     $interest->subject()->associate($this);
        //     $currentLogin->interests()->save($interest);
        // }


        $transaction = new Transaction();
        $transaction->scheduled_donation_id = $scheduledDonationId;
        $transaction->user()->associate(auth()->user());
        $transaction->source()->associate($source);
        $transaction->amount = round($amount);
        $transaction->description = $description;
        $transaction->owner()->associate($currentLogin);
        $transaction->destination()->associate($this);
        $transaction->anonymouse = $anonymous;
        $transaction->cover_fees = $coverFees;
        $transaction->fee_amount = $feeAmount;
        $transaction->fee = round($tip);
        $transaction = Payments::processTransaction($transaction);
        $transaction->save();

        if ($transaction->status === Transaction::STATUS_FAILED) {
            abort(400, 'The transaction has failed');
        }



        return $transaction;
    }

    public function getGiversAttribute()
    {
        $userIds = $this->receivedTransactions()->where('status', Transaction::STATUS_SUCCESS)->groupBy(['source_id'])->where('source_type', 'user')->pluck('source_id');

        return User::query()->find($userIds);
    }

    public function posts()
    {
        return Post::query()->whereHas('organization', function ($query) {
            $query->whereIn('id', $this->organizations->pluck('id')->all());
        });
    }

    public function givenByDonor($donor)
    {
        if (!$donor) {
            return 0;
        }

        return Transaction::where([['givelist_id', $this->id], ['owner_id', $donor->id], ['owner_type', $donor->getMorphClass()]])->orWhere([['destination_type', 'givelist'], ['destination_id', $this->id], ['owner_id', $donor->id], ['owner_type', $donor->getMorphClass()]])->sum('amount') / 100;
    }

    public function givenThisYearByUser($user)
    {
        if (!$user) {
            return 0;
        }
        return $this->transactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->where('created_at', '>', date('Y-m-d', strtotime(date('Y-01-01'))))->where([['destination_id', $this->id], ['owner_id', $user->id], ['owner_type', $user->getMorphClass()]])->sum('amount') / 100;
    }
    public function fundraisedByUser($user)
    {
        if (!$user) {
            return 0;
        }
        $fundraisers = $user->fundraisers()->pluck('id');

        $transactions = $this->transactions()->whereIn('status', [Transaction::STATUS_SUCCESS, Transaction::STATUS_PROCESSING, Transaction::STATUS_PENDING])->whereIn('fundraiser_id', $fundraisers)->get();

        return $transactions->sum('amount') / 100;
    }
}
