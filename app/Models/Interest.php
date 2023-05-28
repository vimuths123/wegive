<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    use HasFactory;
    protected $fillable = ['subject_id', 'subject_type'];

    // From this
    public function subject()
    {
        return $this->morphTo();
    }

    // Actual user who created
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Actual user who created
    public function enthusiast()
    {
        return $this->morphTo();
    }
}
