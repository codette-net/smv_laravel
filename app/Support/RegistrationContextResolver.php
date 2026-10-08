<?php

namespace App\Support;

use App\Enums\RegistrationContext;

class RegistrationContextResolver
{
    public function __construct(
        private readonly SavedVacancyIntent $savedVacancyIntent,
        private readonly SavedCompanyIntent $savedCompanyIntent,
        private readonly VacancyPlacementSession $vacancyPlacement,
    ) {}

    public function pending(): ?RegistrationContext
    {
        if ($this->savedVacancyIntent->pending() || $this->savedCompanyIntent->pending()) {
            return RegistrationContext::JobSeeker;
        }

        if ($this->vacancyPlacement->package() !== null) {
            return RegistrationContext::Employer;
        }

        return null;
    }

    public function registrationRoute(): string
    {
        return match ($this->pending()) {
            RegistrationContext::JobSeeker => route('register.job-seeker'),
            RegistrationContext::Employer => route('register.employer'),
            null => route('register'),
        };
    }
}
