<?php

namespace App\Filament\Resources\Vacancies\Actions;

use App\Actions\Vacancies\CreateManualVacancyCopy;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Models\Vacancy;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class MakeManualCopyAction
{
    public static function make(): Action
    {
        return Action::make('makeManualCopy')
            ->label('Maak handmatige kopie')
            ->icon('heroicon-o-document-duplicate')
            ->visible(fn (Vacancy $record): bool => $record->import_source_id !== null)
            ->authorize(fn (Vacancy $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
            ->requiresConfirmation()
            ->modalHeading('Handmatige vacaturekopie maken')
            ->modalDescription('De oorspronkelijke vacature blijft door de feed beheerd. De nieuwe kopie wordt een onafhankelijke conceptvacature; toekomstige feedupdates hebben daarop geen invloed.')
            ->modalSubmitActionLabel('Kopie maken')
            ->action(function (Vacancy $record, Component $livewire): void {
                $copy = app(CreateManualVacancyCopy::class)->handle($record);

                Notification::make()
                    ->success()
                    ->title('Handmatige conceptkopie gemaakt')
                    ->send();

                $livewire->redirect(VacancyResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
