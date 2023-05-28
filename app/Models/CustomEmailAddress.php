<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomEmailAddress extends Model
{
    use HasFactory;

    protected $fillable = ['display_name', 'handle'];

    public function domain()
    {
        return $this->belongsTo(CustomEmailDomain::class, 'custom_email_domain_id');
    }
}
