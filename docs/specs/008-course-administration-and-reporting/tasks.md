# Tasks — SPEC-008 Course Administration and Reporting

## Resumen

Status general: Done

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada
- SPEC-005 completada
- SPEC-006 completada
- SPEC-007 preferentemente completada

---

## TASK-001 — Implementar menú/ruta administrativa

Status: Done

Covers:
- AC-001
- AC-002

---

## TASK-002 — Implementar capability check

Status: Done

Covers:
- AC-001
- AC-002
- AC-024

---

## TASK-003 — Implementar CourseReportingQuery

Status: Done

Covers:
- AC-003
- AC-004
- AC-005
- AC-006
- AC-007

---

## TASK-004 — Implementar agregados de Enrollment

Status: Done

Covers:
- AC-004
- AC-005
- AC-006
- AC-007

### Trabajo

Calcular:

- total
- active
- completed
- revoked

---

## TASK-005 — Implementar Approval Rate

Status: Done

Covers:
- métricas

### Fórmula

`completed / (active + completed)`

---

## TASK-006 — Implementar progreso promedio

Status: Done

Covers:
- AC-022

### Política

Promedio sobre alumnos active.

---

## TASK-007 — Implementar vista resumen de cursos

Status: Done

Covers:
- AC-003
- AC-004
- AC-005
- AC-006
- AC-007

---

## TASK-008 — Implementar CourseStudentsQuery

Status: Done

Covers:
- AC-008
- AC-009
- AC-010
- AC-011
- AC-012

---

## TASK-009 — Implementar paginación server-side

Status: Done

Covers:
- AC-021

---

## TASK-010 — Implementar búsqueda por nombre/email

Status: Done

Covers:
- AC-019

---

## TASK-011 — Implementar filtro de estado

Status: Done

Covers:
- AC-020

### Valores

- all
- active
- completed
- revoked

---

## TASK-012 — Implementar filtro básico de quiz

Status: Done

Covers:
- reporting

---

## TASK-013 — Batch load de usuarios

Status: Done

Covers:
- performance

---

## TASK-014 — Batch load de progreso

Status: Done

Covers:
- AC-012
- AC-022
- AC-026

---

## TASK-015 — Batch load de estado de quiz

Status: Done

Covers:
- AC-014
- AC-015
- AC-016
- AC-017
- AC-026

---

## TASK-016 — Implementar estado académico

Status: Done

Covers:
- AC-011
- AC-013

### Regla

Enrollment es fuente de verdad.

---

## TASK-017 — Implementar estado Quiz No aplica

Status: Done

Covers:
- AC-014

---

## TASK-018 — Implementar Quiz Passed/Not Passed

Status: Done

Covers:
- AC-015
- AC-016

---

## TASK-019 — Implementar intentos utilizados

Status: Done

Covers:
- AC-017

---

## TASK-020 — Mostrar completed_at

Status: Done

Covers:
- AC-018

---

## TASK-021 — Implementar indicador de certificado externo

Status: Done

### Trabajo

Indicar únicamente si el curso contempla certificado.

No indicar delivery status.

---

## TASK-022 — Implementar vista detalle del curso

Status: Done

Covers:
- AC-008
- AC-009
- AC-010
- AC-011
- AC-012

---

## TASK-023 — Implementar empty state para curso sin alumnos

Status: Done

---

## TASK-024 — Manejar alumno eliminado/inexistente

Status: Done

### Objetivo

Evitar fatal errors.

---

## TASK-025 — Manejar Course ID inválido

Status: Done

Covers:
- AC-025

---

## TASK-026 — Implementar escaping

Status: Done

Covers:
- seguridad

---

## TASK-027 — Validar filtros y search

Status: Done

Covers:
- AC-019
- AC-020
- AC-024

---

## TASK-028 — Implementar tests de autorización

Status: Done

Covers:
- AC-001
- AC-002
- AC-024

---

## TASK-029 — Implementar tests de agregados

Status: Done

Covers:
- AC-004
- AC-005
- AC-006
- AC-007

---

## TASK-030 — Implementar tests de Approval Rate

Status: Done

---

## TASK-031 — Implementar tests de detalle

Status: Done

Covers:
- AC-008
- AC-009
- AC-010
- AC-011
- AC-012
- AC-013

---

## TASK-032 — Implementar tests de quiz state

Status: Done

Covers:
- AC-014
- AC-015
- AC-016
- AC-017

---

## TASK-033 — Implementar tests de búsqueda

Status: Done

Covers:
- AC-019

---

## TASK-034 — Implementar tests de filtros

Status: Done

Covers:
- AC-020

---

## TASK-035 — Implementar tests de paginación

Status: Done

Covers:
- AC-021

---

## TASK-036 — Implementar tests de progreso promedio

Status: Done

Covers:
- AC-022

---

## TASK-037 — Implementar test read-only

Status: Done

Covers:
- AC-023

---

## TASK-038 — Revisar N+1/query count

Status: Done

Covers:
- AC-026

---

## TASK-039 — Verificar privacidad de emails/progreso

Status: Done

Covers:
- AC-024

---

## TASK-040 — Verificar ausencia de scope creep

Status: Done

### Confirmar ausencia de:

- edición de progreso
- manual completion
- certificate delivery
- email campaigns
- analytics avanzados

---

## TASK-041 — Ejecutar Quality Gate

Status: Done

---

## TASK-042 — Actualizar Implementation Status

Status: Done

---

## TASK-043 — Preparar candidato de auditoría

Status: Done

### Resultado esperado

Solo si todos los gates pasan:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```