<?php

namespace App\Filament\Resources\ImportMappings\Pages;

use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Imports\Preview\ImportPreview;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class PreviewImportMapping extends ViewRecord
{
    protected static string $resource = ImportMappingResource::class;

    public array $preview = [];

    public string $filter = 'Alles';

    public ?string $error = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->refreshPreview();
    }

    public function refreshPreview(): void
    {
        try {
            $preview = app(ImportPreview::class)->build($this->record->importSource, $this->record->load('fields'), 25);
            $this->preview = [
                'counts' => $preview['counts'],
                'records' => collect($preview['records'])->map(fn ($record): array => [
                    'position' => $record->position,
                    'status' => $record->status(),
                    'data' => $record->result->data->toArray(),
                    'warnings' => $record->result->warnings,
                    'errors' => $record->result->errors,
                    'provenance' => $record->provenance,
                    'source' => $record->source,
                ])->all(),
            ];
            $this->error = null;
        } catch (\Throwable $exception) {
            $this->preview = ['counts' => [], 'records' => []];
            $this->error = 'De preview kon niet veilig worden gelezen. Controleer bron, recordpad, selectieregels en mapping.';
        }
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('refresh')->label('Preview vernieuwen')->action('refreshPreview')];
    }

    public function getView(): string
    {
        return 'filament.import-mappings.preview';
    }
}
