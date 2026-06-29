<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
