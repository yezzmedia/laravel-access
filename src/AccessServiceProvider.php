<?php

declare(strict_types=1);

namespace YezzMedia\Access;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Support\NullAuthorizationAuditWriter;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Access\Support\UserRoleManager;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * Boots the access package without hiding authorization behavior in provider logic.
 */
class AccessServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-access')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AuthorizationAuditWriter::class, static fn (): AuthorizationAuditWriter => new NullAuthorizationAuditWriter);
        $this->app->singleton(PermissionSyncService::class);
        $this->app->singleton(RoleManager::class);
        $this->app->singleton(UserRoleManager::class);
    }

    public function packageBooted(): void
    {
        // Access must join the same explicit foundation registration flow as other packages.
        $this->app->make(PlatformPackageRegistrar::class)->register(new AccessPlatformPackage);
    }
}
