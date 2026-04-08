<?php

declare(strict_types=1);

namespace YezzMedia\Access;

use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use YezzMedia\Access\Console\SeedRolesCommand;
use YezzMedia\Access\Console\SyncPermissionsCommand;
use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Events\RolesSynchronized;
use YezzMedia\Access\Events\UserRoleAssigned;
use YezzMedia\Access\Events\UserRoleRemoved;
use YezzMedia\Access\Listeners\AuthorizationAuditListener;
use YezzMedia\Access\Support\AccessSecurityVisibilityReporter;
use YezzMedia\Access\Support\ActivityLogAuthorizationAuditWriter;
use YezzMedia\Access\Support\NullAuthorizationAuditWriter;
use YezzMedia\Access\Support\PermissionCacheManager;
use YezzMedia\Access\Support\PermissionMap;
use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Access\Support\SuperAdminGateBootstrapper;
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
            ->hasConfigFile()
            ->hasCommands([
                SyncPermissionsCommand::class,
                SeedRolesCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AuthorizationAuditWriter::class, fn (): AuthorizationAuditWriter => $this->makeAuthorizationAuditWriter());
        $this->app->singleton(PermissionCacheManager::class);
        $this->app->singleton(PermissionMap::class);
        $this->app->singleton(PermissionSyncService::class);
        $this->app->singleton(PermissionStoreSetup::class);
        $this->app->singleton(RoleManager::class);
        $this->app->singleton(AccessSecurityVisibilityReporter::class);
        $this->app->singleton(SuperAdminGateBootstrapper::class);
        $this->app->singleton(UserRoleManager::class);
    }

    public function packageBooted(): void
    {
        // Access must join the same explicit foundation registration flow as other packages.
        $this->app->make(PlatformPackageRegistrar::class)->register(new AccessPlatformPackage);

        $this->registerAuthorizationAuditListeners($this->app->make(Dispatcher::class));
        $this->app->make(SuperAdminGateBootstrapper::class)->bootstrap();
    }

    private function registerAuthorizationAuditListeners(Dispatcher $events): void
    {
        $events->listen(PermissionsSynchronized::class, [AuthorizationAuditListener::class, 'handlePermissionsSynchronized']);
        $events->listen(RolesSynchronized::class, [AuthorizationAuditListener::class, 'handleRolesSynchronized']);
        $events->listen(UserRoleAssigned::class, [AuthorizationAuditListener::class, 'handleUserRoleAssigned']);
        $events->listen(UserRoleRemoved::class, [AuthorizationAuditListener::class, 'handleUserRoleRemoved']);
    }

    private function makeAuthorizationAuditWriter(): AuthorizationAuditWriter
    {
        $driver = config('access.audit.driver');

        if ($driver === null) {
            return new NullAuthorizationAuditWriter;
        }

        if ($driver !== 'activitylog') {
            throw new InvalidArgumentException(sprintf('Unsupported access audit driver [%s].', $driver));
        }

        if (! class_exists('Spatie\\Activitylog\\ActivitylogServiceProvider') || ! class_exists(ActivityLogger::class)) {
            throw new InvalidArgumentException('Access audit driver [activitylog] requires spatie/laravel-activitylog.');
        }

        return new ActivityLogAuthorizationAuditWriter($this->app->make(ActivityLogger::class));
    }
}
