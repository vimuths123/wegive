<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'content'];


    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $currentLogin = auth()->user()->currentLogin;

            activity()->causedBy($currentLogin)->performedOn($model->commentable)->log("Made a comment on {$model->organization->name}'s post");
        });

        self::deleted(function ($model) {
            $currentLogin = auth()->user()->currentLogin;

            activity()->causedBy($currentLogin)->performedOn($model->commentable)->log("Removed a comment from {$model->organization->name}'s post");
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}
