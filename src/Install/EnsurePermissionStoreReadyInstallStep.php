<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use RuntimeException;
use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;

final class EnsurePermissionStoreReadyInstallStep implements InstallStep
{
    public function __construct(private readonly PermissionStoreSetup $setup) {}

    public function key(): string
    {
        return 'ensure_permission_store_ready';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 30;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return ! $this->setup->permissionStoreReady() || $this->setup->hasPendingPublishedMigrations();
    }

    public function handle(InstallContext $context): void
    {
        if (! $context->allowMigrations) {
            throw new RuntimeException('The permission store is not ready or has pending published migrations. Run `php artisan migrate` or rerun `php artisan website:install --migrate`.');
        }

        $this->setup->migratePermissionStore();

        if (! $this->setup->permissionStoreReady() || $this->setup->hasPendingPublishedMigrations()) {
            throw new RuntimeException('The permission store is still not ready after running migrations.');
        }
    }
}
