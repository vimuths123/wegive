<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'available_quantity'];

    // Model it belongs to
    public function owner()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function purchasers()
    {
        $userIds = $this->transactions()->groupBy(['source_id'])->where('source_type', 'user')->pluck('source_id');

        return User::query()->find($userIds);
    }


    public function getRemainingAvailabilityAttribute()
    {
        return $this->available_quantity - $this->transactions->count() ?? 0;
    }
}
