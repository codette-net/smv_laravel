<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\BlogPost;
use App\Models\Company;
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
        $latestBlogPosts = BlogPost::query()
            ->publiclyVisible()
            ->with([
                'media',
                'categories' => fn ($query) => $query->where('type', CategoryType::blog_category->value),
                'tags' => fn ($query) => $query->where('type', 'blog'),
            ])
            ->latest('published_at')
            ->latest('id')
            ->limit(3)
            ->get();

        return view('home', [
            'vacancies' => $vacancySearch->query($filters, $sort)->paginate(6)->withQueryString(),
            'featuredCompanies' => Company::query()
                ->publiclyVisible()
                ->withSavedStateFor($request->user())
                ->with(['media', 'categories'])
                ->withCount([
                    'vacancies as public_vacancies_count' => fn ($query) => $query->publiclyVisible(),
                ])
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->limit(3)
                ->get(),
            'latestBlogPosts' => $latestBlogPosts,
            'latestBlogPost' => $latestBlogPosts->first(),
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
