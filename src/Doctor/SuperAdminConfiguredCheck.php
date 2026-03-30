<?php

declare(strict_types=1);

namespace YezzMedia\Access\Doctor;

use Throwable;
use YezzMedia\Access\Support\SuperAdminGateBootstrapper;
use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;

/**
 * Reports whether the configured super-admin bootstrap can be initialized safely.
 */
final readonly class SuperAdminConfiguredCheck implements DoctorCheck
{
    private const KEY = 'super_admin_configured';

    private const PACKAGE = 'yezzmedia/laravel-access';

    public function __construct(
        private SuperAdminGateBootstrapper $bootstrapper,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function package(): string
    {
        return self::PACKAGE;
    }

    public function run(): DoctorResult
    {
        if (! (bool) config('access.super_admin.enabled', false)) {
            return $this->result(
                status: 'skipped',
                message: 'Super-admin bootstrap is disabled.',
                isBlocking: false,
            );
        }

        try {
            $roleName = $this->bootstrapper->configuredRoleName();
        } catch (Throwable $exception) {
            return $this->result(
                status: 'failed',
                message: 'Super-admin bootstrap configuration is invalid.',
                isBlocking: true,
                context: [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ],
            );
        }

        return $this->result(
            status: 'passed',
            message: 'Super-admin bootstrap configuration is valid.',
            isBlocking: false,
            context: ['role_name' => $roleName],
        );
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function result(string $status, string $message, bool $isBlocking, ?array $context = null): DoctorResult
    {
        return new DoctorResult(
            key: $this->key(),
            package: $this->package(),
            status: $status,
            message: $message,
            isBlocking: $isBlocking,
            context: $context,
        );
    }
}
