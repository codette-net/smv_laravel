<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Company;
use App\Support\Companies\CompanyDescription;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $searchInput = $request->query('q');
        $categoryInput = $request->query('category');
        $search = is_string($searchInput) ? Str::limit(Str::squish($searchInput), 100, '') : '';
        $category = is_string($categoryInput) ? Str::limit(Str::slug($categoryInput), 100, '') : '';

        $companies = Company::query()
            ->publiclyVisible()
            ->matchingSearch($search)
            ->inCompanyCategory($category)
            ->withSavedStateFor($request->user())
            ->with([
                'media',
                'categories' => fn ($query) => $query->where('type', CategoryType::company_category->value),
                'publicVacanciesPreview',
            ])
            ->withCount([
                'vacancies as public_vacancies_count' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $companyCategories = Category::query()
            ->where('type', CategoryType::company_category->value)
            ->whereHas('companies', fn ($query) => $query->publiclyVisible())
            ->withCount([
                'companies as public_companies_count' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('companies.index', [
            'category' => $category,
            'companyCategories' => $companyCategories,
            'companies' => $companies,
            'search' => $search,
            'seoCanonical' => $request->integer('page', 1) > 1
                ? route('companies.index', ['page' => $request->integer('page')])
                : route('companies.index'),
        ]);
    }

    public function show(Company $company): View
    {
        abort_unless($company->isPubliclyVisible(), 404);

        $company->load(['categories', 'media']);

        $vacancies = $company->vacancies()
            ->with(['company', 'categories'])
            ->publiclyVisible()
            ->withSavedStateFor(auth()->user())
            ->latest()
            ->get();

        $companyDescription = app(CompanyDescription::class);
        $description = $companyDescription->plainText($company->description ?? $company->tagline);

        return view('companies.show', [
            'company' => $company,
            'descriptionHtml' => $companyDescription->sanitize($company->description),
            'coverUrl' => $company->publicCoverUrl(),
            'logoUrl' => $company->publicLogoUrl(),
            'metaDescription' => Str::limit($description, 155),
            'structuredData' => StructuredData::organization($company, withContext: true),
            'vacancies' => $vacancies,
        ]);
    }
}
