<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;
use Tests\Fixtures\FakePermissionStoreSetup;
use YezzMedia\Access\Install\ConfigureAccessAuditInstallStep;
use YezzMedia\Access\Install\EnsurePermissionStoreReadyInstallStep;
use YezzMedia\Access\Install\PublishPermissionConfigInstallStep;
use YezzMedia\Access\Install\PublishPermissionMigrationsInstallStep;
use YezzMedia\Access\Install\SeedRolesFromPermissionHintsInstallStep;
use YezzMedia\Access\Install\SyncPermissionsInstallStep;
use YezzMedia\Access\Support\PermissionStoreSetup;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

function registerAccessInstallPermissionPackage(string $name, array $permissions): void
{
    app(PlatformPackageRegistrar::class)->register(new class($name, $permissions) implements DefinesPermissions, PlatformPackage
    {
        public function __construct(
            private readonly string $name,
            private readonly array $permissions,
        ) {}

        public function metadata(): PackageMetadata
        {
            return new PackageMetadata(
                name: $this->name,
                vendor: 'yezzmedia',
                description: 'Access install step test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

it('refreshes the published permission config only when explicitly requested', function (): void {
    $setup = new FakePermissionStoreSetup(hasPublishedConfig: true);
    $step = new PublishPermissionConfigInstallStep($setup);

    expect($step->shouldRun(new InstallContext))->toBeFalse()
        ->and($step->shouldRun(new InstallContext(refreshPublishedResources: true)))->toBeTrue();

    $step->handle(new InstallContext(refreshPublishedResources: true));

    expect($setup->calls)->toBe(['publish_permission_config'])
        ->and($setup->configWasForced)->toBeTrue();
});

it('re-runs migration publishing during refresh mode without forcing duplicates', function (): void {
    $setup = new FakePermissionStoreSetup(hasPublishedMigrations: true);
    $step = new PublishPermissionMigrationsInstallStep($setup);

    expect($step->shouldRun(new InstallContext))->toBeFalse()
        ->and($step->shouldRun(new InstallContext(refreshPublishedResources: true)))->toBeTrue();

    $step->handle(new InstallContext(refreshPublishedResources: true));

    expect($setup->calls)->toBe(['publish_permission_migrations']);
});

it('requires explicit migration permission before making the permission store ready', function (): void {
    $setup = new FakePermissionStoreSetup;
    $step = new EnsurePermissionStoreReadyInstallStep($setup);

    expect(fn () => $step->handle(new InstallContext))
        ->toThrow(RuntimeException::class, 'The permission store is not ready or has pending published migrations. Run `php artisan migrate` or rerun `php artisan website:install --migrate`.');

    $step->handle(new InstallContext(allowMigrations: true));

    expect($setup->calls)->toBe(['ensure_permission_store_ready'])
        ->and($setup->hasReadyStore)->toBeTrue();
});

it('treats pending published access migrations as store readiness work', function (): void {
    $setup = new FakePermissionStoreSetup(
        hasPublishedConfig: true,
        hasPublishedMigrations: true,
        hasReadyStore: true,
        hasPendingPublishedMigrations: true,
    );
    $ensureStep = new EnsurePermissionStoreReadyInstallStep($setup);
    $syncStep = new SyncPermissionsInstallStep($setup);

    expect($ensureStep->shouldRun(new InstallContext))->toBeTrue()
        ->and($syncStep->shouldRun(new InstallContext))->toBeFalse();

    $ensureStep->handle(new InstallContext(allowMigrations: true));

    expect($setup->calls)->toBe(['ensure_permission_store_ready'])
        ->and($setup->hasPendingPublishedMigrations)->toBeFalse()
        ->and($syncStep->shouldRun(new InstallContext))->toBeTrue();
});

it('reuses the existing permission sync runtime inside the install workflow', function (): void {
    $setup = new FakePermissionStoreSetup(hasReadyStore: true);
    $step = new SyncPermissionsInstallStep($setup);

    expect($step->shouldRun(new InstallContext))->toBeTrue();

    $step->handle(new InstallContext);

    expect($setup->calls)->toBe(['sync_permissions']);
});

it('seeds roles from permission hints during install when role hints are enabled', function (): void {
    config()->set('access.roles.apply_default_role_hints', true);

    registerAccessInstallPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition(
            'content.pages.publish',
            'yezzmedia/laravel-content',
            'Publish pages',
            defaultRoleHints: ['super-admin'],
        ),
        new PermissionDefinition(
            'content.pages.archive',
            'yezzmedia/laravel-content',
            'Archive pages',
            defaultRoleHints: ['super-admin'],
        ),
    ]);

    app(PermissionSyncService::class)->syncPackage('yezzmedia/laravel-content');

    $step = app(SeedRolesFromPermissionHintsInstallStep::class);

    expect($step->shouldRun(new InstallContext))->toBeTrue();

    $step->handle(new InstallContext);

    $role = app(RoleManager::class)->findRole('super-admin');

    expect($role)->toBeInstanceOf(Role::class);

    if (! $role instanceof Role) {
        throw new RuntimeException('Expected persisted seeded role instance.');
    }

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('skips role hint seeding during install when role hints are disabled', function (): void {
    config()->set('access.roles.apply_default_role_hints', false);

    $step = app(SeedRolesFromPermissionHintsInstallStep::class);

    expect($step->shouldRun(new InstallContext))->toBeFalse();
});

it('configures access audit persistence only when requested', function (): void {
    $setup = new FakePermissionStoreSetup(hasPublishedConfig: true);
    app()->instance(PermissionStoreSetup::class, $setup);
    $step = new ConfigureAccessAuditInstallStep($setup);

    expect($step->shouldRun(new InstallContext))->toBeFalse()
        ->and($step->shouldRun(new InstallContext(configureAudit: true, auditPackages: ['yezzmedia/laravel-access'])))->toBeTrue()
        ->and($step->shouldRun(new InstallContext(configureAccessAudit: true)))->toBeTrue();

    $step->handle(new InstallContext(configureAudit: true, auditPackages: ['yezzmedia/laravel-access']));

    expect($setup->calls)->toBe(['publish_access_config', 'configure_access_audit'])
        ->and($setup->auditDriver)->toBe('activitylog')
        ->and($setup->auditDriverConfigured)->toBeTrue();
});
