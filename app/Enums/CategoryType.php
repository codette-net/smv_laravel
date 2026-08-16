<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case vacancy_category = 'vacancy_category';
    case job_type = 'job_type';
    case career_level = 'career_level';
    case experience = 'experience';
    case qualification = 'qualification';
    case company_category = 'company_category';
    case blog_category = 'blog_category';

    public function getLabel(): string
    {
        return match ($this) {
            self::vacancy_category => 'Vacaturecategorie',
            self::job_type => 'Dienstverband',
            self::career_level => 'Carrièreniveau',
            self::experience => 'Ervaring',
            self::qualification => 'Opleidingsniveau',
            self::company_category => 'Bedrijfscategorie',
            self::blog_category => 'Blogcategorie',
        };
    }
}
