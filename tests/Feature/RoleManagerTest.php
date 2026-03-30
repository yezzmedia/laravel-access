<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Events\RolesSynchronized;
use YezzMedia\Access\Support\PermissionCacheManager;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Registry\PermissionRegistry;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerRolePermissionPackage(string $name, array $permissions): void
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
                description: 'Access role sync test package.',
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
function synchronizeRoleTestPermissions(string $package, array $permissions): void
{
    registerRolePermissionPackage($package, $permissions);

    app(PermissionSyncService::class)->syncPackage($package);
}

it('creates a role, assigns permissions, and dispatches the synchronization event', function (): void {
    Event::fake();

    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    app(RoleManager::class)->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can manage content publishing tasks.',
        permissionNames: ['content.pages.publish', 'content.pages.archive'],
    ));

    $role = Role::query()->where('name', 'content_editor')->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);

    Event::assertDispatched(RolesSynchronized::class, static fn (RolesSynchronized $event): bool => $event->roleNames === ['content_editor']
        && $event->createdCount === 1
        && $event->updatedCount === 0
        && $event->unchangedCount === 0);
});

it('updates an existing role when its permission composition changes', function (): void {
    Event::fake();

    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $manager = app(RoleManager::class);

    $manager->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $manager->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish and archive content.',
        permissionNames: ['content.pages.publish', 'content.pages.archive'],
    ));

    $role = Role::query()->where('name', 'content_editor')->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);

    Event::assertDispatched(RolesSynchronized::class, static fn (RolesSynchronized $event): bool => $event->createdCount === 0
        && $event->updatedCount === 1
        && $event->unchangedCount === 0);
});

it('is idempotent when the role already has the desired permissions', function (): void {
    Event::fake();

    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    $manager = app(RoleManager::class);

    $definition = new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    );

    $manager->syncRole($definition);

    $manager->syncRole($definition);

    expect(Role::query()->count())->toBe(1)
        ->and(Role::query()->firstOrFail()->permissions->pluck('name')->all())->toBe(['content.pages.publish']);

    Event::assertDispatched(RolesSynchronized::class, static fn (RolesSynchronized $event): bool => $event->createdCount === 0
        && $event->updatedCount === 0
        && $event->unchangedCount === 1);
});

it('syncs multiple roles deterministically and can find a persisted role', function (): void {
    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    Event::fake();

    $manager = app(RoleManager::class);
    $manager->syncRoles([
        new RoleDefinition(
            name: 'content_archivist',
            label: 'Content archivist',
            description: 'Can archive content.',
            permissionNames: ['content.pages.archive'],
        ),
        new RoleDefinition(
            name: 'content_editor',
            label: 'Content editor',
            description: 'Can publish content.',
            permissionNames: ['content.pages.publish'],
        ),
    ]);

    $foundRole = $manager->findRole('content_editor');

    expect($foundRole)->toBeInstanceOf(Role::class);

    if (! $foundRole instanceof Role) {
        throw new RuntimeException('Expected persisted role instance.');
    }

    expect($foundRole->name)->toBe('content_editor')
        ->and($manager->findRole('missing_role'))->toBeNull();

    Event::assertDispatched(RolesSynchronized::class, static fn (RolesSynchronized $event): bool => $event->roleNames === ['content_archivist', 'content_editor']
        && $event->createdCount === 2
        && $event->updatedCount === 0
        && $event->unchangedCount === 0);
});

it('fails fast when a role references unknown permissions', function (): void {
    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    expect(fn () => app(RoleManager::class)->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish', 'content.pages.archive'],
    )))->toThrow(InvalidArgumentException::class, 'Missing: [content.pages.archive]');
});

it('can seed roles from permission default role hints through the role manager', function (): void {
    config()->set('access.roles.apply_default_role_hints', true);

    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition(
            'content.pages.publish',
            'yezzmedia/laravel-content',
            'Publish pages',
            defaultRoleHints: ['content_editor'],
        ),
        new PermissionDefinition(
            'content.pages.archive',
            'yezzmedia/laravel-content',
            'Archive pages',
            defaultRoleHints: ['content_editor', 'content_archivist'],
        ),
    ]);

    $roleNames = app(RoleManager::class)->syncRolesFromPermissionHints(app(PermissionRegistry::class)->all());

    expect($roleNames)->toBe(['content_archivist', 'content_editor']);

    $contentEditor = app(RoleManager::class)->findRole('content_editor');

    expect($contentEditor)->toBeInstanceOf(Role::class);

    if (! $contentEditor instanceof Role) {
        throw new RuntimeException('Expected persisted seeded role instance.');
    }

    expect($contentEditor->permissions->pluck('name')->sort()->values()->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('forgets permission map cache entries after role synchronization', function (): void {
    synchronizeRoleTestPermissions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    $cache = app(PermissionCacheManager::class);
    cache()->put($cache->allKey(), ['stale.permission'], 600);
    cache()->put($cache->roleKey('content_editor'), ['stale.permission'], 600);

    app(RoleManager::class)->syncRole(new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    expect(cache()->has($cache->allKey()))->toBeFalse()
        ->and(cache()->has($cache->roleKey('content_editor')))->toBeFalse();
});
