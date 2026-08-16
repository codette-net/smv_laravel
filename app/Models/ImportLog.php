<?php

namespace App\Models;

use App\Enums\ImportLogLevel;
use Database\Factories\ImportLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    /** @use HasFactory<ImportLogFactory> */
    use HasFactory;

    protected $fillable = [
        'import_id',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'level' => ImportLogLevel::class,
            'context' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
