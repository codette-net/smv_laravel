<?php

declare(strict_types=1);

use App\Models\Vacancy;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = (string) config('database.connections.sqlite.database');
$testingDirectory = realpath(dirname(__DIR__, 3).'/storage/framework/testing');
$databaseDirectory = realpath(dirname($database));

if (app()->environment() !== 'testing'
    || $testingDirectory === false
    || $databaseDirectory !== $testingDirectory
    || pathinfo($database, PATHINFO_EXTENSION) !== 'sqlite') {
    fwrite(STDERR, "Refusing to prepare browser fixtures outside the isolated testing database.\n");
    exit(1);
}

$source = Vacancy::query()
    ->where('slug', 'acceptance-vacancy-manual-internal')
    ->firstOrFail();

foreach (range(1, 8) as $number) {
    $key = "browser-smoke-pagination-fixture-{$number}";
    $fixture = Vacancy::query()->firstOrNew(['slug' => $key]);
    $fixture->forceFill($source->replicate()->getAttributes());
    $fixture->slug = $key;
    $fixture->reference = $key;
    $fixture->title = "Browser pagination fixture {$number}";
    $fixture->save();
    $fixture->categories()->sync($source->categories()->pluck('categories.id'));
}
