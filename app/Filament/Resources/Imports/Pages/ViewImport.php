<?php

namespace App\Filament\Resources\Imports\Pages;

use App\Filament\Resources\Imports\ImportResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewImport extends ViewRecord
{
    protected static string $resource = ImportResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
