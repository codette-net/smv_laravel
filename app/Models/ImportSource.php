<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportSource extends Model
{
    /** @use HasFactory<\Database\Factories\ImportSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'endpoint_url',
        'auth_type',
        'credentials',
        'default_mapping',
        'is_active',
        'last_imported_at'
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'default_mapping' => 'array'
    ];
}
