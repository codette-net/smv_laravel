<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyDetailView extends Model
{
    public const CREATED_AT = 'viewed_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class)->withTrashed();
    }
}
