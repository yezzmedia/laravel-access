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

Activate this skill when working inside `yezzmedia/laravel-access`, especially when one of these functions changes:

- synchronize declared foundation permissions into persistent storage
- synchronize one package's permissions without touching others
- seed or synchronize role presets from explicit definitions or enabled role hints
- assign one persisted role to one user or remove one persisted role from one user
- bootstrap or validate the super-admin bypass
- read permission names or invalidate permission lookup caches
- translate access runtime events into normalized audit writes
- switch audit persistence between `null` and `activitylog`
- expose or change `website:sync-permissions` and `website:seed-roles`
- expose or change doctor diagnostics for synchronization or super-admin readiness
- use or extend access testing helpers for sync and role workflows

## Functional Workflow

1. Identify the access function that is changing before choosing code paths.
2. Read the matching reference file for that function group.
3. Reuse the existing runtime path instead of adding a parallel helper or alternate engine.
4. Keep the boundary between permission sync, role sync, user-role assignment, audit, doctor, commands, and testing helpers explicit.
5. Prove the change with package tests first, then host integration only when the function crosses package boundaries.

## Core Rules

- Keep access focused on persistent authorization runtime concerns.
- Keep permission synchronization separate from role synchronization.
- Keep role synchronization separate from user-role assignment.
- Use stable permission names as the cross-package contract.
- Do not treat role names as cross-package runtime contracts.
- Use `defaultRoleHints` only in explicit seeding or preset flows, never as ordinary runtime sync behavior.
- Keep super-admin behavior centralized, explicit, and testable.
- Keep CLI commands thin and keep business logic in the runtime services.
- Keep doctor checks diagnostic-only; do not repair state inside a check.
- Keep testing helpers on the real runtime path; do not add fake sync or role subsystems.

## Runtime Integration Rules

- Use the configured Spatie permission and role models instead of hard-coding assumptions when possible.
- Keep guard handling explicit and consistent.
- Reset permission-related caches after persistent authorization state changes.
- Dispatch runtime events only after successful state changes.
- Fail fast when required persisted roles or permissions are missing.
- Keep optional `class_exists()` safety checks in provider binding logic only.
- Keep `activitylog` optional and bind it through `access.audit.driver` only.

## Testing Pattern

- Prefer package-owned tests first.
- Use realistic Testbench schema for permissions, roles, and assignment pivots.
- Keep host tests for cross-package integration confirmation.
- Prefer real foundation registration and access service resolution over shortcuts.
- Use the access testing helpers when they reduce repetitive setup without bypassing the real workflow.

## References

- Use [references/runtime-surface.md](references/runtime-surface.md) for the approved V1 access surface.
- Use [references/permission-and-role-runtime.md](references/permission-and-role-runtime.md) for permission sync, role seeding, role synchronization, assignment, and lookup boundaries.
- Use [references/audit-and-ops.md](references/audit-and-ops.md) for audit wiring, doctor checks, and setup commands.
- Use [references/testing.md](references/testing.md) for package-versus-host verification rules.
- Use [references/checklist.md](references/checklist.md) before finalizing access changes.

## Common Pitfalls

- mixing permission sync, role sync, and user-role assignment into one service
- treating `defaultRoleHints` as automatic runtime behavior
- hiding super-admin semantics in unrelated services
- dispatching audit or lifecycle events without a real state change
- putting audit driver fallback logic inside listeners instead of provider bindings
- turning commands into business-logic entry points instead of thin operational wrappers
- writing doctor checks that mutate runtime state
- inventing test-only authorization engines instead of using the real services
- skipping package tests and relying only on host integration tests
