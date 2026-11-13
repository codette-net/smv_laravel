<?php

namespace App\Filament\Resources\ImportSources\Pages;

use App\Enums\ImportExecutionOrigin;
use App\Enums\ImportStatus;
use App\Filament\Resources\Imports\ImportResource;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Imports\VacancyImportRunner;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewImportSource extends ViewRecord
{
    protected static string $resource = ImportSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('history')
                ->label('Importhistorie')
                ->url(fn (): string => ImportResource::getUrl('index', ['tableFilters' => ['import_source_id' => ['value' => $this->record->id]]])),
            Action::make('runAgain')
                ->label('Opnieuw uitvoeren')
                ->authorize('run')
                ->disabled(fn (): bool => ! $this->record->is_active)
                ->requiresConfirmation()
                ->modalDescription('Start een volledig nieuwe handmatige run via de normale importer. Vergrendeling en snapshotsafety blijven actief.')
                ->action(function (): void {
                    $mapping = $this->record->mappings()->where('is_active', true)->where('is_default', true)->orderBy('id')->first();
                    if ($mapping === null) {
                        Notification::make()->title('Geen actieve standaardmapping')->warning()->send();

                        return;
                    }

                    $run = app(VacancyImportRunner::class)->run($mapping, ImportExecutionOrigin::Manual);
                    $notification = Notification::make()
                        ->title($run->status === ImportStatus::Completed ? 'Import voltooid' : 'Import niet volledig uitgevoerd')
                        ->body("Status: {$run->status->value}; gelezen: {$run->total_rows}; toegepast: {$run->acceptedRows()}; afgewezen: {$run->rejectedRows()}.");

                    (match ($run->status) {
                        ImportStatus::Completed => $notification->success(),
                        ImportStatus::Skipped, ImportStatus::Incomplete => $notification->warning(),
                        default => $notification->danger(),
                    })->send();
                }),
            EditAction::make(),
        ];
    }
}
