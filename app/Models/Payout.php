<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = ['pf_id'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
