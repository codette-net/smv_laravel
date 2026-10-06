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
        $companyCategoryConstraint = fn ($query) => $query->where('type', CategoryType::company_category->value);

        return view('home', [
            'vacancies' => $vacancySearch->query($filters, $sort)->paginate(6)->withQueryString(),
            'featuredCompanies' => Company::query()
                ->publiclyVisible()
                ->withSavedStateFor($request->user())
                ->with(['media', 'categories' => $companyCategoryConstraint])
                ->withCount([
                    'vacancies as public_vacancies_count' => fn ($query) => $query->publiclyVisible(),
                ])
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->limit(3)
                ->get(),
            'bannerCompanies' => Company::query()
                ->publiclyVisible()
                ->with('media')
                ->withExists([
                    'media as has_media_logo' => fn ($query) => $query->where('collection_name', 'logo'),
                ])
                ->orderByDesc('has_media_logo')
                ->orderByRaw("case when logo is null or logo = '' then 0 else 1 end desc")
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->orderBy('id')
                ->limit(8)
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
