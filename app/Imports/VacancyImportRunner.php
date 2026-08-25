<?php

namespace App\Imports;

use App\Enums\ImportLogLevel;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Validation\ImportRecordValidator;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

class VacancyImportRunner
{
    public function run(ImportMapping $mapping): Import
    {
        $source = $mapping->importSource()->with('company')->firstOrFail();
        $run = Import::create(['import_source_id' => $source->id, 'status' => ImportStatus::Processing, 'started_at' => now(), 'mapping' => ['mapping_id' => $mapping->id]]);
        $seenReferences = [];
        $persistedVacancies = [];
        $reconciliationIsReliable = true;
        try {
            $payload = in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true)
                ? app(SourceFetcher::class)->fetch($source)
                : app(LocalSourceLoader::class)->forSource($source);
            foreach (app(RecordSelector::class)->filter(app(ImportReaderResolver::class)->for($source)->records($source, $payload), $source->selection_rules) as $record) {
                $run->increment('total_rows');
                $outcome = null;
                try {
                    $result = app(ImportMapper::class)->map($record, $mapping->load('fields'), $source);
                    $outcome = app(ImportRecordValidator::class)->validate($result->data, $source);
                    $reference = $outcome->data->get('source_reference');
                    if (is_scalar($reference) && filled(trim((string) $reference))) {
                        $seenReferences[] = trim((string) $reference);
                    } else {
                        $reconciliationIsReliable = false;
                    }
                    if (! $outcome->canImport()) {
                        $run->increment('skipped_rows');
                        $this->log($run, ImportLogLevel::Warning, 'Record overgeslagen.', $record->position, $outcome, 'validation_or_resolution');

                        continue;
                    }
                    $persistence = DB::transaction(fn () => $this->persist($source, $mapping, $outcome));
                    $persistedVacancies[$persistence['vacancy_id']] = $persistence;
                    $run->increment($persistence['created'] ? 'imported_rows' : 'updated_rows');
                    $this->log($run, ImportLogLevel::Info, $persistence['created'] ? 'Vacature aangemaakt.' : 'Vacature bijgewerkt.', $record->position, $outcome, $persistence['created'] ? 'created' : 'updated');
                } catch (\Throwable $e) {
                    $reconciliationIsReliable = false;
                    $run->increment('failed_rows');
                    $this->log($run, ImportLogLevel::Error, 'Record kon niet worden verwerkt.', $record->position, $outcome, 'processing_failed');
                }
            }
            $this->finalizeSeenVacancies($run, $persistedVacancies);
            if ($reconciliationIsReliable) {
                $this->detectMissingVacancies($run, $source->id, array_values(array_unique($seenReferences)));
            } else {
                $this->log($run, ImportLogLevel::Warning, 'Controle op ontbrekende vacatures is overgeslagen omdat deze run niet volledig betrouwbaar was.', null, null, 'missing_detection_skipped');
            }
            $run->forceFill(['status' => ImportStatus::Completed, 'finished_at' => now()])->save();
        } catch (\Throwable $e) {
            $run->forceFill(['status' => ImportStatus::Failed, 'finished_at' => now()])->save();
            $this->log($run, ImportLogLevel::Error, 'Importbron kon niet worden verwerkt.', null, null, 'source_failed');
        }

        return $run->fresh('importLogs');
    }

    /** @return array{created: bool, vacancy_id: int, source_reference: string, was_missing: bool} */
    private function persist($source, ImportMapping $mapping, $outcome): array
    {
        $data = $outcome->data;
        $reference = (string) $data->get('source_reference');
        $vacancy = Vacancy::where('import_source_id', $source->id)->where('source_reference', $reference)->first();
        $created = $vacancy === null;
        $wasMissing = $vacancy?->missing_since !== null;
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

        return ['created' => $created, 'vacancy_id' => $vacancy->id, 'source_reference' => $reference, 'was_missing' => $wasMissing];
    }

    /** @param array<int, array{created: bool, vacancy_id: int, source_reference: string, was_missing: bool}> $vacancies */
    private function finalizeSeenVacancies(Import $run, array $vacancies): void
    {
        if ($vacancies === []) {
            return;
        }

        $now = now();
        Vacancy::query()->whereKey(array_keys($vacancies))->update([
            'last_seen_at' => $now,
            'last_seen_import_id' => $run->id,
            'missing_since' => null,
        ]);

        foreach ($vacancies as $vacancy) {
            if (! $vacancy['was_missing']) {
                continue;
            }

            $run->increment('restored_rows');
            $this->log($run, ImportLogLevel::Info, 'Vacature is teruggekeerd in de importbron.', null, null, 'restored_in_source', [
                'source_reference' => $vacancy['source_reference'],
                'vacancy_id' => $vacancy['vacancy_id'],
            ]);
        }
    }

    /** @param list<string> $seenReferences */
    private function detectMissingVacancies(Import $run, int $sourceId, array $seenReferences): void
    {
        Vacancy::query()
            ->where('import_source_id', $sourceId)
            ->when($seenReferences !== [], fn ($query) => $query->whereNotIn('source_reference', $seenReferences))
            ->orderBy('id')
            ->each(function (Vacancy $vacancy) use ($run): void {
                if ($vacancy->missing_since === null) {
                    $vacancy->forceFill(['missing_since' => now()])->save();
                    $run->increment('missing_rows');
                    $message = 'Vacature ontbreekt voor het eerst in de importbron.';
                    $code = 'missing_from_source';
                } else {
                    $message = 'Vacature ontbreekt nog steeds in de importbron.';
                    $code = 'still_missing_from_source';
                }

                $this->log($run, ImportLogLevel::Warning, $message, null, null, $code, [
                    'source_reference' => $vacancy->source_reference,
                    'vacancy_id' => $vacancy->id,
                ]);
            });
    }

    /** @param array<string, int|string|null> $context */
    private function log(Import $run, ImportLogLevel $level, string $message, int|string|null $position = null, $outcome = null, ?string $code = null, array $context = []): void
    {
        ImportLog::create(['import_id' => $run->id, 'level' => $level, 'message' => $message, 'context' => array_filter(array_merge(['position' => $position, 'source_reference' => $outcome?->data->get('source_reference'), 'code' => $code], $context))]);
    }
}
