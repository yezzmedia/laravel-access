<?php

declare(strict_types=1);

use Tests\Fixtures\FakePermissionStoreSetup;
use YezzMedia\Access\Install\ConfigureAccessAuditInstallStep;
use YezzMedia\Access\Support\PermissionStoreSetup;

use function Pest\Laravel\artisan;

it('fails clearly when the permission store is missing and migrations are not allowed', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);
    app()->forgetInstance(ConfigureAccessAuditInstallStep::class);

    $command = artisan('website:install');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Status: failed')
        ->expectsOutputToContain('Executed install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access] failed. The permission store is not ready or has pending published migrations. Run `php artisan migrate` or rerun `php artisan website:install --migrate`.')
        ->assertFailed();
});

it('runs the full access install flow when migrations are explicitly allowed', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install', ['--migrate' => true]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Migration execution is enabled for this install run.')
        ->expectsOutputToContain('Status: partial')
        ->expectsOutputToContain('Executed install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [seed_roles_from_permission_hints] for package [yezzmedia/laravel-access].')
        ->assertSuccessful();
});

it('seeds roles from permission hints during install when role hints are enabled', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);
    config()->set('access.roles.apply_default_role_hints', true);

    $command = artisan('website:install', ['--migrate' => true]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Migration execution is enabled for this install run.')
        ->expectsOutputToContain('Status: success')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [seed_roles_from_permission_hints] for package [yezzmedia/laravel-access].')
        ->assertSuccessful();
});

it('configures access audit persistence when explicitly requested', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install', ['--migrate' => true, '--configure-access-audit' => true]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Audit persistence configuration is enabled for this install run.')
        ->expectsOutputToContain('The [--configure-access-audit] option is deprecated.')
        ->expectsOutputToContain('Migration execution is enabled for this install run.')
        ->expectsOutputToContain('Audit packages: yezzmedia/laravel-access')
        ->expectsOutputToContain('Status: success')
        ->expectsOutputToContain('Executed install step [configure_access_audit] for package [yezzmedia/laravel-access].')
        ->doesntExpectOutputToContain('Executed install step [publish_permission_migrations]')
        ->assertSuccessful();
});

it('configures access audit persistence through the generic audit flow', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install', [
        '--configure-audit' => true,
        '--audit-package' => ['yezzmedia/laravel-access'],
    ]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Audit persistence configuration is enabled for this install run.')
        ->expectsOutputToContain('Audit packages: yezzmedia/laravel-access')
        ->expectsOutputToContain('Status: success')
        ->expectsOutputToContain('Executed install step [configure_access_audit] for package [yezzmedia/laravel-access].')
        ->doesntExpectOutputToContain('Executed install step [publish_permission_migrations]')
        ->assertSuccessful();
});

it('reuses already prepared access setup and only synchronizes permissions', function (): void {
    $setup = new FakePermissionStoreSetup(
        hasPublishedConfig: true,
        hasPublishedMigrations: true,
        hasReadyStore: true,
    );
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Status: partial')
        ->expectsOutputToContain('Skipped install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
        ->assertSuccessful();
});

it('refreshes published access resources explicitly after package updates', function (): void {
    $setup = new FakePermissionStoreSetup(
        hasPublishedConfig: true,
        hasPublishedMigrations: true,
        hasReadyStore: true,
    );
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install', ['--refresh-publish' => true]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Published resource refresh is enabled for this install run.')
        ->expectsOutputToContain('Status: partial')
        ->expectsOutputToContain('Executed install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
        ->assertSuccessful();
});

it('fails clearly when access migrations are published but still pending after an update', function (): void {
    $setup = new FakePermissionStoreSetup(
        hasPublishedConfig: true,
        hasPublishedMigrations: true,
        hasReadyStore: true,
        hasPendingPublishedMigrations: true,
    );
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Status: failed')
        ->expectsOutputToContain('Skipped install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access] failed. The permission store is not ready or has pending published migrations. Run `php artisan migrate` or rerun `php artisan website:install --migrate`.')
        ->assertFailed();
});

it('runs pending published access migrations during install when migrate is explicitly allowed', function (): void {
    $setup = new FakePermissionStoreSetup(
        hasPublishedConfig: true,
        hasPublishedMigrations: true,
        hasReadyStore: true,
        hasPendingPublishedMigrations: true,
    );
    app()->instance(PermissionStoreSetup::class, $setup);

    $command = artisan('website:install', ['--migrate' => true]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:install.');
    }

    $command
        ->expectsOutputToContain('Migration execution is enabled for this install run.')
        ->expectsOutputToContain('Status: partial')
        ->expectsOutputToContain('Skipped install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Skipped install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
        ->assertSuccessful();
});
