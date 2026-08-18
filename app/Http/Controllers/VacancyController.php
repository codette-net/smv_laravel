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
            ->when($filters['categorie'] !== '', fn (Builder $query): Builder => $query
                ->whereHas('categories', fn (Builder $query): Builder => $query
                    ->where('type', CategoryType::vacancy_category->value)
                    ->where('slug', $filters['categorie'])))
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
            'categories' => $this->categories(),
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

    /** @return Collection<int, Category> */
    private function categories()
    {
        return Category::query()
            ->where('type', CategoryType::vacancy_category->value)
            ->whereHas('vacancies', fn (Builder $query): Builder => $query
                ->publiclyVisible()
                ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible()))
            ->orderBy('name')
            ->get();
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
}
