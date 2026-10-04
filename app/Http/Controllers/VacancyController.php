<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Vacancy;
use App\Support\Seo\StructuredData;
use App\Support\Vacancies\VacancyDescription;
use App\Support\Vacancies\VacancyFilterOptions;
use App\Support\Vacancies\VacancySearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VacancyController extends Controller
{
    public function index(Request $request, VacancyFilterOptions $filterOptions, VacancySearch $vacancySearch): View
    {
        $filters = $vacancySearch->filters($request, $filterOptions);
        $sort = $vacancySearch->sort($request);
        $vacancies = $vacancySearch->query($filters, $sort);

        $hasFilters = collect($request->query())->except('page')->filter(fn ($value): bool => filled($value))->isNotEmpty();
        $page = max(1, $request->integer('page', 1));

        return view('vacancies.index', [
            'vacancies' => $vacancies->paginate(12)->withQueryString(),
            'filters' => $filters,
            'sort' => $sort,
            'sortOptions' => VacancyFilterOptions::SORTS,
            'locations' => $filterOptions->locations(),
            'taxonomyOptions' => $filterOptions->taxonomyOptions(),
            'companies' => $filterOptions->companies(),
            'activeFilters' => $this->activeFilters($filters, $sort),
            'seoCanonical' => $hasFilters || $page === 1
                ? route('vacancies.index')
                : route('vacancies.index', ['page' => $page]),
            'seoRobots' => $hasFilters ? 'noindex, follow' : 'index, follow',
        ]);
    }

    public function show(Vacancy $vacancy): View
    {
        abort_unless(Vacancy::query()->publiclyVisible()->whereKey($vacancy->getKey())->exists(), 404);
        abort_unless($vacancy->company()->publiclyVisible()->exists(), 404);

        $vacancy->load(['company.media', 'categories.parent', 'tags']);

        $relatedVacancies = Vacancy::query()
            ->publiclyVisible()
            ->where('company_id', $vacancy->company_id)
            ->whereKeyNot($vacancy->getKey())
            ->with(['company.media', 'categories'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('vacancies.show', [
            'vacancy' => $vacancy,
            'taxonomy' => $this->vacancyTaxonomy($vacancy),
            'logoUrl' => $vacancy->company->publicLogoUrl(),
            'relatedVacancies' => $relatedVacancies,
            'structuredData' => StructuredData::jobPosting($vacancy),
            'descriptionHtml' => app(VacancyDescription::class)->sanitize($vacancy->description),
        ]);
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
                'label' => $labels[$key].': '.$this->activeFilterValue($key, $value),
                'url' => route('vacancies.index', array_diff_key($parameters, [$key => true])),
            ])
            ->values()
            ->all();
    }

    private function activeFilterValue(string $key, string $value): string
    {
        $type = VacancyFilterOptions::taxonomyFilterTypes()[$key] ?? null;

        if ($type !== null) {
            return Category::query()
                ->where('type', $type->value)
                ->where('slug', $value)
                ->value('name') ?? $value;
        }

        if ($key === 'categorie') {
            return Category::query()
                ->whereIn('type', [CategoryType::function_area->value, CategoryType::vacancy_category->value])
                ->where('slug', $value)
                ->value('name') ?? $value;
        }

        return $value;
    }

    /** @return array<string, Collection<int, Category>> */
    private function vacancyTaxonomy(Vacancy $vacancy): array
    {
        return collect(VacancyFilterOptions::taxonomyFilterTypes())
            ->map(fn (CategoryType $type): Collection => $vacancy->categories
                ->where('type', $type)
                ->values())
            ->all();
    }
}
