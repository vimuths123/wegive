<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowerUser extends Model
{
    use HasFactory;

    protected $fillable = ['requested_at', 'accepted_at'];

    protected $table = 'follower_user';

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {

            activity()->causedBy(auth()->user()->currentLogin)->performedOn($model->user)->log("{$model->follower->name} followed {$model->user->name}");
        });

        self::deleted(function ($model) {
            activity()->causedBy(auth()->user()->currentLogin)->performedOn($model->user)->log("{$model->follower->name} unfollowed {$model->user->name}");
        });
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
