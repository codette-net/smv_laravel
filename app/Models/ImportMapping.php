<?php

namespace App\Models;

use Database\Factories\ImportMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportMapping extends Model
{
    /** @use HasFactory<ImportMappingFactory> */
    use HasFactory;

    protected $fillable = ['import_source_id', 'name', 'is_active', 'is_default'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean'];
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ImportMappingField::class)->orderBy('position');
    }
}
