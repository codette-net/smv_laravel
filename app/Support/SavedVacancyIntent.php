<?php

namespace App\Support;

use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Session\Store;

class SavedVacancyIntent
{
    private const VACANCY_KEY = 'saved_vacancy.vacancy_id';

    public function __construct(private readonly Store $session) {}

    public function remember(Vacancy $vacancy): void
    {
        $this->session->put(self::VACANCY_KEY, $vacancy->getKey());
        $this->session->put('url.intended', route('vacancies.show', $vacancy));
    }

    public function pending(): bool
    {
        return is_int($this->session->get(self::VACANCY_KEY));
    }

    public function complete(User $user): ?bool
    {
        $vacancyId = $this->session->pull(self::VACANCY_KEY);

        if (! is_int($vacancyId)) {
            return null;
        }

        $vacancy = Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn ($query) => $query->publiclyVisible())
            ->find($vacancyId);

        if ($vacancy === null) {
            return false;
        }

        $user->savedVacancies()->syncWithoutDetaching([$vacancy->getKey()]);

        return true;
    }
}
