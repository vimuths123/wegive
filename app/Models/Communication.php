<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use HasFactory;

    public const STATUS_SENT = 1;

    public const TYPE_SMS = 1;
    public const TYPE_EMAIL = 2;

    public function message() {
        return $this->morphTo();
    }
    
    public function sender() {
        return $this->morphTo();
    }

    public function receiver() {
        return $this->morphTo();
    }

    public function subject() {
        return $this->morphTo();
    }

    public function initiator() {
        return $this->morphTo();
    }
}
