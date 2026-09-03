<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Vacancy;
use App\Support\Vacancies\VacancyFilterOptions;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(VacancyFilterOptions $filterOptions): View
    {
        return view('home', [
            'vacancies' => Vacancy::query()
                ->publiclyVisible()
                ->with(['company.media', 'categories'])
                ->latest()
                ->take(10)
                ->get(),
            'latestBlogPost' => BlogPost::query()
                ->publiclyVisible()
                ->with(['media', 'categories', 'tags'])
                ->latest('published_at')
                ->first(),
            'filters' => $filterOptions->emptyFilters(),
            'sort' => 'nieuwste',
            'sortOptions' => VacancyFilterOptions::SORTS,
            'locations' => $filterOptions->locations(),
            'taxonomyOptions' => $filterOptions->taxonomyOptions(),
            'companies' => $filterOptions->companies(),
        ]);
    }
}
