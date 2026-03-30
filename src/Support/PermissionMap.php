<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Cache\Repository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Exposes a narrow read model for persisted permission-name lookups.
 */
final class PermissionMap
{
    public function __construct(
        private readonly Repository $cache,
        private readonly PermissionCacheManager $permissionCache,
    ) {}

    /**
     * @return list<string>
     */
    public function all(): array
    {
        if (! $this->cacheEnabled()) {
            return $this->queryAll();
        }

        return $this->rememberPermissions($this->permissionCache->allKey(), fn (): array => $this->queryAll());
    }

    /**
     * @return list<string>
     */
    public function forRole(string $role): array
    {
        if (! $this->cacheEnabled()) {
            return $this->queryRole($role);
        }

        return $this->rememberPermissions($this->permissionCache->roleKey($role), fn (): array => $this->queryRole($role));
    }

    public function has(string $permission): bool
    {
        if ($this->cacheEnabled()) {
            return in_array($permission, $this->all(), true);
        }

        return $this->permissionModel()::query()
            ->where('guard_name', $this->guardName())
            ->where('name', $permission)
            ->exists();
    }

    private function cacheEnabled(): bool
    {
        return (bool) config('access.cache.permission_map.enabled', false);
    }

    /**
     * @return list<string>
     */
    private function queryAll(): array
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
    private function queryRole(string $role): array
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

    /**
     * @param  callable(): list<string>  $resolver
     * @return list<string>
     */
    private function rememberPermissions(string $key, callable $resolver): array
    {
        $cachedPermissions = $this->cache->get($key);

        if (is_array($cachedPermissions)) {
            /** @var list<string> $permissions */
            $permissions = array_values($cachedPermissions);

            return $permissions;
        }

        $permissions = $resolver();
        $this->cache->forever($key, $permissions);

        return $permissions;
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
