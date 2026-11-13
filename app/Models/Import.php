<?php

namespace App\Models;

use App\Enums\ImportExecutionOrigin;
use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory, SoftDeletes;

    // Legacy `source` is intentionally excluded: import_source_id is the provider identity.
    protected $fillable = [
        'import_source_id',
        'type',
        'filename',
        'status',
        'execution_origin',
        'total_rows',
        'imported_rows',
        'updated_rows',
        'skipped_rows',
        'failed_rows',
        'missing_rows',
        'restored_rows',
        'mapping',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'execution_origin' => ImportExecutionOrigin::class,
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'updated_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'missing_rows' => 'integer',
            'restored_rows' => 'integer',
            'mapping' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function acceptedRows(): int
    {
        return $this->imported_rows + $this->updated_rows;
    }

    public function rejectedRows(): int
    {
        return $this->skipped_rows + $this->failed_rows;
    }

    public function snapshotState(): ?string
    {
        $state = data_get($this->mapping, 'snapshot.state');

        return is_string($state) ? $state : null;
    }

    public function reconciliationOutcome(): string
    {
        return match ($this->snapshotState()) {
            'verified_complete' => 'Uitgevoerd na veilige verificatie',
            'not_configured' => 'Niet geconfigureerd',
            'concurrency_rejected' => 'Niet gestart door bronvergrendeling',
            'processing' => 'Nog niet vastgesteld',
            default => 'Voorkomen: snapshot was niet veilig',
        };
    }
}
