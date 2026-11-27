<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Support\Authorization\StaffRoleCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SyncStaffRoles
{
    /** @param array<int, mixed> $requestedRoles */
    public function validate(User $actor, array $requestedRoles, ?User $target = null): array
    {
        $roles = collect($requestedRoles)
            ->filter(fn (mixed $role): bool => is_string($role) && $role !== '')
            ->unique()
            ->values()
            ->all();

        if (count($roles) !== count($requestedRoles)
            || array_diff($roles, StaffRoleCatalog::names()) !== []) {
            throw ValidationException::withMessages([
                'staff_roles' => 'Selecteer uitsluitend ondersteunde medewerkersrollen.',
            ]);
        }

        if (array_diff($roles, array_keys(StaffRoleCatalog::assignableOptions($actor))) !== []) {
            throw ValidationException::withMessages([
                'staff_roles' => 'Je mag één of meer van deze medewerkersrollen niet toekennen.',
            ]);
        }

        if ($target !== null && $target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Alleen een superbeheerder mag een superbeheerder wijzigen.');
        }

        return $roles;
    }

    /** @param array<int, mixed> $requestedRoles */
    public function execute(User $actor, User $target, array $requestedRoles): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        $staffRoles = $this->validate($actor, $requestedRoles, $target);
        $preservedRoles = $target->roles
            ->pluck('name')
            ->diff(StaffRoleCatalog::names())
            ->values()
            ->all();

        $target->syncRoles([...$preservedRoles, ...$staffRoles]);
    }
}
