# Audit And Ops

Use this reference when changing access audit behavior, doctor diagnostics, or setup commands.

## Authorization audit integration

- Translate access-owned runtime events into normalized audit writes.
- Keep listeners free of vendor-specific persistence logic.
- Keep the audit writer binding explicit in the provider.
- Default to the null writer when no audit driver is configured.
- Switch to the activitylog-backed writer only when `access.audit.driver=activitylog`.
- Fail explicitly when `activitylog` is requested but the optional dependency is unavailable.
- Keep audit event keys stable:
  - `access.permissions.synchronized`
  - `access.roles.synchronized`
  - `access.user_role.assigned`
  - `access.user_role.removed`

## Doctor diagnostics

- Report readiness; do not repair state.
- Use synchronization diagnostics to answer:
  - no permission definitions registered
  - declared permissions missing from persistence
  - stale extra permissions still persisted
  - declared and persisted permissions aligned
- Use super-admin diagnostics to answer:
  - super-admin disabled
  - enabled but invalid configuration
  - enabled and safely bootstrappable
- Keep check results normalized and blocking only when the reported issue would break runtime expectations.

## Setup commands

- Keep commands thin wrappers around the real runtime services.
- Use `website:sync-permissions` for explicit permission setup or repair workflows.
- Use `website:seed-roles` for explicit role preset seeding workflows.
- Do not hide synchronization or seeding in package boot.
- Do not let command code become a second business-logic implementation.
- Make the command output explain what happened in operational terms:
  - which packages were synchronized
  - which roles were seeded
  - whether nothing was available to seed

## Useful ops questions

- Is the code changing runtime behavior, or only operational entry points to existing runtime behavior?
- Is the audit driver decision still owned by provider bindings?
- Would a doctor check be more appropriate than a command, or vice versa?
- Does the command still delegate to the real service instead of reimplementing the workflow?
