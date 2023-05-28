<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesforceApexTrigger extends Model
{
    use HasFactory;

    public function salesforceIntegration()
    {
        return $this->belongsTo(SalesforceIntegration::class);
    }
}
