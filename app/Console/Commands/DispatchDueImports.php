<?php

namespace App\Console\Commands;

use App\Imports\Scheduling\DueImportDispatcher;
use Illuminate\Console\Command;

class DispatchDueImports extends Command
{
    protected $signature = 'imports:dispatch-due';

    protected $description = 'Dispatch due approved import sources to the bounded import queue';

    public function handle(DueImportDispatcher $dispatcher): int
    {
        $count = $dispatcher->dispatch();
        $this->info("{$count} importbron(nen) ingepland.");

        return self::SUCCESS;
    }
}
