<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tagline',
        'description',
        'email',
        'phone',
        'website',
        'logo',
        'cover_image',
        'location',
        'linkedin_url',
        'facebook_url',
        'instagram_url',
        'video_url',
        'status',
        'is_featured'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public function blog_posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function categories(): morphToMany
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }




}
