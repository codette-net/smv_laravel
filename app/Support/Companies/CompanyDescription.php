<?php

namespace App\Support\Companies;

use App\Support\Content\LimitedRichText;

class CompanyDescription
{
    public function __construct(private readonly LimitedRichText $richText) {}

    public function sanitize(?string $description): string
    {
        $html = $this->richText->sanitize($description);

        return $this->richText->plainText($html) === '' ? '' : $html;
    }

    public function plainText(?string $description): string
    {
        return $this->richText->plainText($description);
    }
}
