<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Events\RolesSynchronized;

/**
 * Synchronizes explicit role presets against the persisted access runtime.
 */
final class RoleManager
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
        private readonly PermissionCacheManager $permissionCache,
        private readonly Dispatcher $events,
    ) {}

    public function syncRole(RoleDefinition $role): void
    {
        $this->syncRoles([$role]);
    }

    /**
     * @param  array<int, RoleDefinition>  $roles
     */
    public function syncRoles(array $roles): void
    {
        usort($roles, static fn (RoleDefinition $left, RoleDefinition $right): int => $left->name <=> $right->name);

        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $guardName = (string) config('auth.defaults.guard', 'web');
        $roleModel = $this->roleModel();
        $roleNames = [];

        foreach ($roles as $roleDefinition) {
            $roleNames[] = $roleDefinition->name;

            /** @var Role $role */
            $role = $roleModel::query()->firstOrCreate([
                'name' => $roleDefinition->name,
                'guard_name' => $guardName,
            ]);

            $permissions = $this->resolvePermissions($roleDefinition->permissionNames, $guardName);
            $currentPermissionNames = $role->permissions()->pluck('name')->sort()->values()->all();
            $desiredPermissionNames = $permissions->pluck('name')->sort()->values()->all();

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($permissions);
                $createdCount++;

                continue;
            }

            if ($currentPermissionNames === $desiredPermissionNames) {
                $unchangedCount++;

                continue;
            }

            $role->syncPermissions($permissions);
            $updatedCount++;
        }

        $this->permissionRegistrar->forgetCachedPermissions();
        $this->permissionCache->forgetAll();
        $this->events->dispatch(new RolesSynchronized(
            roleNames: $roleNames,
            createdCount: $createdCount,
            updatedCount: $updatedCount,
            unchangedCount: $unchangedCount,
        ));
    }

    public function findRole(string $name): ?object
    {
        return $this->roleModel()::query()
            ->where('name', $name)
            ->where('guard_name', (string) config('auth.defaults.guard', 'web'))
            ->first();
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return Collection<int, Permission>
     */
    private function resolvePermissions(array $permissionNames, string $guardName)
    {
        if ($permissionNames === []) {
            return collect();
        }

        $normalizedPermissionNames = array_values(array_unique($permissionNames));
        sort($normalizedPermissionNames);

        $permissionModel = $this->permissionModel();

        $permissions = $permissionModel::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', $normalizedPermissionNames)
            ->get();

        $resolvedPermissionNames = $permissions->pluck('name')->sort()->values()->all();

        if ($resolvedPermissionNames !== $normalizedPermissionNames) {
            $missingPermissionNames = array_values(array_diff($normalizedPermissionNames, $resolvedPermissionNames));

            throw new InvalidArgumentException(sprintf(
                'Role synchronization requires existing permissions. Missing: [%s].',
                implode(', ', $missingPermissionNames),
            ));
        }

        return $permissions;
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
