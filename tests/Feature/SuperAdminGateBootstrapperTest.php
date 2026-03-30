<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Tests\Fixtures\TestUser;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Access\Support\SuperAdminGateBootstrapper;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerSuperAdminPermissionPackage(string $name, array $permissions): void
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
                description: 'Access super-admin bootstrap test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

function prepareSuperAdminRole(string $roleName): void
{
    registerSuperAdminPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    app(PermissionSyncService::class)->syncPackage('yezzmedia/laravel-content');
    app(RoleManager::class)->syncRole(new RoleDefinition(
        name: $roleName,
        label: 'Super admin',
        description: 'Has full access bypass.',
        permissionNames: ['content.pages.publish'],
    ));
}

it('does not grant a bypass when super admin bootstrap is disabled', function (): void {
    config()->set('access.super_admin.enabled', false);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSuperAdminRole('super_admin');

    $user = TestUser::query()->create(['name' => 'Admin']);
    $user->assignRole('super_admin');

    Gate::define('publish-content', static fn (): bool => false);

    app(SuperAdminGateBootstrapper::class)->bootstrap();

    expect(Gate::forUser($user)->allows('publish-content'))->toBeFalse();
});

it('fails fast when super admin bootstrap is enabled without a role name', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', '');

    expect(fn () => app(SuperAdminGateBootstrapper::class)->bootstrap())
        ->toThrow(InvalidArgumentException::class, 'requires a non-empty configured role name');
});

it('grants a gate bypass to users with the configured super admin role', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSuperAdminRole('super_admin');

    $superAdmin = TestUser::query()->create(['name' => 'Admin']);
    $normalUser = TestUser::query()->create(['name' => 'Editor']);

    $superAdmin->assignRole('super_admin');

    Gate::define('publish-content', static fn (): bool => false);

    app(SuperAdminGateBootstrapper::class)->bootstrap();

    expect(Gate::forUser($superAdmin)->allows('publish-content'))->toBeTrue()
        ->and(Gate::forUser($normalUser)->allows('publish-content'))->toBeFalse();
});

it('is idempotent when bootstrap is called multiple times', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSuperAdminRole('super_admin');

    $superAdmin = TestUser::query()->create(['name' => 'Admin']);
    $superAdmin->assignRole('super_admin');

    Gate::define('publish-content', static fn (): bool => false);

    $bootstrapper = app(SuperAdminGateBootstrapper::class);

    $bootstrapper->bootstrap();
    $bootstrapper->bootstrap();

    expect(Gate::forUser($superAdmin)->allows('publish-content'))->toBeTrue();
});
