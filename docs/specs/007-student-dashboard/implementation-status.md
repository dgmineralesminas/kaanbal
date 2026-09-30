# Implementation Status

SPEC: SPEC-007 — Student Dashboard

Branch: feature/spec-007-student-dashboard
Implementation commit: 4fe0f1eab59115f60dd938132e32687092512dd8
Pre-audit remediation commit: ff87511
Visual fix commit: 591e7c0
Login link commit: d4abd03
Round 1 audited commit: d4abd03 (PASS WITH RECOMMENDATIONS)
Round 1 remediation commit: 4d16911
Audit commit: 4d16911e8cca8058486bb04cade014b24c22ea96

Status: Completed
Ready for audit: No
Current audit round: 2

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

Ejecutado sobre el candidato exacto `4d16911` (extraído con `git archive`),
con PHP 8.4.21, WordPress 7.1.2 (core de este proyecto), MariaDB y
WooCommerce 11.1.1 (copia de `wp-content/plugins/woocommerce` de este
proyecto), permalinks `/%postname%/`.

PHP Syntax:

`PASS — composer lint (95 archivos de src y tests) + php -l templates/ (7 archivos)`

Unit Tests:

`PASS — composer test (87 tests, 117 assertions)`

Integration Tests (WooCommerce activo):

`PASS — test:integration:dashboard, test:integration (wordpress-lifecycle),
test:integration:curriculum, test:integration:player,
test:integration:progress, test:integration:quiz,
test:integration:woocommerce`

Integration Tests (WooCommerce inactivo):

`PASS — test:integration:dashboard, test:integration:quiz`

Dashboard Tests:

`PASS — rewrite persistida, acceso, ownership vía template() con user_id
falsificado (anónimo y autenticado), cursos, progreso, estados de quiz,
quiz requerido inválido o sin publicar sin estado visible, página del curso
sin sección de evaluación para quiz inválido/sin publicar, visibilidad de
intentos, certificado, estado vacío, read-only, destino real del login y
enlace "Mis cursos" en la navegación de Mi cuenta`

Security Tests:

`PASS — anónimo sin datos académicos; user_id falsificado ignorado`

Performance Review:

`PASS — 1 curso = 13 consultas, 10 cursos = 13 consultas (la validez del quiz
agrega una sola consulta agregada)`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs (exit 0)`

PHPStan:

`PASS — composer analyse (0 errors)`

Environment notes:

`Avisos de CLI ajenos al plugin (SERVER_NAME no definido, sendmail ausente)
durante las integraciones; no afectan los resultados.`

Manual Verification:

`PARTIAL — Daniel revisó /mis-cursos/ y el acceso denegado en navegador
durante la ronda 1 (portada, color del botón y destino Mi cuenta corregidos).
Pendiente revisar en navegador el enlace "Mis cursos" en Mi cuenta.`

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
- Unavailable (quiz requerido sin publicar o inválido): nunca visible

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
5. Acceso denegado con enlace de login (en `ff87511` regresaba a
   `/mis-cursos/`; desde `d4abd03` lleva a Mi cuenta, ver CODE-002).
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

## Round 1 Remediation (4d16911)

Auditoría: `audits/round-1/code-audit.md` — PASS WITH RECOMMENDATIONS,
decisiones humanas en §10.

### CODE-001 — Accepted, resolved

Decisión humana: "Si no hay preguntas válidas o quiz en un curso no mostrar
nada."

- `Quiz\Application\QuizValidityRule`: única definición de quiz válido.
- `Quiz\Application\QuizValidityService::validQuizIds()`: validez en lote con
  una consulta agregada (`QuestionRepository::answerStatsForQuizzes`);
  `isValid()` usa la misma operación.
- `QuizEligibilityService` usa `QuizValidityService` (se eliminó
  `hasValidQuestions`, sin duplicar la regla).
- Dashboard: quiz requerido sin publicar o inválido → estado `unavailable`,
  nunca visible; el resto de la tarjeta no cambia. Se elimina el mensaje
  "La evaluación final aún no está disponible."
- Página del curso: `CourseQuizStatusService` devuelve `invalid_quiz` para
  quiz sin publicar o inválido antes de evaluar el progreso, y
  `templates/frontend/course.php` no renderiza la sección de evaluación.
- Pruebas: `QuizValidityRuleTest`, casos nuevos en `QuizDashboardStateTest`,
  integración con quiz sin preguntas (al 100%), quiz sin publicar, página del
  curso con progreso completo e incompleto, y conteo de consultas.

### CODE-002 — Accepted, resolved (adición de alcance aprobada)

Decisión humana: "Puede caer en mi cuenta, por si necesita cambiar algo. Pero
debe haber un botón que lleve a Mis cursos."

- `DashboardRouter::loginUrl()` sin cambios: Mi cuenta de WooCommerce si está
  activo; `wp_login_url()` si no.
- `WooCommerce\Presentation\Frontend\AccountMenuLinks`: agrega "Mis cursos"
  (después de "Escritorio") al menú de Mi cuenta mediante
  `woocommerce_account_menu_items`, con la URL de `DashboardRouter::url()` vía
  `woocommerce_get_endpoint_url`. Se registra en `WooCommerceModule` solo si
  WooCommerce está activo; Dashboard no depende de WooCommerce.
- La prueba del login afirma el destino real: permalink de la página
  `myaccount` con WooCommerce; `wp_login_url('/mis-cursos/')` sin él. La
  navegación renderizada de Mi cuenta contiene el enlace a `/mis-cursos/`.

### CODE-003 — Accepted, resolved

- Quality gate completo ejecutado sobre el candidato exacto `4d16911`.
- `test:integration:woocommerce` ejecutada con WooCommerce 11.1.1.
- El cambio sin commitear en "Human Review" (`Approved.`) no se conserva: la
  revisión humana formal ocurre después de la última ronda
  (`development-workflow.md` §24-25). Daniel puede registrarla al cerrar la
  ronda 2.

### CODE-004 — Open, pending human decision

Estados sin explicación para el alumno (aprobado con progreso actual < 100%;
curso sin quiz al 100% con matrícula `active`). No implementado.

### CODE-005 — Open, pending human decision

Observaciones menores (helper de `access_url`, temas de bloques sin
`header.php`, aserción de render de `completed_at`, `update_meta_cache`
duplicado). No implementado.

### CODE-006 — Deferred by human decision

La página de curso oculta la sección de evaluación cuando el quiz requerido
deja de ser válido; esto también oculta el mensaje de aprobación previamente
obtenida. El hallazgo es no bloqueante y su posible ajuste de UX se difiere a
una SPEC posterior.

---

## Human Scope Decisions

- 2026-09-29 — Daniel aprueba agregar en "Mi cuenta" de WooCommerce una acción
  visible hacia `/mis-cursos/` (CODE-002). SPEC-007 §4 excluía el frontend de
  cuenta WooCommerce; esta adición queda aprobada por el responsable humano sin
  modificar `spec.md`.
- Diferido a una SPEC posterior: que los botones "Mis cursos" del tema (header,
  tarjeta de compra y CTA del home) apunten a `/mis-cursos/`.
- 2026-09-30 — Daniel aprueba el cierre de SPEC-007. CODE-004, CODE-005,
  CODE-006, ARCH-001 a ARCH-003 y SEC-REC-001 se difieren como recomendaciones
  no bloqueantes; no se autoriza ampliar el alcance de esta SPEC.

---

## Known Issues

- El template `parts/dashboard-content.php` conserva dos líneas con varias
  sentencias en línea (heredadas); `templates/` está fuera del alcance
  configurado de PHPCS.
- `access_url` construye `/courses/{slug}/` en el query en lugar de reutilizar
  un helper de `PlayerModule` (CODE-005, pendiente de decisión).

---

## Open Findings

Blocking: 0

Round 1 (código):

- CODE-001 — Resolved and verified in round 2
- CODE-002 — Resolved and verified in round 2
- CODE-003 — Resolved and verified in round 2
- CODE-004 — Deferred to a future SPEC by human decision
- CODE-005 — Deferred to a future SPEC by human decision
- CODE-006 — Deferred to a future SPEC by human decision

Round 2 (architecture and security):

- ARCH-001 — Deferred as low-priority technical debt by human decision
- ARCH-002 and ARCH-003 — Informational; no action required
- SEC-REC-001 — Deferred hardening recommendation; no security vulnerability
  was found

Candidato de ronda 2: `4d16911`.

---

## Change Requests

None.

---

## Human Review

- Approved — 2026-09-30.
- Daniel confirmó que los cursos se listan en "Mi cuenta" y que puede acceder
  al curso desde ese enlace.
- Daniel confirmó que, al resolver el quiz, ya no quedan reintentos
  disponibles.
- Daniel confirmó que, al aprobar correctamente el curso, se muestra el mensaje
  correspondiente.

---

## Notes

La remediación previa a auditoría (`ff87511`) se hizo en esta sesión con el
rol de implementación. Para respetar la independencia de `docs/agents.md`, la
auditoría de código de la ronda 1 debe ejecutarla otra sesión o agente.

La remediación de la ronda 1 (`4d16911`) también se hizo en esta sesión con el
rol de implementación; la auditoría de código de la ronda 2 debe ejecutarla
otra sesión o agente.
