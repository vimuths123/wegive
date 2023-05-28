<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasFactory;

    public const INVITE_DONOR = 1;
    public const INVITE_EMPLOYEE = 2;
    public const INVITE_ORGANIZATION_ADMIN = 3;
    public const INVITE_GROUP_MEMBER = 4;

    protected $fillable = [
        'email', 'token', 'inviter_type', 'inviter_id', 'action'
    ];

    public function inviter()
    {
        return $this->morphTo();
    }

    public function accept($user = null)
    {
        if (!$user) $user = auth()->user();
        if (!$user || ($user->email !== $this->email)) return;

        if ($this->action === Invite::INVITE_EMPLOYEE) {
            $user->employers()->save($this->inviter);
        }

        if ($this->action === Invite::INVITE_ORGANIZATION_ADMIN) {
            $login = new Login();
            $login->loginable()->associate($this->inviter);
            $user->logins()->save($login);
            $user->currentLogin()->associate($login->loginable);
            $user->save();
        }

        if ($this->action === Invite::INVITE_GROUP_MEMBER) {
            $household = $this->inviter;
            $household->members()->attach($user->currentLogin);
            $household->save();
        }

        return $user;
    }
}
