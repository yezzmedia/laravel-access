<?php

declare(strict_types=1);

use Tests\Fixtures\TestUser;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

use function Pest\Laravel\artisan;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerSuperAdminCommandPermissionPackage(string $name, array $permissions): void
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
                description: 'Access super-admin command test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

function prepareConfiguredSuperAdminRole(string $roleName = 'super_admin'): void
{
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', $roleName);
    config()->set('auth.providers.users.model', TestUser::class);

    registerSuperAdminCommandPermissionPackage('yezzmedia/laravel-content', [
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

it('assigns the configured super-admin role to the requested user', function (): void {
    prepareConfiguredSuperAdminRole();
    $email = 'bootstrap-admin@example.com';

    TestUser::query()->create([
        'email' => $email,
        'name' => 'Admin',
    ]);

    $command = artisan('website:assign-super-admin', ['email' => $email]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:assign-super-admin.');
    }

    $command
        ->expectsOutputToContain(sprintf('Assigned super-admin role [super_admin] to [%s].', $email))
        ->assertSuccessful();
});

it('fails clearly when the super-admin bootstrap is not configured', function (): void {
    config()->set('access.super_admin.enabled', false);
    config()->set('access.super_admin.role_name', null);

    $command = artisan('website:assign-super-admin', ['email' => 'admin@example.com']);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:assign-super-admin.');
    }

    $command
        ->expectsOutputToContain('Super-admin bootstrap is not configured.')
        ->assertFailed();
});

it('fails clearly when the requested user does not exist', function (): void {
    prepareConfiguredSuperAdminRole();
    $email = 'missing-bootstrap-admin@example.com';

    $command = artisan('website:assign-super-admin', ['email' => $email]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:assign-super-admin.');
    }

    $command
        ->expectsOutputToContain(sprintf('No user found for email [%s].', $email))
        ->assertFailed();
});

it('fails clearly when the configured super-admin role does not exist yet', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');
    config()->set('auth.providers.users.model', TestUser::class);
    $email = 'missing-role-bootstrap-admin@example.com';

    TestUser::query()->create([
        'email' => $email,
        'name' => 'Admin',
    ]);

    $command = artisan('website:assign-super-admin', ['email' => $email]);

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:assign-super-admin.');
    }

    $command
        ->expectsOutputToContain('Missing: [super_admin]')
        ->assertFailed();
});
