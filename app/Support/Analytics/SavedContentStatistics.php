<?php

namespace App\Support\Analytics;

use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SavedContentStatistics
{
    public function currentVacancySaves(): int
    {
        return DB::table('saved_vacancies')
            ->join('vacancies', 'vacancies.id', '=', 'saved_vacancies.vacancy_id')
            ->whereNull('vacancies.deleted_at')
            ->count();
    }

    public function currentCompanySaves(): int
    {
        return DB::table('saved_companies')
            ->join('companies', 'companies.id', '=', 'saved_companies.company_id')
            ->whereNull('companies.deleted_at')
            ->count();
    }

    /** @return Collection<int, Vacancy> */
    public function topVacancies(int $limit = 5): Collection
    {
        return Vacancy::query()
            ->with('company:id,name')
            ->has('savedByUsers')
            ->withCount('savedByUsers')
            ->orderByDesc('saved_by_users_count')
            ->orderBy('title')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Company> */
    public function topCompanies(int $limit = 5): Collection
    {
        return Company::query()
            ->has('savedByUsers')
            ->withCount('savedByUsers')
            ->orderByDesc('saved_by_users_count')
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
