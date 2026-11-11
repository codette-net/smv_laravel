<?php

namespace App\Models;

use App\Enums\ImportEmployerIdentityType;
use Database\Factories\ImportSourceEmployerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ImportSourceEmployer extends Model
{
    /** @use HasFactory<ImportSourceEmployerFactory> */
    use HasFactory;

    protected $fillable = [
        'import_source_id',
        'company_id',
        'identity_type',
        'external_employer_id',
        'source_name',
        'source_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'identity_type' => ImportEmployerIdentityType::class,
            'is_active' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            $mapping->external_employer_id = self::exact($mapping->external_employer_id);
            $mapping->source_name = self::exact($mapping->source_name);
            $mapping->source_url = self::exact($mapping->source_url);
            $mapping->identity_key = match ($mapping->identity_type) {
                ImportEmployerIdentityType::ProviderId => self::providerKey($mapping->external_employer_id),
                ImportEmployerIdentityType::ReviewedComposite => self::compositeKey($mapping->source_name, $mapping->source_url),
            };
            $mapping->reviewed_at ??= now();
            $mapping->reviewed_by ??= auth()->id();
        });
    }

    public static function providerKey(?string $externalId): string
    {
        $externalId = self::exact($externalId);
        if ($externalId === null) {
            throw new InvalidArgumentException('Een provider-ID is verplicht voor dit type werkgeverkoppeling.');
        }

        return hash('sha256', "provider_id\0{$externalId}");
    }

    public static function compositeKey(?string $name, ?string $url): string
    {
        $name = self::exact($name);
        $url = self::exact($url);
        if ($name === null || $url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Een exacte werkgeversnaam en geldige bron-URL zijn verplicht voor een samengestelde werkgeverkoppeling.');
        }

        return hash('sha256', "reviewed_composite\0{$name}\0{$url}");
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    private static function exact(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
