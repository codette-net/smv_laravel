<?php

namespace App\Imports\Validation;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ImportSource;
use App\Models\ImportTaxonomyMapping;
use Illuminate\Support\Str;

class TaxonomyResolver
{
    public function resolve(ImportSource $source, CategoryType $type, mixed $value): array
    {
        $value = trim((string) $value);
        $key = Str::lower($value);
        if ($value === '') {
            return ['unresolved' => false];
        }
        $mapping = ImportTaxonomyMapping::query()->where('import_source_id', $source->id)->where('category_type', $type)->where('source_key', $key)->with('category')->first();
        if ($mapping?->category) {
            return ['category' => $mapping->category, 'source_value' => $value, 'explicit' => true];
        }
        $matches = Category::query()->where('type', $type)->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [$key])->orWhereRaw('LOWER(slug) = ?', [$key]))->get();
        if ($matches->count() === 1) {
            return ['category' => $matches->first(), 'source_value' => $value, 'explicit' => false];
        }

        return ['unresolved' => true, 'source_value' => $value];
    }
}
