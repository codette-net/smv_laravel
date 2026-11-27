<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Users\SyncStaffRoles;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Authorization\StaffRoleCatalog;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var list<string> */
    protected array $staffRoles = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['staff_roles'] = $this->record->roles
            ->pluck('name')
            ->intersect(StaffRoleCatalog::names())
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $this->staffRoles = app(SyncStaffRoles::class)->validate(
            $actor,
            $data['staff_roles'] ?? [],
            $this->record,
        );
        unset($data['staff_roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        app(SyncStaffRoles::class)->execute($actor, $this->record, $this->staffRoles);
    }
}
