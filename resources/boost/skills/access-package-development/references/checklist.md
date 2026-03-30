# Checklist

- Was the change kept inside the approved access V1 surface?
- Was the change framed around the correct access function rather than a new parallel subsystem?
- Was the responsibility added to the correct runtime boundary?
- Were configured Spatie models and guard behavior respected?
- Were caches reset after persistent authorization state changes?
- Were lifecycle events dispatched only after successful state changes?
- Were `defaultRoleHints` used only in explicit seeding flows?
- Did audit driver selection stay in provider binding logic?
- Did doctor checks stay diagnostic-only?
- Did commands stay thin wrappers around the real services?
- Did testing helpers stay on the real sync and role workflows?
- Were package tests added or updated first?
- Was host integration verified when the change crosses package boundaries?
