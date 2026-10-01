<?php

namespace App\Filament\Resources\Vacancies\Pages;

use App\Filament\Resources\Vacancies\Pages\Concerns\SynchronizesVacancyTaxonomies;
use App\Filament\Resources\Vacancies\VacancyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVacancy extends EditRecord
{
    use SynchronizesVacancyTaxonomies;

    protected static string $resource = VacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->syncTaxonomies($this->record);
    }
}
