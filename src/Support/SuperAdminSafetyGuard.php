<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Prevents super-admin role removals from dropping below the minimum operator count.
 */
final class SuperAdminSafetyGuard
{
    private const MINIMUM_OPERATORS = 2;

    public function enabled(): bool
    {
        return (bool) config('access.super_admin.enabled', false) && $this->configuredRoleName() !== null;
    }

    public function configuredRoleName(): ?string
    {
        $roleName = config('access.super_admin.role_name');

        if (! is_string($roleName) || trim($roleName) === '') {
            return null;
        }

        return trim($roleName);
    }

    public function minimumOperators(): int
    {
        return self::MINIMUM_OPERATORS;
    }

    public function currentQualifiedOperatorCount(): int
    {
        $configuredRoleName = $this->configuredRoleName();

        if ($configuredRoleName === null) {
            return 0;
        }

        $role = $this->resolveRole($configuredRoleName);

        if (! $role instanceof Role) {
            return 0;
        }

        return $role->users()->count();
    }

    public function assertUserRoleRemovalAllowed(Authenticatable $user, string $roleName): void
    {
        if (! $this->enabled()) {
            return;
        }

        $configuredRoleName = $this->configuredRoleName();

        if ($configuredRoleName === null || $roleName !== $configuredRoleName || ! method_exists($user, 'hasRole') || ! $user->hasRole($roleName)) {
            return;
        }

        if ($this->currentQualifiedOperatorCount() <= $this->minimumOperators()) {
            throw new InvalidArgumentException(sprintf(
                'Removing the [%s] role would reduce active super-admin-capable operators below the minimum [%d].',
                $roleName,
                $this->minimumOperators(),
            ));
        }
    }

    private function resolveRole(string $roleName): ?Role
    {
        $roleModel = $this->roleModel();

        /** @var Role|null $role */
        $role = $roleModel::query()
            ->where('name', $roleName)
            ->where('guard_name', (string) config('auth.defaults.guard', 'web'))
            ->first();

        return $role;
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
