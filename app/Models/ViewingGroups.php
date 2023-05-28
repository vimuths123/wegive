<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewingGroups extends Model
{
    use HasFactory;

    protected $fillable = ['destination_id', 'destination_type'];

    public function object()
    {
        return $this->morphTo();
    }

    public function destination()
    {
        return $this->morphTo();
    }
}
