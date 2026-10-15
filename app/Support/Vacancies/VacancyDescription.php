<?php

namespace App\Support\Vacancies;

use App\Support\Content\LimitedRichText;

class VacancyDescription
{
    public function __construct(private readonly LimitedRichText $richText) {}

    public function sanitize(?string $description): string
    {
        return $this->richText->sanitize($description);
    }

    public function plainText(?string $description): string
    {
        return $this->richText->plainText($description);
    }
}
