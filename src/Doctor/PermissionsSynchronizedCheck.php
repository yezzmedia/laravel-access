<?php

declare(strict_types=1);

namespace YezzMedia\Access\Doctor;

use Throwable;
use YezzMedia\Access\Support\PermissionMap;
use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\Foundation\Registry\PermissionRegistry;

/**
 * Reports whether declared foundation permissions are present in the access store.
 */
final readonly class PermissionsSynchronizedCheck implements DoctorCheck
{
    private const KEY = 'permissions_synchronized';

    private const PACKAGE = 'yezzmedia/laravel-access';

    public function __construct(
        private PermissionRegistry $permissions,
        private PermissionMap $permissionMap,
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
        try {
            /** @var list<string> $declaredPermissions */
            $declaredPermissions = $this->permissions->all()
                ->pluck('name')
                ->sort()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            return $this->result(
                status: 'failed',
                message: 'Foundation permission definitions could not be read.',
                isBlocking: true,
                context: $this->exceptionContext($exception),
            );
        }

        if ($declaredPermissions === []) {
            return $this->result(
                status: 'skipped',
                message: 'No foundation permission definitions are currently registered.',
                isBlocking: false,
            );
        }

        try {
            $persistedPermissions = $this->permissionMap->all();
        } catch (Throwable $exception) {
            return $this->result(
                status: 'failed',
                message: 'The persistent permission store could not be read.',
                isBlocking: true,
                context: $this->exceptionContext($exception),
            );
        }

        $missingPermissions = array_values(array_diff($declaredPermissions, $persistedPermissions));
        $extraPermissions = array_values(array_diff($persistedPermissions, $declaredPermissions));
        $context = [
            'declared_permissions' => $declaredPermissions,
            'persisted_permissions' => $persistedPermissions,
            'missing_permissions' => $missingPermissions,
            'extra_permissions' => $extraPermissions,
        ];

        if ($missingPermissions !== []) {
            return $this->result(
                status: 'failed',
                message: 'Declared permissions are missing from the persistent permission store.',
                isBlocking: true,
                context: $context,
            );
        }

        if ($extraPermissions !== []) {
            return $this->result(
                status: 'warning',
                message: 'The persistent permission store contains undeclared permissions.',
                isBlocking: false,
                context: $context,
            );
        }

        return $this->result(
            status: 'passed',
            message: 'Declared permissions are synchronized with the persistent permission store.',
            isBlocking: false,
            context: $context,
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

    /**
     * @return array{exception: class-string<Throwable>, message: string}
     */
    private function exceptionContext(Throwable $exception): array
    {
        return [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ];
    }
}
