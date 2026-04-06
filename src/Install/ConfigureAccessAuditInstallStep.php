<?php

declare(strict_types=1);

namespace YezzMedia\Access\Install;

use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\AuditInstallStep;
use YezzMedia\Foundation\Install\OptionalInstallStep;

final class ConfigureAccessAuditInstallStep implements AuditInstallStep, OptionalInstallStep
{
    public function __construct() {}

    public function key(): string
    {
        return 'configure_access_audit';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-access';
    }

    public function priority(): int
    {
        return 25;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return $context->shouldConfigureAuditFor($this->package());
    }

    public function handle(InstallContext $context): void
    {
        app(PermissionStoreSetup::class)->configureAuditDriver('activitylog');
    }

    public function isOptional(): bool
    {
        return true;
    }
}
