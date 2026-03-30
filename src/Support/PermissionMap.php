<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Exposes a narrow read model for persisted permission-name lookups.
 */
final class PermissionMap
{
    /**
     * @return list<string>
     */
    public function all(): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->permissionModel()::query()
            ->where('guard_name', $this->guardName())
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public function forRole(string $role): array
    {
        $persistedRole = $this->roleModel()::query()
            ->where('name', $role)
            ->where('guard_name', $this->guardName())
            ->first();

        if (! $persistedRole instanceof Role) {
            return [];
        }

        /** @var list<string> $permissions */
        $permissions = $persistedRole->permissions()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return $permissions;
    }

    public function has(string $permission): bool
    {
        return $this->permissionModel()::query()
            ->where('guard_name', $this->guardName())
            ->where('name', $permission)
            ->exists();
    }

    private function guardName(): string
    {
        return (string) config('auth.defaults.guard', 'web');
    }

    /**
     * @return class-string<Role>
     */
    private function roleModel(): string
    {
        $model = config('permission.models.role', Role::class);

        if (! is_string($model) || $model === '' || ! is_a($model, Role::class, true)) {
            return Role::class;
        }

        return $model;
    }

    /**
     * @return class-string<Permission>
     */
    private function permissionModel(): string
    {
        $model = config('permission.models.permission', Permission::class);

        if (! is_string($model) || $model === '' || ! is_a($model, Permission::class, true)) {
            return Permission::class;
        }

        return $model;
    }
}
