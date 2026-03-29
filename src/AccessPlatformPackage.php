<?php

declare(strict_types=1);

namespace YezzMedia\Access;

use YezzMedia\Foundation\Contracts\DefinesAuditEvents;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Contracts\ProvidesDoctorChecks;
use YezzMedia\Foundation\Contracts\ProvidesOpsModules;
use YezzMedia\Foundation\Data\AuditEventDefinition;
use YezzMedia\Foundation\Data\OpsModuleDefinition;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Doctor\DoctorCheck;

/**
 * Describes the access package surface that foundation should register.
 */
final class AccessPlatformPackage implements DefinesAuditEvents, DefinesPermissions, PlatformPackage, ProvidesDoctorChecks, ProvidesOpsModules
{
    public function metadata(): PackageMetadata
    {
        return new PackageMetadata(
            name: 'yezzmedia/laravel-access',
            vendor: 'yezzmedia',
            description: 'Persistent roles and permissions package for the Yezz Media Laravel website platform.',
            packageClass: self::class,
        );
    }

    /**
     * @return array<int, PermissionDefinition>
     */
    public function permissionDefinitions(): array
    {
        return [];
    }

    /**
     * @return array<int, AuditEventDefinition>
     */
    public function auditEventDefinitions(): array
    {
        return [
            new AuditEventDefinition(
                key: 'access.permissions.synchronized',
                package: 'yezzmedia/laravel-access',
                action: 'synchronized',
                subjectType: 'permission_set',
                description: 'Access permissions were synchronized.',
                severity: 'info',
                contextKeys: ['package_names', 'created_count', 'updated_count', 'unchanged_count', 'removed_count'],
            ),
            new AuditEventDefinition(
                key: 'access.roles.synchronized',
                package: 'yezzmedia/laravel-access',
                action: 'synchronized',
                subjectType: 'role_set',
                description: 'Access roles were synchronized.',
                severity: 'info',
                contextKeys: ['role_names', 'created_count', 'updated_count', 'unchanged_count'],
            ),
            new AuditEventDefinition(
                key: 'access.user_role.assigned',
                package: 'yezzmedia/laravel-access',
                action: 'assigned',
                subjectType: 'user_role_assignment',
                description: 'A role was assigned to a user.',
                severity: 'warning',
                contextKeys: ['user_id', 'role_name', 'actor_id', 'guard_name'],
            ),
            new AuditEventDefinition(
                key: 'access.user_role.removed',
                package: 'yezzmedia/laravel-access',
                action: 'removed',
                subjectType: 'user_role_assignment',
                description: 'A role was removed from a user.',
                severity: 'warning',
                contextKeys: ['user_id', 'role_name', 'actor_id', 'guard_name'],
            ),
        ];
    }

    /**
     * @return array<int, DoctorCheck>
     */
    public function doctorChecks(): array
    {
        return [];
    }

    /**
     * @return array<int, OpsModuleDefinition>
     */
    public function opsModuleDefinitions(): array
    {
        return [];
    }
}
