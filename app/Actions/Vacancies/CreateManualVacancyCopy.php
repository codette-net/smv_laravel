<?php

namespace App\Actions\Vacancies;

use App\Enums\ApplicationMode;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateManualVacancyCopy
{
    /** @var list<string> */
    private const EDITORIAL_FIELDS = [
        'company_id',
        'title',
        'description',
        'location',
        'advertised_state',
        'advertised_country',
        'advertised_postal_code',
        'workplace_city',
        'workplace_country',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'salary_basis',
        'rate_min',
        'rate_max',
        'rate_currency',
        'rate_period',
    ];

    public function handle(Vacancy $source): Vacancy
    {
        if ($source->import_source_id === null) {
            throw new LogicException('Only imported vacancies can be copied to a manual vacancy.');
        }

        return DB::transaction(function () use ($source): Vacancy {
            $source->loadMissing(['categories', 'tags']);

            $copy = new Vacancy;
            $copy->fill($source->only(self::EDITORIAL_FIELDS));
            $copy->fill([
                ...$this->applicationDestination($source),
                'import_source_id' => null,
                'reference' => null,
                'source_reference' => null,
                'source_requisition_id' => null,
                'source_salary_text' => null,
                'source_language' => null,
                'source_product_code' => null,
                'last_seen_at' => null,
                'last_seen_import_id' => null,
                'missing_since' => null,
                'source_offlined_at' => null,
                'published_at' => null,
                'deadline_at' => null,
                'expires_at' => null,
                'is_featured' => false,
                'commercial_priority_adjustment' => 0,
                'is_filled' => false,
                'status' => VacancyStatus::Draft,
                'source' => VacancySource::Manual,
                'placement_package' => null,
            ]);
            $copy->save();

            $copy->categories()->sync($source->categories->modelKeys());
            $copy->syncTags($source->tags);

            return $copy->refresh();
        });
    }

    /** @return array{application_mode: ApplicationMode, application_url: ?string, application_email: ?string} */
    private function applicationDestination(Vacancy $source): array
    {
        if ($source->application_mode === ApplicationMode::External
            && $this->isSafeExternalUrl($source->application_url)) {
            return [
                'application_mode' => ApplicationMode::External,
                'application_url' => $source->application_url,
                'application_email' => null,
            ];
        }

        if ($source->application_mode === ApplicationMode::Email
            && filter_var($source->application_email, FILTER_VALIDATE_EMAIL) !== false) {
            return [
                'application_mode' => ApplicationMode::Email,
                'application_url' => null,
                'application_email' => $source->application_email,
            ];
        }

        return [
            'application_mode' => ApplicationMode::Internal,
            'application_url' => null,
            'application_email' => null,
        ];
    }

    private function isSafeExternalUrl(?string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url((string) $url);

        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && filled($parts['host'] ?? null)
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
