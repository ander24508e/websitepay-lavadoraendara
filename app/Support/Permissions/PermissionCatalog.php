<?php

namespace App\Support\Permissions;

use Illuminate\Support\Collection;

class PermissionCatalog
{
    /**
     * Grupos de permisos tal como estan definidos en config/permissions_catalog.php.
     *
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return config('permissions_catalog.groups', []);
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(static::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    public static function label(string $permission): string
    {
        foreach (static::groups() as $group) {
            if (isset($group['permissions'][$permission])) {
                return $group['permissions'][$permission];
            }
        }

        return $permission;
    }

    /**
     * Grupos limitados a los permisos indicados (los que el usuario puede otorgar).
     *
     * @param  array<int, string>|null  $allowed  null = sin restriccion
     */
    public static function groupsFor(?array $allowed = null): Collection
    {
        return collect(static::groups())
            ->map(function (array $group, string $key) use ($allowed) {
                $permissions = collect($group['permissions'])
                    ->when($allowed !== null, fn (Collection $items) => $items->only($allowed));

                return [
                    'key' => $key,
                    'label' => $group['label'],
                    'permissions' => $permissions->all(),
                ];
            })
            ->filter(fn (array $group) => $group['permissions'] !== [])
            ->values();
    }
}
