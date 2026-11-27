<?php

namespace App\Support\Authorization;

use App\Models\User;

final class StaffRoleCatalog
{
    /** @var array<string, string> */
    private const LABELS = [
        'super-admin' => 'Superbeheerder',
        'admin' => 'Beheerder',
        'editor' => 'Redacteur',
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::LABELS);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return self::LABELS;
    }

    /** @return array<string, string> */
    public static function assignableOptions(User $actor): array
    {
        if ($actor->hasRole('super-admin')) {
            return self::LABELS;
        }

        if ($actor->hasRole('admin')) {
            return array_intersect_key(self::LABELS, array_flip(['admin', 'editor']));
        }

        return [];
    }

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }
}
