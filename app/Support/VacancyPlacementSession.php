<?php

namespace App\Support;

use App\Enums\AdvertisingPackage;
use Illuminate\Session\Store;

class VacancyPlacementSession
{
    private const PACKAGE_KEY = 'vacancy_placement.package';

    private const COMPANY_KEY = 'vacancy_placement.company_id';

    public function __construct(private readonly Store $session) {}

    public function package(): ?AdvertisingPackage
    {
        $value = $this->session->get(self::PACKAGE_KEY);

        return is_string($value) ? AdvertisingPackage::tryFrom($value) : null;
    }

    public function rememberPackage(AdvertisingPackage $package): void
    {
        $this->session->put(self::PACKAGE_KEY, $package->value);
    }

    public function rememberIntendedDestination(string $url): void
    {
        $this->session->put('url.intended', $url);
    }

    public function rememberCompany(int $companyId): void
    {
        $this->session->put(self::COMPANY_KEY, $companyId);
    }

    public function clear(): void
    {
        $this->session->forget([self::PACKAGE_KEY, self::COMPANY_KEY]);
    }
}
