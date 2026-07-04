<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacancy extends Model
{
    /** @use HasFactory<\Database\Factories\VacancyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'description',
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
        'source'
    ];



    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function categories(): morphToMany
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }

    public function vacancy_url(): string
    {
        return '/vacature/' . $this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
