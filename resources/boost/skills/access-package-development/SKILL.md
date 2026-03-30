---
name: access-package-development
description: "Build and maintain yezzmedia/laravel-access. Activate when changing permission synchronization, role synchronization, user-role assignment flows, super-admin bootstrap behavior, permission-map or cache services, access audit integration, access commands, doctor checks, or package tests that depend on the approved access V1 surface."
license: MIT
metadata:
  author: yezzmedia
---

# Access Package Development

## Documentation

Use `search-docs` for Laravel, Pest, Package Tools, and Spatie permission details. Use the reference files in this skill for package-specific access rules.

Use the `foundation-package-development` skill when the work depends on foundation descriptor registration or capability declarations.

## When To Use This Skill

Activate this skill when working inside `yezzmedia/laravel-access`, especially when changing:

- `PermissionSyncService`
- `RoleManager`
- `UserRoleManager`
- `SuperAdminGateBootstrapper`
- `PermissionMap`
- `PermissionCacheManager`
- access runtime events
- audit integration
- access console commands
- doctor checks
- access package tests and fixtures

## Core Rules

- Keep access focused on persistent authorization runtime concerns.
- Keep permission synchronization separate from role synchronization.
- Keep role synchronization separate from user-role assignment.
- Use stable permission names as the cross-package contract.
- Do not treat role names as cross-package runtime contracts.
- Use `defaultRoleHints` only in explicit seeding or preset flows, never as ordinary runtime sync behavior.
- Keep super-admin behavior centralized, explicit, and testable.

## Recommended Slice Order

1. `PermissionSyncService`
2. `RoleManager`
3. `UserRoleManager`
4. `SuperAdminGateBootstrapper`
5. `PermissionMap`
6. `PermissionCacheManager`
7. audit integration
8. commands
9. doctor checks

## Runtime Integration Rules

- Use the configured Spatie permission and role models instead of hard-coding assumptions when possible.
- Keep guard handling explicit and consistent.
- Reset permission-related caches after persistent authorization state changes.
- Dispatch runtime events only after successful state changes.
- Fail fast when required persisted roles or permissions are missing.

## Testing Pattern

- Prefer package-owned tests first.
- Use realistic Testbench schema for permissions, roles, and assignment pivots.
- Keep host tests for cross-package integration confirmation.
- Prefer real foundation registration and access service resolution over shortcuts.

## References

- Use [references/runtime-surface.md](references/runtime-surface.md) for the approved V1 access surface.
- Use [references/role-and-user-flows.md](references/role-and-user-flows.md) for sync and assignment boundaries.
- Use [references/testing.md](references/testing.md) for package-versus-host verification rules.
- Use [references/checklist.md](references/checklist.md) before finalizing access changes.

## Common Pitfalls

- mixing permission sync, role sync, and user-role assignment into one service
- treating `defaultRoleHints` as automatic runtime behavior
- hiding super-admin semantics in unrelated services
- dispatching audit or lifecycle events without a real state change
- skipping package tests and relying only on host integration tests
