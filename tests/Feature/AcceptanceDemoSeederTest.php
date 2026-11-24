<?php

use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use App\Enums\ImportStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportSource;
use App\Models\Order;
use App\Models\OutboundApplyClick;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\AcceptanceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedAcceptanceDataset(): void
{
    test()->seed(AcceptanceDemoSeeder::class);
}

test('acceptance seeder creates the documented role, company, vacancy, source and history scenarios', function (): void {
    seedAcceptanceDataset();

    expect(User::query()->where('email', 'like', '%@smv-acceptance.test')->count())->toBe(5)
        ->and(Role::query()->whereIn('name', ['candidate', 'employer', 'editor', 'admin', 'super-admin'])->count())->toBe(5)
        ->and(Company::query()->where('slug', 'like', 'acceptance-company-%')->count())->toBe(4)
        ->and(Vacancy::query()->where('reference', 'like', 'acceptance-%')->count())->toBeGreaterThanOrEqual(13)
        ->and(Vacancy::query()->where('source', VacancySource::Manual->value)->where('status', VacancyStatus::Draft->value)->where('reference', 'acceptance-manual-draft')->exists())->toBeTrue()
        ->and(Vacancy::query()->where('source', VacancySource::Manual->value)->where('status', VacancyStatus::Active->value)->where('is_filled', true)->where('reference', 'acceptance-manual-filled')->exists())->toBeTrue()
        ->and(Vacancy::query()->where('source', VacancySource::Import->value)->where('source_reference', 'acceptance-import-review')->where('status', VacancyStatus::Pending->value)->exists())->toBeTrue()
        ->and(Vacancy::query()->where('source_reference', 'acceptance-import-missing')->whereNotNull('missing_since')->exists())->toBeTrue()
        ->and(Vacancy::query()->where('source_reference', 'acceptance-import-offline')->where('status', VacancyStatus::Archived->value)->whereNotNull('source_offlined_at')->exists())->toBeTrue()
        ->and(Vacancy::query()->where('source_reference', 'acceptance-import-restored')->where('status', VacancyStatus::Active->value)->whereNull('missing_since')->whereNull('source_offlined_at')->exists())->toBeTrue()
        ->and(Order::query()->whereIn('order_number', ['SMV-ACCEPTANCE-DRAFT', 'SMV-ACCEPTANCE-PENDING'])->count())->toBe(2)
        ->and(Order::query()->where('order_number', 'SMV-ACCEPTANCE-PENDING')->where('status', 'pending_payment')->whereHas('orderItems')->exists())->toBeTrue()
        ->and(Order::query()->where('order_number', 'SMV-ACCEPTANCE-PAID')->where('status', 'paid')->exists())->toBeTrue()
        ->and(Payment::query()->whereIn('idempotency_key', ['acceptance-payment-failed', 'acceptance-payment-cancelled', 'acceptance-payment-paid'])->count())->toBe(3);
});

test('acceptance imports use safe local fixtures and expose useful policy and monitoring states', function (): void {
    seedAcceptanceDataset();

    $trusted = ImportSource::query()->where('slug', 'acceptance-source-trusted')->firstOrFail();
    $review = ImportSource::query()->where('slug', 'acceptance-source-review')->firstOrFail();
    $manual = ImportSource::query()->where('slug', 'acceptance-source-manual')->firstOrFail();

    expect($trusted->publication_policy)->toBe(ImportPublicationPolicy::AutoPublish)
        ->and($trusted->missing_vacancy_policy)->toBe(ImportMissingVacancyPolicy::AutoOffline)
        ->and($trusted->isEligibleForAutomaticExecution())->toBeTrue()
        ->and($review->publication_policy)->toBe(ImportPublicationPolicy::ReviewRequired)
        ->and($review->missing_vacancy_policy)->toBe(ImportMissingVacancyPolicy::ReviewRequired)
        ->and($review->isEligibleForAutomaticExecution())->toBeFalse()
        ->and($manual->endpoint_url)->toBeNull()
        ->and($manual->automatic_import_enabled)->toBeFalse()
        ->and($manual->imports()->count())->toBe(0)
        ->and(ImportSource::query()->where('slug', 'like', 'acceptance-source-%')->get()->every(fn (ImportSource $source): bool => $source->credentials === null))->toBeTrue()
        ->and(ImportSource::query()->where('slug', 'like', 'acceptance-source-%')->pluck('endpoint_url')->filter()->every(fn (string $url): bool => str_contains($url, 'acceptance.invalid')))->toBeTrue()
        ->and(ImportSource::query()->where('slug', 'like', 'acceptance-source-%')->pluck('endpoint_url')->filter()->every(fn (string $url): bool => ! preg_match('/joobs|vnom|partner/i', $url)))->toBeTrue()
        ->and(Import::query()->where('status', ImportStatus::Completed->value)->exists())->toBeTrue()
        ->and(Import::query()->where('status', ImportStatus::Incomplete->value)->exists())->toBeTrue()
        ->and(Import::query()->where('status', ImportStatus::Failed->value)->exists())->toBeTrue()
        ->and(Import::query()->where('status', ImportStatus::Skipped->value)->exists())->toBeTrue();
});

test('acceptance seeder creates candidate account history and outbound reporting data without fake external applications', function (): void {
    seedAcceptanceDataset();

    $candidate = User::query()->where('email', 'candidate@smv-acceptance.test')->firstOrFail();

    expect($candidate->savedVacancies()->where('reference', 'acceptance-manual-internal')->exists())->toBeTrue()
        ->and($candidate->savedCompanies()->where('slug', 'acceptance-company-complete')->exists())->toBeTrue()
        ->and(Application::query()->where('candidate_id', $candidate->id)->count())->toBe(1)
        ->and(Application::query()->where('candidate_id', $candidate->id)->whereHas('vacancy', fn ($query) => $query->where('application_mode', 'external'))->count())->toBe(0)
        ->and(OutboundApplyClick::query()->count())->toBe(5)
        ->and(OutboundApplyClick::query()->whereHas('vacancy', fn ($query) => $query->where('source', VacancySource::Import->value))->exists())->toBeTrue()
        ->and(OutboundApplyClick::query()->where('clicked_at', '<', now()->subDays(30))->exists())->toBeFalse();
});

test('acceptance seeder is idempotent for deterministic fixture records', function (): void {
    seedAcceptanceDataset();

    $counts = [
        'users' => User::query()->where('email', 'like', '%@smv-acceptance.test')->count(),
        'companies' => Company::query()->where('slug', 'like', 'acceptance-company-%')->count(),
        'vacancies' => Vacancy::query()->where('reference', 'like', 'acceptance-%')->count(),
        'sources' => ImportSource::query()->where('slug', 'like', 'acceptance-source-%')->count(),
        'imports' => Import::query()->where('filename', 'like', 'acceptance-%')->count(),
        'applications' => Application::query()->whereHas('candidate', fn ($query) => $query->where('email', 'like', '%@smv-acceptance.test'))->count(),
        'clicks' => OutboundApplyClick::query()->count(),
        'orders' => Order::query()->whereIn('order_number', ['SMV-ACCEPTANCE-DRAFT', 'SMV-ACCEPTANCE-PENDING', 'SMV-ACCEPTANCE-PAID'])->count(),
        'payments' => Payment::query()->whereIn('idempotency_key', ['acceptance-payment-failed', 'acceptance-payment-cancelled', 'acceptance-payment-paid'])->count(),
    ];

    seedAcceptanceDataset();

    expect([
        'users' => User::query()->where('email', 'like', '%@smv-acceptance.test')->count(),
        'companies' => Company::query()->where('slug', 'like', 'acceptance-company-%')->count(),
        'vacancies' => Vacancy::query()->where('reference', 'like', 'acceptance-%')->count(),
        'sources' => ImportSource::query()->where('slug', 'like', 'acceptance-source-%')->count(),
        'imports' => Import::query()->where('filename', 'like', 'acceptance-%')->count(),
        'applications' => Application::query()->whereHas('candidate', fn ($query) => $query->where('email', 'like', '%@smv-acceptance.test'))->count(),
        'clicks' => OutboundApplyClick::query()->count(),
        'orders' => Order::query()->whereIn('order_number', ['SMV-ACCEPTANCE-DRAFT', 'SMV-ACCEPTANCE-PENDING', 'SMV-ACCEPTANCE-PAID'])->count(),
        'payments' => Payment::query()->whereIn('idempotency_key', ['acceptance-payment-failed', 'acceptance-payment-cancelled', 'acceptance-payment-paid'])->count(),
    ])->toBe($counts);
});

test('acceptance seeder refuses production environments before writing fixtures', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    try {
        expect(fn () => app(AcceptanceDemoSeeder::class)->run())
            ->toThrow(RuntimeException::class, 'AcceptanceDemoSeeder is limited');
    } finally {
        $this->app->detectEnvironment(fn (): string => 'testing');
    }

    expect(User::query()->count())->toBe(0)
        ->and(Company::query()->count())->toBe(0)
        ->and(Vacancy::query()->count())->toBe(0);
});
