<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'type',

    ];

    public function vacancies(): MorphToMany
    {
        return $this->morphedByMany(Vacancy::class, 'categoryable');
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'categoryable');
    }

    public function blog_posts(): MorphToMany
    {
        return $this->morphedByMany(BlogPost::class, 'categoryable');
    }
}
