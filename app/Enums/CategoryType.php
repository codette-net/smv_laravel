<?php

namespace App\Enums;

enum CategoryType: string
{
    case vacancy_category = 'vacancy_category';
    case job_type = 'job_type';
    case career_level = 'career_level';
    case experience = 'experience';
    case qualification = 'qualification';
    case company_category = 'company_category';
    case blog_category = 'blog_category';

}
