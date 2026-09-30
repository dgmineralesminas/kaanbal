
# Tasks — SPEC-007 Student Dashboard

## Resumen

Status general: Done

Implementación: `4fe0f1e` (Codex) + remediación previa a auditoría `ff87511`.

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada
- SPEC-004 completada
- SPEC-005 completada
- SPEC-006 completada

---

## TASK-001 — Implementar ruta del Student Dashboard

Status: Done

Evidencia: `DashboardModule::registerRewriteRules()` (`^mis-cursos/?$`); integración verifica la regla persistida.

Covers:
- AC-001
- AC-002

---

## TASK-002 — Implementar autorización del Dashboard

Status: Done

Evidencia: `DashboardRouter::template()` usa `get_current_user_id()`; integración con `?user_id=` falsificado (anónimo y autenticado).

Covers:
- AC-001
- AC-002
- AC-023

### Trabajo

Usar exclusivamente la identidad autenticada.

---

## TASK-003 — Implementar StudentDashboardQuery

Status: Done

Covers:
- AC-003
- AC-004
- AC-006
- AC-007

---

## TASK-004 — Cargar Enrollments del alumno

Status: Done

Covers:
- AC-003
- AC-004
- AC-005

---

## TASK-005 — Excluir Revoked del listado principal

Status: Done

Evidencia: `EnrollmentRepository::forUser()` filtra `active`/`completed`.

Covers:
- AC-005

---

## TASK-006 — Implementar carga batch de Courses

Status: Done

Covers:
- performance

---

## TASK-007 — Integrar Course Progress

Status: Done

Covers:
- AC-006
- AC-007

---

## TASK-008 — Optimizar carga de progreso de múltiples cursos

Status: Done

Evidencia: integración mide 12 consultas con 1 curso y 12 con 8 cursos.

Covers:
- AC-025

---

## TASK-009 — Implementar Student Course View Model

Status: Done

Covers:
- AC-008
- AC-009

---

## TASK-010 — Implementar estado En curso

Status: Done

Covers:
- AC-008

---

## TASK-011 — Implementar estado Aprobado

Status: Done

Covers:
- AC-009

---

## TASK-012 — Implementar acción Continuar curso

Status: Done

Covers:
- AC-010

---

## TASK-013 — Implementar acción Ver curso

Status: Done

Covers:
- AC-011

---

## TASK-014 — Implementar Quiz Dashboard State

Status: Done

Evidencia: `QuizDashboardState` + `isVisibleFor()` / `showsAttempts()`; `QuizDashboardStateTest`.

Covers:
- AC-012
- AC-013
- AC-014
- AC-015
- AC-016
- AC-017

---

## TASK-015 — Implementar estado Quiz Not Required

Status: Done

Covers:
- AC-012

---

## TASK-016 — Implementar estado Quiz Locked

Status: Done

Covers:
- AC-013

---

## TASK-017 — Implementar estado Quiz Available

Status: Done

Covers:
- AC-014

---

## TASK-018 — Implementar estado Quiz Passed

Status: Done

Covers:
- AC-015

---

## TASK-019 — Implementar intentos restantes

Status: Done

Covers:
- AC-016

---

## TASK-020 — Implementar estado No Attempts Left

Status: Done

Covers:
- AC-017

---

## TASK-021 — Mostrar completed_at

Status: Done

Covers:
- AC-018

---

## TASK-022 — Implementar mensaje de certificado

Status: Done

Evidencia: mensaje completo RF-023 en `parts/dashboard-content.php`, solo con `show_certificate`.

Covers:
- AC-019
- AC-020

---

## TASK-023 — Implementar estado aprobado sin certificado

Status: Done

Covers:
- AC-021

---

## TASK-024 — Verificar que no exista descarga

Status: Done

Covers:
- AC-020

---

## TASK-025 — Implementar Dashboard Template

Status: Done

Evidencia: `templates/student/dashboard.php` envuelve el contenido con `get_header()`/`get_footer()`.

Covers:
- AC-003
- AC-004
- AC-006
- AC-008
- AC-009

---

## TASK-026 — Implementar Course Card

Status: Done

Covers:
- presentación

---

## TASK-027 — Implementar Empty State

Status: Done

Covers:
- AC-022

---

## TASK-028 — Implementar escaping

Status: Done

Covers:
- seguridad

---

## TASK-029 — Implementar tests de acceso al Dashboard

Status: Done

Covers:
- AC-001
- AC-002
- AC-023

---

## TASK-030 — Implementar tests de cursos activos/completed/revoked

Status: Done

Covers:
- AC-003
- AC-004
- AC-005
- AC-008
- AC-009

---

## TASK-031 — Implementar tests de progreso

Status: Done

Covers:
- AC-006
- AC-007

---

## TASK-032 — Implementar tests de Quiz State

Status: Done

Covers:
- AC-012
- AC-013
- AC-014
- AC-015
- AC-016
- AC-017

---

## TASK-033 — Implementar tests de certificado informativo

Status: Done

Covers:
- AC-019
- AC-020
- AC-021

---

## TASK-034 — Implementar test Empty Dashboard

Status: Done

Covers:
- AC-022

---

## TASK-035 — Implementar test Dashboard Read Only

Status: Done

Covers:
- AC-024

---

## TASK-036 — Revisar queries y N+1

Status: Done

Evidencia: `update_meta_cache` + `update_post_thumbnail_cache` + lecturas batch; conteo de consultas en integración.

Covers:
- AC-025

---

## TASK-037 — Verificar ausencia de scope creep

Status: Done

### Confirmar que no se implementa:

- admin reporting
- analytics globales
- certificado descargable
- emails
- compras

---

## TASK-038 — Ejecutar Quality Gate

Status: Done

Evidencia: ver Quality Gate en `implementation-status.md`.

---

## TASK-039 — Actualizar Implementation Status

Status: Done

---

## TASK-040 — Preparar candidato de auditoría

Status: Done

### Resultado esperado

Solo si todos los gates pasan:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```

