<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * Owns the centralized super-admin authorization bypass for the access runtime.
 */
final class SuperAdminGateBootstrapper
{
    private bool $bootstrapped = false;

    public function __construct(
        private readonly Gate $gate,
        private readonly AccessSecurityVisibilityReporter $securityVisibility,
    ) {}

    public function bootstrap(): void
    {
        if (! (bool) config('access.super_admin.enabled', false) || $this->bootstrapped) {
            return;
        }

        $roleName = $this->configuredRoleName();

        $this->securityVisibility->submitPrivilegedMfaRequest(
            roleName: $roleName,
            channel: 'gate_bootstrap',
            source: self::class,
            actorReference: 'system',
        );

        $this->gate->before(function (Authenticatable $user, string $ability) use ($roleName): ?bool {
            if (! method_exists($user, 'hasRole')) {
                return null;
            }

            if (! $user->hasRole($roleName)) {
                return null;
            }

            $this->securityVisibility->recordPrivilegedMfaRuntimeUsage(
                roleName: $roleName,
                ability: $ability,
                user: $user,
                source: self::class,
                channel: 'gate_before',
            );

            return true;
        });

        $this->bootstrapped = true;
    }

    public function configuredRoleName(): string
    {
        $roleName = config('access.super_admin.role_name');

        if (! is_string($roleName) || trim($roleName) === '') {
            throw new InvalidArgumentException('Super-admin bootstrap requires a non-empty configured role name.');
        }

        return trim($roleName);
    }
}
