<?php

namespace App\Filament\Resources\ImportSourceEmployers\Pages;

use App\Filament\Resources\ImportSourceEmployers\ImportSourceEmployerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImportSourceEmployer extends EditRecord
{
    protected static string $resource = ImportSourceEmployerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
