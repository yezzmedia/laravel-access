# Permission And Role Runtime

Use this reference when changing the functions that keep persistent authorization state aligned with foundation declarations.

## Permission synchronization

- Synchronize all declared permissions through the real permission sync path.
- Synchronize one package only when the change is intentionally scoped to one package.
- Keep normal permission synchronization focused on permission persistence only.
- Do not create roles or assign users during ordinary permission synchronization.
- Ignore `PermissionDefinition.defaultRoleHints` during ordinary permission synchronization.
- Reset permission lookup caches after persistent permission state changes.
- Dispatch the synchronization event only after the persistent state is updated successfully.

## Role seeding and role synchronization

- Build roles from explicit `RoleDefinition` input.
- Use stable permission names inside role definitions.
- Keep role composition deterministic and repeatable.
- Treat `defaultRoleHints` as optional preset input only.
- Consult `defaultRoleHints` only in explicit seeding flows and only when `access.roles.apply_default_role_hints` is enabled.
- Fail fast when a role definition references a permission that is not persisted.
- Do not let role synchronization become user management.

## User-role assignment and removal

- Assign or remove one existing persisted role for one user.
- Fail fast when the referenced persisted role does not exist.
- Emit runtime events only when the user's state really changes.
- Treat repeated assignment or repeated removal as a no-op, not as a second state change.
- Invalidate user-specific permission lookup cache after a real assignment or removal.

## Super-admin and lookup boundaries

- Keep super-admin bypass centralized and explicit.
- Require a valid configured role name when the bypass is enabled.
- Keep permission lookup read-only.
- Use permission lookup services to answer questions such as:
  - all known permission names
  - permission names for one role
  - whether one permission exists
- Do not let lookup services become a second authorization truth source.

## Useful runtime questions

- Is the change about persisting declared permissions, or about composing roles from existing permissions?
- Is the change about user assignment, or about role composition?
- Is `defaultRoleHints` being used only in an explicit seeding flow?
- Are cache invalidation and runtime events tied only to successful state changes?
