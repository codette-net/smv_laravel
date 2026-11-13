<?php

namespace App\Imports;

use App\Enums\ImportExecutionOrigin;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VacancyImportRunner
{
    public const SOURCE_LOCK_SECONDS = 900;

    public function run(ImportMapping $mapping, ImportExecutionOrigin $origin = ImportExecutionOrigin::Manual): Import
    {
        $source = $mapping->importSource()->with('company')->firstOrFail();
        $lock = Cache::lock(self::sourceLockKey($source->id), self::SOURCE_LOCK_SECONDS);
        if (! $lock->get()) {
            return $this->skippedRun($source->id, $mapping->id, $origin);
        }

        try {
            $mapping->load('fields');
            $run = Import::create([
                'import_source_id' => $source->id,
                'status' => ImportStatus::Processing,
                'execution_origin' => $origin,
                'started_at' => now(),
                'mapping' => $this->runMapping($mapping->id, $source, 'processing'),
            ]);
            $payload = in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true)
                ? app(SourceFetcher::class)->fetch($source)
                : app(LocalSourceLoader::class)->forSource($source);
            app(SourceContractValidator::class)->validate($source, $payload);
            $records = $this->prepareRecords($run, $source, $mapping, $payload);
            $snapshot = $source->usesVerifiedSnapshotReconciliation();
            $hasUnsafeRecord = collect($records)->contains(fn (array $record): bool => $record['mapping_errors'] !== [] || ! $record['outcome']->canImport());
            $safety = $snapshot ? $this->snapshotSafety($source, $records) : null;
            if ($safety !== null) {
                $this->log($run, ImportLogLevel::Warning, 'Snapshot is niet volledig verifieerbaar; ontbrekende vacatures zijn niet verwerkt.', null, null, $safety);

                return $this->finish($run, ImportStatus::Incomplete, $safety);
            }

            $persistedVacancies = [];
            foreach ($records as $entry) {
                $outcome = $entry['outcome'];
                if (! $outcome->canImport()) {
                    $run->increment('skipped_rows');
                    $this->log($run, ImportLogLevel::Warning, 'Record overgeslagen.', $entry['position'], $outcome, 'validation_or_resolution');

                    continue;
                }

                try {
                    $persistence = DB::transaction(fn () => $this->persist($source, $mapping, $outcome));
                    $persistedVacancies[$persistence['vacancy_id']] = $persistence;
                    $run->increment($persistence['created'] ? 'imported_rows' : 'updated_rows');
                    $this->log($run, ImportLogLevel::Info, $persistence['created'] ? 'Vacature aangemaakt.' : 'Vacature bijgewerkt.', $entry['position'], $outcome, $persistence['created'] ? 'created' : 'updated');
                } catch (\Throwable $e) {
                    $run->increment('failed_rows');
                    $this->log($run, ImportLogLevel::Error, 'Record kon niet worden verwerkt.', $entry['position'], $outcome, 'processing_failed');
                    if ($snapshot) {
                        return $this->finish($run, ImportStatus::Failed, 'application_failed');
                    }
                }
            }
            $this->finalizeSeenVacancies($run, $persistedVacancies);
            if ($snapshot) {
                $this->detectMissingVacancies($run, $source->id, array_column($records, 'reference'));
            } elseif ($hasUnsafeRecord) {
                $this->log($run, ImportLogLevel::Warning, 'Controle op ontbrekende vacatures is overgeslagen omdat deze run niet volledig betrouwbaar was.', null, null, 'missing_detection_skipped');
            }

            return $this->finish($run, ImportStatus::Completed, $snapshot ? 'verified_complete' : 'not_configured');
        } catch (\Throwable $e) {
            $run ??= Import::create(['import_source_id' => $source->id, 'status' => ImportStatus::Processing, 'execution_origin' => $origin, 'started_at' => now(), 'mapping' => $this->runMapping($mapping->id, $source, 'processing')]);
            $this->log($run, ImportLogLevel::Error, 'Importbron kon niet worden verwerkt.', null, null, 'source_failed');

            return $this->finish($run, ImportStatus::Failed, 'read_or_parse_failed');
        } finally {
            $lock->release();
        }
    }

    /** @return list<array{position: int|string, outcome: mixed, reference: string, mapping_errors: list<string>}> */
    private function prepareRecords(Import $run, $source, ImportMapping $mapping, mixed $payload): array
    {
        $records = [];
        foreach (app(RecordSelector::class)->filter(app(ImportReaderResolver::class)->for($source)->records($source, $payload), $source->selection_rules) as $record) {
            $run->increment('total_rows');
            $result = app(ImportMapper::class)->map($record, $mapping, $source);
            $outcome = app(ImportRecordValidator::class)->validate($result->data, $source);
            $reference = $outcome->data->get('source_reference');
            $records[] = [
                'position' => $record->position,
                'outcome' => $outcome,
                'reference' => is_scalar($reference) ? trim((string) $reference) : '',
                'mapping_errors' => $result->errors,
            ];
        }

        return $records;
    }

    /** @param list<array{position: int|string, outcome: mixed, reference: string, mapping_errors: list<string>}> $records */
    private function snapshotSafety($source, array $records): ?string
    {
        if ($source->marksSnapshotsAsPartial()) {
            return 'snapshot_marked_partial';
        }
        if ($records === [] && ! $source->allowsEmptyVerifiedSnapshot()) {
            return 'snapshot_empty_unconfirmed';
        }
        $references = array_column($records, 'reference');
        if (in_array('', $references, true)) {
            return 'snapshot_identity_missing';
        }
        if (count($references) !== count(array_unique($references))) {
            return 'snapshot_duplicate_reference';
        }
        foreach ($records as $record) {
            if ($record['mapping_errors'] !== []) {
                return 'snapshot_mapping_invalid';
            }
            if (! $record['outcome']->canImport()) {
                return 'snapshot_record_unresolved';
            }
        }

        return null;
    }

    private function skippedRun(int $sourceId, int $mappingId, ImportExecutionOrigin $origin): Import
    {
        $run = Import::create([
            'import_source_id' => $sourceId,
            'status' => ImportStatus::Skipped,
            'execution_origin' => $origin,
            'started_at' => now(),
            'finished_at' => now(),
            'mapping' => ['mapping_id' => $mappingId, 'snapshot' => ['state' => 'concurrency_rejected']],
        ]);
        $this->log($run, ImportLogLevel::Warning, 'Import overgeslagen omdat deze bron al wordt verwerkt.', null, null, 'source_locked');

        return $run->fresh('importLogs');
    }

    private function finish(Import $run, ImportStatus $status, string $snapshotState): Import
    {
        $mapping = is_array($run->mapping) ? $run->mapping : [];
        $snapshot = is_array($mapping['snapshot'] ?? null) ? $mapping['snapshot'] : [];
        $mapping['snapshot'] = [...$snapshot, 'state' => $snapshotState];
        $run->forceFill(['status' => $status, 'finished_at' => now(), 'mapping' => $mapping])->save();
        $run->importSource()->update(['last_imported_at' => now()]);

        return $run->fresh('importLogs');
    }

    private function runMapping(int $mappingId, $source, string $state): array
    {
        return [
            'mapping_id' => $mappingId,
            'snapshot' => [
                'state' => $state,
                'reconciliation_enabled' => $source->usesVerifiedSnapshotReconciliation(),
                'allow_empty' => $source->allowsEmptyVerifiedSnapshot(),
            ],
        ];
    }

    public static function sourceLockKey(int $sourceId): string
    {
        return "imports:source:{$sourceId}";
    }

    /** @return array{created: bool, vacancy_id: int, source_reference: string, was_missing: bool} */
    private function persist($source, ImportMapping $mapping, $outcome): array
    {
        $data = $outcome->data;
        $reference = (string) $data->get('source_reference');
        $vacancy = Vacancy::where('import_source_id', $source->id)->where('source_reference', $reference)->first();
        $created = $vacancy === null;
        $wasMissing = $vacancy?->missing_since !== null;
        $allowed = ['title', 'description', 'location', 'advertised_state', 'advertised_country', 'advertised_postal_code', 'workplace_city', 'workplace_country', 'source_requisition_id', 'source_salary_text', 'source_language', 'source_product_code', 'published_at', 'deadline_at', 'expires_at', 'application_mode', 'application_url', 'application_email', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'salary_basis', 'rate_min', 'rate_max', 'rate_currency', 'rate_period'];
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
        $attributes += ['company_id' => $outcome->companyId, 'import_source_id' => $source->id, 'source_reference' => $reference, 'source' => VacancySource::Import, 'status' => $created ? VacancyStatus::Pending : $vacancy->status];
        $vacancy ??= new Vacancy;
        $vacancy->fill($attributes)->save();
        foreach (['employment_type', 'workplace', 'sector', 'function_area', 'experience', 'qualification'] as $type) {
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
