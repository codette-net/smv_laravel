<?php

use App\Enums\ApplicationStatus;
use App\Enums\BlogPostStatus;
use App\Enums\CompanyStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\BlogPost;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportSource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('vacancy fields, slug and finite state values use the intended model contract', function () {
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'name' => 'Voorbeeldbedrijf',
        'slug' => 'voorbeeldbedrijf',
        'status' => CompanyStatus::Active,
        'is_featured' => true,
    ]);

    $vacancy = Vacancy::create([
        'company_id' => $company->id,
        'title' => 'Senior Accountmanager',
        'description' => 'Een inhoudelijke vacaturetekst.',
        'location' => 'Utrecht',
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        'is_featured' => true,
        'is_filled' => false,
    ]);

    expect($vacancy->slug)->toBe('senior-accountmanager')
        ->and($vacancy->location)->toBe('Utrecht')
        ->and($vacancy->status)->toBe(VacancyStatus::Active)
        ->and($vacancy->source)->toBe(VacancySource::Manual)
        ->and($vacancy->is_featured)->toBeTrue()
        ->and($vacancy->is_filled)->toBeFalse()
        ->and($vacancy->deadline_at)->not->toBeNull()
        ->and($vacancy->expires_at)->not->toBeNull();
});

test('updating a vacancy title preserves its existing slug', function () {
    User::factory()->create();
    $company = Company::factory()->create();
    $vacancy = Vacancy::factory()->create([
        'company_id' => $company->id,
        'title' => 'Oorspronkelijke titel',
    ]);
    $originalSlug = $vacancy->slug;

    $vacancy->update(['title' => 'Nieuwe titel']);

    expect($vacancy->fresh()->slug)->toBe($originalSlug);
});

test('vacancies with identical titles receive distinct slugs', function () {
    User::factory()->create();
    $company = Company::factory()->create();
    $firstVacancy = Vacancy::create([
        'company_id' => $company->id,
        'title' => 'Accountmanager',
        'description' => 'Eerste vacature',
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
    ]);
    $secondVacancy = Vacancy::create([
        'company_id' => $company->id,
        'title' => 'Accountmanager',
        'description' => 'Tweede vacature',
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
    ]);

    expect($firstVacancy->slug)->toBe('accountmanager')
        ->and($secondVacancy->slug)->not->toBe($firstVacancy->slug);
});

test('vacancy factories defer collision-safe slug generation to the model', function () {
    $company = Company::factory()->create();
    $firstVacancy = Vacancy::factory()->create([
        'company_id' => $company->id,
        'title' => 'Accountmanager',
    ]);
    $secondVacancy = Vacancy::factory()->create([
        'company_id' => $company->id,
        'title' => 'Accountmanager',
    ]);

    expect($firstVacancy->slug)->toBe('accountmanager')
        ->and($secondVacancy->slug)->not->toBe($firstVacancy->slug);
});

test('manual vacancies do not require import identity', function () {
    User::factory()->create();
    $company = Company::factory()->create();
    $vacancy = Vacancy::factory()->create([
        'company_id' => $company->id,
        'source' => VacancySource::Manual,
        'import_source_id' => null,
        'source_reference' => null,
    ]);

    expect($vacancy->import_source_id)->toBeNull()
        ->and($vacancy->source_reference)->toBeNull();
});

test('soft-deleting operational records preserves their rows and related history', function () {
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'name' => 'Historisch bedrijf',
        'slug' => 'historisch-bedrijf',
        'status' => CompanyStatus::Active,
    ]);
    $vacancy = Vacancy::create([
        'company_id' => $company->id,
        'title' => 'Salesmanager',
        'description' => 'Vacaturetekst',
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
    ]);
    $application = Application::create([
        'vacancy_id' => $vacancy->id,
        'candidate_name' => 'Kandidaat',
        'candidate_email' => 'kandidaat@example.test',
        'status' => ApplicationStatus::New,
    ]);

    $company->delete();
    $vacancy->delete();
    $application->delete();

    $this->assertSoftDeleted($company);
    $this->assertSoftDeleted($vacancy);
    $this->assertSoftDeleted($application);
    $this->assertDatabaseHas('applications', ['id' => $application->id]);
});

test('import runs and imported vacancies belong to a provider-specific source', function () {
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'name' => 'Importbedrijf',
        'slug' => 'importbedrijf',
        'status' => CompanyStatus::Active,
    ]);
    $firstSource = ImportSource::create([
        'company_id' => $company->id,
        'name' => 'Provider A',
        'slug' => 'provider-a',
        'type' => ImportType::xml,
        'is_active' => true,
    ]);
    $secondSource = ImportSource::create([
        'company_id' => $company->id,
        'name' => 'Provider B',
        'slug' => 'provider-b',
        'type' => ImportType::json,
        'is_active' => true,
    ]);
    $run = Import::create([
        'import_source_id' => $firstSource->id,
        'type' => ImportType::xml,
        'status' => ImportStatus::Completed,
        'total_rows' => 3,
        'imported_rows' => 2,
        'updated_rows' => 1,
        'failed_rows' => 0,
    ]);

    foreach ([$firstSource, $secondSource] as $source) {
        Vacancy::create([
            'company_id' => $company->id,
            'import_source_id' => $source->id,
            'title' => "Vacature {$source->id}",
            'description' => 'Geïmporteerde vacature',
            'source_reference' => 'EXT-123',
            'status' => VacancyStatus::Active,
            'source' => VacancySource::Import,
        ]);
    }

    expect(fn () => Vacancy::create([
        'company_id' => $company->id,
        'import_source_id' => $firstSource->id,
        'title' => 'Dubbele bronvacature',
        'description' => 'Mag niet dubbel worden opgeslagen.',
        'source_reference' => 'EXT-123',
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Import,
    ]))->toThrow(QueryException::class);

    expect($run->importSource)->toBeInstanceOf(ImportSource::class)
        ->and($run->importSource->is($firstSource))->toBeTrue()
        ->and($run->source)->toBeNull()
        ->and($firstSource->imports)->toHaveCount(1)
        ->and($firstSource->vacancies)->toHaveCount(1)
        ->and($run->type)->toBe(ImportType::xml)
        ->and($run->status)->toBe(ImportStatus::Completed)
        ->and($run->imported_rows)->toBe(2)
        ->and($run->updated_rows)->toBe(1);
});

test('orders and payments expose consistent historical relationships and casts', function () {
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'name' => 'Betalend bedrijf',
        'slug' => 'betalend-bedrijf',
        'status' => CompanyStatus::Active,
    ]);
    $order = Order::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'order_number' => 'ORDER-1',
        'status' => OrderStatus::Paid,
        'subtotal_cents' => 10000,
        'vat_cents' => 2100,
        'total_cents' => 12100,
        'currency' => 'EUR',
    ]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'mollie',
        'provider_payment_id' => 'tr_123',
        'status' => PaymentStatus::Paid,
        'amount_cents' => 12100,
        'currency' => 'EUR',
        'paid_at' => now(),
        'raw_payload' => ['mode' => 'test'],
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'title_snapshot' => 'Vacaturepakket',
        'price_cents' => 10000,
        'quantity' => 1,
        'total_cents' => 10000,
    ]);

    expect($order->user())->toBeInstanceOf(BelongsTo::class)
        ->and($order->company())->toBeInstanceOf(BelongsTo::class)
        ->and($order->payments())->toBeInstanceOf(HasMany::class)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->provider)->toBe('mollie')
        ->and($payment->provider_payment_id)->toBe('tr_123')
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->raw_payload)->toBe(['mode' => 'test'])
        ->and($item->vacancy())->toBeInstanceOf(BelongsTo::class);

    $company->forceDelete();

    expect($order->fresh())->not->toBeNull()
        ->and($order->fresh()->company_id)->toBeNull();
});

test('corrective migrations expose the current schema contract', function () {
    expect(Schema::hasColumns('imports', [
        'import_source_id',
        'total_rows',
        'imported_rows',
        'updated_rows',
        'failed_rows',
    ]))->toBeTrue()
        ->and(Schema::hasColumn('imports', 'updated'))->toBeFalse()
        ->and(Schema::hasColumns('payments', ['provider', 'provider_payment_id']))->toBeTrue()
        ->and(Schema::hasColumn('payments', 'payment_provider'))->toBeFalse()
        ->and(Schema::hasColumn('payments', 'payment_id'))->toBeFalse()
        ->and(Schema::hasColumn('blog_posts', 'featured_image'))->toBeTrue()
        ->and(Schema::hasColumn('vacancies', 'import_source_id'))->toBeTrue();
});

test('blog posts cast publication state and accept normal editorial article length', function () {
    $author = User::factory()->create();
    $post = BlogPost::create([
        'author_id' => $author->id,
        'title' => 'Lang artikel',
        'slug' => 'lang-artikel',
        'content' => str_repeat('Inhoud ', 12_000),
        'featured_image' => 'blog/uitgelicht.jpg',
        'status' => BlogPostStatus::Published,
        'published_at' => now(),
    ]);

    expect($post->status)->toBe(BlogPostStatus::Published)
        ->and($post->published_at)->not->toBeNull()
        ->and($post->featured_image)->toBe('blog/uitgelicht.jpg')
        ->and(strlen($post->content))->toBeGreaterThan(65_535);
});
