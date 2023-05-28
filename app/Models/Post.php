<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    protected $fillable = ['content', 'title', 'posted_at'];

    protected $casts = [
        'posted_at' => 'timestamp',
    ];

    public function viewingGroups()
    {
        return $this->morphMany(ViewingGroups::class, 'object');
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


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donors()
    {
        return $this->belongsToMany(Donor::class, 'post_donors')->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeForGivelist($query, ...$givelistIds)
    {
        return $query->join('organization_post', 'organization_post.post_id', '=', 'posts.id')
            ->join('givelist_organization', 'organization_post.organization_id', '=', 'givelist_organization.organization_id')
            ->whereIn('givelist_organization.givelist_id', $givelistIds);
    }

    // userCanEditOrDelete => if post belongs to org and user belongs to org
    // getCreatedAtFormatWrittenAttribute => $this->created_at->format('F dS') . ' at ' . $this->created_at->format('h:ia')
    // getExcerptAttribute => return if less than 96, otherwise return preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1...', $this->content)
    //
}
