# Tasks — SPEC-006 Final Quiz and Course Completion

## Resumen

Status general: Ready for audit

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada
- SPEC-004 completada
- SPEC-005 completada

---

## TASK-001 — Implementar configuración de quiz por curso

Status: Done

Covers:
- AC-001
- AC-002

---

## TASK-002 — Implementar configuración de certificado informativo

Status: Done

Covers:
- AC-021
- AC-024

### Trabajo

Permitir configurar si el curso contempla entrega externa de certificado.

No generar documentos.

---

## TASK-003 — Implementar Quiz CPT

Status: Done

Covers:
- AC-002
- AC-005
- AC-006
- AC-007

---

## TASK-004 — Crear migración de Questions

Status: Done

Covers:
- AC-003

---

## TASK-005 — Crear migración de Answers

Status: Done

Covers:
- AC-004

---

## TASK-006 — Crear migración de Quiz Attempts

Status: Done

Covers:
- AC-013

---

## TASK-007 — Crear migración de Attempt Answers

Status: Done

Covers:
- AC-013

---

## TASK-008 — Implementar Question Repository

Status: Done

Covers:
- AC-003

---

## TASK-009 — Implementar Answer Repository

Status: Done

Covers:
- AC-004

---

## TASK-010 — Implementar Quiz Attempt Repository

Status: Done

Covers:
- AC-013
- AC-014

---

## TASK-011 — Implementar UI administrativa de Quiz

Status: Done

Covers:
- AC-002
- AC-005
- AC-006
- AC-007

---

## TASK-012 — Implementar UI de Questions y Answers

Status: Done

Covers:
- AC-003
- AC-004

---

## TASK-013 — Validar exactamente una respuesta correcta

Status: Done

Covers:
- AC-004

---

## TASK-014 — Implementar QuizEligibilityService

Status: Done

Covers:
- AC-008
- AC-014

### Validar

- access
- 100% lessons
- quiz válido
- attempts
- quiz no aprobado previamente

---

## TASK-015 — Implementar Quiz Score Calculator

Status: Done

Covers:
- AC-010
- AC-011
- AC-012

---

## TASK-016 — Implementar QuizSubmissionService

Status: Done

Covers:
- AC-009
- AC-010
- AC-011
- AC-012
- AC-013
- AC-026
- AC-027

---

## TASK-017 — Validar Question → Quiz

Status: Done

Covers:
- AC-027

---

## TASK-018 — Validar Answer → Question

Status: Done

Covers:
- seguridad

---

## TASK-019 — Implementar attempts limitados

Status: Done

Covers:
- AC-006
- AC-014

---

## TASK-020 — Implementar attempts ilimitados

Status: Done

Covers:
- AC-007

---

## TASK-021 — Implementar CourseCompletionPolicy

Status: Done

Covers:
- AC-015
- AC-016
- AC-017
- AC-025

### Reglas

```text
progress < 100
→ not complete

progress = 100 and no quiz required
→ complete

progress = 100 and quiz required but not passed
→ not complete

progress = 100 and quiz passed
→ complete
```

---

## TASK-022 — Implementar CourseCompletionService

Status: Done

Covers:
- AC-015
- AC-016
- AC-017
- AC-018
- AC-019

---

## TASK-023 — Integrar completion después de Lesson Completion

Status: Done

Covers:
- AC-015
- AC-016

---

## TASK-024 — Integrar completion después de Quiz Pass

Status: Done

Covers:
- AC-017

---

## TASK-025 — Implementar completed_at idempotente

Status: Done

Covers:
- AC-018
- AC-019

---

## TASK-026 — Verificar acceso después de completed

Status: Done

Covers:
- AC-020

---

## TASK-027 — Implementar mensaje final con certificado

Status: Done

Covers:
- AC-022

---

## TASK-028 — Implementar mensaje final sin certificado

Status: Done

Covers:
- AC-024

---

## TASK-029 — Verificar ausencia de generación de certificados

Status: Done

Covers:
- AC-023

### Confirmar ausencia de:

- PDF generation
- document generation
- certificate download endpoint

---

## TASK-030 — Implementar tests de QuizScoreCalculator

Status: Done

Covers:
- AC-010
- AC-011
- AC-012

---

## TASK-031 — Implementar tests de Eligibility

Status: Done

Covers:
- AC-008
- AC-014

---

## TASK-032 — Implementar tests de Attempts

Status: Done

Covers:
- AC-006
- AC-007
- AC-013
- AC-014

---

## TASK-033 — Implementar tests de CourseCompletionPolicy

Status: Done

Covers:
- AC-015
- AC-016
- AC-017

---

## TASK-034 — Implementar tests de CourseCompletionService

Status: Done

Covers:
- AC-018
- AC-019
- AC-020

---

## TASK-035 — Implementar tests de certificado informativo

Status: Done

Covers:
- AC-021
- AC-022
- AC-023
- AC-024

---

## TASK-036 — Implementar security tests de quiz

Status: Done

Covers:
- AC-026
- AC-027

### Casos

- anonymous
- invalid CSRF
- another user
- another course
- another quiz
- another question
- another answer
- forged score
- forged passed state

---

## TASK-037 — Implementar tests de integración quiz → completion

Status: Done

Covers:
- AC-017
- AC-018

---

## TASK-038 — Verificar quiz no modifica porcentaje

Status: Done

Covers:
- AC-025

---

## TASK-039 — Revisar concurrencia de attempt numbers

Status: Done

Covers:
- integridad

---

## TASK-040 — Revisar idempotencia Course Completion

Status: Done

Covers:
- AC-019

---

## TASK-041 — Verificar ausencia de scope creep

Status: Done

### Confirmar que no se implementa:

- PDF
- certificate generation
- email attachments
- advanced analytics
- course reporting

---

## TASK-042 — Ejecutar Quality Gate

Status: Done

---

## TASK-043 — Actualizar Implementation Status

Status: Done

---

## TASK-044 — Preparar candidato de auditoría

Status: Done

### Resultado esperado

Solo si todo pasa:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```
