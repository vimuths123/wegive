<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Element extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    public static function boot()
    {
        parent::boot();
    }

    public function messageTemplates()
    {
        return $this->morphMany(MessageTemplate::class, 'owner');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function elementable()
    {
        return $this->morphTo();
    }

    public function donations()
    {
        return $this->hasMany(Transaction::class);
    }

    public function donors()
    {
        return $this->belongsToMany(Donor::class, Transaction::class, null, 'owner_id')->wherePivot('owner_type', 'donor');
    }

    public function getDonationCountAttribute()
    {
        $donations = $this->donations;

        return count($donations);
    }

    public function getNetDonationVolumeAttribute()
    {
        $donations = $this->donations;

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function getRecurringDonationVolumeAttribute()
    {
        $donations = $this->donations->whereNotNull('scheduled_donation_id');

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function getOneTimeDonationVolumeAttribute()
    {
        $donations = $this->donations->whereNull('scheduled_donation_id');

        return $donations->sum('amount') - $donations->sum('fee');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
