<?php

namespace App\Filament\Resources\ImportMappings\Pages;

use App\Filament\Resources\ImportMappings\ImportMappingResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateImportMapping extends CreateRecord
{
    protected static string $resource = ImportMappingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?bool $hasUnsavedDataChangesAlert = true;
}
