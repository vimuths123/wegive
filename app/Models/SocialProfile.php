<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialProfile extends Model
{
    use HasFactory;

    protected $hidden = ['provider_id', 'token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
