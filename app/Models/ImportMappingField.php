<?php

namespace App\Models;

use App\Imports\Mapping\DestinationRegistry;
use Database\Factories\ImportMappingFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMappingField extends Model
{
    /** @use HasFactory<ImportMappingFieldFactory> */
    use HasFactory;

    protected $fillable = ['import_mapping_id', 'destination_key', 'operation', 'source_paths', 'configuration', 'position'];

    protected function casts(): array
    {
        return ['source_paths' => 'array', 'configuration' => 'array', 'position' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $field) => app(DestinationRegistry::class)->get($field->destination_key));
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ImportMapping::class, 'import_mapping_id');
    }
}
