<?php

declare(strict_types=1);

use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use App\Models\VacancyDetailView;
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
    fwrite(STDERR, "Refusing to inspect analytics outside the isolated browser database.\n");
    exit(1);
}

$vacancy = Vacancy::query()
    ->where('slug', 'acceptance-vacancy-manual-external')
    ->firstOrFail();

echo json_encode([
    'views' => VacancyDetailView::query()->whereBelongsTo($vacancy)->count(),
    'clicks' => OutboundApplyClick::query()->whereBelongsTo($vacancy)->count(),
], JSON_THROW_ON_ERROR);
