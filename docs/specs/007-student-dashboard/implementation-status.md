# Implementation Status

SPEC: SPEC-007 — Student Dashboard

Branch: feature/spec-007-student-dashboard
Implementation commit: 4fe0f1eab59115f60dd938132e32687092512dd8
Pre-audit remediation commit: ff87511
Visual fix commit: 591e7c0
Login link commit: d4abd03
Audit commit: d4abd03

Status: Ready for audit
Ready for audit: Yes
Current audit round: 1

## Human Approval

SPEC approved for implementation: Yes

---

## Dependencies

SPEC-001:
Completed

SPEC-002:
Completed

SPEC-003:
Completed

SPEC-004:
Completed

SPEC-005:
Completed

SPEC-006:
Completed

---

## Tasks

Total: 40
Done: 40
In Progress: 0
Pending: 0
Blocked: 0
Not Applicable: 0

---

## Quality Gate

Ejecutado sobre `ff87511` con PHP 8.4.21, WordPress 7.1.2 (core de este
proyecto) y MariaDB en un entorno limpio con permalinks `/%postname%/`.

PHP Syntax:

`PASS — composer lint (src, tests) + php -l templates/student`

Unit Tests:

`PASS — composer test (74 tests, 103 assertions)`

Integration Tests:

`PASS — student-dashboard, wordpress-lifecycle, courses-curriculum,
course-access-player, student-progress, final-quiz-course-completion`

`woocommerce-enrollment: BLOCKED BY ENVIRONMENT — WooCommerce no instalado
en el entorno de validación. SPEC-007 no modifica código WooCommerce.`

Dashboard Tests:

`PASS — rewrite persistida, acceso, ownership vía template() con user_id
falsificado (anónimo y autenticado), cursos, progreso, estados de quiz,
visibilidad de quiz/intentos, certificado, estado vacío y read-only`

Security Tests:

`PASS — anónimo sin datos académicos; user_id falsificado ignorado`

Performance Review:

`PASS — 1 curso = 12 consultas, 8 cursos = 12 consultas`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (0 errors)`

Full-page render:

`PASS — con un tema mínimo, /mis-cursos/ produce <html>, wp_head con
dashboard.css, contenido y estado vacío; el acceso denegado incluye
enlace de login con redirect_to`

Manual Verification:

`PARTIAL — revisión visual humana detectó portada recortada y enlace morado (corregidos en 591e7c0); pendiente confirmar en navegador`

---

## Acceptance Criteria

AC-001: IMPLEMENTED — authenticated route and denial template
AC-002: IMPLEMENTED — anonymous request returns 403 without dashboard context
AC-003: IMPLEMENTED — active enrollments appear in the read model
AC-004: IMPLEMENTED — completed enrollments appear as approved
AC-005: IMPLEMENTED — revoked and orphaned courses are excluded
AC-006: IMPLEMENTED — progress counts and percentage from CourseProgressService
AC-007: IMPLEMENTED — no dashboard-owned progress storage
AC-008: IMPLEMENTED — active status label En curso
AC-009: IMPLEMENTED — completed status label Aprobado
AC-010: IMPLEMENTED — Continuar curso action
AC-011: IMPLEMENTED — Ver curso action preserves the player URL
AC-012: IMPLEMENTED — no quiz state for courses without a quiz
AC-013: IMPLEMENTED — locked quiz state
AC-014: IMPLEMENTED — available quiz state
AC-015: IMPLEMENTED — passed quiz state
AC-016: IMPLEMENTED — limited attempts remaining
AC-017: IMPLEMENTED — exhausted attempts state
AC-018: IMPLEMENTED — completed_at is included in the view model
AC-019: IMPLEMENTED — certificate follow-up message
AC-020: IMPLEMENTED — no certificate download or URL
AC-021: IMPLEMENTED — completed course without certificate
AC-022: IMPLEMENTED — empty dashboard state
AC-023: IMPLEMENTED — session identity only; forged user_id ignored
AC-024: IMPLEMENTED — dashboard query is read-only
AC-025: IMPLEMENTED — batched enrollment, course, progress and quiz-attempt reads

---

## Student Status Policy

Enrollment active:

`En curso`

Enrollment completed:

`Aprobado`

Enrollment revoked:

`Excluded from main dashboard`

---

## Progress Policy

Source:

`CourseProgressService`

Stored independently by Dashboard:

`No`

100% means Approved automatically:

`No`

Formal approval source:

`Enrollment.status = completed`

---

## Quiz Dashboard Policy

Possible states:

- Not Required
- Locked
- Available
- Failed / Retry Available
- Passed
- Attempts Exhausted

---

## Certificate Policy

Certificate generated:

`No`

Certificate downloadable:

`No`

Certificate delivered by Kaanbal:

`No`

If applicable, the dashboard only displays the informational follow-up message.

---

## Dashboard Write Policy

Normal dashboard request modifies academic state:

`No`

Expected behavior:

`Read only`

---

## Pre-audit Remediation (ff87511)

Revisión previa a auditoría solicitada por el responsable humano:

1. Templates sin `get_header()`/`get_footer()`: el tema y `dashboard.css`
   no se cargaban. Corregido; se agrega `defined('ABSPATH') || exit;`.
2. Curso aprobado mostraba quiz pendiente si el temario o el quiz cambiaron
   después (RB-004, EC-007). Ahora solo muestra un quiz aprobado.
3. El contador de intentos aparecía con quiz aprobado o agotado. Ahora solo
   con quiz disponible o reintentable.
4. Mensaje de certificado completado según RF-023.
5. Acceso denegado con enlace de login que regresa a `/mis-cursos/`.
6. Miniaturas precargadas (`update_post_thumbnail_cache`).
7. La prueba de user_id falsificado ahora pasa por `template()` con la
   identidad de sesión.
8. La prueba de rewrite buscaba `mis-cursos/?$`; la llave real es
   `^mis-cursos/?$`. Era la causa del "rerun pendiente".

---

## Visual Fix (591e7c0)

Tras la revisión visual del responsable humano:

- La portada del curso (póster 4:5) se recortaba a una franja de 170 px.
  Ahora se muestra completa en una tarjeta horizontal (apilada en móvil).
- El botón de acción se veía morado por `a:visited` del tema; se fija el
  color en `:visited`, `:focus` y `:active`.

Solo CSS; verificado con captura en 1200 px y 390 px.

---

## Login Link (d4abd03)

El botón "Iniciar sesión" del acceso denegado lleva a la página Mi cuenta de
WooCommerce (`wc_get_page_permalink('myaccount')`), igual que el tema.
`wp-login.php` solo se usa si WooCommerce no está activo.

---

## Known Issues

- El template `parts/dashboard-content.php` conserva dos líneas con varias
  sentencias en línea (heredadas); `templates/` está fuera del alcance
  configurado de PHPCS.
- `access_url` construye `/courses/{slug}/` en el query en lugar de reutilizar
  un helper de `PlayerModule` (recomendación, no bloqueante).

---

## Open Findings

Ninguna ronda de auditoría ejecutada. Candidato de ronda 1: `d4abd03`.

---

## Change Requests

None.

---

## Human Review

Pendiente. Se recomienda revisión visual de `/mis-cursos/` con el tema Kaanbal.

---

## Notes

La remediación previa a auditoría (`ff87511`) se hizo en esta sesión con el
rol de implementación. Para respetar la independencia de `docs/agents.md`, la
auditoría de código de la ronda 1 debe ejecutarla otra sesión o agente.
