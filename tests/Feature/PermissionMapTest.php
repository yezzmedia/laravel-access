<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\PermissionCacheManager;
use YezzMedia\Access\Support\PermissionMap;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerPermissionMapPackage(string $name, array $permissions): void
{
    app(PlatformPackageRegistrar::class)->register(new class($name, $permissions) implements DefinesPermissions, PlatformPackage
    {
        /**
         * @param  array<int, PermissionDefinition>  $permissions
         */
        public function __construct(
            private readonly string $name,
            private readonly array $permissions,
        ) {}

        public function metadata(): PackageMetadata
        {
            return new PackageMetadata(
                name: $this->name,
                vendor: 'yezzmedia',
                description: 'Access permission map test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function synchronizePermissionMapPackage(string $package, array $permissions): void
{
    registerPermissionMapPackage($package, $permissions);

    app(PermissionSyncService::class)->syncPackage($package);
}

it('returns all known permission names for the active guard', function (): void {
    synchronizePermissionMapPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);
    Permission::query()->create([
        'name' => 'admin.hidden.permission',
        'guard_name' => 'admin',
    ]);

    expect(app(PermissionMap::class)->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('returns the permission names assigned to the named role', function (): void {
    synchronizePermissionMapPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    app(RoleManager::class)->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish and archive content.',
        permissionNames: ['content.pages.publish', 'content.pages.archive'],
    ));

    expect(app(PermissionMap::class)->forRole('content_editor'))->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('returns an empty list for unknown roles', function (): void {
    expect(app(PermissionMap::class)->forRole('missing_role'))->toBe([]);
});

it('can check whether a permission exists in the derived map', function (): void {
    synchronizePermissionMapPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    $map = app(PermissionMap::class);

    expect($map->has('content.pages.publish'))->toBeTrue()
        ->and($map->has('admin.hidden.permission'))->toBeFalse()
        ->and($map->has('content.pages.archive'))->toBeFalse();
});

it('uses the global permission map cache when enabled until invalidated', function (): void {
    config()->set('access.cache.permission_map.enabled', true);

    synchronizePermissionMapPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    $cache = app(PermissionCacheManager::class);
    $map = app(PermissionMap::class);

    expect($map->all())->toBe(['content.pages.publish'])
        ->and(cache()->has($cache->allKey()))->toBeTrue();

    Permission::query()->create([
        'name' => 'content.pages.archive',
        'guard_name' => 'web',
    ]);

    expect($map->all())->toBe(['content.pages.publish']);

    $cache->forgetAll();

    expect($map->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('uses the role permission map cache when enabled until role synchronization invalidates it', function (): void {
    config()->set('access.cache.permission_map.enabled', true);

    synchronizePermissionMapPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    $cache = app(PermissionCacheManager::class);
    $manager = app(RoleManager::class);
    $manager->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $map = app(PermissionMap::class);

    expect($map->forRole('content_editor'))->toBe(['content.pages.publish'])
        ->and(cache()->has($cache->roleKey('content_editor')))->toBeTrue();

    $manager->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish and archive content.',
        permissionNames: ['content.pages.publish', 'content.pages.archive'],
    ));

    expect($map->forRole('content_editor'))->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});
