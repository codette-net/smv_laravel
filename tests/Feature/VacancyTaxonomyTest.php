<?php

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

test('structured category types cast and retain stable type-scoped slugs', function () {
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    $sectorSales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::sector]);

    $sales->update(['name' => 'Commerciële functies']);

    expect($sales->fresh()->type)->toBe(CategoryType::function_area)
        ->and($sales->fresh()->slug)->toBe('sales')
        ->and($sectorSales->slug)->toBe('sales');
});

test('a category hierarchy only accepts same-type non-self parents', function () {
    $parent = Category::factory()->create(['type' => CategoryType::sector]);
    $child = Category::factory()->create(['type' => CategoryType::sector, 'parent_id' => $parent->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->sole()->is($child))->toBeTrue();

    $otherType = Category::factory()->create(['type' => CategoryType::function_area]);

    expect(fn () => $otherType->update(['parent_id' => $parent->id]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $parent->update(['parent_id' => $parent->id]))->toThrow(InvalidArgumentException::class);
});

test('vacancies keep structured categories separate from flexible Spatie tags', function () {
    $vacancy = Vacancy::factory()->create(['company_id' => Company::factory()]);
    $sector = Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]);
    $vacancy->categories()->attach($sector);
    $vacancy->syncTags(['AI', 'CRM']);

    expect($vacancy->categories->sole()->is($sector))->toBeTrue()
        ->and($vacancy->tags->pluck('name')->all())->toContain('AI', 'CRM')
        ->and(Tag::query()->count())->toBe(2);
});

test('companies can retain their polymorphic category relation for sectors', function () {
    $company = Company::factory()->create();
    $sector = Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]);
    $company->categories()->attach($sector);

    expect($company->categories->sole()->is($sector))->toBeTrue();
});

test('taxonomy seeding is deterministic and provides hierarchy and free tags', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    $it = Category::query()->where('type', CategoryType::sector->value)->where('slug', 'it')->sole();

    expect(Category::query()->where('type', CategoryType::employment_type->value)->count())->toBe(4)
        ->and($it->children()->where('slug', 'saas')->exists())->toBeTrue()
        ->and(Tag::query()->get()->contains(fn (Tag $tag): bool => $tag->name === 'AI'))->toBeTrue();
});

test('demo seed vacancies receive deterministic structured taxonomies and tags', function () {
    $this->seed(DatabaseSeeder::class);

    $salesManager = Vacancy::query()->where('title', 'Sales Manager SaaS')->sole();

    expect(Vacancy::query()->publiclyVisible()->count())->toBe(6)
        ->and($salesManager->categories()->where('type', CategoryType::employment_type->value)->where('slug', 'fulltime')->exists())->toBeTrue()
        ->and($salesManager->categories()->where('type', CategoryType::workplace->value)->where('slug', 'hybride')->exists())->toBeTrue()
        ->and($salesManager->categories()->where('type', CategoryType::sector->value)->where('slug', 'saas')->exists())->toBeTrue()
        ->and($salesManager->categories()->where('type', CategoryType::function_area->value)->where('slug', 'sales')->exists())->toBeTrue()
        ->and($salesManager->categories()->where('type', CategoryType::experience->value)->where('slug', 'senior')->exists())->toBeTrue()
        ->and($salesManager->tags->pluck('name')->all())->toContain('B2B', 'SaaS');
});

test('an administrator can access the category resource', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $administrator = User::factory()->create();
    $administrator->assignRole('admin');

    $this->actingAs($administrator)
        ->get(CategoryResource::getUrl())
        ->assertSuccessful();
});
