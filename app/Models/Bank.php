<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Account
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'primary' => 'boolean',
        'user_agreed' => 'boolean',
    ];

    protected $fillable = ['tl_token', 'last_four', 'name'];


    public static function boot()
    {
        parent::boot();



        self::created(function ($model) {
            if(!auth()->user()) return;
            $currentLogin = auth()->user()->currentLogin;

            

            activity()->causedBy($currentLogin)->performedOn($model)->log("Added bank ending in {$model->last_four}");
        });

        self::deleted(function ($model) {
            if(!auth()->user()) return;
            $currentLogin = auth()->user()->currentLogin;

            

            activity()->causedBy($currentLogin)->performedOn($model)->log("Removed bank ending in {$model->last_four}");
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

    public function hasBeenUsed()
    {
        return count($this->sentTransactions()->get()) > 0;
    }


    public function timesUsed($donor)
    {
        return count($donor->donations->where('source_type', 'card')->where('source_id', $this->id)->get());
    }
}
