<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Registry\PermissionRegistry;

/**
 * Synchronizes foundation permission definitions into the persistent access runtime.
 */
final class PermissionSyncService
{
    public function __construct(
        private readonly PermissionRegistry $permissions,
        private readonly PermissionRegistrar $permissionRegistrar,
        private readonly PermissionCacheManager $permissionCache,
        private readonly Dispatcher $events,
    ) {}

    public function sync(): PermissionsSynchronized
    {
        return $this->synchronize($this->permissions->all()->all());
    }

    public function syncPackage(string $package): PermissionsSynchronized
    {
        return $this->synchronize($this->permissions->forPackage($package)->all(), $package);
    }

    /**
     * @param  array<int, PermissionDefinition>  $definitions
     */
    private function synchronize(array $definitions, ?string $targetPackage = null): PermissionsSynchronized
    {
        usort($definitions, static fn (PermissionDefinition $left, PermissionDefinition $right): int => [$left->package, $left->name] <=> [$right->package, $right->name]);

        $createdCount = 0;
        $unchangedCount = 0;
        $guardName = (string) config('auth.defaults.guard', 'web');
        $permissionModel = $this->permissionModel();
        $packageNames = array_values(array_unique(array_map(
            static fn (PermissionDefinition $definition): string => $definition->package,
            $definitions,
        )));

        if ($packageNames === [] && $targetPackage !== null) {
            $packageNames = [$targetPackage];
        }

        foreach ($definitions as $definition) {
            /** @var Model $permission */
            $permission = $permissionModel::query()->firstOrCreate([
                'name' => $definition->name,
                'guard_name' => $guardName,
            ]);

            if ($permission->wasRecentlyCreated) {
                $createdCount++;

                continue;
            }

            $unchangedCount++;
        }

        $this->permissionRegistrar->forgetCachedPermissions();
        $this->permissionCache->forgetAll();

        $result = new PermissionsSynchronized(
            packageNames: $packageNames,
            createdCount: $createdCount,
            updatedCount: 0,
            unchangedCount: $unchangedCount,
            removedCount: 0,
        );

        $this->events->dispatch($result);

        return $result;
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
