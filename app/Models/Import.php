<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'source',
        'type',
        'filename',
        'status',
        'total_rows',
        'imported_rows',
        'updated_rows',
        'failed_rows',
        'mapping',
        'started_at',
        'finished_at'
    ];

    protected $casts = [
        'mapping' => 'array'
    ];

    public function import_source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function import_logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

}
