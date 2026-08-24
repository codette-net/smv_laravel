<?php

namespace App\Filament\Resources\ImportSources;

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Models\ImportSource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class ImportSourceForm
{
    private const REMOTE_TRANSPORTS = [ImportTransport::Http->value, ImportTransport::Api->value];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Algemeen')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Naam')->required()->maxLength(255),
                Select::make('company_id')->label('Bedrijf')->relationship('company', 'name')->required()->searchable()->preload(),
                Select::make('transport')->label('Transport')->options(ImportTransport::class)->required()->live(),
                Select::make('format')->label('Formaat')->options(ImportFormat::class)->required(),
                Toggle::make('is_active')->label('Actief')->helperText('De bron is ingeschakeld voor gebruik.')->default(true),
            ]),
            Section::make('Bron')->columns(2)->columnSpanFull()->schema([
                FileUpload::make('uploaded_source_path')
                    ->label('Bronbestand')
                    ->disk('local')
                    ->directory('imports/sources')
                    ->visibility('private')
                    ->storeFileNamesIn('uploaded_source_name')
                    ->acceptedFileTypes([
                        'application/json', 'text/json', 'application/xml', 'text/xml', 'text/csv', 'application/csv',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->rules(['extensions:json,xml,csv,xlsx'])
                    ->maxSize(50 * 1024)
                    ->multiple(false)
                    ->downloadable(false)
                    ->openable(false)
                    ->previewable(false)
                    ->deleteUploadedFileUsing(fn (): null => null)
                    ->required(fn (Get $get, ?ImportSource $record): bool => self::isUpload($get('transport')) && blank(data_get($record?->configuration, 'source_path')))
                    ->visible(fn (Get $get): bool => self::isUpload($get('transport')))
                    ->helperText('Upload één JSON-, XML-, CSV- of XLSX-bestand (maximaal 50 MB). Het bestand wordt privé opgeslagen.')
                    ->columnSpanFull(),
                TextInput::make('endpoint_url')->label('URL / endpoint')->url()->maxLength(2048)
                    ->visible(fn (Get $get): bool => self::isRemote($get('transport')))
                    ->required(fn (Get $get): bool => self::isRemote($get('transport')))
                    ->columnSpanFull(),
                TextInput::make('record_path')->label('Recordpad')->maxLength(255)->placeholder('response.jobs.*')
                    ->helperText('Geeft aan waar de afzonderlijke vacatures in de bron staan. Bijvoorbeeld response.jobs.* bij een passende JSON-structuur; het juiste pad verschilt per feed.')
                    ->columnSpanFull(),
                Textarea::make('selection_rules')->label('Selectieregels (JSON)')->json()->rows(4)
                    ->helperText('Optionele regels die bepalen welke bronrecords worden verwerkt. Dit staat los van het koppelen van taxonomieën en categorieën.')
                    ->formatStateUsing(fn (?array $state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->dehydrateStateUsing(fn (?string $state): ?array => filled($state) ? json_decode($state, true, 512, JSON_THROW_ON_ERROR) : null)
                    ->columnSpanFull(),
            ]),
            Section::make('Geavanceerde broninstellingen')
                ->description('Optionele technische instellingen voor deze bron; normaal kan dit leeg blijven.')
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Textarea::make('advanced_configuration')->label('Configuratie (JSON)')->json()->rows(5)
                        ->formatStateUsing(fn (?array $state): ?string => empty($state) ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->dehydrateStateUsing(fn (?string $state): array => filled($state) ? json_decode($state, true, 512, JSON_THROW_ON_ERROR) : [])
                        ->columnSpanFull(),
                ]),
            Section::make('Beveiliging')->columnSpanFull()->schema([
                Textarea::make('credentials')->label('Inloggegevens (JSON)')->json()->rows(4)
                    ->visible(fn (Get $get): bool => self::isRemote($get('transport')))
                    ->helperText('Alleen nodig voor een beveiligde externe feed of API. Vul dit alleen in om nieuwe inloggegevens op te slaan; bestaande waarden worden nooit getoond.')
                    ->formatStateUsing(fn () => null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR)),
                Toggle::make('approved_for_automatic_run')->label('Goedkeuren voor automatische imports')
                    ->helperText('Alleen relevant voor automatische externe productieruns; niet nodig voor een handmatig geüpload testbestand.')
                    ->visible(fn (Get $get): bool => self::isRemote($get('transport')) && (auth()->user()?->can('approve', ImportSource::class) ?? false))
                    ->dehydrated(false),
            ]),
        ]);
    }

    public static function prepareForFill(array $data): array
    {
        $configuration = is_array($data['configuration'] ?? null) ? $data['configuration'] : [];
        $data['uploaded_source_path'] = $configuration['source_path'] ?? null;
        $data['uploaded_source_name'] = $configuration['source_name'] ?? null;
        $data['advanced_configuration'] = Arr::except($configuration, ['source_path', 'source_name', 'sample_path']);
        unset($data['configuration']);

        return $data;
    }

    public static function prepareForSave(array $data, ?ImportSource $record = null): array
    {
        $existing = is_array($record?->configuration) ? $record->configuration : [];
        $configuration = is_array($data['advanced_configuration'] ?? null) ? $data['advanced_configuration'] : [];
        $path = $data['uploaded_source_path'] ?? data_get($existing, 'source_path');
        $name = $data['uploaded_source_name'] ?? data_get($existing, 'source_name');

        if (filled($path)) {
            $configuration['source_path'] = $path;
        }
        if (filled($name)) {
            $configuration['source_name'] = $name;
        }
        if (isset($existing['sample_path']) && ! isset($configuration['source_path'])) {
            $configuration['sample_path'] = $existing['sample_path'];
        }

        $data['configuration'] = $configuration ?: null;
        unset($data['advanced_configuration'], $data['uploaded_source_path'], $data['uploaded_source_name']);

        return $data;
    }

    private static function isUpload(mixed $transport): bool
    {
        return ($transport instanceof ImportTransport ? $transport->value : $transport) === ImportTransport::Upload->value;
    }

    private static function isRemote(mixed $transport): bool
    {
        $value = $transport instanceof ImportTransport ? $transport->value : $transport;

        return in_array($value, self::REMOTE_TRANSPORTS, true);
    }
}
