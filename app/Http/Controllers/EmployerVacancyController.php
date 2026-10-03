<?php

namespace App\Http\Controllers;

use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Http\Requests\SaveVacancyPlacementRequest;
use App\Http\Requests\SelectAdvertisingPackageRequest;
use App\Models\Category;
use App\Models\Vacancy;
use App\Support\VacancyPlacementSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmployerVacancyController extends Controller
{
    /** @var array<string, CategoryType> */
    private const TAXONOMY_FIELDS = [
        'employment_type_category_id' => CategoryType::employment_type,
        'workplace_category_id' => CategoryType::workplace,
        'sector_category_id' => CategoryType::sector,
        'function_area_category_id' => CategoryType::function_area,
        'experience_category_id' => CategoryType::experience,
    ];

    public function index(): View
    {
        return view('vacancy-placement.index', [
            'plans' => AdvertisingPackage::presentation(),
        ]);
    }

    public function selectPackage(
        SelectAdvertisingPackageRequest $request,
        VacancyPlacementSession $placement,
    ): RedirectResponse {
        $package = AdvertisingPackage::from($request->validated('package'));

        if (! $package->entersPlacementFlow()) {
            return to_route('contact', ['reason' => 'advertising']);
        }

        $placement->rememberPackage($package);
        $placement->rememberIntendedDestination(route('vacancy-placement.create'));

        return $request->user() === null
            ? to_route('vacancy-placement.account')
            : to_route('vacancy-placement.create');
    }

    public function account(VacancyPlacementSession $placement): View|RedirectResponse
    {
        if ($placement->package() === null) {
            return to_route('vacancy-placement.index');
        }

        if (auth()->check()) {
            return to_route('vacancy-placement.create');
        }

        return view('vacancy-placement.account', ['package' => $placement->package()]);
    }

    public function create(VacancyPlacementSession $placement): View|RedirectResponse
    {
        $package = $placement->package();

        if ($package === null) {
            return to_route('vacancy-placement.index');
        }

        $companies = auth()->user()->companies()->orderBy('name')->get();

        if ($companies->isEmpty()) {
            return to_route('vacancy-placement.company.create');
        }

        Gate::authorize('create', Vacancy::class);

        return view('vacancy-placement.form', [
            'vacancy' => null,
            'package' => $package,
            'companies' => $companies,
            'taxonomyOptions' => $this->taxonomyOptions(),
            'selectedTaxonomy' => [],
            'descriptionValue' => null,
        ]);
    }

    public function store(
        SaveVacancyPlacementRequest $request,
        VacancyPlacementSession $placement,
    ): RedirectResponse {
        $package = $placement->package();

        if ($package === null || ! $package->entersPlacementFlow()) {
            return to_route('vacancy-placement.index');
        }

        Gate::authorize('create', Vacancy::class);

        $vacancy = DB::transaction(function () use ($request, $package): Vacancy {
            $vacancy = Vacancy::create($this->vacancyAttributes($request, [
                'status' => VacancyStatus::Draft,
                'source' => VacancySource::Manual,
                'placement_package' => $package,
                'is_featured' => false,
                'is_filled' => false,
                'published_at' => null,
                'expires_at' => null,
            ]));

            $this->syncTaxonomy($vacancy, $request->validated());

            return $vacancy;
        });

        return to_route('vacancy-placement.preview', $vacancy);
    }

    public function edit(Vacancy $vacancy): View
    {
        $this->authorizeOwnedDraft($vacancy);
        $vacancy->load('categories');

        return view('vacancy-placement.form', [
            'vacancy' => $vacancy,
            'package' => $vacancy->placement_package,
            'companies' => auth()->user()->companies()->orderBy('name')->get(),
            'taxonomyOptions' => $this->taxonomyOptions(),
            'selectedTaxonomy' => $this->selectedTaxonomy($vacancy),
            'descriptionValue' => $this->editableDescription($vacancy->description),
        ]);
    }

    public function update(SaveVacancyPlacementRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $this->authorizeOwnedDraft($vacancy);

        DB::transaction(function () use ($request, $vacancy): void {
            $vacancy->update($this->vacancyAttributes($request));
            $this->syncTaxonomy($vacancy, $request->validated());
        });

        return to_route('vacancy-placement.preview', $vacancy);
    }

    public function preview(Vacancy $vacancy): View
    {
        $this->authorizeOwnedDraft($vacancy);
        $vacancy->load(['company.media', 'categories.parent']);

        return view('vacancy-placement.preview', [
            'vacancy' => $vacancy,
            'package' => $vacancy->placement_package,
        ]);
    }

    public function submit(Vacancy $vacancy, VacancyPlacementSession $placement): RedirectResponse
    {
        $this->authorizeOwnedDraft($vacancy);

        $vacancy->update([
            'status' => VacancyStatus::Pending,
            'published_at' => null,
            'is_featured' => false,
        ]);

        $placement->clear();

        return to_route('vacancy-placement.success', $vacancy);
    }

    public function success(Vacancy $vacancy): View
    {
        Gate::authorize('view', $vacancy);
        abort_unless($vacancy->status === VacancyStatus::Pending, 404);

        return view('vacancy-placement.success', compact('vacancy'));
    }

    /** @param array<string, mixed> $protected */
    private function vacancyAttributes(SaveVacancyPlacementRequest $request, array $protected = []): array
    {
        $data = $request->validated();
        $mode = ApplicationMode::from($data['application_mode']);
        $hasSalary = ($data['salary_min'] ?? null) !== null || ($data['salary_max'] ?? null) !== null;

        return [
            'company_id' => $data['company_id'],
            'title' => $data['title'],
            'description' => nl2br(e($data['description'])),
            'location' => $data['location'],
            'application_mode' => $mode,
            'application_email' => $mode === ApplicationMode::Email ? ($data['application_email'] ?? null) : null,
            'application_url' => $mode === ApplicationMode::External ? ($data['application_url'] ?? null) : null,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'salary_currency' => $hasSalary ? 'EUR' : null,
            'salary_period' => $hasSalary ? CompensationPeriod::Month : null,
            'deadline_at' => $data['deadline_at'] ?? null,
            ...$protected,
        ];
    }

    /** @return array<string, array<int|string, string>> */
    private function taxonomyOptions(): array
    {
        return collect(self::TAXONOMY_FIELDS)
            ->mapWithKeys(fn (CategoryType $type, string $field): array => [
                $field => Category::query()
                    ->where('type', $type->value)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all(),
            ])
            ->all();
    }

    /** @return array<string, int> */
    private function selectedTaxonomy(Vacancy $vacancy): array
    {
        return collect(self::TAXONOMY_FIELDS)
            ->mapWithKeys(function (CategoryType $type, string $field) use ($vacancy): array {
                $category = $vacancy->categories->firstWhere('type', $type);

                return $category === null ? [] : [$field => $category->id];
            })
            ->all();
    }

    /** @param array<string, mixed> $data */
    private function syncTaxonomy(Vacancy $vacancy, array $data): void
    {
        $controlledTypes = array_map(fn (CategoryType $type): string => $type->value, self::TAXONOMY_FIELDS);
        $uncontrolled = $vacancy->categories()
            ->whereNotIn('type', $controlledTypes)
            ->pluck('categories.id');
        $selected = collect(array_keys(self::TAXONOMY_FIELDS))
            ->map(fn (string $field): mixed => $data[$field] ?? null)
            ->filter()
            ->map(fn (mixed $id): int => (int) $id);

        $vacancy->categories()->sync($uncontrolled->merge($selected)->unique()->all());
    }

    private function authorizeOwnedDraft(Vacancy $vacancy): void
    {
        Gate::authorize('update', $vacancy);
        abort_unless($vacancy->status === VacancyStatus::Draft, 404);
    }

    private function editableDescription(string $description): string
    {
        $withLineBreaks = preg_replace('/<br\s*\/?>/i', "\n", $description) ?? $description;

        return html_entity_decode(strip_tags($withLineBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
