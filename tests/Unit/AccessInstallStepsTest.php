<?php

declare(strict_types=1);

use Tests\Fixtures\FakePermissionStoreSetup;
use YezzMedia\Access\Install\EnsurePermissionStoreReadyInstallStep;
use YezzMedia\Access\Install\PublishPermissionConfigInstallStep;
use YezzMedia\Access\Install\PublishPermissionMigrationsInstallStep;
use YezzMedia\Access\Install\SyncPermissionsInstallStep;
use YezzMedia\Foundation\Data\InstallContext;

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
