<?php

namespace App\Filament\Resources\ImportSources;

use App\Enums\ImportTransport;
use App\Imports\Monitoring\ImportSourceHealthEvaluator;
use App\Models\ImportSource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportSourceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overzicht')->columns(2)->columnSpanFull()->schema([
                TextEntry::make('name')->label('Naam'), TextEntry::make('company.name')->label('Bedrijf'),
                TextEntry::make('transport')->label('Transport')->badge(), TextEntry::make('format')->label('Formaat')->badge(),
                IconEntry::make('is_active')->label('Actief')->boolean(), TextEntry::make('record_path')->label('Recordpad')->placeholder('-'),
                IconEntry::make('automatic_import_enabled')->label('Automatisch')->boolean(),
                TextEntry::make('schedule_frequency')->label('Frequentie')->placeholder('-'),
                TextEntry::make('safe_endpoint')->label('URL / endpoint (zonder querygegevens)')->state(fn (ImportSource $record): ?string => $record->safeEndpointLabel())->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Operationele gezondheid')->columns(2)->columnSpanFull()->schema([
                TextEntry::make('health')->label('Gezondheid')->state(fn (ImportSource $record) => app(ImportSourceHealthEvaluator::class)->for($record)->health)->badge(),
                TextEntry::make('latestImport.status')->label('Laatste resultaat')->badge()->placeholder('-'),
                TextEntry::make('last_imported_at')->label('Laatste uitvoerpoging')->dateTime()->placeholder('-'),
                TextEntry::make('last_completed_at')->label('Laatste afgeronde run')->state(fn (ImportSource $record) => app(ImportSourceHealthEvaluator::class)->for($record)->lastCompleted?->finished_at)->dateTime()->placeholder('-'),
                TextEntry::make('last_successful_at')->label('Laatste succesvolle run')->state(fn (ImportSource $record) => app(ImportSourceHealthEvaluator::class)->for($record)->lastSuccessful?->finished_at)->dateTime()->placeholder('-'),
                TextEntry::make('last_verified_at')->label('Laatste geverifieerde snapshot')->state(fn (ImportSource $record) => app(ImportSourceHealthEvaluator::class)->for($record)->lastVerified?->finished_at)->dateTime()->placeholder('-'),
                TextEntry::make('last_scheduled_at')->label('Laatst ingepland')->dateTime()->placeholder('-'),
                TextEntry::make('latest_counts')->label('Laatste telling (gelezen / toegepast / afgewezen)')->state(function (ImportSource $record): ?string {
                    $run = app(ImportSourceHealthEvaluator::class)->for($record)->lastAttempt;

                    return $run ? "{$run->total_rows} / {$run->acceptedRows()} / {$run->rejectedRows()}" : null;
                })->placeholder('-'),
                TextEntry::make('snapshot_result')->label('Snapshot / foutcategorie')->state(fn (ImportSource $record): ?string => app(ImportSourceHealthEvaluator::class)->for($record)->lastAttempt?->snapshotState())->placeholder('-'),
                TextEntry::make('missing_rows')->label('Nieuw ontbrekend in laatste run')->state(fn (ImportSource $record): ?int => app(ImportSourceHealthEvaluator::class)->for($record)->lastAttempt?->missing_rows)->placeholder('-'),
                TextEntry::make('count_drop')->label('Waarschuwing recorddaling')->state(function (ImportSource $record): ?string {
                    $report = app(ImportSourceHealthEvaluator::class)->for($record);

                    return $report->hasSubstantialCountDrop()
                        ? "{$report->verifiedCountDropPercent}% lager dan de vorige geverifieerde snapshot ({$report->previousVerifiedTotal} records)"
                        : null;
                })->placeholder('Geen substantiële daling vastgesteld'),
            ]),
            Section::make('Beveiliging en administratie')->columns(2)->columnSpanFull()->schema([
                TextEntry::make('approval_applicability')->label('Automatische goedkeuring')->state('Niet van toepassing op geüploade bestanden')
                    ->visible(fn ($record): bool => $record->transport === ImportTransport::Upload),
                TextEntry::make('approved_at')->label('Goedgekeurd op')->dateTime()->placeholder('Niet goedgekeurd')
                    ->visible(fn ($record): bool => in_array($record->transport, [ImportTransport::Http, ImportTransport::Api], true)),
                TextEntry::make('approvedBy.name')->label('Goedgekeurd door')->placeholder('-')
                    ->visible(fn ($record): bool => in_array($record->transport, [ImportTransport::Http, ImportTransport::Api], true)),
                TextEntry::make('created_at')->label('Aangemaakt')->dateTime(),
                TextEntry::make('updated_at')->label('Bijgewerkt')->dateTime(),
            ]),
        ]);
    }
}
