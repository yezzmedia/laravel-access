<?php

declare(strict_types=1);

use Tests\Fixtures\TestUser;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Access\Support\SuperAdminSafetyGuard;
use YezzMedia\Access\Support\UserRoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerSafetyPermissionPackage(string $name, array $permissions): void
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
                description: 'Access super-admin safety test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

function prepareSafetyRole(string $roleName): void
{
    registerSafetyPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    app(PermissionSyncService::class)->syncPackage('yezzmedia/laravel-content');
    app(RoleManager::class)->syncRole(new RoleDefinition(
        name: $roleName,
        label: 'Super admin',
        description: 'Has privileged platform access.',
        permissionNames: ['content.pages.publish'],
    ));
}

function configureSafetyUserProvider(): void
{
    config()->set('auth.defaults.guard', 'web');
    config()->set('auth.defaults.provider', 'users');
    config()->set('auth.providers.users', [
        'driver' => 'eloquent',
        'model' => TestUser::class,
    ]);
}

it('reports the current super-admin posture from persisted assignments', function (): void {
    configureSafetyUserProvider();
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSafetyRole('super_admin');

    $firstOperator = TestUser::query()->create(['name' => 'Primary operator']);
    $secondOperator = TestUser::query()->create(['name' => 'Secondary operator']);

    $firstOperator->assignRole('super_admin');
    $secondOperator->assignRole('super_admin');

    expect(app(SuperAdminSafetyGuard::class)->enabled())->toBeTrue()
        ->and(app(SuperAdminSafetyGuard::class)->configuredRoleName())->toBe('super_admin')
        ->and(app(SuperAdminSafetyGuard::class)->currentQualifiedOperatorCount())->toBe(2)
        ->and(app(SuperAdminSafetyGuard::class)->minimumOperators())->toBe(2);
});

it('rejects super-admin role removals that would drop below the minimum operator count', function (): void {
    configureSafetyUserProvider();
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSafetyRole('super_admin');

    $firstOperator = TestUser::query()->create(['name' => 'Primary operator']);
    $secondOperator = TestUser::query()->create(['name' => 'Secondary operator']);

    $firstOperator->assignRole('super_admin');
    $secondOperator->assignRole('super_admin');

    expect(fn () => app(UserRoleManager::class)->removeRole($secondOperator, 'super_admin', $firstOperator))
        ->toThrow(InvalidArgumentException::class, 'below the minimum [2]');

    $secondOperator->refresh();

    expect($secondOperator->hasRole('super_admin'))->toBeTrue();
});

it('allows super-admin role removals when the minimum operator count stays satisfied', function (): void {
    configureSafetyUserProvider();
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    prepareSafetyRole('super_admin');

    $firstOperator = TestUser::query()->create(['name' => 'Primary operator']);
    $secondOperator = TestUser::query()->create(['name' => 'Secondary operator']);
    $thirdOperator = TestUser::query()->create(['name' => 'Reserve operator']);

    $firstOperator->assignRole('super_admin');
    $secondOperator->assignRole('super_admin');
    $thirdOperator->assignRole('super_admin');

    app(UserRoleManager::class)->removeRole($thirdOperator, 'super_admin', $firstOperator);

    $thirdOperator->refresh();

    expect($thirdOperator->hasRole('super_admin'))->toBeFalse()
        ->and(app(SuperAdminSafetyGuard::class)->currentQualifiedOperatorCount())->toBe(2);
});
