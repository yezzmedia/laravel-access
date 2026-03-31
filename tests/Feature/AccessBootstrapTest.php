<?php

declare(strict_types=1);

use YezzMedia\Access\AccessPlatformPackage;
use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Doctor\PermissionsSynchronizedCheck;
use YezzMedia\Access\Doctor\SuperAdminConfiguredCheck;
use YezzMedia\Access\Install\EnsurePermissionStoreReadyInstallStep;
use YezzMedia\Access\Install\PublishPermissionConfigInstallStep;
use YezzMedia\Access\Install\PublishPermissionMigrationsInstallStep;
use YezzMedia\Access\Install\SyncPermissionsInstallStep;
use YezzMedia\Access\Support\ActivityLogAuthorizationAuditWriter;
use YezzMedia\Access\Support\NullAuthorizationAuditWriter;
use YezzMedia\Foundation\Contracts\DefinesAuditEvents;
use YezzMedia\Foundation\Contracts\DefinesInstallSteps;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Contracts\ProvidesDoctorChecks;
use YezzMedia\Foundation\Contracts\ProvidesOpsModules;
use YezzMedia\Foundation\Doctor\DoctorManager;
use YezzMedia\Foundation\Registry\OpsModuleRegistry;
use YezzMedia\Foundation\Registry\PackageRegistry;
use YezzMedia\Foundation\Registry\PermissionRegistry;

it('registers the access bootstrap bindings', function (): void {
    $doctorResults = app(DoctorManager::class)->run()->keyBy('key');

    expect(app(AuthorizationAuditWriter::class))->toBeInstanceOf(NullAuthorizationAuditWriter::class)
        ->and(app(PackageRegistry::class)->has('yezzmedia/laravel-access'))->toBeTrue()
        ->and(app(PermissionRegistry::class)->forPackage('yezzmedia/laravel-access'))->toHaveCount(0)
        ->and(app(OpsModuleRegistry::class)->forPackage('yezzmedia/laravel-access'))->toHaveCount(0)
        ->and($doctorResults->keys()->all())->toContain('permissions_synchronized', 'super_admin_configured')
        ->and($doctorResults->get('permissions_synchronized')?->status)->toBe('skipped')
        ->and($doctorResults->get('super_admin_configured')?->status)->toBe('skipped');
});

it('merges the package configuration', function (): void {
    expect(config('access.audit.driver'))->toBeNull()
        ->and(config('access.super_admin.enabled'))->toBeFalse()
        ->and(config('access.super_admin.role_name'))->toBeNull()
        ->and(config('access.cache.permission_map.enabled'))->toBeFalse()
        ->and(config('access.roles.apply_default_role_hints'))->toBeFalse();
});

it('can bind the activitylog audit writer when the driver is enabled', function (): void {
    config()->set('access.audit.driver', 'activitylog');
    app()->forgetInstance(AuthorizationAuditWriter::class);

    if (! class_exists('Spatie\\Activitylog\\ActivitylogServiceProvider')) {
        expect(fn () => app(AuthorizationAuditWriter::class))
            ->toThrow(InvalidArgumentException::class, 'Access audit driver [activitylog] requires spatie/laravel-activitylog.');

        return;
    }

    expect(app(AuthorizationAuditWriter::class))->toBeInstanceOf(ActivityLogAuthorizationAuditWriter::class);
});

it('fails fast for unsupported audit drivers', function (): void {
    config()->set('access.audit.driver', 'unsupported');
    app()->forgetInstance(AuthorizationAuditWriter::class);

    expect(fn () => app(AuthorizationAuditWriter::class))
        ->toThrow(InvalidArgumentException::class, 'Unsupported access audit driver [unsupported].');
});

it('describes the approved bootstrap surface', function (): void {
    $package = new AccessPlatformPackage;
    $metadata = $package->metadata();
    $auditEvents = collect($package->auditEventDefinitions())->keyBy('key');

    expect($package)->toBeInstanceOf(PlatformPackage::class)
        ->and($package)->toBeInstanceOf(DefinesPermissions::class)
        ->and($package)->toBeInstanceOf(DefinesAuditEvents::class)
        ->and($package)->toBeInstanceOf(DefinesInstallSteps::class)
        ->and($package)->toBeInstanceOf(ProvidesDoctorChecks::class)
        ->and($package)->toBeInstanceOf(ProvidesOpsModules::class)
        ->and($metadata->name)->toBe('yezzmedia/laravel-access')
        ->and($metadata->vendor)->toBe('yezzmedia')
        ->and($metadata->packageClass)->toBe(AccessPlatformPackage::class)
        ->and($package->permissionDefinitions())->toBe([])
        ->and($package->installSteps())->toHaveCount(4)
        ->and($package->installSteps()[0])->toBeInstanceOf(PublishPermissionConfigInstallStep::class)
        ->and($package->installSteps()[1])->toBeInstanceOf(PublishPermissionMigrationsInstallStep::class)
        ->and($package->installSteps()[2])->toBeInstanceOf(EnsurePermissionStoreReadyInstallStep::class)
        ->and($package->installSteps()[3])->toBeInstanceOf(SyncPermissionsInstallStep::class)
        ->and($package->doctorChecks())->toHaveCount(2)
        ->and($package->doctorChecks()[0])->toBeInstanceOf(PermissionsSynchronizedCheck::class)
        ->and($package->doctorChecks()[1])->toBeInstanceOf(SuperAdminConfiguredCheck::class)
        ->and($package->opsModuleDefinitions())->toBe([])
        ->and($auditEvents->keys()->all())->toBe([
            'access.permissions.synchronized',
            'access.roles.synchronized',
            'access.user_role.assigned',
            'access.user_role.removed',
        ])
        ->and($auditEvents->get('access.permissions.synchronized')?->contextKeys)->toBe([
            'package_names',
            'created_count',
            'updated_count',
            'unchanged_count',
            'removed_count',
        ])
        ->and($auditEvents->get('access.roles.synchronized')?->contextKeys)->toBe([
            'role_names',
            'created_count',
            'updated_count',
            'unchanged_count',
        ])
        ->and($auditEvents->get('access.user_role.assigned')?->severity)->toBe('warning')
        ->and($auditEvents->get('access.user_role.removed')?->severity)->toBe('warning');
});
