<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Address extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    use HasFactory;

    protected $fillable = ['address_1', 'address_2', 'city', 'primary', 'state', 'type', 'zip'];


    protected $casts = [
        'primary' => 'boolean',

    ];
    public function addressable()
    {
        return $this->morphTo();
    }
}
