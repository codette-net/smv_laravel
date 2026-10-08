<?php

namespace App\Support\Vacancies;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VacancySearch
{
    /** @return array<string, string> */
    public function filters(Request $request, VacancyFilterOptions $filterOptions): array
    {
        return collect($filterOptions->emptyFilters())
            ->map(function (string $default, string $filter) use ($request): string {
                $value = $request->query($filter, $default);

                return is_scalar($value) || $value === null
                    ? trim((string) $value)
                    : '__ongeldige_invoer__';
            })
            ->all();
    }

    public function sort(Request $request): string
    {
        $sort = $request->query('sort');

        return is_string($sort) && array_key_exists($sort, VacancyFilterOptions::SORTS)
            ? $sort
            : 'nieuwste';
    }

    /** @param array<string, string> $filters */
    public function validationErrors(array $filters): array
    {
        $errors = [];
        $hasAmount = $filters['bedrag_van'] !== '' || $filters['bedrag_tot'] !== '';

        if (! $hasAmount) {
            return $errors;
        }

        if (! in_array($filters['vergoeding'], ['maand', 'uur'], true)) {
            $errors['vergoeding'] = 'Kies of je op bruto maandsalaris (FTE) of uurtarief wilt filteren.';
        }

        foreach (['bedrag_van' => 'Het minimumbedrag', 'bedrag_tot' => 'Het maximumbedrag'] as $field => $label) {
            if ($filters[$field] !== '' && (! ctype_digit($filters[$field]) || (int) $filters[$field] <= 0)) {
                $errors[$field] = $label.' moet een positief heel bedrag zijn.';
            }
        }

        if ($errors === []
            && $filters['bedrag_van'] !== ''
            && $filters['bedrag_tot'] !== ''
            && (int) $filters['bedrag_van'] > (int) $filters['bedrag_tot']) {
            $errors['bedrag_tot'] = 'Het maximumbedrag moet gelijk zijn aan of hoger zijn dan het minimumbedrag.';
        }

        return $errors;
    }

    /** @param array<string, string> $filters */
    public function secondaryFilterCount(array $filters): int
    {
        $count = collect($filters)
            ->only(['categorie', 'bedrijf', 'dienstverband', 'werklocatie', 'sector', 'functiegebied', 'ervaring', 'opleiding'])
            ->filter(fn (string $value): bool => $value !== '')
            ->count();

        return $count + (($filters['vergoeding'] !== '' || $filters['bedrag_van'] !== '' || $filters['bedrag_tot'] !== '') ? 1 : 0);
    }

    /** @param array<string, string> $filters */
    public function query(array $filters, string $sort): Builder
    {
        $vacancies = Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible())
            ->withSavedStateFor(auth()->user())
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
            ->when($filters['sector'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::sector, $filters['sector'], true))
            ->when($filters['functiegebied'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::function_area, $filters['functiegebied'], true))
            // `categorie` remains a backward-compatible alias from SMV-022.
            ->when($filters['functiegebied'] === '' && $filters['categorie'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery
                    ->whereIn('type', [CategoryType::function_area->value, CategoryType::vacancy_category->value])
                    ->where('slug', $filters['categorie'])))
            ->when($filters['ervaring'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::experience, $filters['ervaring']))
            ->when($filters['opleiding'] !== '', fn (Builder $query): Builder => $this->whereHasCategory($query, CategoryType::qualification, $filters['opleiding']))
            ->when($filters['bedrijf'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('company', fn (Builder $query): Builder => $query
                    ->where('slug', $filters['bedrijf'])));

        if ($this->validationErrors($filters) === [] && ($filters['bedrag_van'] !== '' || $filters['bedrag_tot'] !== '')) {
            $this->applyCompensationFilter($vacancies, $filters);
        }

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

    /** @param array<string, string> $filters */
    private function applyCompensationFilter(Builder $query, array $filters): void
    {
        $isMonthly = $filters['vergoeding'] === 'maand';
        $minimumColumn = $isMonthly ? 'salary_min' : 'rate_min';
        $maximumColumn = $isMonthly ? 'salary_max' : 'rate_max';

        $isMonthly
            ? $query->withComparableMonthlySalary()
            : $query->withComparableHourlyRate();

        if ($filters['bedrag_van'] !== '') {
            $query->whereRaw("COALESCE({$maximumColumn}, {$minimumColumn}) >= ?", [(int) $filters['bedrag_van']]);
        }

        if ($filters['bedrag_tot'] !== '') {
            $query->whereRaw("COALESCE({$minimumColumn}, {$maximumColumn}) <= ?", [(int) $filters['bedrag_tot']]);
        }
    }

    private function whereHasCategory(Builder $query, CategoryType $type, string $slug, bool $includeDescendants = false): Builder
    {
        $categoryIds = $this->categoryIds($type, $slug, $includeDescendants);

        if ($categoryIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery
            ->where('type', $type->value)
            ->whereIn('categories.id', $categoryIds));
    }

    /** @return Collection<int, int> */
    private function categoryIds(CategoryType $type, string $slug, bool $includeDescendants): Collection
    {
        $category = Category::query()
            ->where('type', $type->value)
            ->where('slug', $slug)
            ->first(['id']);

        if ($category === null) {
            return collect();
        }

        $ids = collect([$category->id]);

        if (! $includeDescendants) {
            return $ids;
        }

        $frontier = $ids;
        while ($frontier->isNotEmpty()) {
            $frontier = Category::query()
                ->where('type', $type->value)
                ->whereIn('parent_id', $frontier)
                ->whereNotIn('id', $ids)
                ->pluck('id');
            $ids = $ids->merge($frontier);
        }

        return $ids->unique()->values();
    }
}
