<?php

use App\Imports\Data\SourcePayload;
use App\Imports\Exceptions\InvalidSourceException;
use App\Imports\Joobs\JoobsFeedContract;

function joobsFixtureContents(): string
{
    $contents = file_get_contents(base_path('tests/Fixtures/Imports/joobs/joobs-job-feed-v1-example.xml'));

    if ($contents === false) {
        throw new RuntimeException('The Joobs contract fixture could not be read.');
    }

    return $contents;
}

function inspectJoobs(string $xml): array
{
    return app(JoobsFeedContract::class)->inspect(SourcePayload::fromContents($xml, 'joobs-contract-test'));
}

function replaceFirst(string $pattern, string $replacement, string $subject): string
{
    $result = preg_replace($pattern, $replacement, $subject, 1);

    if (! is_string($result)) {
        throw new RuntimeException('The Joobs test fixture mutation failed.');
    }

    return $result;
}

test('the supplied Joobs example satisfies the parser-level feed contract', function () {
    $result = inspectJoobs(joobsFixtureContents());
    $records = $result['records'];

    expect($result['root'])->toBe('source')
        ->and($result['feed_version'])->toBe('joobs-1.0')
        ->and($records)->toHaveCount(2)
        ->and(collect($records)->map->get('referencenumber')->unique()->count())->toBe(2)
        ->and(collect($records)->map->get('requisitionid')->unique()->all())->toBe(['k7Qd2XvP'])
        ->and(collect($records)->map->get('city')->all())->toBe(['Antwerpen', 'Breda'])
        ->and($records[1]->get('city'))->not->toBe($records[1]->get('joobs_workplacecity'))
        ->and($records[1]->get('joobs_workplacecity'))->toBe('Antwerpen')
        ->and($records[1]->get('joobs_workplacecountry'))->toBe('BE')
        ->and($records[0]->get('url'))->toBe('https://joobs.be/nl/jobs/k7Qd2XvP?utm_source=salesenmarketingvacatures&utm_medium=jobboard&utm_campaign=SM260142&utm_content=antwerpen')
        ->and($records[1]->get('url'))->toEndWith('utm_content=breda')
        ->and($records[0]->get('description'))->toContain('<p>')
        ->and($records[0]->get('description'))->toContain('<strong>Account Manager B2B</strong>')
        ->and($records[0]->get('salary'))->toBe('€3800 - €4600 per month')
        ->and($records[0]->get('expirationdate'))->toBe('2026-12-07')
        ->and($records[0]->get('joobs_language'))->toBe('nl')
        ->and($records[0]->get('joobs_product'))->toBe('standard')
        ->and($records[0]->get('joobs_contracttype'))->toBe('permanent')
        ->and($records[0]->get('joobs_logo'))->toBe('https://joobs.be/images/example/logo-voorbeeldbedrijf.png');
});

test('optional Joobs fields may be absent without changing required parser output', function () {
    $xml = preg_replace(
        '/\s*<(salary|education|jobtype|experience|remotetype|joobs_contracttype|joobs_logo)>.*?<\/\1>/s',
        '',
        joobsFixtureContents(),
    );

    expect($xml)->toBeString();

    $records = inspectJoobs($xml)['records'];

    expect($records)->toHaveCount(2)
        ->and($records[0]->get('salary'))->toBeNull()
        ->and($records[0]->get('education'))->toBeNull()
        ->and($records[0]->get('remotetype'))->toBeNull()
        ->and($records[0]->get('joobs_contracttype'))->toBeNull()
        ->and($records[0]->get('joobs_logo'))->toBeNull()
        ->and($records[0]->get('referencenumber'))->toBe('SM260142-k7Qd2XvP-antwerpen');
});

test('the Joobs contract rejects malformed XML', function () {
    expect(fn () => inspectJoobs('<source feedversion="joobs-1.0"><job>'))
        ->toThrow(InvalidSourceException::class, 'malformed XML');
});

test('the Joobs contract rejects missing required identifiers', function (string $field) {
    $xml = replaceFirst("/\\s*<{$field}>.*?<\\/{$field}>/s", '', joobsFixtureContents());

    expect(fn () => inspectJoobs($xml))
        ->toThrow(InvalidSourceException::class, "missing required field [{$field}]");
})->with(['referencenumber', 'requisitionid']);

test('the Joobs contract rejects missing required business fields', function (string $field) {
    $xml = replaceFirst("/\\s*<{$field}>.*?<\\/{$field}>/s", '', joobsFixtureContents());

    expect(fn () => inspectJoobs($xml))
        ->toThrow(InvalidSourceException::class, "missing required field [{$field}]");
})->with(['title', 'date', 'company', 'city', 'country', 'description', 'category', 'expirationdate', 'joobs_language', 'joobs_product']);

test('the Joobs contract rejects unsupported language codes', function () {
    $xml = replaceFirst('/<joobs_language>.*?<\/joobs_language>/s', '<joobs_language>xx</joobs_language>', joobsFixtureContents());

    expect(fn () => inspectJoobs($xml))
        ->toThrow(InvalidSourceException::class, 'unsupported language');
});

test('the Joobs contract rejects duplicate advertisement references', function () {
    $xml = str_replace(
        'SM260142-k7Qd2XvP-breda',
        'SM260142-k7Qd2XvP-antwerpen',
        joobsFixtureContents(),
    );

    expect(fn () => inspectJoobs($xml))
        ->toThrow(InvalidSourceException::class, 'duplicate referencenumber');
});

test('the Joobs contract rejects an unsupported feed version', function () {
    $xml = str_replace('feedversion="joobs-1.0"', 'feedversion="joobs-2.0"', joobsFixtureContents());

    expect(fn () => inspectJoobs($xml))
        ->toThrow(InvalidSourceException::class, 'version [joobs-2.0] is not supported');
});

test('the Joobs contract rejects missing or invalid application URLs', function (string $replacement) {
    $xml = replaceFirst('/\s*<url>.*?<\/url>/s', $replacement, joobsFixtureContents());

    expect(fn () => inspectJoobs($xml))->toThrow(InvalidSourceException::class);
})->with([
    'missing URL' => '',
    'invalid URL' => '<url><![CDATA[not-a-url]]></url>',
]);
