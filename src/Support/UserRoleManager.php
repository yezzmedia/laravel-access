<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use YezzMedia\Access\Events\UserRoleAssigned;
use YezzMedia\Access\Events\UserRoleRemoved;

/**
 * Owns explicit and auditable user-role assignment changes.
 */
final class UserRoleManager
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
        private readonly PermissionCacheManager $permissionCache,
        private readonly Dispatcher $events,
        private readonly SuperAdminSafetyGuard $superAdminSafety,
    ) {}

    public function assignRole(Authenticatable $user, string $roleName, ?Authenticatable $actor = null): void
    {
        $role = $this->resolveRole($roleName);

        if ($this->userHasRole($user, $roleName)) {
            return;
        }

        $this->assertUserSupportsRoleAssignments($user);
        $this->invokeUserRoleMethod($user, 'assignRole', $role);
        $this->permissionRegistrar->forgetCachedPermissions();
        $this->permissionCache->forgetAll();
        $this->permissionCache->forgetUser($user);

        $this->events->dispatch(new UserRoleAssigned(
            userId: $user->getAuthIdentifier(),
            roleName: $role->name,
            actorId: $actor?->getAuthIdentifier(),
            guardName: $role->guard_name,
        ));
    }

    public function removeRole(Authenticatable $user, string $roleName, ?Authenticatable $actor = null): void
    {
        $role = $this->resolveRole($roleName);

        if (! $this->userHasRole($user, $roleName)) {
            return;
        }

        $this->superAdminSafety->assertUserRoleRemovalAllowed($user, $roleName);
        $this->assertUserSupportsRoleAssignments($user);
        $this->invokeUserRoleMethod($user, 'removeRole', $role);
        $this->permissionRegistrar->forgetCachedPermissions();
        $this->permissionCache->forgetAll();
        $this->permissionCache->forgetUser($user);

        $this->events->dispatch(new UserRoleRemoved(
            userId: $user->getAuthIdentifier(),
            roleName: $role->name,
            actorId: $actor?->getAuthIdentifier(),
            guardName: $role->guard_name,
        ));
    }

    private function resolveRole(string $roleName): Role
    {
        $role = $this->roleModel()::query()
            ->where('name', $roleName)
            ->where('guard_name', (string) config('auth.defaults.guard', 'web'))
            ->first();

        if (! $role instanceof Role) {
            throw new InvalidArgumentException(sprintf(
                'User-role changes require an existing persisted role. Missing: [%s].',
                $roleName,
            ));
        }

        return $role;
    }

    private function userHasRole(Authenticatable $user, string $roleName): bool
    {
        return (bool) $this->invokeUserRoleMethod($user, 'hasRole', $roleName);
    }

    private function assertUserSupportsRoleAssignments(object $user): void
    {
        foreach (['assignRole', 'removeRole', 'hasRole'] as $method) {
            if (! method_exists($user, $method)) {
                throw new InvalidArgumentException(sprintf(
                    'User-role changes require a model that supports %s().',
                    $method,
                ));
            }
        }
    }

    private function invokeUserRoleMethod(object $user, string $method, mixed ...$arguments): mixed
    {
        $this->assertUserSupportsRoleAssignments($user);

        return $user->{$method}(...$arguments);
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
}
