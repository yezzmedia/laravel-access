<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use YezzMedia\Access\Doctor\PermissionsSynchronizedCheck;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

/**
 * @param  array<int, PermissionDefinition>  $permissions
 */
function registerDoctorPermissionPackage(string $name, array $permissions): void
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
                description: 'Access doctor permission test package.',
                packageClass: self::class,
            );
        }

        public function permissionDefinitions(): array
        {
            return $this->permissions;
        }
    });
}

it('skips when no foundation permission definitions are registered', function (): void {
    $result = app(PermissionsSynchronizedCheck::class)->run();

    expect($result->key)->toBe('permissions_synchronized')
        ->and($result->package)->toBe('yezzmedia/laravel-access')
        ->and($result->status)->toBe('skipped')
        ->and($result->isBlocking)->toBeFalse();
});

it('passes when all declared permissions exist in persistent storage', function (): void {
    registerDoctorPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    app(PermissionSyncService::class)->sync();

    $result = app(PermissionsSynchronizedCheck::class)->run();

    expect($result->status)->toBe('passed')
        ->and($result->isBlocking)->toBeFalse()
        ->and($result->context)->toMatchArray([
            'missing_permissions' => [],
            'extra_permissions' => [],
        ]);
});

it('warns when persistent permissions contain undeclared entries', function (): void {
    registerDoctorPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);

    app(PermissionSyncService::class)->sync();

    Permission::query()->create([
        'name' => 'legacy.permission',
        'guard_name' => (string) config('auth.defaults.guard', 'web'),
    ]);

    $result = app(PermissionsSynchronizedCheck::class)->run();

    expect($result->status)->toBe('warning')
        ->and($result->isBlocking)->toBeFalse()
        ->and($result->context)->toMatchArray([
            'missing_permissions' => [],
            'extra_permissions' => ['legacy.permission'],
        ]);
});

it('fails when declared permissions are missing from persistent storage', function (): void {
    registerDoctorPermissionPackage('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $result = app(PermissionsSynchronizedCheck::class)->run();

    expect($result->status)->toBe('failed')
        ->and($result->isBlocking)->toBeTrue()
        ->and($result->context)->toMatchArray([
            'missing_permissions' => ['content.pages.archive', 'content.pages.publish'],
        ]);
});
