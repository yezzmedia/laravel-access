<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use YezzMedia\Access\Data\RoleDefinition;
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
