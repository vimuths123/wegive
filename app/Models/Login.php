<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    use HasFactory;

    public const NOTIFICATION_FREQUENCY_DAILY = 1;
    public const NOTIFICATION_FREQUENCY_WEEKLY = 2;

    protected $guarded = ['id'];


    public function loginable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
