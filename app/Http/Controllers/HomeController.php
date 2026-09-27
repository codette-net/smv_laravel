<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\Vacancies\VacancyFilterOptions;
use App\Support\Vacancies\VacancySearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, VacancyFilterOptions $filterOptions, VacancySearch $vacancySearch): View
    {
        $filters = $vacancySearch->filters($request, $filterOptions);
        $sort = $vacancySearch->sort($request);
        $hasFilters = collect($request->query())->except('page')->filter(fn ($value): bool => filled($value))->isNotEmpty();
        $hasAdditionalFilters = collect($filters)
            ->except('zoek')
            ->contains(fn (string $value): bool => filled($value))
            || filled($request->query('sort'));

        return view('home', [
            'vacancies' => $vacancySearch->query($filters, $sort)->paginate(6)->withQueryString(),
            'latestBlogPost' => BlogPost::query()
                ->publiclyVisible()
                ->with(['media', 'categories', 'tags'])
                ->latest('published_at')
                ->first(),
            'filters' => $filters,
            'sort' => $sort,
            'sortOptions' => VacancyFilterOptions::SORTS,
            'locations' => $filterOptions->locations(),
            'taxonomyOptions' => $filterOptions->taxonomyOptions(),
            'companies' => $filterOptions->companies(),
            'hasFilters' => $hasFilters,
            'hasAdditionalFilters' => $hasAdditionalFilters,
        ]);
    }
}
