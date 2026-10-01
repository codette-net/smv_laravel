<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Tags\Tag;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories(CategoryType::employment_type, ['Fulltime', 'Parttime', 'Freelance', 'Stage']);
        $this->seedCategories(CategoryType::workplace, ['Op locatie', 'Hybride', 'Remote']);
        $this->seedCategories(CategoryType::function_area, ['Sales', 'Marketing', 'Business Development', 'Accountmanagement', 'Communicatie']);
        $this->seedCategories(CategoryType::experience, ['Starter', 'Junior', 'Medior', 'Senior']);

        $sectors = $this->seedCategories(CategoryType::sector, ['IT', 'Horeca', 'Financiële dienstverlening', 'Retail', 'Zakelijke dienstverlening']);
        $this->seedCategories(CategoryType::sector, ['SaaS', 'E-commerce'], $sectors['IT']->id);
        $this->seedCategories(CategoryType::sector, ['Fintech'], $sectors['Financiële dienstverlening']->id);

        $this->seedCategories(CategoryType::company_category, ['Recruitment Bureau', 'Marketing Agency', 'SaaS', 'B2B', 'Corporate', 'Scale-up', 'Startup', 'Non-profit', 'Media', 'Consultancy']);
        $this->seedCategories(CategoryType::blog_category, ['Carrière', 'Sollicitatietips', 'CV', 'LinkedIn', 'Sales', 'Marketing', 'Werkgeluk', 'AI', 'Arbeidsmarkt', 'Employer Branding', 'Recruitment', 'Persoonlijke Ontwikkeling', 'Salaris', 'Remote Werken', 'Leiderschap']);

        foreach (['AI', 'Content', 'SaaS', 'B2B', 'CRM', 'SEO'] as $tag) {
            Tag::findOrCreate($tag);
        }
    }

    /** @return array<string, Category> */
    private function seedCategories(CategoryType $type, array $names, ?int $parentId = null): array
    {
        $categories = [];

        foreach ($names as $name) {
            $category = Category::firstOrCreate(
                ['type' => $type->value, 'slug' => Str::slug($name)],
                ['name' => $name, 'parent_id' => $parentId],
            );

            if ($category->parent_id !== $parentId) {
                $category->update(['parent_id' => $parentId]);
            }

            $categories[$name] = $category;
        }

        return $categories;
    }
}
