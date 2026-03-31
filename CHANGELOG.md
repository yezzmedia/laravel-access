# Changelog

All notable changes to `yezzmedia/laravel-access` will be documented in this file.

The format is based on Keep a Changelog and this package follows Semantic Versioning.

## [Unreleased]

### Added

- foundation-driven install workflow through `DefinesInstallSteps`
- host setup orchestration through `PermissionStoreSetup`
- install steps for publishing config, publishing migrations, preparing the permission store, and synchronizing permissions
- `SuperAdminSafetyGuard` to prevent unsafe removal of the configured super-admin role below the enforced minimum operator count

### Changed

- `UserRoleManager` now enforces super-admin removal safety before mutating assignments
- access install readiness now treats pending published access migrations as blocking setup work

### Documentation

- documented the central foundation install flow and the super-admin role-removal safety behavior in the package README

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
