<?php

declare(strict_types=1);

use Tests\Fixtures\FakePermissionStoreSetup;
use YezzMedia\Access\Support\PermissionStoreSetup;

use function Pest\Laravel\artisan;

it('fails clearly when the permission store is missing and migrations are not allowed', function (): void {
    $setup = new FakePermissionStoreSetup;
    app()->instance(PermissionStoreSetup::class, $setup);

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
        ->expectsOutputToContain('Status: success')
        ->expectsOutputToContain('Executed install step [publish_permission_config] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [publish_permission_migrations] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [ensure_permission_store_ready] for package [yezzmedia/laravel-access].')
        ->expectsOutputToContain('Executed install step [sync_permissions] for package [yezzmedia/laravel-access].')
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
