<?php

namespace App\Support\Vacancies;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VacancyFilterOptions
{
    public const SORTS = [
        'nieuwste' => 'Nieuwste',
        'deadline' => 'Deadline eerst',
        'az' => 'A–Z',
    ];

    /** @return array<string, string> */
    public function emptyFilters(): array
    {
        return array_fill_keys([
            'zoek',
            'locatie',
            'categorie',
            'bedrijf',
            'dienstverband',
            'werklocatie',
            'sector',
            'functiegebied',
            'ervaring',
        ], '');
    }

    /** @return array<int, string> */
    public function locations(): array
    {
        return Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible())
            ->whereNotNull('location')
            ->whereRaw("TRIM(location) <> ''")
            ->selectRaw('TRIM(location) as location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->all();
    }

    /** @return array<string, Collection<int, Category>> */
    public function taxonomyOptions(): array
    {
        return collect(self::taxonomyFilterTypes())
            ->map(fn (CategoryType $type): Collection => Category::query()
                ->with('parent')
                ->where('type', $type->value)
                ->whereHas('vacancies', fn (Builder $query): Builder => $query
                    ->publiclyVisible()
                    ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible()))
                ->orderBy('name')
                ->get())
            ->all();
    }

    /** @return Collection<int, Company> */
    public function companies(): Collection
    {
        return Company::query()
            ->publiclyVisible()
            ->whereHas('vacancies', fn (Builder $query): Builder => $query->publiclyVisible())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /** @return array<string, CategoryType> */
    public static function taxonomyFilterTypes(): array
    {
        return [
            'dienstverband' => CategoryType::employment_type,
            'werklocatie' => CategoryType::workplace,
            'sector' => CategoryType::sector,
            'functiegebied' => CategoryType::function_area,
            'ervaring' => CategoryType::experience,
        ];
    }
}
