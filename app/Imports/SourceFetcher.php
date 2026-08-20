<?php

namespace App\Imports;

use App\Imports\Data\SourcePayload;
use App\Imports\Exceptions\InvalidSourceException;
use App\Imports\Exceptions\UnsafeRemoteSourceException;
use App\Models\ImportSource;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class SourceFetcher
{
    private const MAX_REDIRECTS = 3;

    private const MAX_RESPONSE_BYTES = 10_485_760;

    public function __construct(private readonly HttpFactory $http) {}

    public function fetch(ImportSource $source): SourcePayload
    {
        $url = (string) $source->endpoint_url;

        if ($source->isApprovedForAutomaticRun() && parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new UnsafeRemoteSourceException('Approved import sources must use HTTPS.');
        }

        for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; $redirect++) {
            $this->assertSafeUrl($url);
            $response = $this->http->timeout(15)->connectTimeout(5)->withoutRedirecting()->accept('*/*')->get($url);

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (! is_string($location) || $location === '') {
                    throw new InvalidSourceException('The remote import source returned an invalid redirect.');
                }

                $url = $this->resolveRedirect($url, $location);

                continue;
            }

            if (! $response->successful()) {
                throw new InvalidSourceException('The remote import source could not be retrieved.');
            }

            return SourcePayload::fromContents($this->responseContents($response), 'remote import source');
        }

        throw new UnsafeRemoteSourceException('The remote import source exceeded the redirect limit.');
    }

    public function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeRemoteSourceException('The remote import source URL is unsafe.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        if (in_array(mb_strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
            throw new UnsafeRemoteSourceException('The remote import source URL resolves to a local address.');
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $addresses = array_filter(array_map(fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null, $records));

        if ($addresses === []) {
            throw new UnsafeRemoteSourceException('The remote import source host could not be resolved safely.');
        }

        foreach ($addresses as $address) {
            $this->assertPublicIp($address);
        }
    }

    private function assertPublicIp(string $address): void
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new UnsafeRemoteSourceException('The remote import source resolves to a private or reserved address.');
        }
    }

    private function responseContents(Response $response): string
    {
        $contentLength = $response->header('Content-Length');

        if (is_numeric($contentLength) && (int) $contentLength > self::MAX_RESPONSE_BYTES) {
            throw new InvalidSourceException('The remote import source exceeds the maximum allowed response size.');
        }

        $contents = $response->body();

        if (strlen($contents) > self::MAX_RESPONSE_BYTES) {
            throw new InvalidSourceException('The remote import source exceeds the maximum allowed response size.');
        }

        return $contents;
    }

    private function resolveRedirect(string $url, string $location): string
    {
        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $parts = parse_url($url);
        $path = Str::startsWith($location, '/') ? $location : rtrim(dirname((string) ($parts['path'] ?? '/')), '/').'/'.$location;

        return "{$parts['scheme']}://{$parts['host']}{$path}";
    }
}
