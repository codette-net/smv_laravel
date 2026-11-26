<?php

namespace App\Models;

use App\Enums\PlacementCreditEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementCreditEvent extends Model
{
    protected $fillable = [
        'placement_credit_id',
        'vacancy_id',
        'actor_user_id',
        'event_type',
        'deduplication_key',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => PlacementCreditEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function placementCredit(): BelongsTo
    {
        return $this->belongsTo(PlacementCredit::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
