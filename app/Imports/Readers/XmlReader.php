<?php

namespace App\Imports\Readers;

use App\Imports\Contracts\ImportReader;
use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSourceException;
use App\Imports\Exceptions\RecordPathNotFoundException;
use App\Models\ImportSource;
use DOMDocument;
use DOMElement;
use XMLReader as NativeXmlReader;

class XmlReader implements ImportReader
{
    public function records(ImportSource $source, SourcePayload $payload): iterable
    {
        $segments = array_values(array_filter(explode('.', (string) ($source->record_path ?: 'job'))));
        $recordName = end($segments);

        if ($recordName === false || $recordName === '*') {
            throw new InvalidSourceException('An XML import source requires a concrete final record path segment.');
        }

        $reader = new NativeXmlReader;

        if (! $reader->open($payload->path(), null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new InvalidSourceException('The XML import source could not be opened.');
        }

        $reader->setParserProperty(NativeXmlReader::LOADDTD, false);
        $reader->setParserProperty(NativeXmlReader::SUBST_ENTITIES, false);

        $position = 0;
        $matched = false;
        $path = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== NativeXmlReader::ELEMENT) {
                    continue;
                }

                $path[$reader->depth] = $reader->localName;
                $path = array_slice($path, 0, $reader->depth + 1);

                if ($reader->localName !== $recordName || ! $this->matchesRecordPath($path, $segments)) {
                    continue;
                }

                $matched = true;
                $xml = $reader->readOuterXML();
                $record = $this->elementToArray($xml);

                yield new SourceRecord(++$position, $record, $source->record_path ?: $recordName);
            }
        } finally {
            $reader->close();
        }

        if (! $matched) {
            throw new RecordPathNotFoundException("The configured XML record path [{$source->record_path}] did not produce any records.");
        }
    }

    /** @return array<string, mixed> */
    private function elementToArray(string $xml): array
    {
        $document = new DOMDocument;

        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING) || ! $document->documentElement instanceof DOMElement) {
            throw new InvalidSourceException('The XML import source contains an invalid record.');
        }

        return $this->childrenToArray($document->documentElement);
    }

    /** @return array<string, mixed> */
    private function childrenToArray(DOMElement $element): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        $numberedNames = array_map(function (DOMElement $child): ?string {
            return preg_match('/^(.+)_\d+$/', $child->localName, $matches) === 1 ? $matches[1] : null;
        }, $children);

        if ($children !== [] && ! in_array(null, $numberedNames, true) && count(array_unique($numberedNames)) === 1) {
            return array_map(fn (DOMElement $child): mixed => $this->nodeValue($child), $children);
        }

        $result = [];

        foreach ($children as $child) {
            $key = preg_replace('/_\d+$/', '', $child->localName) ?: $child->localName;
            $value = $this->nodeValue($child);

            if (! array_key_exists($key, $result)) {
                $result[$key] = $value;
            } elseif (is_array($result[$key]) && array_is_list($result[$key])) {
                $result[$key][] = $value;
            } else {
                $result[$key] = [$result[$key], $value];
            }
        }

        return $result;
    }

    private function nodeValue(DOMElement $element): mixed
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return $this->childrenToArray($element);
            }
        }

        return trim($element->textContent);
    }

    /** @param list<string> $actual */
    /** @param list<string> $configured */
    private function matchesRecordPath(array $actual, array $configured): bool
    {
        if (count($configured) > count($actual)) {
            return false;
        }

        $actual = array_slice($actual, -count($configured));

        foreach ($configured as $index => $segment) {
            if ($segment !== '*' && $segment !== $actual[$index]) {
                return false;
            }
        }

        return true;
    }
}
