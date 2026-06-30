<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'candidate_id',
        'candidate_name',
        'candidate_email',
        'candidate_phone',
        'candidate_location',
        'linkedin_url',
        'cv_path',
        'motivation',
        'status'
    ];
}
