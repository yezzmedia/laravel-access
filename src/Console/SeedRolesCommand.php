<?php

declare(strict_types=1);

namespace YezzMedia\Access\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;
use YezzMedia\Access\Data\RoleDefinition;
use YezzMedia\Access\Support\RoleManager;
use YezzMedia\Foundation\Data\PermissionDefinition;
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
            $roles = $this->seedableRoles();

            if ($roles === []) {
                $this->info('No role definitions available for seeding.');

                return self::SUCCESS;
            }

            $this->roles->syncRoles($roles);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Roles seeded.');
        $this->line(sprintf('Roles: %s', implode(', ', array_map(
            static fn (RoleDefinition $role): string => $role->name,
            $roles,
        ))));

        return self::SUCCESS;
    }

    /**
     * @return array<int, RoleDefinition>
     */
    private function seedableRoles(): array
    {
        if (! (bool) config('access.roles.apply_default_role_hints', false)) {
            return [];
        }

        $roles = [];

        foreach ($this->permissions->all() as $permission) {
            foreach ($this->normalizedRoleHints($permission) as $roleName) {
                $roles[$roleName] ??= [];
                $roles[$roleName][] = $permission->name;
            }
        }

        ksort($roles);

        return array_map(
            function (string $roleName, array $permissionNames): RoleDefinition {
                $permissionNames = array_values(array_unique($permissionNames));
                sort($permissionNames);

                return new RoleDefinition(
                    name: $roleName,
                    label: (string) Str::of($roleName)->replace('_', ' ')->title(),
                    description: sprintf('Seeded access role for [%s].', $roleName),
                    permissionNames: $permissionNames,
                );
            },
            array_keys($roles),
            array_values($roles),
        );
    }

    /**
     * @return list<string>
     */
    private function normalizedRoleHints(PermissionDefinition $permission): array
    {
        $roleHints = $permission->defaultRoleHints ?? [];

        if ($roleHints === []) {
            return [];
        }

        $normalizedRoleHints = array_values(array_filter(array_map(
            static fn (string $roleName): string => trim($roleName),
            $roleHints,
        ), static fn (string $roleName): bool => $roleName !== ''));

        $normalizedRoleHints = array_values(array_unique($normalizedRoleHints));
        sort($normalizedRoleHints);

        return $normalizedRoleHints;
    }
}
