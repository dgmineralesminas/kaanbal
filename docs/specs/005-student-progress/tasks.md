# Tasks — SPEC-005 Student Progress

## Resumen

Status general: Pending

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada
- SPEC-004 completada

---

## TASK-001 — Crear migración de Lesson Progress

Status: Pending

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

Status: Pending

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

Status: Pending

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

Status: Pending

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

Status: Pending

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

Status: Pending

Covers:
- AC-004
- AC-005

### Validación

Solo enrollment válido permite escritura.

---

## TASK-007 — Proteger ownership

Status: Pending

Covers:
- AC-006
- AC-019

### Trabajo

No aceptar `user_id` arbitrario como autoridad del request.

---

## TASK-008 — Implementar protección CSRF

Status: Pending

Covers:
- seguridad

### Validación

Request sin token válido es rechazado.

---

## TASK-009 — Implementar endpoint de completar lección

Status: Pending

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

Status: Pending

Covers:
- AC-003
- AC-016

### Resultado esperado

Repetición no genera error funcional ni duplicado.

---

## TASK-011 — Integrar progreso en Course View

Status: Pending

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

Status: Pending

Covers:
- RNF-006

### Objetivo

Evitar N+1 por cada Lesson.

---

## TASK-013 — Integrar estado en Lesson View

Status: Pending

Covers:
- AC-014
- AC-015
- AC-016

---

## TASK-014 — Implementar botón Marcar como completada

Status: Pending

Covers:
- AC-015

### Trabajo

UI mínima para ejecutar el endpoint.

---

## TASK-015 — Implementar estado visual completed

Status: Pending

Covers:
- AC-012
- AC-014
- AC-016

---

## TASK-016 — Verificar que abrir lección no escriba progreso

Status: Pending

Covers:
- AC-002

---

## TASK-017 — Implementar cálculo de curso vacío

Status: Pending

Covers:
- AC-010

---

## TASK-018 — Ignorar completions fuera del curriculum

Status: Pending

Covers:
- AC-018

### Objetivo

Solo Lesson IDs actuales cuentan.

---

## TASK-019 — Soportar curriculum dinámico

Status: Pending

Covers:
- AC-017
- AC-018

### Validación

Agregar/eliminar lecciones recalcula porcentaje automáticamente.

---

## TASK-020 — Garantizar exclusión de quiz

Status: Pending

Covers:
- AC-011

### Objetivo

Progress Service depende solo de Lesson.

---

## TASK-021 — Evitar Course Completion automático

Status: Pending

Covers:
- AC-020

### Validación

100% de lecciones no cambia enrollment a completed.

---

## TASK-022 — Revisar TOCTOU del flujo de escritura

Status: Pending

Covers:
- seguridad
- RNF-004

### Trabajo

Documentar y aplicar la estrategia usada para mantener invariantes durante la escritura.

---

## TASK-023 — Implementar tests de LessonProgressRepository

Status: Pending

Covers:
- AC-001
- AC-003
- AC-007
- AC-019

---

## TASK-024 — Implementar tests de ProgressCalculator

Status: Pending

Covers:
- AC-008
- AC-009
- AC-010

---

## TASK-025 — Implementar tests CourseProgressService

Status: Pending

Covers:
- AC-008
- AC-009
- AC-010
- AC-011
- AC-017
- AC-018

---

## TASK-026 — Implementar tests CompleteLessonService

Status: Pending

Covers:
- AC-001
- AC-003
- AC-004
- AC-005
- AC-006

---

## TASK-027 — Implementar tests del endpoint

Status: Pending

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

Status: Pending

Covers:
- AC-019

---

## TASK-029 — Implementar tests de curriculum dinámico

Status: Pending

Covers:
- AC-017
- AC-018

---

## TASK-030 — Implementar tests de 100% sin Course Completion

Status: Pending

Covers:
- AC-020

---

## TASK-031 — Verificar concurrencia/idempotencia

Status: Pending

Covers:
- AC-003

### Trabajo

Validar unique constraint y manejo de inserción duplicada.

---

## TASK-032 — Revisar consultas y N+1

Status: Pending

Covers:
- RNF-006

---

## TASK-033 — Verificar ausencia de scope creep

Status: Pending

### Confirmar que no se implementa:

- quiz
- certificado
- finalización de matrícula
- video tracking

---

## TASK-034 — Ejecutar Quality Gate

Status: Pending

Covers:
- Definition of Done

---

## TASK-035 — Actualizar Implementation Status

Status: Pending

Covers:
- Definition of Done

---

## TASK-036 — Preparar candidato de auditoría

Status: Pending

Covers:
- Definition of Done

### Resultado esperado

Solo si todo pasa:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```