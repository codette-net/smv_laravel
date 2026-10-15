<?php

namespace App\Filament\Resources\ImportMappings\Pages;

use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Mapping\SourceFieldOptions;
use App\Imports\VacancyImportRunner;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;

class EditImportMapping extends EditRecord
{
    protected static string $resource = ImportMappingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?bool $hasUnsavedDataChangesAlert = true;

    public string $saveState = 'Opgeslagen';

    public function updatedData(): void
    {
        $this->saveState = 'Wijzigingen niet opgeslagen';
    }

    protected function beforeSave(): void
    {
        $this->saveState = 'Opslaan...';
    }

    protected function afterSave(): void
    {
        $this->saveState = 'Opgeslagen';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveState')->label(fn (): string => $this->saveState)->disabled()->color(fn (): string => $this->saveState === 'Opgeslagen' ? 'success' : 'warning'),
            Action::make('execute')->label('Import uitvoeren')->requiresConfirmation()->modalDescription(fn (): string => "Voer mapping [{$this->record->name}] uit voor bron [{$this->record->importSource->name}].")->authorize('execute')->action(function (): void {
                $run = app(VacancyImportRunner::class)->run($this->record->load('fields'));
                Notification::make()->title('Import voltooid')->body("Aangemaakt: {$run->imported_rows}; bijgewerkt: {$run->updated_rows}; overgeslagen: {$run->skipped_rows}; mislukt: {$run->failed_rows}.")->success()->send();
            }),
            Action::make('preview')->label('Preview import')->url(fn (): string => ImportMappingResource::getUrl('preview', ['record' => $this->record])),
            Action::make('sample')->label('Voorbeeld controleren')->modalHeading('Genormaliseerd voorbeeld')->modalWidth(Width::SevenExtraLarge)->modalContent(function (): View {
                $record = app(SourceFieldOptions::class)->firstRecordFor($this->record->importSource);
                $result = $record ? app(ImportMapper::class)->map($record, $this->record->load('fields'), $this->record->importSource) : null;

                return view('filament.import-mappings.sample-result', compact('result'));
            })->modalSubmitAction(false),
            Action::make('reset')->label('Koppelingen wissen')->color('danger')->requiresConfirmation()->action(fn () => $this->record->fields()->delete())->successNotificationTitle('Alle veldkoppelingen zijn gewist.'),
            DeleteAction::make(),
        ];
    }
}
