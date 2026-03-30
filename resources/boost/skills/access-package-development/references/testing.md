# Testing Rules

- Package tests are the primary proof for access package behavior.
- Host tests confirm local cross-package integration with `laravel-foundation`.
- Use realistic Testbench tables for:
  - `permissions`
  - `roles`
  - `role_has_permissions`
  - `model_has_roles` when testing user-role assignment
- Prefer real provider boot and container resolution.
