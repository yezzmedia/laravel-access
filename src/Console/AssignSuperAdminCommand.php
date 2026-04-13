<?php

declare(strict_types=1);

namespace YezzMedia\Access\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use YezzMedia\Access\Support\SuperAdminSafetyGuard;
use YezzMedia\Access\Support\UserRoleManager;

final class AssignSuperAdminCommand extends Command
{
    protected $signature = 'website:assign-super-admin
                            {email : The user email that should receive the configured super-admin role.}';

    protected $description = 'Assign the configured super-admin role to a user.';

    public function __construct(
        private readonly UserRoleManager $userRoleManager,
        private readonly SuperAdminSafetyGuard $superAdminSafety,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $roleName = $this->superAdminSafety->configuredRoleName();

        if (! $this->superAdminSafety->enabled() || $roleName === null) {
            $this->components->error('Super-admin bootstrap is not configured. Set access.super_admin.enabled=true and access.super_admin.role_name.');

            return self::FAILURE;
        }

        $user = $this->resolveUser((string) $this->argument('email'));

        if (! $user instanceof Authenticatable) {
            $this->components->error(sprintf('No user found for email [%s].', (string) $this->argument('email')));

            return self::FAILURE;
        }

        try {
            $this->userRoleManager->assignRole($user, $roleName);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Assigned super-admin role [%s] to [%s].',
            $roleName,
            (string) $this->argument('email'),
        ));

        return self::SUCCESS;
    }

    private function resolveUser(string $email): ?Authenticatable
    {
        $modelClass = config('auth.providers.users.model');

        if (! is_string($modelClass) || $modelClass === '' || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('The users auth provider model is not configured correctly.');
        }

        /** @var Model|null $user */
        $user = $modelClass::query()
            ->where('email', $email)
            ->first();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        return $user;
    }
}
