<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
