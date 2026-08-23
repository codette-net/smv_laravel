<?php

namespace App\Filament\Resources\ImportMappings\Pages;

use App\Enums\CategoryType;
use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Imports\Preview\ImportPreview;
use App\Models\Category;
use App\Models\ImportTaxonomyMapping;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PreviewImportMapping extends ViewRecord
{
    protected static string $resource = ImportMappingResource::class;

    public array $preview = [];

    public string $filter = 'Alles';

    public ?string $error = null;

    public string $resolutionType = 'function_area';

    public string $resolutionValue = '';

    public ?int $resolutionCategoryId = null;

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
                    'can_import' => $record->outcome->canImport(),
                    'data' => $record->result->data->toArray(),
                    'warnings' => $record->result->warnings,
                    'errors' => $record->result->errors,
                    'validation_warnings' => $record->outcome->warnings,
                    'validation_errors' => $record->outcome->errors,
                    'unresolved' => $record->outcome->unresolved,
                    'resolved' => $record->outcome->resolved,
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

    public function saveTaxonomyMapping(): void
    {
        Gate::authorize('update', $this->record);
        $type = CategoryType::tryFrom($this->resolutionType);
        $category = Category::find($this->resolutionCategoryId);
        if ($type === null || blank($this->resolutionValue) || $category === null || $category->type !== $type) {
            $this->addError('resolutionCategoryId', 'Kies een categorie van hetzelfde taxonomietype.');

            return;
        }
        ImportTaxonomyMapping::updateOrCreate(
            ['import_source_id' => $this->record->import_source_id, 'category_type' => $type, 'source_key' => Str::lower(trim($this->resolutionValue))],
            ['source_value' => trim($this->resolutionValue), 'category_id' => $category->id],
        );
        $this->resolutionValue = '';
        $this->resolutionCategoryId = null;
        $this->refreshPreview();
    }

    public function categoriesForResolution(): array
    {
        return Category::query()->where('type', $this->resolutionType)->orderBy('name')->pluck('name', 'id')->all();
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
