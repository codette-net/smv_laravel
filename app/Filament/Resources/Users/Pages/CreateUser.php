<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Users\SyncStaffRoles;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var list<string> */
    protected array $staffRoles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $this->staffRoles = app(SyncStaffRoles::class)->validate($actor, $data['staff_roles'] ?? []);
        unset($data['staff_roles']);
        $data['role'] = 'candidate';

        return $data;
    }

    protected function afterCreate(): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        app(SyncStaffRoles::class)->execute($actor, $this->record, $this->staffRoles);
    }
}
