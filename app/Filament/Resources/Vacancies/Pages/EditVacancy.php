<?php

namespace App\Filament\Resources\Vacancies\Pages;

use App\Enums\VacancyStatus;
use App\Filament\Resources\Vacancies\Actions\MakeManualCopyAction;
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
            MakeManualCopyAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->syncTaxonomies($this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->source_offlined_at !== null
            && ($data['status'] ?? null) === VacancyStatus::Archived->value) {
            $data['source_offlined_at'] = null;
        }

        return $data;
    }
}
