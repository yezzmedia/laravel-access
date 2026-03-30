<?php

declare(strict_types=1);

use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

use function Pest\Laravel\artisan;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerRoleSeedPermissionPackage(string $name, array $permissions): void
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
                description: 'Access role seeding test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

it('shows a helpful message when no role definitions are available for seeding', function (): void {
    $command = artisan('website:seed-roles');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:seed-roles.');
    }

    $command
        ->expectsOutputToContain('No role definitions available for seeding.')
        ->assertSuccessful();
});

it('seeds roles from default role hints when explicit role hint seeding is enabled', function (): void {
    config()->set('access.roles.apply_default_role_hints', true);

    registerRoleSeedPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages', defaultRoleHints: ['content_editor']),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages', defaultRoleHints: ['content_editor', 'content_archivist']),
    ]);

    app(PermissionSyncService::class)->sync();

    $command = artisan('website:seed-roles');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:seed-roles.');
    }

    $command
        ->expectsOutputToContain('Roles seeded.')
        ->expectsOutputToContain('Roles: content_archivist, content_editor')
        ->assertSuccessful();
});

it('fails instead of hiding missing permission synchronization during role seeding', function (): void {
    config()->set('access.roles.apply_default_role_hints', true);

    registerRoleSeedPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages', defaultRoleHints: ['content_editor']),
    ]);

    $command = artisan('website:seed-roles');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:seed-roles.');
    }

    $command
        ->expectsOutputToContain('Role synchronization requires existing permissions.')
        ->assertFailed();
});
