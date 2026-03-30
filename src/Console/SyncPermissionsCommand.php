<?php

declare(strict_types=1);

namespace YezzMedia\Access\Console;

use Illuminate\Console\Command;
use Throwable;
use YezzMedia\Access\Support\PermissionSyncService;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'website:sync-permissions';

    protected $description = 'Synchronize declared foundation permissions into the access runtime.';

    public function __construct(
        private readonly PermissionSyncService $permissions,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->permissions->sync();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $packages = $result->packageNames === [] ? 'none' : implode(', ', $result->packageNames);

        $this->info('Permissions synchronized.');
        $this->line(sprintf('Packages: %s', $packages));
        $this->line(sprintf(
            'Created: %d | Updated: %d | Unchanged: %d | Removed: %d',
            $result->createdCount,
            $result->updatedCount,
            $result->unchangedCount,
            $result->removedCount,
        ));

        return self::SUCCESS;
    }
}
