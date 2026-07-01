<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Class User
 *
 * @package App\Models
 * @mixin Builder
 */
class CategorySeeder extends Seeder
{

    public function run(): void
    {
        $categories = [

            'vacancy_category' => [
                'Sales',
                'Marketing',
                'Online Marketing',
                'Digital Marketing',
                'SEO',
                'SEA',
                'Content Marketing',
                'E-commerce',
                'Customer Success',
                'Business Development',
                'Account Management',
                'Inside Sales',
                'Field Sales',
                'Retail',
                'Management',
                'HR',
                'Recruitment',
                'Communicatie',
            ],
            'job_type' => [
                'Fulltime',
                'Parttime',
                'Freelance',
                'Stage',
                'Tijdelijk',
            ],

            'career_level' => [
                'Starter',
                'Junior',
                'Medior',
                'Senior',
                'Lead',
                'Manager',
                'Director',
            ],

            'experience' => [
                '0-2 jaar',
                '2-5 jaar',
                '5-10 jaar',
                '10+ jaar',
            ],

            'qualification' => [
                'MBO',
                'HBO',
                'WO',
            ],

            'company_category' => [
                'Recruitment Bureau',
                'Marketing Agency',
                'Retail',
                'SaaS',
                'E-commerce',
                'B2B',
                'Corporate',
                'Scale-up',
                'Startup',
                'Non-profit',
                'Media',
                'Consultancy',
            ],

            'blog_category' => [
                'Carrière',
                'Sollicitatietips',
                'CV',
                'LinkedIn',
                'Sales',
                'Marketing',
                'Werkgeluk',
                'AI',
                'Arbeidsmarkt',
                'Employer Branding',
                'Recruitment',
                'Persoonlijke Ontwikkeling',
                'Salaris',
                'Remote Werken',
                'Leiderschap',
            ],

        ];

        foreach ($categories as $type => $items) {
            foreach ($items as $name) {
                Category::firstOrCreate(
                    [
                        'type' => $type,
                        'slug' => Str::slug($name),
                    ],
                    [
                        'name' => $name,
                    ]
                );
            }
        }
    }
}
