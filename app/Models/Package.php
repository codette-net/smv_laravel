<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'vacancy_duration_days',
        'includes_featured',
        'includes_social_campaign',
        'includes_newsletter',
        'is_active',
        'sort_order'
    ];

    public function order_items():hasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
