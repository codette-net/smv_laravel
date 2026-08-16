<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    /** @use HasFactory<\Database\Factories\ImportLogFactory> */
    use HasFactory;

    protected $fillable = [
        'import_id',
        'level',
        'message',
        'context'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
