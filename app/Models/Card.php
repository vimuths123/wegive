<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Card extends Account
{
    use HasFactory;
    use SoftDeletes;



    protected $casts = [
        'primary' => 'boolean',
        'expires_at' => 'datetime'
    ];

    protected $fillable = ['tl_token', 'last_four', 'expiration', 'name', 'issuer'];

    public static function boot()
    {
        parent::boot();

        self::creating(
            function ($model) {
                $dateArray = explode('/', $model->expiration);
                $d = new Carbon();
                $d->month($dateArray[0]);
                $d->year($dateArray[1]);
                $d = $d->endOfMonth();
                $model->expires_at = $d;
            }
        );
        
        self::updating(
            function ($model) {
                $dateArray = explode('/', $model->expiration);
                $d = new Carbon();
                $d->month($dateArray[0]);
                $d->year($dateArray[1]);
                $d = $d->endOfMonth();
                $model->expires_at = $d;
            }
        );

        self::created(function ($model) {
            if (auth()->user()) {
                $currentLogin = auth()->user()->currentLogin;


                activity()->causedBy($currentLogin)->performedOn($model)->log("Added card ending in {$model->last_four} issued by {$model->issuer}");
            }
        });

        self::deleted(function ($model) {
            if (auth()->user()) {
                $currentLogin = auth()->user()->currentLogin;


                activity()->causedBy($currentLogin)->performedOn($model)->log("Removed card ending in {$model->last_four} issued by {$model->issuer}");
            }
        });
    }

    public function sentTransactions()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function scheduled_donations()
    {
        return $this->morphMany(ScheduledDonation::class, 'payment_method');
    }
}
