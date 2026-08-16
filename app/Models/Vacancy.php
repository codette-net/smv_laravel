<?php

namespace App\Models;

use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'company_id',
        'import_source_id',
        'title',
        'slug',
        'description',
        'location',
        'application_email',
        'application_url',
        'salary_min',
        'salary_max',
        'rate_min',
        'rate_max',
        'reference',
        'source_reference',
        'deadline_at',
        'expires_at',
        'is_featured',
        'is_filled',
        'status',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_filled' => 'boolean',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'rate_min' => 'integer',
            'rate_max' => 'integer',
            'status' => VacancyStatus::class,
            'source' => VacancySource::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }

    public function vacancy_url(): string
    {
        return '/vacature/'.$this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
