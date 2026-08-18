<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VacancyController extends Controller
{
    private const SORTS = [
        'nieuwste' => 'Nieuwste',
        'deadline' => 'Deadline eerst',
        'az' => 'A–Z',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'zoek' => trim((string) $request->query('zoek', '')),
            'locatie' => trim((string) $request->query('locatie', '')),
            'categorie' => trim((string) $request->query('categorie', '')),
            'bedrijf' => trim((string) $request->query('bedrijf', '')),
            'dienstverband' => trim((string) $request->query('dienstverband', '')),
            'werklocatie' => trim((string) $request->query('werklocatie', '')),
            'sector' => trim((string) $request->query('sector', '')),
            'functiegebied' => trim((string) $request->query('functiegebied', '')),
            'ervaring' => trim((string) $request->query('ervaring', '')),
        ];
        $sort = array_key_exists($request->query('sort'), self::SORTS)
            ? $request->query('sort')
            : 'nieuwste';

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

        return view('vacancies.index', [
            'vacancies' => $vacancies->paginate(12)->withQueryString(),
            'filters' => $filters,
            'sort' => $sort,
            'sortOptions' => self::SORTS,
            'locations' => $this->locations(),
            'taxonomyOptions' => $this->taxonomyOptions(),
            'companies' => $this->companies(),
            'activeFilters' => $this->activeFilters($filters, $sort),
        ]);
    }

    public function show(Vacancy $vacancy)
    {
        $vacancy->load(['company', 'categories']);

        return $vacancy;

    }

    /** @return array<int, string> */
    private function locations(): array
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
    private function taxonomyOptions(): array
    {
        return collect([
            'dienstverband' => CategoryType::employment_type,
            'werklocatie' => CategoryType::workplace,
            'sector' => CategoryType::sector,
            'functiegebied' => CategoryType::function_area,
            'ervaring' => CategoryType::experience,
        ])->map(fn (CategoryType $type): Collection => Category::query()
            ->where('type', $type->value)
            ->whereHas('vacancies', fn (Builder $query): Builder => $query
                ->publiclyVisible()
                ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible()))
            ->orderBy('name')
            ->get())
            ->all();
    }

    /** @return Collection<int, Company> */
    private function companies()
    {
        return Company::query()
            ->publiclyVisible()
            ->whereHas('vacancies', fn (Builder $query): Builder => $query->publiclyVisible())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'deadline' => $query
                ->orderByRaw('deadline_at IS NULL')
                ->orderBy('deadline_at')
                ->orderByDesc('published_at'),
            'az' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at')->orderByDesc('created_at'),
        };
    }

    /** @param array<string, string> $filters */
    private function activeFilters(array $filters, string $sort): array
    {
        $labels = [
            'zoek' => 'Zoeken',
            'locatie' => 'Locatie',
            'categorie' => 'Categorie',
            'bedrijf' => 'Bedrijf',
            'dienstverband' => 'Dienstverband',
            'werklocatie' => 'Werklocatie',
            'sector' => 'Sector',
            'functiegebied' => 'Functiegebied',
            'ervaring' => 'Ervaring',
        ];

        $parameters = array_filter($filters, fn (string $value): bool => $value !== '');

        if ($sort !== 'nieuwste') {
            $parameters['sort'] = $sort;
        }

        return collect($filters)
            ->filter(fn (string $value): bool => $value !== '')
            ->map(fn (string $value, string $key): array => [
                'label' => $labels[$key].': '.$value,
                'url' => route('vacancies.index', array_diff_key($parameters, [$key => true])),
            ])
            ->values()
            ->all();
    }

    private function whereHasCategory(Builder $query, CategoryType $type, string $slug): Builder
    {
        return $query->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery
            ->where('type', $type->value)
            ->where('slug', $slug));
    }
}
