<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Support\PermissionStoreSetup;

class FakePermissionStoreSetup extends PermissionStoreSetup
{
    /**
     * @var array<int, string>
     */
    public array $calls = [];

    public bool $configWasForced = false;

    public function __construct(
        public bool $hasPublishedConfig = false,
        public bool $hasPublishedMigrations = false,
        public bool $hasReadyStore = false,
        public bool $migrationSucceeds = true,
        public bool $hasMissingPublishedMigrations = false,
        public bool $hasPendingPublishedMigrations = false,
    ) {}

    public function configPublished(): bool
    {
        return $this->hasPublishedConfig;
    }

    public function publishConfig(bool $force = false): void
    {
        $this->calls[] = 'publish_permission_config';
        $this->hasPublishedConfig = true;
        $this->configWasForced = $force;
    }

    public function migrationsPublished(): bool
    {
        return $this->hasPublishedMigrations;
    }

    public function publishMigrations(): void
    {
        $this->calls[] = 'publish_permission_migrations';
        $this->hasPublishedMigrations = true;
    }

    public function permissionStoreReady(): bool
    {
        return $this->hasReadyStore;
    }

    public function hasMissingPublishedMigrations(): bool
    {
        return $this->hasMissingPublishedMigrations;
    }

    public function hasPendingPublishedMigrations(): bool
    {
        return $this->hasPendingPublishedMigrations;
    }

    public function migratePermissionStore(): void
    {
        $this->calls[] = 'ensure_permission_store_ready';

        if ($this->migrationSucceeds) {
            $this->hasReadyStore = true;
            $this->hasPendingPublishedMigrations = false;
        }
    }

    public function synchronizePermissions(): PermissionsSynchronized
    {
        $this->calls[] = 'sync_permissions';

        return new PermissionsSynchronized(
            packageNames: [],
            createdCount: 0,
            updatedCount: 0,
            unchangedCount: 0,
            removedCount: 0,
        );
    }
}
