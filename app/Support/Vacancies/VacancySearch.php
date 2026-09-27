<?php

namespace App\Support\Vacancies;

use App\Enums\CategoryType;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VacancySearch
{
    /** @return array<string, string> */
    public function filters(Request $request, VacancyFilterOptions $filterOptions): array
    {
        return collect($filterOptions->emptyFilters())
            ->map(fn (string $default, string $filter): string => trim((string) $request->query($filter, $default)))
            ->all();
    }

    public function sort(Request $request): string
    {
        return array_key_exists($request->query('sort'), VacancyFilterOptions::SORTS)
            ? $request->query('sort')
            : 'nieuwste';
    }

    /** @param array<string, string> $filters */
    public function query(array $filters, string $sort): Builder
    {
        $vacancies = Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible())
            ->with(['company.media', 'categories'])
            ->when($filters['zoek'] !== '', function (Builder $query) use ($filters): Builder {
                $search = '%'.$filters['zoek'].'%';

                return $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('company', fn (Builder $query): Builder => $query->where('name', 'like', $search));
                });
            })
            ->when($filters['locatie'] !== '', fn (Builder $query): Builder => $query
                ->whereRaw('TRIM(location) = ?', [$filters['locatie']]))
            ->when($filters['dienstverband'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::employment_type, $filters['dienstverband']))
            ->when($filters['werklocatie'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::workplace, $filters['werklocatie']))
            ->when($filters['sector'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::sector, $filters['sector']))
            ->when($filters['functiegebied'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::function_area, $filters['functiegebied']))
            // `categorie` remains a backward-compatible alias from SMV-022.
            ->when($filters['functiegebied'] === '' && $filters['categorie'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery
                    ->whereIn('type', [CategoryType::function_area->value, CategoryType::vacancy_category->value])
                    ->where('slug', $filters['categorie'])))
            ->when($filters['ervaring'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::experience, $filters['ervaring']))
            ->when($filters['bedrijf'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('company', fn (Builder $query): Builder => $query
                    ->where('slug', $filters['bedrijf'])));

        $this->applySort($vacancies, $sort);

        return $vacancies;
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'deadline' => $query
                ->orderByRaw('deadline_at IS NULL')
                ->orderBy('deadline_at')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            'az' => $query->orderBy('title')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    private function whereHasCategory(Builder $query, CategoryType $type, string $slug): Builder
    {
        return $query->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery
            ->where('type', $type->value)
            ->where('slug', $slug));
    }
}
