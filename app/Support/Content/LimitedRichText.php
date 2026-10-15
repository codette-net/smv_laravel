<?php

namespace App\Support\Content;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class LimitedRichText
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->dropElement('script')
            ->dropElement('style')
            ->dropElement('template')
            ->dropElement('iframe')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('svg')
            ->dropElement('math')
            ->withMaxInputLength(200_000);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        if (! preg_match('/<[a-z][^>]*>/i', $content)) {
            $content = $this->plainTextToHtml($content);
        } else {
            $content = $this->normalizeEditorBlocks($content);
        }

        return trim($this->sanitizer->sanitize($content));
    }

    public function plainText(?string $content): string
    {
        $html = $this->sanitize($content);
        $withBoundaries = preg_replace('/<(?:br\s*\/?|\/(?:p|h2|h3|li|ul|ol))>/i', ' ', $html) ?? $html;

        return Str::squish(html_entity_decode(strip_tags($withBoundaries), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function plainTextToHtml(string $content): string
    {
        $normalized = preg_replace("/\r\n?|\n/", "\n", $content) ?? $content;
        $paragraphs = preg_split('/\n{2,}/', $normalized) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', e(trim($paragraph))).'</p>')
            ->implode('');
    }

    private function normalizeEditorBlocks(string $content): string
    {
        return preg_replace(
            ['/<div(?:\s[^>]*)?>/i', '/<\/div\s*>/i'],
            ['<p>', '</p>'],
            $content,
        ) ?? $content;
    }
}
