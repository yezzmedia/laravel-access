<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;

final class PublishPermissionConfigInstallStep implements InstallStep
{
    public function __construct(private readonly PermissionStoreSetup $setup) {}

    public function key(): string
    {
        return 'publish_permission_config';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 10;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return $context->refreshPublishedResources || ! $this->setup->configPublished();
    }

    public function handle(InstallContext $context): void
    {
        $this->setup->publishConfig($context->refreshPublishedResources);
    }
}
