<?php

namespace App\Models;

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Enums\ImportType;
use Database\Factories\ImportSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class ImportSource extends Model
{
    /** @use HasFactory<ImportSourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'type',
        'transport',
        'format',
        'endpoint_url',
        'auth_type',
        'credentials',
        'configuration',
        'record_path',
        'selection_rules',
        'default_mapping',
        'is_active',
        'approved_at',
        'approved_by',
        'last_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'transport' => ImportTransport::class,
            'format' => ImportFormat::class,
            'credentials' => 'encrypted:array',
            'configuration' => 'array',
            'selection_rules' => 'array',
            'default_mapping' => 'array',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'last_imported_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public function scopeApprovedForAutomaticRun($query)
    {
        return $query->where('is_active', true)->whereNotNull('approved_at')->whereNotNull('approved_by');
    }

    public function isApprovedForAutomaticRun(): bool
    {
        return $this->is_active && $this->approved_at !== null && $this->approved_by !== null;
    }

    public function approve(User $user): void
    {
        $this->forceFill(['approved_at' => now(), 'approved_by' => $user->getKey()])->save();
    }

    public function revokeApproval(): void
    {
        $this->forceFill(['approved_at' => null, 'approved_by' => null])->save();
    }

    protected static function booted(): void
    {
        static::saving(function (self $source): void {
            if (! in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true)) {
                return;
            }

            $scheme = parse_url((string) $source->endpoint_url, PHP_URL_SCHEME);
            $username = parse_url((string) $source->endpoint_url, PHP_URL_USER);

            if (! filter_var($source->endpoint_url, FILTER_VALIDATE_URL) || $username !== null || ! in_array($scheme, ['http', 'https'], true)) {
                throw new InvalidArgumentException('Een HTTP- of API-importbron vereist een geldige HTTP(S)-URL.');
            }

            if ($source->approved_at !== null && $scheme !== 'https') {
                throw new InvalidArgumentException('Een goedgekeurde importbron vereist een HTTPS-URL.');
            }
        });
    }
}
