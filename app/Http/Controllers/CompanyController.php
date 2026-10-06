<?php

namespace App\Http\Controllers;

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
        $companies = Company::query()
            ->publiclyVisible()
            ->withSavedStateFor($request->user())
            ->with(['media', 'categories'])
            ->withCount([
                'vacancies as public_vacancies_count' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
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
