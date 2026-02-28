---
status: complete
phase: 02-gesti-n-de-csd
source: [02-01-SUMMARY.md, 02-02-SUMMARY.md, 02-03-SUMMARY.md, 02-04-SUMMARY.md]
started: 2026-02-28T01:15:00Z
updated: 2026-02-28T01:30:00Z
---

## Current Test

[testing complete]

## Tests

### 1. CSD resource appears in Filament sidebar
expected: Navigate to the Filament admin panel. In the left sidebar, you should see a "Configuración" navigation group containing "Certificados de Sello Digital" with a shield-check icon.
result: pass

### 2. CSD list table renders with correct columns
expected: Click on "Certificados de Sello Digital". The list page should show a table with columns: No. Certificado, RFC, Vigencia desde, Vigencia hasta, Estado (as colored badge), and Fecha de carga. The table should be empty initially.
result: pass

### 3. Upload form renders with file inputs and passphrase
expected: Click the "Subir CSD" button in the top-right. The create page should show a form with three fields: "Archivo .cer" (file upload), "Archivo .key" (file upload), and "Contraseña del archivo .key" (password input with reveal toggle). Title should read "Subir Certificado de Sello Digital".
result: pass

### 4. Upload valid CSD certificate pair
expected: Upload a valid .cer and .key file pair with the correct passphrase. After submission, you should see a success notification "CSD cargado correctamente" and be redirected to a view page showing the certificate details (No. Certificado, RFC, dates, Estado as "Inactivo").
result: pass

### 5. Upload form shows error for invalid files
expected: Upload invalid files (e.g. a .txt file renamed to .cer) or enter a wrong passphrase. You should see a danger notification "Error al procesar el certificado" with a Spanish error message explaining the issue. The form should remain on screen (not redirect).
result: pass

### 6. View page shows read-only CSD details
expected: From the list table, click the eye/view icon on a CSD row. You should see a read-only detail page with: No. Certificado, RFC, Vigencia desde, Vigencia hasta, Estado (as colored badge), and Fecha de carga. No edit button should be present.
result: pass

### 7. Activate CSD action with confirmation
expected: On the list table, find a CSD with Estado "Inactivo". You should see an "Activar" button (green, with check-circle icon). Clicking it shows a confirmation modal saying "¿Activar este CSD?" with description about deactivating the current active CSD. Confirming should change the status to "Activo" (green badge).
result: pass

### 8. Deactivate CSD action with confirmation
expected: On the list table, find the CSD with Estado "Activo". You should see a "Desactivar" button (yellow/warning, with x-circle icon). Clicking it shows a confirmation modal saying "¿Desactivar este CSD?" with warning about not being able to stamp. Confirming should change status to "Inactivo" (gray badge).
result: pass

### 9. Delete CSD (soft delete)
expected: On the list table, click the "Eliminar" action on a CSD row. After confirmation, the record should disappear from the list. The record is soft-deleted (still exists in the database but hidden from the UI).
result: pass

### 10. Dashboard expiry warning widget
expected: If a CSD has an expiry date within 90 days, the Filament dashboard should show a yellow warning banner at the top with the certificate number, days remaining, and expiry date in Spanish. If no CSDs are expiring soon, no banner should appear.
result: pass

### 11. All 29 CSD tests pass
expected: Run `./vendor/bin/sail php artisan test --compact --filter=Csd` in your terminal. All 29 tests should pass with 0 failures and 0 errors. The full test suite (87 tests) should also pass with no regressions.
result: pass

## Summary

total: 11
passed: 11
issues: 0
pending: 0
skipped: 0

## Gaps

[none]
