<?php

namespace App\Models;

use App\Jobs\ProcessNeonIntegrationCampaigns;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Fundraiser extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use HasSlug;


    protected $fillable = ['name', 'goal', 'description', 'owner_type', 'owner_id', 'publicity', 'recipient_id', 'recipient_type', 'expiration', 'show_activity', 'show_leader_board'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
    public static function boot()
    {
        parent::boot();

        self::created(
            function ($model) {
                if (auth()->user()) {
                    $currentLogin = auth()->user()->currentLogin;
                    $name = $model->name;

                    activity()->causedBy($currentLogin)->performedOn($model->recipient)->withProperties(['model_link' => $model->getMorphClass(), 'model_id' => $model->id])->log("Created fundraiser, {$name}");
                }

                try {
                    if ($model->recipient instanceof Organization) {
                        if ($n = $model->recipient->neonIntegration) {

                            ProcessNeonIntegrationCampaigns::dispatch($n, $model);
                        }
                    }
                } catch (Exception $e) {
                }
            }
        );

        self::deleted(
            function ($model) {
                if (auth()->user()) {
                    $currentLogin = auth()->user()->currentLogin;
                    $name = $model->name;

                    activity()->causedBy($currentLogin)->performedOn($model)->log("Ended fundraiser, {$name}");
                }
            }
        );

        self::updated(
            function ($model) {
                if (auth()->user()) {
                    $currentLogin = auth()->user()->currentLogin;

                    $name = $model->name;

                    activity()->causedBy($currentLogin)->performedOn($model)->log("Created fundraiser, {$name}");
                }

                if ($model->recipient instanceof Organization) {
                    if ($n = $model->recipient->neonIntegration) {
                        ProcessNeonIntegrationCampaigns::dispatch($n, $model);
                    }
                }
            }
        );
    }

    public function owner()
    {
        return $this->morphTo();
    }


    public function recipient()
    {
        return $this->morphTo();
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function donors()
    {
        return $this->belongsToMany(Donor::class, Transaction::class, null, 'owner_id')->wherePivot('owner_type', 'donor');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function products()
    {
        return $this->morphMany(Product::class, 'owner');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function totalRaised()
    {
        return $this->transactions->sum('amount');
    }
    public function leaderBoard()
    {

        $donors = $this->donors()->get();

        foreach ($donors as &$donor) {

            $donations = $donor->donations()->where('fundraiser_id', $this->id);
            $donor['given'] = $donations->sum('amount') - $donations->sum('fee');
        }

        $sorted =  $donors->sortByDesc(function ($item) {
            return $item['given'];
        })->values();


        return $sorted->take(15);
    }

    public function recentDonations()
    {

        return $this->transactions()->orderByDesc('created_at')->limit(15)->get();
    }
}
