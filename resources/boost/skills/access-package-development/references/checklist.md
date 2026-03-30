# Checklist

- Was the change kept inside the approved access V1 surface?
- Was the responsibility added to the correct service boundary?
- Were configured Spatie models and guard behavior respected?
- Were caches reset after persistent authorization state changes?
- Were lifecycle events dispatched only after successful state changes?
- Were package tests added or updated first?
- Was host integration verified when the change crosses package boundaries?
