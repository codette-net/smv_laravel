<?php

namespace App\Support\Commerce;

use App\Enums\CompanyStatus;
use App\Enums\PlacementCreditStatus;
use App\Enums\VacancyStatus;
use App\Models\PlacementCredit;

final class PlacementCreditPresentation
{
    public static function explanation(PlacementCredit $credit): ?string
    {
        $status = $credit->status();

        if ($status === PlacementCreditStatus::RequiresReview) {
            return 'De betaling is terugbetaald. SMV beoordeelt handmatig wat er met de gekoppelde plaatsing gebeurt; publicatie wijzigt niet automatisch.';
        }

        if ($status === PlacementCreditStatus::Reversed) {
            return 'Dit ongebruikte plaatsingsrecht is na de terugbetaling ingetrokken.';
        }

        if ($status === PlacementCreditStatus::Expired) {
            return 'De betaalde plaatsingsduur is verstreken.';
        }

        if ($status === PlacementCreditStatus::Scheduled) {
            return 'De publicatie staat gepland voor '.$credit->activated_at->translatedFormat('j F Y H:i').'; dan start de betaalde plaatsingsduur.';
        }

        if ($status !== PlacementCreditStatus::Allocated || $credit->vacancy === null) {
            return null;
        }

        if ($credit->company->status !== CompanyStatus::Active) {
            return $credit->company->status === CompanyStatus::Pending
                ? 'Het bedrijf wacht op goedkeuring. De plaatsingsduur start pas nadat bedrijf en vacature publiek zijn.'
                : 'Het bedrijf is nog niet openbaar. De plaatsingsduur start pas nadat bedrijf en vacature publiek zijn.';
        }

        if ($credit->vacancy->status === VacancyStatus::Pending) {
            return 'De vacature wacht op goedkeuring. De plaatsingsduur start pas bij publieke plaatsing.';
        }

        if ($credit->vacancy->status === VacancyStatus::Draft) {
            return 'De vacature is nog een concept en moet eerst worden ingediend en goedgekeurd.';
        }

        if ($credit->vacancy->published_at?->isFuture()) {
            return 'De publicatie staat gepland voor '.$credit->vacancy->published_at->translatedFormat('j F Y H:i').'.';
        }

        return 'De plaatsing wacht tot bedrijf en vacature aan alle publicatievoorwaarden voldoen.';
    }
}
