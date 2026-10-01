<?php

namespace App\Filament\Resources\Vacancies\Pages\Concerns;

use App\Enums\CategoryType;
use App\Models\Vacancy;

trait SynchronizesVacancyTaxonomies
{
    /** @var array<string, CategoryType> */
    private const TAXONOMY_FIELDS = [
        'employment_type_categories' => CategoryType::employment_type,
        'workplace_categories' => CategoryType::workplace,
        'sector_categories' => CategoryType::sector,
        'function_area_categories' => CategoryType::function_area,
        'experience_categories' => CategoryType::experience,
    ];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (self::TAXONOMY_FIELDS as $field => $type) {
            $data[$field] = $this->record->categories()
                ->where('type', $type->value)
                ->pluck('categories.id')
                ->all();
        }

        return $data;
    }

    protected function syncTaxonomies(Vacancy $vacancy): void
    {
        $taxonomyIds = collect(self::TAXONOMY_FIELDS)
            ->flatMap(fn (CategoryType $type, string $field) => $this->data[$field] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $otherCategoryIds = $vacancy->categories()
            ->whereNotIn('type', array_map(fn (CategoryType $type): string => $type->value, self::TAXONOMY_FIELDS))
            ->pluck('categories.id')
            ->all();

        $vacancy->categories()->sync([...$otherCategoryIds, ...$taxonomyIds]);
    }
}
