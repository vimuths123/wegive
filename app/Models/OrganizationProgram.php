<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationProgram extends Model
{
    use HasFactory;

    protected $dates = [
        'e_return_period',
    ];



    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
