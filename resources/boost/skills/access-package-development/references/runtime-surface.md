# Approved V1 Access Surface

The conservative V1 access surface includes these functional groups:

## Runtime state

- synchronize declared permissions into persistent storage
- seed or synchronize explicit roles from stable permission names
- assign or remove one persisted role for one user
- bootstrap the configured super-admin bypass
- expose read-only permission lookup and cache invalidation helpers

## Audit and ops

- translate access runtime events into normalized audit writes
- optionally persist those writes through `activitylog`
- expose doctor checks for synchronization and super-admin readiness
- expose setup commands for permission sync and role seeding

## Testing support

- shared Testbench base for access package tests
- helper to register permission definitions and run the real sync path
- helper to build role fixtures and run the real role synchronization path

The approved public types behind those functions are:

- `AccessPlatformPackage`
- `AccessServiceProvider`
- `PermissionSyncService`
- `RoleManager`
- `UserRoleManager`
- `SuperAdminGateBootstrapper`
- `PermissionMap`
- `PermissionCacheManager`
- `AuthorizationAuditWriter`
- `NullAuthorizationAuditWriter`
- `ActivityLogAuthorizationAuditWriter`
- `AuthorizationAuditListener`
- `PermissionsSynchronizedCheck`
- `SuperAdminConfiguredCheck`
- `SyncPermissionsCommand`
- `SeedRolesCommand`
- `RoleDefinition`
- `PermissionsSynchronized`
- `RolesSynchronized`
- `UserRoleAssigned`
- `UserRoleRemoved`
- `AccessTestCase`
- `InteractsWithPermissionSync`
- `InteractsWithRoles`

Do not invent new public access runtime surface without plan and reference alignment.
