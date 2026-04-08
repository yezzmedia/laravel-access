<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;
use YezzMedia\Foundation\Registry\PermissionRegistry;

final readonly class SeedRolesFromPermissionHintsInstallStep implements InstallStep
{
    public function __construct(
        private PermissionRegistry $permissions,
        private RoleManager $roles,
    ) {}

    public function key(): string
    {
        return 'seed_roles_from_permission_hints';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 45;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return (bool) config('access.roles.apply_default_role_hints', false);
    }

    public function handle(InstallContext $context): void
    {
        $this->roles->syncRolesFromPermissionHints($this->permissions->all());
    }
}
