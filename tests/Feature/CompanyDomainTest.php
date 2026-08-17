<?php

use App\Enums\BlogPostStatus;
use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

uses(RefreshDatabase::class);

test('company factory creates an independent realistic company', function () {
    $company = Company::factory()->create();

    expect($company->user)->toBeInstanceOf(User::class)
        ->and($company->status)->toBe(CompanyStatus::Active)
        ->and($company->slug)->not->toBeEmpty()
        ->and($company->tagline)->not->toBeEmpty()
        ->and($company->description)->not->toBeEmpty()
        ->and($company->email)->not->toBeEmpty()
        ->and($company->location)->not->toBeEmpty()
        ->and(User::query()->count())->toBe(1);
});

test('company slug stays stable when its name changes', function () {
    $company = Company::create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Oorspronkelijk Bedrijf',
        'status' => CompanyStatus::Active,
    ]);
    $originalSlug = $company->slug;

    $company->update(['name' => 'Nieuwe Bedrijfsnaam']);

    expect($company->fresh()->slug)->toBe($originalSlug);
});

test('companies with duplicate names receive unique slugs', function () {
    $owner = User::factory()->create();
    $firstCompany = Company::create([
        'user_id' => $owner->id,
        'name' => 'Dezelfde Werkgever',
        'status' => CompanyStatus::Active,
    ]);
    $secondCompany = Company::create([
        'user_id' => $owner->id,
        'name' => 'Dezelfde Werkgever',
        'status' => CompanyStatus::Active,
    ]);

    expect($firstCompany->slug)->toBe('dezelfde-werkgever')
        ->and($secondCompany->slug)->not->toBe($firstCompany->slug);
});

test('company exposes established vacancy taxonomy content and order relationships', function () {
    $company = Company::factory()->create();
    $category = Category::create([
        'name' => 'Zakelijke dienstverlening',
        'slug' => 'zakelijke-dienstverlening',
        'type' => CategoryType::company_category,
    ]);
    $company->categories()->attach($category);

    $vacancy = Vacancy::create([
        'company_id' => $company->id,
        'title' => 'Accountmanager',
        'description' => 'Vacaturetekst',
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
    ]);
    $blogPost = BlogPost::create([
        'author_id' => $company->user_id,
        'company_id' => $company->id,
        'title' => 'Werken bij deze werkgever',
        'slug' => 'werken-bij-deze-werkgever',
        'content' => 'Inhoud',
        'status' => BlogPostStatus::Draft,
    ]);
    $order = Order::create([
        'user_id' => $company->user_id,
        'company_id' => $company->id,
        'order_number' => 'COMPANY-ORDER-1',
        'status' => OrderStatus::Pending,
        'subtotal_cents' => 1000,
        'vat_cents' => 210,
        'total_cents' => 1210,
        'currency' => 'EUR',
    ]);

    expect($company->vacancies->sole()->is($vacancy))->toBeTrue()
        ->and($company->categories->sole()->is($category))->toBeTrue()
        ->and($company->blogPosts->sole()->is($blogPost))->toBeTrue()
        ->and($company->orders->sole()->is($order))->toBeTrue();
});

test('company uses single-file media collections for logo and cover imagery', function () {
    Storage::fake('public');
    $company = Company::factory()->create();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

    expect($company)->toBeInstanceOf(HasMedia::class);

    $company->addMedia(UploadedFile::fake()->createWithContent('eerste-logo.png', $png))
        ->toMediaCollection('logo');
    $company->addMedia(UploadedFile::fake()->createWithContent('tweede-logo.png', $png))
        ->toMediaCollection('logo');
    $company->addMedia(UploadedFile::fake()->createWithContent('omslag.png', $png))
        ->toMediaCollection('cover');

    expect($company->fresh()->getMedia('logo'))->toHaveCount(1)
        ->and($company->fresh()->getFirstMedia('logo')->file_name)->toBe('tweede-logo.png')
        ->and($company->fresh()->getMedia('cover'))->toHaveCount(1);
});

test('company deletion is soft and retains its row', function () {
    $company = Company::factory()->create();

    $company->delete();

    $this->assertSoftDeleted($company);
    expect(Company::withTrashed()->find($company->id))->not->toBeNull();
});
