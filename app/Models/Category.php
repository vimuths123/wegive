<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $casts = [
        'ntees' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function givelists()
    {
        return Givelist::query()->where('is_public', true)->whereHas('organizations.categories', function ($query) {
            $query->where('category_id', $this->id);
        });
    }

    public function getGivelistsAttribute(): Collection
    {
        return $this->givelists()->get();
    }

    public function getFundsAttribute(): Collection
    {
        return $this->getGivelistsAttribute();
    }

    public static function getFeaturedCategoryIds(): array
    {
        $defaultIds = [338, 371, 116, 450, 284, 440, 448, 432, 608];
        $cachedIds = cache()->get('featured_category_ids');

        return $cachedIds ?? $defaultIds;
    }

    public function getIsFeaturedAttribute(): bool
    {
        return in_array($this->id, self::getFeaturedCategoryIds());
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getBannerImageAttribute()
    {
        $keywords = $this->getKeywordsFromName();

        return "https://loremflickr.com/g/1440/250/$keywords";
    }

    public function getOrderAttribute(): int
    {
        return 0;
    }

    private function getKeywordsFromName()
    {
        $name = preg_replace('/[^a-zA-Z ]/', '', $this->name);
        $keywords = str_replace('  ', ' ', $name);
        $keywords = str_replace(' ', ',', $keywords);

        return strtolower($keywords);
    }

    public function getOrganizationsCount()
    {
        return Cache::remember("category.{$this->id}.organization.count", now()->addMonth(), function () {
            $results = \DB::select("select count(category_id) as count from category_organization where category_id = {$this->id}");
            $result = reset($results);

            return $result->count;
        });
    }

    public function getGivelistsCount()
    {
        return null;

        return Cache::remember("category.{$this->id}.givelist.count", now()->addWeek(), function () {
            return $this->funds->count();
        });
    }

    public static function getDescendants(Category $category, $childrenCarry)
    {
        /** @var Collection $childrenCarry */
        $childrenCarry = $childrenCarry->add($category);

        $newChildren = $category->children;
        if ($newChildren->count() === 0) {
            return $childrenCarry;
        }

        foreach ($newChildren as $child) {
            $childrenCarry = self::getDescendants($child, $childrenCarry);
        }

        return $childrenCarry;
    }

    public function getDescendantsHelper()
    {
        return self::getDescendants($this, collect());
    }
}
