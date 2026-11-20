<?php

namespace App\Models;

use App\Enums\ImportFormat;
use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use App\Enums\ImportScheduleFrequency;
use App\Enums\ImportTransport;
use App\Enums\ImportType;
use Database\Factories\ImportSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ImportSource extends Model
{
    /** @use HasFactory<ImportSourceFactory> */
    use HasFactory, HasSlug, SoftDeletes;

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
        'publication_policy',
        'missing_vacancy_policy',
        'automatic_import_enabled',
        'schedule_frequency',
        'approved_at',
        'approved_by',
        'last_imported_at',
        'last_scheduled_at',
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
            'publication_policy' => ImportPublicationPolicy::class,
            'missing_vacancy_policy' => ImportMissingVacancyPolicy::class,
            'automatic_import_enabled' => 'boolean',
            'schedule_frequency' => ImportScheduleFrequency::class,
            'approved_at' => 'datetime',
            'last_imported_at' => 'datetime',
            'last_scheduled_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
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

    public function latestImport(): HasOne
    {
        return $this->hasOne(Import::class)->latestOfMany();
    }

    public function recentImports(): HasMany
    {
        return $this->hasMany(Import::class)->latest('id')->limit(10);
    }

    public function safeEndpointLabel(): ?string
    {
        if (! is_string($this->endpoint_url) || $this->endpoint_url === '') {
            return null;
        }

        $parts = parse_url($this->endpoint_url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '');
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ImportMapping::class);
    }

    public function taxonomyMappings(): HasMany
    {
        return $this->hasMany(ImportTaxonomyMapping::class);
    }

    public function employerMappings(): HasMany
    {
        return $this->hasMany(ImportSourceEmployer::class);
    }

    public function usesSourceScopedEmployers(): bool
    {
        return data_get($this->configuration, 'employer_resolution') === 'source_scoped';
    }

    public function usesVerifiedSnapshotReconciliation(): bool
    {
        return data_get($this->configuration, 'snapshot_reconciliation.enabled') === true;
    }

    public function allowsEmptyVerifiedSnapshot(): bool
    {
        return $this->usesVerifiedSnapshotReconciliation()
            && data_get($this->configuration, 'snapshot_reconciliation.allow_empty') === true;
    }

    public function marksSnapshotsAsPartial(): bool
    {
        return data_get($this->configuration, 'snapshot_reconciliation.partial') === true;
    }

    public function publishesNewVacanciesAutomatically(): bool
    {
        return $this->publication_policy === ImportPublicationPolicy::AutoPublish;
    }

    public function takesMissingVacanciesOffline(): bool
    {
        return $this->missing_vacancy_policy === ImportMissingVacancyPolicy::AutoOffline;
    }

    public function scopeApprovedForAutomaticRun($query)
    {
        return $query->where('is_active', true)->whereNotNull('approved_at')->whereNotNull('approved_by');
    }

    public function scopeEligibleForAutomaticExecution($query)
    {
        return $query
            ->approvedForAutomaticRun()
            ->where('automatic_import_enabled', true)
            ->whereIn('transport', [ImportTransport::Http->value, ImportTransport::Api->value]);
    }

    public function isApprovedForAutomaticRun(): bool
    {
        return $this->is_active && $this->approved_at !== null && $this->approved_by !== null;
    }

    public function isEligibleForAutomaticExecution(): bool
    {
        return $this->isApprovedForAutomaticRun()
            && $this->automatic_import_enabled
            && in_array($this->transport, [ImportTransport::Http, ImportTransport::Api], true);
    }

    public function isAutomaticImportDue(?Carbon $now = null): bool
    {
        if (! $this->isEligibleForAutomaticExecution() || ! $this->schedule_frequency instanceof ImportScheduleFrequency) {
            return false;
        }

        return $this->schedule_frequency->isDue($this->last_scheduled_at, $now ?? now());
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
