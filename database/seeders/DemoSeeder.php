<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;

class DemoSeeder extends DatabaseSeeder
{
    public function run(): void
    {
        User::factory(10)->create();

        $companies = Company::factory(6)->create();
        $categories = Category::query()
            ->whereIn('type', array_map(fn (CategoryType $type): string => $type->value, $this->taxonomyTypes()))
            ->get()
            ->keyBy(fn (Category $category): string => $category->type->value.':'.$category->slug);

        foreach ($this->vacancyDefinitions() as $index => $definition) {
            $vacancy = Vacancy::factory()->create([
                'company_id' => $companies[$index % $companies->count()]->id,
                'title' => $definition['title'],
                'status' => VacancyStatus::Active,
                'source' => VacancySource::Manual,
                'is_filled' => false,
                'published_at' => now()->subDays($index),
                'deadline_at' => now()->addDays(14 + $index),
                'expires_at' => now()->addDays(45 + $index),
            ]);

            $vacancy->categories()->sync(collect($this->taxonomyTypes())
                ->map(fn (CategoryType $type): int => $categories->get($type->value.':'.$definition[$type->value])->id)
                ->all());
            $vacancy->syncTags($definition['tags']);
        }
    }

    /** @return array<int, CategoryType> */
    private function taxonomyTypes(): array
    {
        return [
            CategoryType::employment_type,
            CategoryType::workplace,
            CategoryType::sector,
            CategoryType::function_area,
            CategoryType::experience,
        ];
    }

    /** @return array<int, array<string, string|array<int, string>>> */
    private function vacancyDefinitions(): array
    {
        return [
            ['title' => 'Sales Manager SaaS', 'employment_type' => 'fulltime', 'workplace' => 'hybride', 'sector' => 'saas', 'function_area' => 'sales', 'experience' => 'senior', 'tags' => ['B2B', 'SaaS']],
            ['title' => 'Marketing Specialist IT', 'employment_type' => 'fulltime', 'workplace' => 'remote', 'sector' => 'it', 'function_area' => 'marketing', 'experience' => 'medior', 'tags' => ['Content', 'AI']],
            ['title' => 'Accountmanager Retail', 'employment_type' => 'parttime', 'workplace' => 'op-locatie', 'sector' => 'retail', 'function_area' => 'accountmanagement', 'experience' => 'junior', 'tags' => ['CRM', 'B2B']],
            ['title' => 'Business Developer Fintech', 'employment_type' => 'freelance', 'workplace' => 'hybride', 'sector' => 'fintech', 'function_area' => 'business-development', 'experience' => 'senior', 'tags' => ['B2B', 'CRM']],
            ['title' => 'Communicatiemedewerker Horeca', 'employment_type' => 'stage', 'workplace' => 'op-locatie', 'sector' => 'horeca', 'function_area' => 'communicatie', 'experience' => 'starter', 'tags' => ['Content']],
            ['title' => 'SEO Marketeer Zakelijke Dienstverlening', 'employment_type' => 'fulltime', 'workplace' => 'remote', 'sector' => 'zakelijke-dienstverlening', 'function_area' => 'marketing', 'experience' => 'medior', 'tags' => ['SEO', 'AI']],
        ];
    }
}
