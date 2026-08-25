<?php

namespace App\Filament\Resources\ImportSources\Pages;

use App\Filament\Resources\ImportSources\ImportSourceForm;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Imports\Mapping\SourceFieldOptions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditImportSource extends EditRecord
{
    protected static string $resource = ImportSourceResource::class;

    protected ?bool $approve = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshSourceFields')
                ->label('Bronvelden vernieuwen')
                ->action(function (): void {
                    try {
                        $fields = app(SourceFieldOptions::class)->refresh($this->record->fresh());
                        Notification::make()->title('Bronanalyse voltooid')->body(count($fields).' bronvelden gevonden.')->success()->send();
                    } catch (Throwable) {
                        Notification::make()->title('Bronanalyse mislukt')->body('De bron kon niet veilig worden gelezen. Controleer de broninstellingen en probeer opnieuw.')->danger()->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['approved_for_automatic_run'] = $this->record->approved_at !== null;

        return ImportSourceForm::prepareForFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->approve = array_key_exists('approved_for_automatic_run', $data)
            ? (bool) $data['approved_for_automatic_run']
            : $this->record->approved_at !== null;
        unset($data['approved_for_automatic_run']);
        abort_unless(auth()->user()?->can('approve', $this->record) || $this->approve === ($this->record->approved_at !== null), 403);

        return ImportSourceForm::prepareForSave($data, $this->record);
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
