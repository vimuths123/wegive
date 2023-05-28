<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checkout extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;


    protected $guarded = ['id'];

    protected $casts = [
        'informational_content' => 'boolean',
        'suggested_amount_descriptions' => 'boolean',
        'recurring_suggested_amount_descriptions' => 'boolean',
        'conversion_step' => 'boolean',
        'allow_frequency_change' => 'boolean',
        'designation' => 'boolean',
        'tribute' => 'boolean',
        'credit_card' => 'boolean',
        'apple_pay' => 'boolean',
        'google_pay' => 'boolean',
        'ach' => 'boolean',
        'bank_login' => 'boolean',
        'crypto' => 'boolean',
        'show_savings' => 'boolean',
        'fee_pass' => 'boolean',
        'default_to_covered' => 'boolean',
        'tipping' => 'boolean',
        'anonymous_donations' => 'boolean',
    ];

    public const PRESET_AMOUNT_SINGULAR = 1;
    public const PRESET_AMOUNT_SUGGESTED = 2;
    public const PRESET_AMOUNT_CUSTOM_AND_SUGGESTED = 3;


    public const ONE_TIME_ONLY = 1;
    public const RECURRING_ONLY = 2;
    public const ONE_TIME_AND_RECURRING = 3;

    public const BEFORE_CONFIRMATION = 1;
    public const AFTER_CONFIRMATION = 2;



    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    public function recipient()
    {
        return $this->morphTo();
    }

    public function designations()
    {
        return $this->recipient->funds;
    }

    public function impactNumber()
    {
        return $this->belongsTo(ImpactNumber::class);
    }


    public function customQuestions()
    {
        return $this->hasMany(CustomQuestion::class);
    }
}
