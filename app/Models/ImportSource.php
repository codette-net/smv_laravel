<?php

namespace App\Models;

use App\Enums\ImportType;
use Database\Factories\ImportSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportSource extends Model
{
    /** @use HasFactory<ImportSourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'endpoint_url',
        'auth_type',
        'credentials',
        'default_mapping',
        'is_active',
        'last_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'credentials' => 'encrypted:array',
            'default_mapping' => 'array',
            'is_active' => 'boolean',
            'last_imported_at' => 'datetime',
        ];
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }
}
