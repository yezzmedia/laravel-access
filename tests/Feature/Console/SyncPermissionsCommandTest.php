<?php

declare(strict_types=1);

use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

use function Pest\Laravel\artisan;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerCommandPermissionPackage(string $name, array $permissions): void
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
                description: 'Access command permission test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

it('synchronizes declared permissions through the command', function (): void {
    registerCommandPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $command = artisan('website:sync-permissions');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:sync-permissions.');
    }

    $command
        ->expectsOutputToContain('Permissions synchronized.')
        ->expectsOutputToContain('Packages: yezzmedia/laravel-content')
        ->expectsOutputToContain('Created: 2 | Updated: 0 | Unchanged: 0 | Removed: 0')
        ->assertSuccessful();
});

it('reports a successful no-op sync when no permissions are registered', function (): void {
    $command = artisan('website:sync-permissions');

    if (is_int($command)) {
        throw new RuntimeException('Expected pending command for website:sync-permissions.');
    }

    $command
        ->expectsOutputToContain('Permissions synchronized.')
        ->expectsOutputToContain('Packages: none')
        ->expectsOutputToContain('Created: 0 | Updated: 0 | Unchanged: 0 | Removed: 0')
        ->assertSuccessful();
});
