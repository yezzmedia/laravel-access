<?php

declare(strict_types=1);

namespace YezzMedia\Access;

use YezzMedia\Access\Doctor\AccessAuditConfiguredCheck;
use YezzMedia\Access\Doctor\PermissionsSynchronizedCheck;
use YezzMedia\Access\Doctor\SuperAdminConfiguredCheck;
use YezzMedia\Access\Install\ConfigureAccessAuditInstallStep;
use YezzMedia\Access\Install\EnsurePermissionStoreReadyInstallStep;
use YezzMedia\Access\Install\PublishPermissionConfigInstallStep;
use YezzMedia\Access\Install\PublishPermissionMigrationsInstallStep;
use YezzMedia\Access\Install\SeedRolesFromPermissionHintsInstallStep;
use YezzMedia\Access\Install\SyncPermissionsInstallStep;
use YezzMedia\Foundation\Contracts\DefinesAuditEvents;
use YezzMedia\Foundation\Contracts\DefinesInstallSteps;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\DefinesSecurityRequests;
use YezzMedia\Foundation\Contracts\DefinesSecurityRequirements;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Contracts\ProvidesDoctorChecks;
use YezzMedia\Foundation\Contracts\ProvidesOpsModules;
use YezzMedia\Foundation\Contracts\RegistersFeatures;
use YezzMedia\Foundation\Data\AuditEventDefinition;
use YezzMedia\Foundation\Data\FeatureDefinition;
use YezzMedia\Foundation\Data\OpsModuleDefinition;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Data\SecurityRequestDefinition;
use YezzMedia\Foundation\Data\SecurityRequirementDefinition;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\Foundation\Install\InstallStep;

/**
 * Describes the access package surface that foundation should register.
 */
final class AccessPlatformPackage implements DefinesAuditEvents, DefinesInstallSteps, DefinesPermissions, DefinesSecurityRequests, DefinesSecurityRequirements, PlatformPackage, ProvidesDoctorChecks, ProvidesOpsModules, RegistersFeatures
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
     * @return array<int, FeatureDefinition>
     */
    public function featureDefinitions(): array
    {
        return [
            new FeatureDefinition(
                'access.permissions',
                'yezzmedia/laravel-access',
                'Permission synchronization',
                'Synchronizes declared platform permissions into the persistent authorization store and exposes their readiness posture.',
            ),
            new FeatureDefinition(
                'access.roles',
                'yezzmedia/laravel-access',
                'Role synchronization',
                'Manages persisted role composition and role synchronization flows for platform authorization.',
            ),
            new FeatureDefinition(
                'access.assignments',
                'yezzmedia/laravel-access',
                'User role assignments',
                'Assigns and removes persisted roles for users through the access runtime.',
            ),
            new FeatureDefinition(
                'access.super_admin',
                'yezzmedia/laravel-access',
                'Super-admin safety',
                'Bootstraps and safeguards the privileged super-admin authorization posture.',
            ),
            new FeatureDefinition(
                'access.audit',
                'yezzmedia/laravel-access',
                'Authorization audit',
                'Translates access runtime events into normalized audit records when an audit backend is available.',
            ),
        ];
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
     * @return array<int, InstallStep>
     */
    public function installSteps(): array
    {
        return [
            app(PublishPermissionConfigInstallStep::class),
            app(ConfigureAccessAuditInstallStep::class),
            app(PublishPermissionMigrationsInstallStep::class),
            app(EnsurePermissionStoreReadyInstallStep::class),
            app(SyncPermissionsInstallStep::class),
            app(SeedRolesFromPermissionHintsInstallStep::class),
        ];
    }

    /**
     * @return array<int, DoctorCheck>
     */
    public function doctorChecks(): array
    {
        return [
            app(AccessAuditConfiguredCheck::class),
            app(PermissionsSynchronizedCheck::class),
            app(SuperAdminConfiguredCheck::class),
        ];
    }

    /**
     * @return array<int, OpsModuleDefinition>
     */
    public function opsModuleDefinitions(): array
    {
        return [];
    }

    /**
     * @return array<int, SecurityRequestDefinition>
     */
    public function securityRequestDefinitions(): array
    {
        return [
            new SecurityRequestDefinition(
                key: 'access.request.identity.privileged-mfa',
                package: 'yezzmedia/laravel-access',
                domain: 'identity',
                control: 'privileged_mfa',
                scope: 'super-admin',
                requestedLevel: 'required',
                requestedEnforcementMode: 'observe_only',
                description: 'Access may submit privileged-operator MFA hardening requests for super-admin posture visibility.',
                payloadSchema: [
                    'role' => 'Configured privileged role name.',
                    'channel' => 'Producing access runtime channel.',
                    'ability' => 'Observed authorization ability when available.',
                    'actor_reference' => 'Masked privileged operator reference.',
                ],
                allowedPreviewFields: ['role', 'channel', 'ability', 'actor_reference'],
                maskedFields: ['actor_reference'],
                notes: 'Access produces masked operator hardening requests and runtime evidence without exposing raw operator identifiers.',
            ),
        ];
    }

    /**
     * @return array<int, SecurityRequirementDefinition>
     */
    public function securityRequirementDefinitions(): array
    {
        return [
            new SecurityRequirementDefinition(
                key: 'access.identity.privileged-mfa',
                package: 'yezzmedia/laravel-access',
                domain: 'identity',
                control: 'privileged_mfa',
                level: 'required',
                scope: 'super-admin',
                description: 'Super-admin capable operators should satisfy stronger account hardening expectations such as MFA or passkeys.',
                enforcementMode: 'observe_only',
                appliesTo: ['super-admin', 'gate-before'],
                notes: 'Access declares privileged account hardening intent while leaving concrete MFA and passkey enforcement to the appropriate auth layer.',
            ),
        ];
    }
}
