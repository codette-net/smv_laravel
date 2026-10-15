<?php

namespace App\Models;

use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if ($category->parent_id === null) {
                return;
            }

            if ($category->parent_id === $category->getKey()) {
                throw new InvalidArgumentException('Een categorie kan niet haar eigen bovenliggende categorie zijn.');
            }

            $parent = $category->parent()->first();

            if ($parent === null || $parent->type !== $category->type) {
                throw new InvalidArgumentException('Een bovenliggende categorie moet hetzelfde type hebben.');
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->extraScope(fn ($query) => $query->where('type', $this->type instanceof CategoryType ? $this->type->value : $this->type));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function vacancies(): MorphToMany
    {
        return $this->morphedByMany(Vacancy::class, 'categoryable');
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'categoryable');
    }

    public function blogPosts(): MorphToMany
    {
        return $this->morphedByMany(BlogPost::class, 'categoryable');
    }
}
