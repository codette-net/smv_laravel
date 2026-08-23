<?php

namespace App\Imports;

use App\Enums\ImportLogLevel;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Imports\Data\SourcePayload;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Validation\ImportRecordValidator;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VacancyImportRunner
{
    public function run(ImportMapping $mapping): Import
    {
        $source = $mapping->importSource()->with('company')->firstOrFail();
        $run = Import::create(['import_source_id' => $source->id, 'status' => ImportStatus::Processing, 'started_at' => now(), 'mapping' => ['mapping_id' => $mapping->id]]);
        try {
            $path = data_get($source->configuration, 'sample_path');
            $payload = in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true) ? app(SourceFetcher::class)->fetch($source) : (is_string($path) && is_file($path) ? SourcePayload::fromPath($path) : throw new RuntimeException('De importbron is niet leesbaar.'));
            foreach (app(RecordSelector::class)->filter(app(ImportReaderResolver::class)->for($source)->records($source, $payload), $source->selection_rules) as $record) {
                $run->increment('total_rows');
                $outcome = null;
                try {
                    $result = app(ImportMapper::class)->map($record, $mapping->load('fields'), $source);
                    $outcome = app(ImportRecordValidator::class)->validate($result->data, $source);
                    if (! $outcome->canImport()) {
                        $run->increment('skipped_rows');
                        $this->log($run, ImportLogLevel::Warning, 'Record overgeslagen.', $record->position, $outcome, 'validation_or_resolution');

                        continue;
                    }
                    $created = DB::transaction(fn () => $this->persist($source, $mapping, $outcome));
                    $run->increment($created ? 'imported_rows' : 'updated_rows');
                    $this->log($run, ImportLogLevel::Info, $created ? 'Vacature aangemaakt.' : 'Vacature bijgewerkt.', $record->position, $outcome, $created ? 'created' : 'updated');
                } catch (\Throwable $e) {
                    $run->increment('failed_rows');
                    $this->log($run, ImportLogLevel::Error, 'Record kon niet worden verwerkt.', $record->position, $outcome, 'processing_failed');
                }
            }
            $run->forceFill(['status' => ImportStatus::Completed, 'finished_at' => now()])->save();
        } catch (\Throwable $e) {
            $run->forceFill(['status' => ImportStatus::Failed, 'finished_at' => now()])->save();
            $this->log($run, ImportLogLevel::Error, 'Importbron kon niet worden verwerkt.', null, null, 'source_failed');
        }

        return $run->fresh('importLogs');
    }

    private function persist($source, ImportMapping $mapping, $outcome): bool
    {
        $data = $outcome->data;
        $reference = (string) $data->get('source_reference');
        $vacancy = Vacancy::where('import_source_id', $source->id)->where('source_reference', $reference)->first();
        $created = $vacancy === null;
        $allowed = ['title', 'description', 'location', 'published_at', 'deadline_at', 'expires_at', 'application_mode', 'application_url', 'application_email', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'rate_min', 'rate_max', 'rate_currency', 'rate_period'];
        $fields = $mapping->fields->pluck('destination_key')->all();
        $attributes = [];
        foreach ($allowed as $field) {
            if (in_array("vacancy.{$field}", $fields, true)) {
                $attributes[$field] = $data->get("vacancy.{$field}");
            }
        }
        if ($created && ! array_key_exists('description', $attributes)) {
            $attributes['description'] = '';
        }
        $attributes += ['company_id' => $source->company_id, 'import_source_id' => $source->id, 'source_reference' => $reference, 'source' => VacancySource::Import, 'status' => $created ? VacancyStatus::Pending : $vacancy->status];
        $vacancy ??= new Vacancy;
        $vacancy->fill($attributes)->save();
        foreach (['employment_type', 'workplace', 'sector', 'function_area', 'experience'] as $type) {
            if (! in_array("taxonomy.{$type}", $fields, true)) {
                continue;
            }
            $ids = collect($outcome->resolved)->where('type', $type)->pluck('category_id')->all();
            $existing = $vacancy->categories()->where('type', $type)->pluck('categories.id')->all();
            if ($existing !== []) {
                $vacancy->categories()->detach($existing);
            }
            if ($ids !== []) {
                $vacancy->categories()->attach($ids);
            }
        }
        if (in_array('tags', $fields, true)) {
            $vacancy->syncTags($data->get('tags', []));
        }

        return $created;
    }

    private function log(Import $run, ImportLogLevel $level, string $message, int|string|null $position = null, $outcome = null, ?string $code = null): void
    {
        ImportLog::create(['import_id' => $run->id, 'level' => $level, 'message' => $message, 'context' => array_filter(['position' => $position, 'source_reference' => $outcome?->data->get('source_reference'), 'code' => $code])]);
    }
}
