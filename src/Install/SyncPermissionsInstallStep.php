<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;

final class SyncPermissionsInstallStep implements InstallStep
{
    public function __construct(private readonly PermissionStoreSetup $setup) {}

    public function key(): string
    {
        return 'sync_permissions';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 40;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return $this->setup->permissionStoreReady() && ! $this->setup->hasPendingPublishedMigrations();
    }

    public function handle(InstallContext $context): void
    {
        $this->setup->synchronizePermissions();
    }
}
