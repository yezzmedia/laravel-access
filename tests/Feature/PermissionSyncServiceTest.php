<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerAccessPermissionPackage(string $name, array $permissions): void
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
                description: 'Access permission sync test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

it('creates missing declared permissions and dispatches the sync event', function (): void {
    Event::fake();

    registerAccessPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $result = app(PermissionSyncService::class)->sync();

    expect(Permission::query()->pluck('name')->sort()->values()->all())->toBe([
        'content.pages.archive',
        'content.pages.publish',
    ])->and($result->packageNames)->toBe(['yezzmedia/laravel-content'])
        ->and($result->createdCount)->toBe(2)
        ->and($result->updatedCount)->toBe(0)
        ->and($result->unchangedCount)->toBe(0)
        ->and($result->removedCount)->toBe(0);

    Event::assertDispatched(PermissionsSynchronized::class, static fn (PermissionsSynchronized $event): bool => $event->packageNames === ['yezzmedia/laravel-content']
        && $event->createdCount === 2
        && $event->updatedCount === 0
        && $event->unchangedCount === 0
        && $event->removedCount === 0);
});

it('is idempotent across repeated sync runs', function (): void {
    registerAccessPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    app(PermissionSyncService::class)->sync();

    $result = app(PermissionSyncService::class)->sync();

    expect(Permission::query()->count())->toBe(2)
        ->and($result->createdCount)->toBe(0)
        ->and($result->updatedCount)->toBe(0)
        ->and($result->unchangedCount)->toBe(2)
        ->and($result->removedCount)->toBe(0);
});

it('can synchronize a single package without touching others', function (): void {
    registerAccessPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);
    registerAccessPermissionPackage('yezzmedia/laravel-media', [
        new PermissionDefinition('media.assets.delete', 'yezzmedia/laravel-media', 'Delete assets'),
    ]);

    $result = app(PermissionSyncService::class)->syncPackage('yezzmedia/laravel-content');

    expect(Permission::query()->pluck('name')->all())->toBe(['content.pages.publish'])
        ->and($result->packageNames)->toBe(['yezzmedia/laravel-content'])
        ->and($result->createdCount)->toBe(1)
        ->and($result->updatedCount)->toBe(0)
        ->and($result->unchangedCount)->toBe(0)
        ->and($result->removedCount)->toBe(0);
});

it('ignores default role hints during normal permission synchronization', function (): void {
    registerAccessPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition(
            'content.pages.publish',
            'yezzmedia/laravel-content',
            'Publish pages',
            defaultRoleHints: ['content_manager'],
        ),
    ]);

    $result = app(PermissionSyncService::class)->sync();

    expect(Permission::query()->pluck('name')->all())->toBe(['content.pages.publish'])
        ->and($result->createdCount)->toBe(1)
        ->and($result->updatedCount)->toBe(0)
        ->and($result->removedCount)->toBe(0);
});
