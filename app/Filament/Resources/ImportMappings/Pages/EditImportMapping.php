<?php

namespace App\Filament\Resources\ImportMappings\Pages;

use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Mapping\SourceFieldOptions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditImportMapping extends EditRecord
{
    protected static string $resource = ImportMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sample')->label('Voorbeeld controleren')->modalHeading('Genormaliseerd voorbeeld')->modalContent(function (): View {
                $record = app(SourceFieldOptions::class)->firstRecordFor($this->record->importSource);
                $result = $record ? app(ImportMapper::class)->map($record, $this->record->load('fields'), $this->record->importSource) : null;

                return view('filament.import-mappings.sample-result', compact('result'));
            })->modalSubmitAction(false),
            Action::make('reset')->label('Koppelingen wissen')->color('danger')->requiresConfirmation()->action(fn () => $this->record->fields()->delete())->successNotificationTitle('Alle veldkoppelingen zijn gewist.'),
            DeleteAction::make(),
        ];
    }
}
