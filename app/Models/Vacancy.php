<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    /** @use HasFactory<\Database\Factories\VacancyFactory> */
    use HasFactory;

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
}
