# Tasks — SPEC-003 WooCommerce Enrollment

## Resumen

Status general: Fixing audit findings

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- WooCommerce disponible en el entorno de integración

---

## TASK-001 — Crear migración de Product Courses

Status: Done

Covers:
- AC-001
- AC-002

### Trabajo

Crear:

`kaanbal_product_courses`

con índices y uniqueness correspondiente.

### Validación

Migración idempotente.

---

## TASK-002 — Crear migración de Enrollments

Status: Done

Covers:
- AC-005
- AC-006

### Trabajo

Crear tabla de matrículas con:

`UNIQUE(user_id, course_id)`

### Validación

No pueden existir matrículas duplicadas.

---

## TASK-003 — Crear migración de Enrollment Sources

Status: Done

Covers:
- AC-007
- AC-010

### Trabajo

Crear tabla de fuentes con trazabilidad WooCommerce.

### Validación

La misma fuente equivalente no se duplica.

---

## TASK-004 — Implementar ProductCourseRepository

Status: Done

Covers:
- AC-001
- AC-002
- AC-004
- AC-020

### Trabajo

Implementar:

- asociar
- desasociar
- resolver cursos por producto
- resolver productos por curso

---

## TASK-005 — Implementar UI Product → Courses

Status: Done

Covers:
- AC-003
- AC-019

### Trabajo

Agregar selector de cursos en administración WooCommerce.

### Seguridad

- nonce
- capability
- sanitización
- validación server-side

---

## TASK-006 — Implementar EnrollmentRepository

Status: Done

Covers:
- AC-005
- AC-006
- AC-016
- AC-017

### Trabajo

Soportar:

- find
- create
- activate
- revoke

---

## TASK-007 — Implementar EnrollmentSourceRepository

Status: Done

Covers:
- AC-007
- AC-010
- AC-013
- AC-014
- AC-015

### Trabajo

Soportar:

- create source
- find source
- list valid sources
- revoke sources by order

---

## TASK-008 — Implementar servicio de matrícula idempotente

Status: Done

Covers:
- AC-005
- AC-006
- AC-009
- AC-010
- AC-017

### Objetivo

Centralizar:

```text
grantCourse(user, course, source)
```

---

## TASK-009 — Implementar WooCommerce Order Adapter

Status: Done

Covers:
- arquitectura

### Trabajo

Encapsular:

- customer ID
- order ID
- status
- items
- product IDs
- order item IDs

---

## TASK-010 — Implementar resolución batch de cursos

Status: Done

Covers:
- AC-008
- AC-009

### Objetivo

Resolver eficientemente cursos para múltiples productos.

---

## TASK-011 — Implementar listener processing

Status: Done

Covers:
- AC-011
- SC-010

### Trabajo

Procesar pedidos al entrar en:

`processing`

---

## TASK-012 — Implementar listener completed

Status: Done

Covers:
- AC-012
- SC-011

### Trabajo

Procesar `completed` mediante el mismo flujo idempotente.

---

## TASK-013 — Implementar bundle enrollment

Status: Done

Covers:
- AC-008
- SC-007

### Validación

Producto con N cursos produce N matrículas.

---

## TASK-014 — Implementar overlapping enrollment

Status: Done

Covers:
- AC-009
- SC-008

### Validación

Dos productos con mismo curso producen:

- una matrícula
- múltiples sources válidas

---

## TASK-015 — Implementar protección contra reprocesamiento

Status: Done

Covers:
- AC-010
- SC-009

### Validación

Mismo pedido N veces conserva estado estable.

---

## TASK-016 — Implementar revocación por cancelled

Status: Done

Covers:
- AC-013
- AC-015
- AC-016

### Trabajo

Revocar fuentes y reevaluar matrícula.

---

## TASK-017 — Implementar revocación por refunded

Status: Done

Covers:
- AC-014
- AC-015
- AC-016

### Trabajo

Revocar fuentes del pedido y reevaluar matrícula.

---

## TASK-018 — Implementar evaluación de fuentes válidas

Status: Done

Covers:
- AC-014
- AC-015
- AC-016

### Objetivo

Determinar si enrollment debe continuar activo.

---

## TASK-019 — Implementar reactivación

Status: Done

Covers:
- AC-017
- SC-016

### Trabajo

Reactivar enrollment revoked cuando aparece una fuente válida nueva.

---

## TASK-020 — Manejar pedidos de invitado

Status: Done

Covers:
- SC-020

### Resultado

No crear matrícula con `user_id = 0`.

---

## TASK-021 — Manejar producto sin cursos

Status: Done

Covers:
- AC-004
- SC-004

### Resultado

No-op académico seguro.

---

## TASK-022 — Garantizar carga sin WooCommerce

Status: Done

Covers:
- AC-018
- SC-017

### Trabajo

Registrar integración únicamente cuando WooCommerce esté disponible.

---

## TASK-023 — Implementar tests Product-Course

Status: Done

Covers:
- AC-001
- AC-002
- AC-003
- AC-020

### Validación

Incluye asociaciones positivas y negativas.

---

## TASK-024 — Implementar tests EnrollmentRepository

Status: Done

Covers:
- AC-005
- AC-006
- AC-017

### Validación

Incluye uniqueness y reactivation.

---

## TASK-025 — Implementar tests Enrollment Sources

Status: Done

Covers:
- AC-007
- AC-010
- AC-015
- AC-016

---

## TASK-026 — Implementar tests WooCommerce processing

Status: Done

Covers:
- AC-008
- AC-009
- AC-010
- AC-011

### Casos

- 1 producto / 1 curso
- 1 producto / varios cursos
- varios productos / curso solapado
- procesamiento repetido

---

## TASK-027 — Implementar tests processing → completed

Status: Done

Covers:
- AC-012

### Validación

No duplicación.

---

## TASK-028 — Implementar tests cancelled

Status: Done

Covers:
- AC-013
- AC-015
- AC-016

---

## TASK-029 — Implementar tests refunded

Status: Done

Covers:
- AC-014
- AC-015
- AC-016

---

## TASK-030 — Implementar test nueva compra después de revocación

Status: Done

Covers:
- AC-017

---

## TASK-031 — Implementar tests de guest order

Status: Done

Covers:
- SC-020

---

## TASK-032 — Implementar tests de permisos administrativos

Status: Done

Covers:
- AC-019

---

## TASK-033 — Revisar concurrencia e idempotencia

Status: Done

Covers:
- AC-006
- AC-010

### Trabajo

Asegurar que unique constraints se manejen correctamente y no produzcan duplicados en carreras plausibles.

---

## TASK-034 — Verificar ausencia de scope creep

Status: Done

Covers:
- AC-021

### Validación

No se implementa:

- progreso
- quiz
- certificado

---

## TASK-035 — Ejecutar Quality Gate

Status: Done

Covers:
- Definition of Done

### Trabajo

Ejecutar checks configurados.

---

## TASK-036 — Actualizar Implementation Status

Status: Done

Covers:
- Definition of Done

---

## TASK-037 — Preparar candidato de auditoría

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
