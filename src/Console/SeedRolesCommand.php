<?php

declare(strict_types=1);

namespace YezzMedia\Access\Console;

use Illuminate\Console\Command;
use Throwable;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Registry\PermissionRegistry;

final class SeedRolesCommand extends Command
{
    protected $signature = 'website:seed-roles';

    protected $description = 'Seed access roles from explicit role hints.';

    public function __construct(
        private readonly PermissionRegistry $permissions,
        private readonly RoleManager $roles,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $roles = $this->roles->syncRolesFromPermissionHints($this->permissions->all());

            if ($roles === []) {
                $this->info('No role definitions available for seeding.');

                return self::SUCCESS;
            }

        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Roles seeded.');
        $this->line(sprintf('Roles: %s', implode(', ', $roles)));

        return self::SUCCESS;
    }
}
