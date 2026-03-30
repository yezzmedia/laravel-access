<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Tests\Fixtures\TestUser;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Events\UserRoleAssigned;
use YezzMedia\Access\Events\UserRoleRemoved;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Access\Support\UserRoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerUserRolePermissionPackage(string $name, array $permissions): void
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
                description: 'Access user-role test package.',
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
function prepareUserRoleTestRole(string $package, array $permissions, RoleDefinition $roleDefinition): void
{
    registerUserRolePermissionPackage($package, $permissions);
    app(PermissionSyncService::class)->syncPackage($package);
    app(RoleManager::class)->syncRole($roleDefinition);
}

function reloadUser(TestUser $user): TestUser
{
    $reloadedUser = TestUser::query()->find($user->getKey());

    if (! $reloadedUser instanceof TestUser) {
        throw new RuntimeException('Expected persisted test user instance.');
    }

    return $reloadedUser;
}

it('assigns a persisted role to a user and dispatches the assignment event', function (): void {
    Event::fake();

    prepareUserRoleTestRole('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ], new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $user = TestUser::query()->create(['name' => 'Editor']);
    $actor = TestUser::query()->create(['name' => 'Admin']);

    app(UserRoleManager::class)->assignRole($user, 'content_editor', $actor);

    expect(reloadUser($user)->hasRole('content_editor'))->toBeTrue();

    Event::assertDispatched(UserRoleAssigned::class, static fn (UserRoleAssigned $event): bool => $event->userId === $user->getAuthIdentifier()
        && $event->roleName === 'content_editor'
        && $event->actorId === $actor->getAuthIdentifier()
        && $event->guardName === 'web');
});

it('does not dispatch an assignment event when the user already has the role', function (): void {
    Event::fake();

    prepareUserRoleTestRole('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ], new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $user = TestUser::query()->create(['name' => 'Editor']);
    $manager = app(UserRoleManager::class);

    $manager->assignRole($user, 'content_editor');
    Event::fake();
    app()->forgetInstance(UserRoleManager::class);

    app(UserRoleManager::class)->assignRole(reloadUser($user), 'content_editor');

    expect(reloadUser($user)->hasRole('content_editor'))->toBeTrue();
    Event::assertNotDispatched(UserRoleAssigned::class);
});

it('removes a persisted role from a user and dispatches the removal event', function (): void {
    Event::fake();

    prepareUserRoleTestRole('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ], new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $user = TestUser::query()->create(['name' => 'Editor']);
    $actor = TestUser::query()->create(['name' => 'Admin']);
    $manager = app(UserRoleManager::class);

    $manager->assignRole($user, 'content_editor');
    $manager->removeRole(reloadUser($user), 'content_editor', $actor);

    expect(reloadUser($user)->hasRole('content_editor'))->toBeFalse();

    Event::assertDispatched(UserRoleRemoved::class, static fn (UserRoleRemoved $event): bool => $event->userId === $user->getAuthIdentifier()
        && $event->roleName === 'content_editor'
        && $event->actorId === $actor->getAuthIdentifier()
        && $event->guardName === 'web');
});

it('does not dispatch a removal event when the user does not have the role', function (): void {
    Event::fake();

    prepareUserRoleTestRole('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ], new RoleDefinition(
        name: 'content_editor',
        label: 'Content editor',
        description: 'Can publish content.',
        permissionNames: ['content.pages.publish'],
    ));

    $user = TestUser::query()->create(['name' => 'Editor']);

    app(UserRoleManager::class)->removeRole($user, 'content_editor');

    expect(reloadUser($user)->hasRole('content_editor'))->toBeFalse();
    Event::assertNotDispatched(UserRoleRemoved::class);
});

it('fails fast when the target role does not exist', function (): void {
    $user = TestUser::query()->create(['name' => 'Editor']);

    expect(fn () => app(UserRoleManager::class)->assignRole($user, 'missing_role'))
        ->toThrow(InvalidArgumentException::class, 'Missing: [missing_role]');
});
