<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeonMappingRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const NEON = 1;
    public const SALESFORCE = 2;

    public const ACCOUNT = 1;
    public const DONATION = 2;
    public const CAMPAIGN = 3;
    public const RECURRING_DONATION = 4;
    public const ADDRESS = 5;
    public const FUND = 6;


    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
