<?php

declare(strict_types=1);

namespace YezzMedia\Access\Doctor;

use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;

/**
 * Reports whether access audit writes are configured for persistence.
 */
final readonly class AccessAuditConfiguredCheck implements DoctorCheck
{
    private const KEY = 'audit_configured';

    private const PACKAGE = 'yezzmedia/laravel-access';

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
        $opsAuditProvider = config('ops.integrations.audit.provider');

        if (! is_string($opsAuditProvider) || $opsAuditProvider === '' || ! class_exists($opsAuditProvider)) {
            return $this->result(
                status: 'skipped',
                message: 'Ops audit persistence is not configured in this environment.',
            );
        }

        $driver = config('access.audit.driver');

        if ($driver === 'activitylog') {
            return $this->result(
                status: 'passed',
                message: 'Access audit persistence is configured for activitylog.',
                context: [
                    'ops_audit_provider' => $opsAuditProvider,
                    'access_audit_driver' => $driver,
                ],
            );
        }

        if ($driver === null) {
            return $this->result(
                status: 'warning',
                message: 'Access audit events are not persisted because access.audit.driver is not configured.',
                context: [
                    'ops_audit_provider' => $opsAuditProvider,
                    'access_audit_driver' => $driver,
                ],
            );
        }

        return $this->result(
            status: 'failed',
            message: sprintf('Access audit driver [%s] is not supported for ops audit persistence.', (string) $driver),
            isBlocking: true,
            context: [
                'ops_audit_provider' => $opsAuditProvider,
                'access_audit_driver' => $driver,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function result(string $status, string $message, bool $isBlocking = false, ?array $context = null): DoctorResult
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
