<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionServiceProvider;
use YezzMedia\Access\AccessServiceProvider;
use YezzMedia\Access\Events\PermissionsSynchronized;

/**
 * Owns host-side setup checks and actions for the persistent permission store.
 */
class PermissionStoreSetup
{
    public function __construct(
        private readonly PermissionSyncService $permissions,
        private readonly Migrator $migrator,
    ) {}

    public function configPublished(): bool
    {
        return File::exists(config_path('permission.php'));
    }

    public function publishConfig(bool $force = false): void
    {
        $arguments = [
            '--provider' => PermissionServiceProvider::class,
            '--tag' => 'permission-config',
        ];

        if ($force) {
            $arguments['--force'] = true;
        }

        Artisan::call('vendor:publish', $arguments);
    }

    public function accessConfigPublished(): bool
    {
        return File::exists(config_path('access.php'));
    }

    public function publishAccessConfig(bool $force = false): void
    {
        $arguments = [
            '--provider' => AccessServiceProvider::class,
        ];

        if ($force) {
            $arguments['--force'] = true;
        }

        Artisan::call('vendor:publish', $arguments);
    }

    public function configureAuditDriver(string $driver): void
    {
        if (! $this->accessConfigPublished()) {
            $this->publishAccessConfig();
        }

        $path = config_path('access.php');
        $contents = File::get($path);

        $updated = preg_replace_callback(
            "/('driver'\\s*=>\\s*)(null|'[^']*')/",
            static fn (array $matches) => $matches[1]."'{$driver}'",
            $contents,
            1,
        );

        if (! is_string($updated) || $updated === $contents) {
            throw new RuntimeException('The access config could not be updated to enable audit persistence.');
        }

        File::put($path, $updated);
        config()->set('access.audit.driver', $driver);
    }

    public function migrationsPublished(): bool
    {
        return $this->publishedMigrationPaths() !== [];
    }

    public function publishMigrations(): void
    {
        Artisan::call('vendor:publish', [
            '--provider' => PermissionServiceProvider::class,
            '--tag' => 'permission-migrations',
        ]);
    }

    public function permissionStoreReady(): bool
    {
        foreach ($this->requiredTables() as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function hasPendingPublishedMigrations(): bool
    {
        $publishedMigrationPaths = $this->publishedMigrationPaths();

        if ($publishedMigrationPaths === []) {
            return false;
        }

        if (! $this->migrator->repositoryExists()) {
            return true;
        }

        $publishedMigrations = $this->migrator->getMigrationFiles($publishedMigrationPaths);
        $ranMigrations = $this->migrator->getRepository()->getRan();

        foreach ($publishedMigrations as $migrationPath) {
            if (! in_array($this->migrator->getMigrationName($migrationPath), $ranMigrations, true)) {
                return true;
            }
        }

        return false;
    }

    public function migratePermissionStore(): void
    {
        if (! $this->migrationsPublished()) {
            throw new RuntimeException('The Spatie permission migrations must be published before migrations can run.');
        }

        Artisan::call('migrate', [
            '--force' => true,
        ]);
    }

    public function synchronizePermissions(): PermissionsSynchronized
    {
        return $this->permissions->sync();
    }

    /**
     * @return array<int, string>
     */
    public function publishedMigrationPaths(): array
    {
        $migrationsPath = database_path('migrations');

        if (! File::isDirectory($migrationsPath)) {
            return [];
        }

        $published = [];

        foreach (File::glob($migrationsPath.DIRECTORY_SEPARATOR.'*.php') as $path) {
            if (in_array($this->normalizeMigrationName(basename($path)), $this->publishableMigrationNames(), true)) {
                $published[] = $path;
            }
        }

        return $published;
    }

    public function hasMissingPublishedMigrations(): bool
    {
        $publishedNames = array_map(
            fn (string $path): string => $this->normalizeMigrationName(basename($path)),
            $this->publishedMigrationPaths(),
        );

        return array_diff($this->publishableMigrationNames(), $publishedNames) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function requiredTables(): array
    {
        return array_filter([
            $this->tableName('permissions', 'permissions'),
            $this->tableName('roles', 'roles'),
            $this->tableName('model_has_permissions', 'model_has_permissions'),
            $this->tableName('model_has_roles', 'model_has_roles'),
            $this->tableName('role_has_permissions', 'role_has_permissions'),
        ]);
    }

    private function tableName(string $key, string $fallback): string
    {
        $table = config(sprintf('permission.table_names.%s', $key));

        return is_string($table) && $table !== '' ? $table : $fallback;
    }

    /**
     * @return array<int, string>
     */
    private function publishableMigrationNames(): array
    {
        $paths = PermissionServiceProvider::pathsToPublish(PermissionServiceProvider::class, 'permission-migrations');

        return array_values(array_unique(array_map(
            fn (string $sourcePath): string => $this->normalizeMigrationName(basename($sourcePath)),
            array_keys($paths),
        )));
    }

    private function normalizeMigrationName(string $filename): string
    {
        $filename = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename) ?? $filename;

        return str_replace('.stub', '', $filename);
    }
}
