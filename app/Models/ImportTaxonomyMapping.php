<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportTaxonomyMapping extends Model
{
    protected $fillable = ['import_source_id', 'category_type', 'source_key', 'source_value', 'category_id'];

    protected function casts(): array
    {
        return ['category_type' => CategoryType::class];
    }

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            $mapping->source_value = trim($mapping->source_value);
            $mapping->source_key = Str::lower($mapping->source_value);
            $category = $mapping->category()->first();

            if ($category === null || $category->type !== $mapping->category_type) {
                throw new InvalidArgumentException('Een taxonomiekoppeling moet naar een categorie van hetzelfde type verwijzen.');
            }
        });
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
