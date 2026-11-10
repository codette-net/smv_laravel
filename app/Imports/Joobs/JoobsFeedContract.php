<?php

namespace App\Imports\Joobs;

use App\Enums\ImportFormat;
use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSourceException;
use App\Imports\Readers\XmlReader;
use App\Models\ImportSource;
use DOMDocument;
use DOMElement;

final class JoobsFeedContract
{
    public const FEED_VERSION = 'joobs-1.0';

    public function __construct(private readonly XmlReader $reader) {}

    /**
     * @return array{root: string, feed_version: string, records: list<SourceRecord>}
     */
    public function inspect(SourcePayload $payload): array
    {
        $document = new DOMDocument;
        $loaded = $document->loadXML(
            $payload->contents(),
            LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        if (! $loaded || ! $document->documentElement instanceof DOMElement) {
            throw new InvalidSourceException('The Joobs feed contains malformed XML.');
        }

        $root = $document->documentElement;

        if ($root->localName !== 'source') {
            throw new InvalidSourceException('The Joobs feed root element must be [source].');
        }

        $feedVersion = trim($root->getAttribute('feedversion'));

        if ($feedVersion !== self::FEED_VERSION) {
            throw new InvalidSourceException("The Joobs feed version [{$feedVersion}] is not supported.");
        }

        $source = new ImportSource;
        $source->forceFill([
            'format' => ImportFormat::Xml,
            'record_path' => 'source.job',
        ]);

        /** @var list<SourceRecord> $records */
        $records = iterator_to_array($this->reader->records($source, $payload), false);
        $references = [];

        foreach ($records as $record) {
            $reference = $this->requiredString($record, 'referencenumber');
            $this->requiredString($record, 'requisitionid');
            $applicationUrl = $this->requiredString($record, 'url');

            if (isset($references[$reference])) {
                throw new InvalidSourceException("The Joobs feed contains duplicate referencenumber [{$reference}].");
            }

            if (! $this->isValidApplicationUrl($applicationUrl)) {
                throw new InvalidSourceException("Joobs advertisement [{$reference}] has an invalid application URL.");
            }

            $references[$reference] = true;
        }

        return [
            'root' => $root->localName,
            'feed_version' => $feedVersion,
            'records' => $records,
        ];
    }

    private function requiredString(SourceRecord $record, string $field): string
    {
        $value = $record->get($field);

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidSourceException("Joobs advertisement at position [{$record->position}] is missing required field [{$field}].");
        }

        return trim($value);
    }

    private function isValidApplicationUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array($scheme, ['http', 'https'], true)
            && parse_url($url, PHP_URL_USER) === null
            && parse_url($url, PHP_URL_HOST) !== null;
    }
}
