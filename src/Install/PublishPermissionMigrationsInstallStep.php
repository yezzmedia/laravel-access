<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;

final class PublishPermissionMigrationsInstallStep implements InstallStep
{
    public function __construct(private readonly PermissionStoreSetup $setup) {}

    public function key(): string
    {
        return 'publish_permission_migrations';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 20;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return $context->refreshPublishedResources || ! $this->setup->migrationsPublished() || $this->setup->hasMissingPublishedMigrations();
    }

    public function handle(InstallContext $context): void
    {
        $this->setup->publishMigrations();
    }
}
