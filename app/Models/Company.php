<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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



}
