<?php

namespace App\Filament\Resources\ImportSourceEmployers\Pages;

use App\Filament\Resources\ImportSourceEmployers\ImportSourceEmployerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportSourceEmployers extends ListRecords
{
    protected static string $resource = ImportSourceEmployerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
