# Implementation Status

SPEC: SPEC-008 — Course Administration and Reporting

Branch: feature/spec-008-course-administration-and-reporting
Base commit: 353f9bd
Current implementation commit: 213ef01

Status: Ready for audit
Ready for audit: Yes
Current audit round: 3

## Human Approval

SPEC approved for implementation: Yes

---

## Dependencies

SPEC-001:
Completed

SPEC-002:
Ready for audit — implementation dependency available; formal closure pending

SPEC-003:
Completed

SPEC-004:
Completed

SPEC-005:
Completed

SPEC-006:
Completed

SPEC-007:
Completed

---

## Tasks

Total: 43
Done: 43
In Progress: 0
Pending: 0
Blocked: 0
Not Applicable: 0

---

## Quality Gate

PHP Syntax:

`PASS — composer lint`

Unit Tests:

`PASS — composer test (89 tests, 122 assertions)`

Integration Tests:

`PASS — composer test:integration, curriculum, player, progress, quiz, dashboard, woocommerce and reporting. El runner de reporting emitió una advertencia no fatal de WordPress core en wp-admin/includes/plugin.php:1873.`

Reporting Tests:

`PASS — composer test:integration:reporting; summary, detail, filters, pagination and quiz states`

Security Tests:

`PASS — anonymous and subscriber authorization, invalid search and filters, escaped pagination query; read-only fixture assertion`

Performance Review:

`PASS — reporting page 1 query budget and 26-student pagination coverage`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (0 errors)`

Manual Verification:

`OBSERVED — Daniel confirmó que se listan los alumnos inscritos en cada curso.`

---

## Acceptance Criteria

AC-001: IMPLEMENTED — administración registrada y observada manualmente
AC-002: IMPLEMENTED — capability para administrador, estudiante y visitante cubierta por integración
AC-003: IMPLEMENTED — resumen de cursos cubierto por integración
AC-004: IMPLEMENTED — total de inscritos cubierto por integración
AC-005: IMPLEMENTED — activos cubiertos por integración
AC-006: IMPLEMENTED — finalizados cubiertos por integración
AC-007: IMPLEMENTED — revocados y tasa de aprobación cubiertos por integración
AC-008: IMPLEMENTED — detalle de inscritos observado manualmente y cubierto por integración
AC-009: IMPLEMENTED — lista por curso observada manualmente y cubierta por integración
AC-010: IMPLEMENTED — datos de alumno, búsqueda por email y detalle cubiertos por integración
AC-011: IMPLEMENTED — estados académicos cubiertos por integración
AC-012: IMPLEMENTED — progreso por alumno cubierto por integración
AC-013: IMPLEMENTED — fuente de verdad de matrícula cubierta por integración
AC-014: IMPLEMENTED — curso sin quiz cubierto por integración
AC-015: IMPLEMENTED — estados disponible, no presentado y aprobado cubiertos por integración
AC-016: IMPLEMENTED — estado reprobado cubierto por integración
AC-017: IMPLEMENTED — intentos usados, agotados e ilimitados cubiertos por integración
AC-018: IMPLEMENTED — fecha de finalización cubierta por integración
AC-019: IMPLEMENTED — búsqueda por nombre/email y caracteres `+` cubierta por integración
AC-020: IMPLEMENTED — filtros válidos e inválidos cubiertos por integración
AC-021: IMPLEMENTED — paginación server-side y conservación de búsqueda cubiertas por integración
AC-022: IMPLEMENTED — progreso promedio cubierto por integración
AC-023: IMPLEMENTED — consulta de reportes de solo lectura cubierta por integración
AC-024: IMPLEMENTED — autorización, entradas inválidas y escaping cubiertos por integración
AC-025: IMPLEMENTED — Course ID inválido cubierto por integración
AC-026: IMPLEMENTED — carga por lotes y presupuesto de consultas cubiertos por integración

---

## Academic Status Policy

Enrollment active:

`En curso`

Enrollment completed:

`Aprobado`

Enrollment revoked:

`Revocado`

Progress determines approval:

`No`

Formal source of approval:

`Enrollment.status = completed`

---

## Metrics Policy

Approval rate:

```text
completed / (active + completed) × 100
```

Revoked included in approval-rate denominator:

`No`

Average progress population:

`active enrollments`

---

## Quiz Reporting Policy

Course without quiz:

`No aplica`

Course with quiz:

May display:

- no presentado
- disponible
- reprobado
- aprobado
- intentos agotados

---

## Certificate Reporting Policy

Kaanbal generates certificate:

`No`

Kaanbal knows if certificate was sent:

`No`

Report may indicate course offers external certificate:

`Yes`

---

## Privacy

Student emails visible to authorized administrative report users only.

---

## Write Policy

Opening reports modifies academic state:

`No`

Reports are:

`Read only`

---

## Known Issues

- SPEC-002 permanece en `Ready for audit` según su `implementation-status.md`.
  La dependencia de implementación está disponible en `main`, pero su cierre
  formal sigue pendiente y no debe declararse completed desde esta SPEC.

---

## Open Findings

### Ronda 2 — remediados, pendientes de verificación de ronda 3

- CODE-001 — `RESOLVED IN CANDIDATE 42de843`: quality gate ejecutado, 43 tareas y 26 criterios actualizados con evidencia real, y aprobación humana versionada.
- CODE-002 — `RESOLVED IN CANDIDATE 42de843`: cobertura de visitante, filtros inválidos, quiz reprobado, intentos agotados e ilimitados.
- CODE-003 — `RESOLVED IN CANDIDATE 42de843`: la búsqueda se codifica antes de generar los enlaces de paginación; la integración cubre `a+b-page`.

### Ronda 2 — decisiones posteriores

- CODE-004 — `RESOLVED IN CANDIDATE 213ef01`: la fecha UTC de aprobación se muestra con `wp_date()` y los formatos/zonahoraria configurados en WordPress. La integración fuerza `America/Mexico_City`, comprueba el marcado y restaura la configuración.
- CODE-005 — `NOT APPLICABLE — decisión humana`: un curso con quiz requiere aprobarlo para completar la matrícula.
- CODE-006 — `NOT APPLICABLE — decisión humana`: el reporte se muestra únicamente cuando hay alumnos inscritos.
- CODE-007 — `NOT APPLICABLE — decisión humana`: el reporte se muestra únicamente cuando hay alumnos inscritos.
- CODE-008 — `NOT APPLICABLE — decisión humana`: los datos del reporte permanecen en inglés por ahora.
- CODE-009 — `NOT APPLICABLE — decisión humana`: el reporte se publica automáticamente.
- CODE-010 — `ACCEPTED RISK — decisión humana`: observaciones de arquitectura y seguridad aceptadas para el alcance actual.

## Remediación de Auditoría — Ronda 2

El candidato `213ef01` incorpora CODE-004 sobre la remediación anterior de
CODE-001, CODE-002 y CODE-003. El quality gate completo vuelve a pasar y la
siguiente acción es solicitar la auditoría de código de ronda 3.

---

## Change Requests

- CODE-004 — fecha/hora de intentos en UTC: Usar hora de wordpress.
- CODE-005 — estado de matrícula completada cuando el quiz continúa pendiente: NO Aplicable, los estudiantes deben finalizar el quiz para aprobar el curso.
- CODE-006 — empty state para curso sin alumnos: NO Aplicable, el reporte solo se muestra si hay alumnos inscritos.
- CODE-007 — presentación del filtro cuando el curso no tiene quiz: NO Aplicable, el reporte solo se muestra si hay alumnos inscritos.
- CODE-008 — localización dinámica de etiquetas: NO Aplicable, los datos del reporte se muestran en inglés por ahora.
- CODE-009 — decisión pendiente sobre publicar o no el reporte: NO Aplicable, el reporte se muestra automáticamente.
- CODE-010 — observaciones de arquitectura y seguridad: NO Aplicable, el reporte se muestra automáticamente.

---

## Human Review

Pending — procede después de los dictámenes de auditoría requeridos.

---

## Notes

SPEC-008 fue aprobada para implementación y su estado contractual es
`Approved` en `spec.md`. Este commit documental registra el candidato
remediado para la ronda 3.
