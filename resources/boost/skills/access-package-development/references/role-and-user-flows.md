# Role And User Flows

- `PermissionSyncService` persists foundation-defined permissions.
- `RoleManager` composes persisted roles from explicit `RoleDefinition` objects.
- `UserRoleManager` assigns or removes one existing persisted role for one user.

Keep these boundaries separate.

Use `defaultRoleHints` only in explicit role seeding or preset flows. Do not let ordinary permission sync create or assign roles implicitly.

Fail fast when role definitions reference unknown permissions or when user-role flows reference unknown persisted roles.
