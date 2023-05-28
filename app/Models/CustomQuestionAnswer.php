<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['*'];

    public function customQuestion() {
        return $this->belongsTo(CustomQuestion::class);
    }

    public function owner() {
        return $this->morphTo();
    }
}
