<?php

namespace App\Imports\Mapping;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class VacancyDescriptionSanitizer
{
    public function sanitize(string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'mailto', 'tel'])
            ->withMaxInputLength(200_000);

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
