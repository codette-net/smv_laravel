<?php

namespace App\Filament\Resources\Imports;

use App\Enums\ImportStatus;
use App\Filament\Resources\Imports\Pages\ListImports;
use App\Filament\Resources\Imports\Pages\ViewImport;
use App\Models\Import;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $navigationLabel = 'Importhistorie';

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('importSource.company'))
            ->columns([
                TextColumn::make('importSource.name')->label('Bron'),
                TextColumn::make('importSource.company.name')->label('Bedrijf'),
                TextColumn::make('execution_origin')->label('Herkomst')->badge()->placeholder('Onbekend'),
                TextColumn::make('status')->badge()->description(fn (Import $record): ?string => $record->status === ImportStatus::Processing && $record->started_at?->lt(now()->subMinutes(15)) ? 'Deze import lijkt langer te duren dan verwacht.' : null),
                TextColumn::make('total_rows')->label('Gelezen'),
                TextColumn::make('accepted_rows')->label('Toegepast')->state(fn (Import $record): int => $record->acceptedRows()),
                TextColumn::make('rejected_rows')->label('Afgewezen')->state(fn (Import $record): int => $record->rejectedRows()),
                TextColumn::make('skipped_rows')->label('Overgeslagen'),
                TextColumn::make('failed_rows')->label('Mislukt'),
                TextColumn::make('missing_rows')->label('Nieuw ontbrekend'),
                TextColumn::make('restored_rows')->label('Teruggekeerd'),
                TextColumn::make('snapshot_state')->label('Snapshot')->state(fn (Import $record): ?string => $record->snapshotState())->placeholder('-'),
                TextColumn::make('started_at')->label('Gestart')->dateTime(),
                TextColumn::make('finished_at')->label('Afgerond')->dateTime()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('import_source_id')->relationship('importSource', 'name')->label('Importbron')->searchable()->preload(),
                SelectFilter::make('status')->options(collect(ImportStatus::cases())->mapWithKeys(fn (ImportStatus $status): array => [$status->value => ucfirst($status->value)])->all())->label('Status'),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Samenvatting')->columns(3)->columnSpanFull()->schema([
                TextEntry::make('importSource.name')->label('Bron'),
                TextEntry::make('importSource.company.name')->label('Bedrijf'),
                TextEntry::make('execution_origin')->label('Herkomst')->badge()->placeholder('Onbekend'),
                TextEntry::make('status')->badge(),
                TextEntry::make('total_rows')->label('Gelezen'),
                TextEntry::make('accepted_rows')->label('Toegepast')->state(fn (Import $record): int => $record->acceptedRows()),
                TextEntry::make('rejected_rows')->label('Afgewezen')->state(fn (Import $record): int => $record->rejectedRows()),
                TextEntry::make('imported_rows')->label('Nieuw'),
                TextEntry::make('updated_rows')->label('Bijgewerkt'),
                TextEntry::make('skipped_rows')->label('Overgeslagen'),
                TextEntry::make('failed_rows')->label('Mislukt'),
                TextEntry::make('missing_rows')->label('Nieuw ontbrekend'),
                TextEntry::make('restored_rows')->label('Teruggekeerd'),
                TextEntry::make('started_at')->label('Gestart')->dateTime(),
                TextEntry::make('finished_at')->label('Afgerond')->dateTime()->placeholder('-'),
            ]),
            Section::make('Snapshotveiligheid')->columns(2)->columnSpanFull()->schema([
                TextEntry::make('snapshot_state')->label('Verificatieresultaat / foutcategorie')->state(fn (Import $record): ?string => $record->snapshotState())->placeholder('-'),
                TextEntry::make('reconciliation_outcome')->label('Reconciliatie')->state(fn (Import $record): string => $record->reconciliationOutcome()),
            ]),
            Section::make('Veilige logregels')->columnSpanFull()->schema([
                RepeatableEntry::make('importLogs')->schema([
                    TextEntry::make('level')->badge(),
                    TextEntry::make('message'),
                    TextEntry::make('context.position')->label('Recordpositie'),
                    TextEntry::make('context.source_reference')->label('Bronreferentie'),
                    TextEntry::make('context.vacancy_id')->label('Vacaturenummer'),
                    TextEntry::make('context.code')->label('Uitkomstcode'),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListImports::route('/'), 'view' => ViewImport::route('/{record}')];
    }
}
