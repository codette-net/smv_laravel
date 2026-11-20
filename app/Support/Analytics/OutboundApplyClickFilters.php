<?php

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

final readonly class OutboundApplyClickFilters
{
    public function __construct(
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $vacancyId = null,
        public ?int $companyId = null,
        public ?int $importSourceId = null,
    ) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $today = CarbonImmutable::now('UTC');
        $dateFrom = self::date($values['date_from'] ?? null, $today->subDays(29));
        $dateTo = self::date($values['date_to'] ?? null, $today);

        if ($dateTo->lt($dateFrom)) {
            $dateTo = $dateFrom;
        }

        return new self(
            $dateFrom->startOfDay(),
            $dateTo->endOfDay(),
            self::identifier($values['vacancy_id'] ?? null),
            self::identifier($values['company_id'] ?? null),
            self::identifier($values['import_source_id'] ?? null),
        );
    }

    public function fromDate(): string
    {
        return $this->dateFrom->toDateString();
    }

    public function toDate(): string
    {
        return $this->dateTo->toDateString();
    }

    private static function date(mixed $value, CarbonImmutable $default): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone('UTC');
        }

        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}/', $value) !== 1) {
            return $default;
        }

        $dateValue = substr($value, 0, 10);

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateValue, 'UTC');
        } catch (Throwable) {
            return $default;
        }

        return $date !== false && $date->toDateString() === $dateValue ? $date : $default;
    }

    private static function identifier(mixed $value): ?int
    {
        $identifier = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $identifier === false ? null : $identifier;
    }
}
