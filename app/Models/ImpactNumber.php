<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpactNumber extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'static' => 'boolean',
    ];

    public function viewingGroups()
    {
        return $this->morphMany(ViewingGroups::class, 'object');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function donors()
    {
        return $this->belongsToMany(Donor::class, 'impact_number_donors')->withTimestamps();
    }

    public function viewers()
    {
        $users = array();
        foreach ($this->viewingGroups as $group) {
            $newUsers =  $group->destination->donors()->get()->pluck('id')->toArray();

            if (count($newUsers)) {

                $users = array_merge($users, $newUsers);
            }
        }

        return User::whereIn('id', array_unique($users))->get();
    }

    public function userCanView(User $user)
    {
        $userIds = $this->viewers()->pluck('id');

        return in_array($user->id, $userIds->all());
    }
}
