<?php

namespace App\Filament\Resources\ImportSources\Pages;

use App\Filament\Resources\ImportSources\ImportSourceForm;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Models\ImportSource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportSource extends CreateRecord
{
    protected static string $resource = ImportSourceResource::class;

    protected bool $approve = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->approve = (bool) ($data['approved_for_automatic_run'] ?? false);
        unset($data['approved_for_automatic_run']);
        abort_unless(! $this->approve || auth()->user()?->can('approve', ImportSource::class), 403);

        return ImportSourceForm::prepareForSave($data);
    }

    protected function afterCreate(): void
    {
        if ($this->approve) {
            $this->record->approve(auth()->user());
        }
    }
}
