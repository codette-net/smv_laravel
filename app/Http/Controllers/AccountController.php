<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployerCompanyRequest;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $companies = $request->user()->companies()
            ->with('media')
            ->withCount('vacancies')
            ->orderBy('name')
            ->get();

        $vacancies = Vacancy::query()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('company')
            ->latest('updated_at')
            ->limit(5)
            ->get();
        $publicVacancyIds = $this->publicVacancyIds($vacancies->pluck('id')->all());

        return view('account.index', [
            'companies' => $companies,
            'vacancies' => $vacancies,
            'publicVacancyIds' => $publicVacancyIds,
            'savedVacanciesCount' => $request->user()->savedVacancies()->count(),
            'savedCompaniesCount' => $request->user()->savedCompanies()->count(),
            'applicationsCount' => $request->user()->applications()->count(),
        ]);
    }

    public function applications(Request $request): View
    {
        $applications = $request->user()->applications()
            ->with('vacancy.company')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12);
        $publicVacancyIds = $this->publicVacancyIds(
            $applications->getCollection()->pluck('vacancy_id')->all(),
        );

        return view('account.applications', compact('applications', 'publicVacancyIds'));
    }

    public function vacancies(Request $request): View
    {
        $vacancies = Vacancy::query()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('company')
            ->latest('updated_at')
            ->paginate(12);
        $publicVacancyIds = $this->publicVacancyIds($vacancies->getCollection()->pluck('id')->all());

        return view('account.vacancies', compact('vacancies', 'publicVacancyIds'));
    }

    public function savedVacancies(Request $request): View
    {
        $vacancies = $request->user()->savedVacancies()
            ->with(['company.media', 'categories'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);
        $publicVacancyIds = $this->publicVacancyIds($vacancies->getCollection()->pluck('id')->all());

        return view('account.saved-vacancies', compact('vacancies', 'publicVacancyIds'));
    }

    public function savedCompanies(Request $request): View
    {
        $companies = $request->user()->savedCompanies()
            ->with(['media', 'categories'])
            ->withCount([
                'vacancies as public_vacancies_count' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);
        $publicCompanyIds = Company::query()
            ->publiclyVisible()
            ->whereKey($companies->getCollection()->pluck('id'))
            ->pluck('id')
            ->all();

        return view('account.saved-companies', compact('companies', 'publicCompanyIds'));
    }

    public function editCompany(Company $company): View
    {
        $this->authorizeOwnedCompany($company);
        $company->load('media');

        return view('account.company-edit', compact('company'));
    }

    public function updateCompany(UpdateEmployerCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorizeOwnedCompany($company);

        $company->update($request->safe()->except(['logo', 'cover']));

        if ($request->hasFile('logo')) {
            $company->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($request->hasFile('cover')) {
            $company->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        return to_route('account.index')->with('account_status', 'Uw bedrijfsprofiel is bijgewerkt.');
    }

    /** @param array<int, int> $ids */
    private function publicVacancyIds(array $ids): array
    {
        return Vacancy::query()
            ->publiclyVisible()
            ->whereKey($ids)
            ->whereHas('company', fn ($query) => $query->publiclyVisible())
            ->pluck('id')
            ->all();
    }

    private function authorizeOwnedCompany(Company $company): void
    {
        Gate::authorize('update', $company);
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
