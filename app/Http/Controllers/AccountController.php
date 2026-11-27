<?php

namespace App\Http\Controllers;

use App\Actions\Placements\AllocatePlacementCredit;
use App\Enums\CategoryType;
use App\Enums\OrderStatus;
use App\Enums\PlacementCreditStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Http\Requests\UpdateEmployerCompanyRequest;
use App\Models\Company;
use App\Models\Order;
use App\Models\PlacementCredit;
use App\Models\Vacancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $credits = PlacementCredit::query()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['company', 'vacancy'])
            ->get();
        $creditCounts = $this->placementCreditCounts($credits);
        $applicationsCount = $request->user()->applications()->count();
        $savedVacanciesCount = $request->user()->savedVacancies()->count();
        $savedCompaniesCount = $request->user()->savedCompanies()->count();
        $ordersCount = $request->user()->orders()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->count();
        $hasEmployerContext = $companies->isNotEmpty();

        $recentOrders = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with([
                'company',
                'orderItems.vacancy',
                'payments' => fn ($query) => $query->latest('id'),
                'operations' => fn ($query) => $query->latest('id'),
                'placementCredits',
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(3)
            ->get();

        $activeVacanciesCount = Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->publiclyVisible())
            ->count();
        $awaitingApprovalCount = Vacancy::query()
            ->where('status', VacancyStatus::Pending->value)
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->count();
        $draftVacanciesCount = Vacancy::query()
            ->where('status', VacancyStatus::Draft->value)
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->count();
        $pendingOrdersCount = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereIn('status', [OrderStatus::Draft->value, OrderStatus::Pending->value, OrderStatus::PendingPayment->value])
            ->count();
        $incompleteCompaniesCount = $companies->filter(fn (Company $company): bool => ! $company->hasCompletePublicProfile())->count();

        return view('account.index', [
            'companies' => $companies,
            'vacancies' => $vacancies,
            'publicVacancyIds' => $publicVacancyIds,
            'savedVacanciesCount' => $savedVacanciesCount,
            'savedCompaniesCount' => $savedCompaniesCount,
            'applicationsCount' => $applicationsCount,
            'ordersCount' => $ordersCount,
            'hasEmployerContext' => $hasEmployerContext,
            'hasCandidateContext' => ! $hasEmployerContext || $applicationsCount + $savedVacanciesCount + $savedCompaniesCount > 0,
            'recentOrders' => $recentOrders,
            'creditCounts' => $creditCounts,
            'employerStats' => [
                'activeVacancies' => $activeVacanciesCount,
                'awaitingApproval' => $awaitingApprovalCount,
                'availableCredits' => $creditCounts['available'],
                'attention' => $draftVacanciesCount + $pendingOrdersCount + $incompleteCompaniesCount + $creditCounts['review'],
            ],
        ]);
    }

    public function placements(Request $request): View
    {
        $credits = PlacementCredit::query()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['company', 'vacancy', 'order'])
            ->orderByDesc('granted_at')
            ->orderByDesc('id')
            ->get();
        $vacancies = Vacancy::query()
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->where('source', VacancySource::Manual->value)
            ->whereNull('import_source_id')
            ->where('status', VacancyStatus::Draft->value)
            ->where('is_filled', false)
            ->orderBy('title')
            ->get()
            ->groupBy('company_id');
        $creditCounts = $this->placementCreditCounts($credits);

        return view('account.placements', compact('credits', 'vacancies', 'creditCounts'));
    }

    public function redeemPlacementCredit(
        Request $request,
        PlacementCredit $placementCredit,
        AllocatePlacementCredit $allocate,
    ): RedirectResponse {
        Gate::authorize('redeem', $placementCredit);
        $validated = $request->validate(['vacancy_id' => ['required', 'integer', 'exists:vacancies,id']]);
        $vacancy = Vacancy::query()->findOrFail($validated['vacancy_id']);

        try {
            $allocate->execute($request->user(), $placementCredit, $vacancy);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['vacancy_id' => $exception->getMessage()]);
        }

        return to_route('account.placements')
            ->with('account_status', 'Het plaatsingsrecht is aan de vacature toegewezen. Publicatie blijft afhankelijk van beoordeling.');
    }

    public function orders(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('company', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with([
                'company',
                'orderItems.vacancy',
                'payments' => fn ($query) => $query->latest('id'),
                'operations' => fn ($query) => $query->latest('id'),
                'placementCredits',
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(12);

        return view('account.orders.index', compact('orders'));
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
            ->with([
                'media',
                'categories' => fn ($query) => $query->where('type', CategoryType::company_category->value),
            ])
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

    /**
     * @param  Collection<int, PlacementCredit>  $credits
     * @return array{available: int, assigned: int, active: int, review: int}
     */
    private function placementCreditCounts(Collection $credits): array
    {
        $statuses = $credits->countBy(fn (PlacementCredit $credit): string => $credit->status()->value);

        return [
            'available' => $statuses[PlacementCreditStatus::Available->value] ?? 0,
            'assigned' => ($statuses[PlacementCreditStatus::Allocated->value] ?? 0)
                + ($statuses[PlacementCreditStatus::Scheduled->value] ?? 0),
            'active' => $statuses[PlacementCreditStatus::Active->value] ?? 0,
            'review' => $statuses[PlacementCreditStatus::RequiresReview->value] ?? 0,
        ];
    }
}
