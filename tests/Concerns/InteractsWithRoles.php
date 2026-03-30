<?php

declare(strict_types=1);

namespace YezzMedia\Access\Tests\Concerns;

use PHPUnit\Framework\Assert;
use Spatie\Permission\Models\Role;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\RoleManager;

trait InteractsWithRoles
{
    /**
     * @param  list<string>  $permissionNames
     */
    public function makeRoleDefinition(
        string $name,
        array $permissionNames,
        ?string $label = null,
        ?string $description = null,
    ): RoleDefinition {
        return new RoleDefinition(
            name: $name,
            label: $label ?? str($name)->replace('_', ' ')->title()->toString(),
            description: $description ?? sprintf('Test role for [%s].', $name),
            permissionNames: $permissionNames,
        );
    }

    /**
     * @param  array<int, RoleDefinition>  $roles
     */
    public function syncRoleDefinitions(array $roles): void
    {
        app(RoleManager::class)->syncRoles($roles);
    }

    /**
     * @param  list<string>  $expectedPermissionNames
     */
    public function assertRoleHasPermissions(string $roleName, array $expectedPermissionNames): void
    {
        sort($expectedPermissionNames);

        /** @var Role|null $role */
        $role = $this->roleModel()::query()
            ->where('name', $roleName)
            ->where('guard_name', (string) config('auth.defaults.guard', 'web'))
            ->first();

        Assert::assertInstanceOf(Role::class, $role);

        /** @var list<string> $persistedPermissionNames */
        $persistedPermissionNames = $role->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        Assert::assertSame($expectedPermissionNames, $persistedPermissionNames);
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
