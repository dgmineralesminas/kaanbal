# Tasks — SPEC-005 Student Progress

## Resumen

Status general: Ready for audit

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada
- SPEC-004 completada

---

## TASK-001 — Crear migración de Lesson Progress

Status: Done

Covers:
- AC-001
- AC-003

### Trabajo

Crear:

`kaanbal_lesson_progress`

con constraint:

`UNIQUE(user_id, lesson_id)`

### Validación

Migración idempotente.

---

## TASK-002 — Implementar LessonProgressRepository

Status: Done

Covers:
- AC-001
- AC-003
- AC-007
- AC-019

### Trabajo

Implementar:

- complete
- isCompleted
- findCompletedLessonIds
- countCompleted cuando corresponda

---

## TASK-003 — Implementar ProgressCalculator

Status: Done

Covers:
- AC-008
- AC-009
- AC-010

### Validación

Cubrir:

- 0/0
- 0/N
- partial
- full
- decimal

---

## TASK-004 — Implementar CourseProgressService

Status: Done

Covers:
- AC-007
- AC-008
- AC-009
- AC-010
- AC-011
- AC-017
- AC-018

### Objetivo

Combinar curriculum actual y completions del usuario.

---

## TASK-005 — Implementar CompleteLessonService

Status: Done

Covers:
- AC-001
- AC-003
- AC-004
- AC-005
- AC-006

### Trabajo

- resolver usuario
- resolver Lesson
- resolver Course
- validar jerarquía
- verificar acceso
- persistir idempotentemente

---

## TASK-006 — Integrar CourseAccessService

Status: Done

Covers:
- AC-004
- AC-005

### Validación

Solo enrollment válido permite escritura.

---

## TASK-007 — Proteger ownership

Status: Done

Covers:
- AC-006
- AC-019

### Trabajo

No aceptar `user_id` arbitrario como autoridad del request.

---

## TASK-008 — Implementar protección CSRF

Status: Done

Covers:
- seguridad

### Validación

Request sin token válido es rechazado.

---

## TASK-009 — Implementar endpoint de completar lección

Status: Done

Covers:
- AC-001
- AC-003
- AC-004
- AC-005
- AC-006

### Trabajo

Exponer operación segura desde player.

---

## TASK-010 — Implementar respuesta idempotente

Status: Done

Covers:
- AC-003
- AC-016

### Resultado esperado

Repetición no genera error funcional ni duplicado.

---

## TASK-011 — Integrar progreso en Course View

Status: Done

Covers:
- AC-012
- AC-013

### Trabajo

Mostrar:

- completadas
- pendientes
- total
- porcentaje

---

## TASK-012 — Implementar batch loading de completions

Status: Done

Covers:
- RNF-006

### Objetivo

Evitar N+1 por cada Lesson.

---

## TASK-013 — Integrar estado en Lesson View

Status: Done

Covers:
- AC-014
- AC-015
- AC-016

---

## TASK-014 — Implementar botón Marcar como completada

Status: Done

Covers:
- AC-015

### Trabajo

UI mínima para ejecutar el endpoint.

---

## TASK-015 — Implementar estado visual completed

Status: Done

Covers:
- AC-012
- AC-014
- AC-016

---

## TASK-016 — Verificar que abrir lección no escriba progreso

Status: Done

Covers:
- AC-002

---

## TASK-017 — Implementar cálculo de curso vacío

Status: Done

Covers:
- AC-010

---

## TASK-018 — Ignorar completions fuera del curriculum

Status: Done

Covers:
- AC-018

### Objetivo

Solo Lesson IDs actuales cuentan.

---

## TASK-019 — Soportar curriculum dinámico

Status: Done

Covers:
- AC-017
- AC-018

### Validación

Agregar/eliminar lecciones recalcula porcentaje automáticamente.

---

## TASK-020 — Garantizar exclusión de quiz

Status: Done

Covers:
- AC-011

### Objetivo

Progress Service depende solo de Lesson.

---

## TASK-021 — Evitar Course Completion automático

Status: Done

Covers:
- AC-020

### Validación

100% de lecciones no cambia enrollment a completed.

---

## TASK-022 — Revisar TOCTOU del flujo de escritura

Status: Done

Covers:
- seguridad
- RNF-004

### Trabajo

Documentar y aplicar la estrategia usada para mantener invariantes durante la escritura.

---

## TASK-023 — Implementar tests de LessonProgressRepository

Status: Done

Covers:
- AC-001
- AC-003
- AC-007
- AC-019

---

## TASK-024 — Implementar tests de ProgressCalculator

Status: Done

Covers:
- AC-008
- AC-009
- AC-010

---

## TASK-025 — Implementar tests CourseProgressService

Status: Done

Covers:
- AC-008
- AC-009
- AC-010
- AC-011
- AC-017
- AC-018

---

## TASK-026 — Implementar tests CompleteLessonService

Status: Done

Covers:
- AC-001
- AC-003
- AC-004
- AC-005
- AC-006

---

## TASK-027 — Implementar tests del endpoint

Status: Done

Covers:
- AC-001
- AC-003
- AC-004
- AC-005
- AC-006

### Casos

- success
- duplicate
- anonymous
- CSRF
- no access
- mismatched course
- invalid lesson

---

## TASK-028 — Implementar test de usuarios independientes

Status: Done

Covers:
- AC-019

---

## TASK-029 — Implementar tests de curriculum dinámico

Status: Done

Covers:
- AC-017
- AC-018

---

## TASK-030 — Implementar tests de 100% sin Course Completion

Status: Done

Covers:
- AC-020

---

## TASK-031 — Verificar concurrencia/idempotencia

Status: Done

Covers:
- AC-003

### Trabajo

Validar unique constraint y manejo de inserción duplicada.

---

## TASK-032 — Revisar consultas y N+1

Status: Done

Covers:
- RNF-006

---

## TASK-033 — Verificar ausencia de scope creep

Status: Done

### Confirmar que no se implementa:

- quiz
- certificado
- finalización de matrícula
- video tracking

---

## TASK-034 — Ejecutar Quality Gate

Status: Done

Covers:
- Definition of Done

---

## TASK-035 — Actualizar Implementation Status

Status: Done

Covers:
- Definition of Done

---

## TASK-036 — Preparar candidato de auditoría

Status: Done

Covers:
- Definition of Done

### Resultado esperado

Solo si todo pasa:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```
