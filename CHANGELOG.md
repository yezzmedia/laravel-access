# Changelog

All notable changes to `yezzmedia/laravel-access` will be documented in this file.

The format is based on Keep a Changelog and this package follows Semantic Versioning.

## [Unreleased]

### Added

- foundation-driven install workflow through `DefinesInstallSteps`
- host setup orchestration through `PermissionStoreSetup`
- install steps for publishing config, publishing migrations, preparing the permission store, and synchronizing permissions
- `ConfigureAccessAuditInstallStep` for explicitly enabling persisted access audit through the central installer
- `AccessAuditConfiguredCheck` for reporting whether access audit persistence is enabled, disabled, or misconfigured
- `SuperAdminSafetyGuard` to prevent unsafe removal of the configured super-admin role below the enforced minimum operator count
- security-governance declarations through foundation:
  - `access.request.identity.privileged-mfa`
  - `access.identity.privileged-mfa`
- optional runtime visibility bridge through `AccessSecurityVisibilityReporter` for ops-security request and evidence submission

### Changed

- `UserRoleManager` now enforces super-admin removal safety before mutating assignments
- access install readiness now treats pending published access migrations as blocking setup work
- `ConfigureAccessAuditInstallStep` now participates in the generic audit installer flow and still supports the deprecated legacy alias
- super-admin bootstrap and diagnostics now emit privileged-account governance visibility when the optional ops-security broker is available
- permission-store readiness checks now reuse memoized migration and table state so ops access pages stop re-checking the same store state inside one request

### Documentation

- documented the central foundation install flow and the super-admin role-removal safety behavior in the package README
- documented the generic `--configure-audit --audit-package=*` flow and the deprecated `--configure-access-audit` compatibility alias
- documented the implemented security-governance declarations, optional ops-security broker integration, and the corrected doctor-check key names in the package README
- documented the exact foundation registration surface in the package README, including declared features, audit events, install steps, doctor checks, security entries, and the intentional absence of access-owned ops modules

## [0.1.0] - 2026-03-30

### Added

- foundation-aligned package bootstrap through `AccessPlatformPackage` and `AccessServiceProvider`
- persistent permission synchronization through `PermissionSyncService`
- explicit role composition through `RoleManager` and `RoleDefinition`
- user-role assignment and removal through `UserRoleManager`
- super-admin gate bypass bootstrap through `SuperAdminGateBootstrapper`
- read-only permission lookup through `PermissionMap`
- explicit permission-map cache invalidation through `PermissionCacheManager`
- runtime events:
  - `PermissionsSynchronized`
  - `RolesSynchronized`
  - `UserRoleAssigned`
  - `UserRoleRemoved`
- authorization audit surface:
  - `AuthorizationAuditWriter`
  - `NullAuthorizationAuditWriter`
  - `ActivityLogAuthorizationAuditWriter`
  - `AuthorizationAuditListener`
- doctor diagnostics:
  - `PermissionsSynchronizedCheck`
  - `SuperAdminConfiguredCheck`
- setup commands:
  - `website:sync-permissions`
  - `website:seed-roles`
- package testing support:
  - `AccessTestCase`
  - `InteractsWithPermissionSync`
  - `InteractsWithRoles`
- package-native Boost skill under `resources/boost/skills/access-package-development/`

### Changed

- aligned the runtime surface and namespaces with the approved 002 access architecture
- moved role-hint seeding behavior onto the role runtime boundary instead of keeping it inside the command layer
- wired `access.cache.permission_map.enabled` to real permission-map caching behavior
- tightened cache invalidation for synchronized roles and user-role changes

### Documentation

- documented the package runtime surface, testing helpers, audit and ops workflows, and checklist through the shipped Boost skill references
