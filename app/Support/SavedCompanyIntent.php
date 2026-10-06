<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Session\Store;

class SavedCompanyIntent
{
    private const COMPANY_KEY = 'saved_company.company_id';

    public function __construct(private readonly Store $session) {}

    public function remember(Company $company): void
    {
        $this->session->put(self::COMPANY_KEY, $company->getKey());
        $this->session->put('url.intended', route('bedrijven.show', $company));
    }

    public function complete(User $user): ?bool
    {
        $companyId = $this->session->pull(self::COMPANY_KEY);

        if (! is_int($companyId)) {
            return null;
        }

        $company = Company::query()->publiclyVisible()->find($companyId);

        if ($company === null) {
            return false;
        }

        $user->savedCompanies()->syncWithoutDetaching([$company->getKey()]);

        return true;
    }
}
