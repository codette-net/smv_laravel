<?php

namespace App\Filament\Resources\Vacancies\Pages;

use App\Filament\Resources\Vacancies\Pages\Concerns\SynchronizesVacancyTaxonomies;
use App\Filament\Resources\Vacancies\VacancyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVacancy extends CreateRecord
{
    use SynchronizesVacancyTaxonomies;

    protected static string $resource = VacancyResource::class;

    protected function afterCreate(): void
    {
        $this->syncTaxonomies($this->record);
    }
}
