<?php

namespace App\Models;

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
        'total_rows',
        'imported_rows',
        'updated_rows',
        'failed_rows',
        'mapping',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'updated_rows' => 'integer',
            'failed_rows' => 'integer',
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
}
