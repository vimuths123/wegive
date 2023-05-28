<?php

namespace App\Models;

use Exception;
use App\Processors\MailgunHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomEmailDomain extends Model
{
    use HasFactory;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomEmailAddress::class);
    }

    public function getMailgunDomain()
    {
        $mg = new MailgunHelper();

        try {
            return $mg->getDomain($this);
        } catch (Exception $e) {
            return null;
        }
    }
}
