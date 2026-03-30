<?php

declare(strict_types=1);

namespace YezzMedia\Access\Tests\Concerns;

use PHPUnit\Framework\Assert;
use Spatie\Permission\Models\Permission;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Support\PermissionSyncService;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;

trait InteractsWithPermissionSync
{
    /**
     * @param  array<int, PermissionDefinition>  $permissions
     */
    public function registerPermissionDefinitions(string $package, array $permissions): void
    {
        $platformPackage = new class implements DefinesPermissions, PlatformPackage
        {
            public string $package;

            /**
             * @var array<int, PermissionDefinition>
             */
            public array $permissions = [];

            public function metadata(): PackageMetadata
            {
                return new PackageMetadata(
                    name: $this->package,
                    vendor: 'yezzmedia',
                    description: 'Access test permission package.',
                    packageClass: self::class,
                );
            }

            public function permissionDefinitions(): array
            {
                return $this->permissions;
            }
        };

        $platformPackage->package = $package;
        $platformPackage->permissions = $permissions;

        app(PlatformPackageRegistrar::class)->register($platformPackage);
    }

    public function syncPermissions(): PermissionsSynchronized
    {
        return app(PermissionSyncService::class)->sync();
    }

    public function syncPermissionsForPackage(string $package): PermissionsSynchronized
    {
        return app(PermissionSyncService::class)->syncPackage($package);
    }

    /**
     * @param  list<string>  $expectedPermissionNames
     */
    public function assertSyncedPermissions(array $expectedPermissionNames): void
    {
        sort($expectedPermissionNames);

        /** @var list<string> $persistedPermissionNames */
        $persistedPermissionNames = $this->permissionModel()::query()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        Assert::assertSame($expectedPermissionNames, $persistedPermissionNames);
    }

    /**
     * @return class-string<Permission>
     */
    private function permissionModel(): string
    {
        $model = config('permission.models.permission', Permission::class);

        if (! is_string($model) || $model === '' || ! is_a($model, Permission::class, true)) {
            return Permission::class;
        }

        return $model;
    }
}
