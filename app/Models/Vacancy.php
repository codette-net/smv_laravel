<?php

namespace App\Models;

use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CompensationPeriod;
use App\Enums\SalaryBasis;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Support\Vacancies\VacancyDescription;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory, HasSlug, HasTags, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Vacancy $vacancy): void {
            if ($vacancy->isDirty('description')) {
                $vacancy->description = app(VacancyDescription::class)->sanitize($vacancy->description);
            }

            foreach (['salary_min', 'salary_max', 'rate_min', 'rate_max'] as $field) {
                if ($vacancy->isDirty($field) && ($vacancy->getAttributes()[$field] ?? null) === '') {
                    $vacancy->setAttribute($field, null);
                }
            }

            foreach (['salary_currency', 'rate_currency'] as $field) {
                if (! $vacancy->isDirty($field)) {
                    continue;
                }

                $currency = $vacancy->getAttribute($field);
                $vacancy->setAttribute($field, filled($currency) ? strtoupper(trim((string) $currency)) : null);
            }

            $wasPublished = $vacancy->exists
                && $vacancy->getRawOriginal('status') === VacancyStatus::Active->value;

            if ($vacancy->status === VacancyStatus::Active
                && $vacancy->published_at === null
                && ! $wasPublished) {
                $vacancy->published_at = now();
            }
        });
    }

    protected $fillable = [
        'company_id',
        'import_source_id',
        'title',
        'slug',
        'description',
        'location',
        'application_email',
        'application_url',
        'application_mode',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'salary_basis',
        'rate_min',
        'rate_max',
        'rate_currency',
        'rate_period',
        'reference',
        'source_reference',
        'last_seen_at',
        'last_seen_import_id',
        'missing_since',
        'published_at',
        'deadline_at',
        'expires_at',
        'is_featured',
        'is_filled',
        'status',
        'source',
        'placement_package',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'deadline_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'missing_since' => 'datetime',
            'is_featured' => 'boolean',
            'is_filled' => 'boolean',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'rate_min' => 'integer',
            'rate_max' => 'integer',
            'salary_period' => CompensationPeriod::class,
            'salary_basis' => SalaryBasis::class,
            'rate_period' => CompensationPeriod::class,
            'status' => VacancyStatus::class,
            'source' => VacancySource::class,
            'application_mode' => ApplicationMode::class,
            'placement_package' => AdvertisingPackage::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function lastSeenImport(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'last_seen_import_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }

    public function relatedBlogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_vacancy');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_vacancies')->withTimestamps();
    }

    /** @param Builder<Vacancy> $query */
    public function scopeWithSavedStateFor(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query;
        }

        return $query->withExists([
            'savedByUsers as is_saved' => fn (Builder $query) => $query->whereKey($user->getKey()),
        ]);
    }

    /**
     * Limit vacancies to those that are currently available on public surfaces.
     *
     * Published records created before publication normalization may still have
     * a null publication timestamp and remain immediately public. A deadline is
     * the final application moment; expiry is the listing's active-until moment.
     * Under the current MVP rule either elapsed boundary removes the Vacancy from
     * public surfaces. Null values mean that boundary has not been configured.
     *
     * @param  Builder<Vacancy>  $query
     * @return Builder<Vacancy>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('status', VacancyStatus::Active->value)
            ->where('is_filled', false)
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', $now))
            ->where(fn (Builder $query) => $query
                ->whereNull('deadline_at')
                ->orWhere('deadline_at', '>=', $now))
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', $now));
    }

    /** @param Builder<Vacancy> $query */
    public function scopeWithComparableMonthlySalary(Builder $query): Builder
    {
        return $query
            ->where('salary_currency', 'EUR')
            ->where('salary_period', CompensationPeriod::Month->value)
            ->where('salary_basis', SalaryBasis::GrossFullTimeEquivalent->value)
            ->where(fn (Builder $query) => $query->where('salary_min', '>', 0)->orWhere('salary_max', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('salary_min')->orWhere('salary_min', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('salary_max')->orWhere('salary_max', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('salary_min')->orWhereNull('salary_max')->orWhereColumn('salary_min', '<=', 'salary_max'));
    }

    /** @param Builder<Vacancy> $query */
    public function scopeWithComparableHourlyRate(Builder $query): Builder
    {
        return $query
            ->where('rate_currency', 'EUR')
            ->where('rate_period', CompensationPeriod::Hour->value)
            ->where(fn (Builder $query) => $query->where('rate_min', '>', 0)->orWhere('rate_max', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('rate_min')->orWhere('rate_min', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('rate_max')->orWhere('rate_max', '>', 0))
            ->where(fn (Builder $query) => $query->whereNull('rate_min')->orWhereNull('rate_max')->orWhereColumn('rate_min', '<=', 'rate_max'));
    }

    public function hasComparableMonthlySalary(): bool
    {
        return $this->salary_currency === 'EUR'
            && $this->salary_period === CompensationPeriod::Month
            && $this->salary_basis === SalaryBasis::GrossFullTimeEquivalent
            && $this->validPositiveRange($this->salary_min, $this->salary_max);
    }

    public function hasComparableHourlyRate(): bool
    {
        return $this->rate_currency === 'EUR'
            && $this->rate_period === CompensationPeriod::Hour
            && $this->validPositiveRange($this->rate_min, $this->rate_max);
    }

    public function vacancy_url(): string
    {
        return '/vacatures/'.$this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function compensationLabel(): ?string
    {
        if ($this->salary_min !== null || $this->salary_max !== null) {
            $period = $this->salary_period === CompensationPeriod::Month
                ? match ($this->salary_basis) {
                    SalaryBasis::GrossFullTimeEquivalent => ' bruto per maand (o.b.v. fulltime)',
                    SalaryBasis::GrossOfferedHours => ' bruto per maand (voor aangeboden uren)',
                    default => ' per maand',
                }
            : $this->periodSuffix($this->salary_period);

            return $this->formattedRange('Salaris', $this->salary_min, $this->salary_max, $this->salary_currency).$period;
        }

        if ($this->rate_min !== null || $this->rate_max !== null) {
            return $this->formattedRange('Tarief', $this->rate_min, $this->rate_max, $this->rate_currency).$this->periodSuffix($this->rate_period);
        }

        return null;
    }

    private function formattedRange(string $label, ?int $minimum, ?int $maximum, ?string $currency): string
    {
        $prefix = strtoupper((string) $currency) === 'EUR' ? '€' : (filled($currency) ? strtoupper($currency).' ' : '');

        return match (true) {
            $minimum !== null && $maximum !== null => $label.': '.$prefix.number_format($minimum, 0, ',', '.').' – '.$prefix.number_format($maximum, 0, ',', '.'),
            $minimum !== null => $label.' vanaf '.$prefix.number_format($minimum, 0, ',', '.'),
            default => $label.' tot '.$prefix.number_format((int) $maximum, 0, ',', '.'),
        };
    }

    private function periodSuffix(?CompensationPeriod $period): string
    {
        return $period === null ? '' : ' '.mb_strtolower($period->getLabel());
    }

    private function validPositiveRange(?int $minimum, ?int $maximum): bool
    {
        return ($minimum !== null || $maximum !== null)
            && ($minimum === null || $minimum > 0)
            && ($maximum === null || $maximum > 0)
            && ($minimum === null || $maximum === null || $minimum <= $maximum);
    }
}
