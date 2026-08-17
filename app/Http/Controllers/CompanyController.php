<?php

namespace App\Http\Controllers;

use App\Enums\VacancyStatus;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->publiclyVisible()
            ->with('media')
            ->withCount([
                'vacancies as public_vacancies_count' => fn ($query) => $query
                    ->where('status', VacancyStatus::Active->value)
                    ->where('is_filled', false)
                    ->where(fn ($query) => $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now())),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12);

        return view('companies.index', [
            'companies' => $companies,
        ]);
    }

    public function show(Company $company): View
    {
        abort_unless($company->isPubliclyVisible(), 404);

        $company->load(['categories', 'media']);

        $vacancies = $company->vacancies()
            ->with('company')
            ->where('status', VacancyStatus::Active->value)
            ->where('is_filled', false)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get();

        $description = Str::squish(strip_tags($company->description ?? $company->tagline ?? ''));

        return view('companies.show', [
            'company' => $company,
            'coverUrl' => $company->publicCoverUrl(),
            'logoUrl' => $company->publicLogoUrl(),
            'metaDescription' => Str::limit($description, 155),
            'vacancies' => $vacancies,
        ]);
    }
}
