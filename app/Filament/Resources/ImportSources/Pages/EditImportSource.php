<?php

namespace App\Filament\Resources\ImportSources\Pages;

use App\Filament\Resources\ImportSources\ImportSourceResource;
use Filament\Resources\Pages\EditRecord;

class EditImportSource extends EditRecord
{
    protected static string $resource = ImportSourceResource::class;

    protected ?bool $approve = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['approved_for_automatic_run'] = $this->record->approved_at !== null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->approve = (bool) ($data['approved_for_automatic_run'] ?? false);
        unset($data['approved_for_automatic_run']);
        abort_unless(auth()->user()?->can('approve', $this->record) || $this->approve === ($this->record->approved_at !== null), 403);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->approve === true && $this->record->approved_at === null) {
            $this->record->approve(auth()->user());
        }
        if ($this->approve === false && $this->record->approved_at !== null) {
            $this->record->revokeApproval();
        }
    }
}
