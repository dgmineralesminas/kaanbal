# Tasks — SPEC-002 Courses and Curriculum

## Resumen

Status general: Pending

La implementación debe comenzar únicamente después de:

- completar/aprobar SPEC-001
- aprobación humana de SPEC-002

---

## TASK-001 — Registrar Course CPT

Status: Pending

Covers:
- AC-001
- SC-001

### Trabajo

Registrar `kaanbal_course` con configuración adecuada para administración.

### Validación

- CPT registrado
- disponible en admin
- permisos coherentes
- sin errores

---

## TASK-002 — Implementar metadata de Course

Status: Pending

Covers:
- AC-002
- SC-001

### Trabajo

Agregar:

- duración
- instructor

### Validación

Guardar y recuperar metadata correctamente.

---

## TASK-003 — Registrar Module CPT

Status: Pending

Covers:
- AC-003
- SC-002

### Validación

`kaanbal_module` registrado correctamente.

---

## TASK-004 — Implementar relación Module → Course

Status: Pending

Covers:
- AC-004
- AC-013
- SC-002
- SC-010

### Trabajo

- persistir course ID
- validar existencia
- validar post type
- sanitizar input

### Validación

Relaciones válidas persisten; relaciones inválidas son rechazadas.

---

## TASK-005 — Implementar orden de módulos

Status: Pending

Covers:
- AC-005
- SC-003

### Trabajo

Usar mecanismo WordPress aprobado para posición.

### Validación

Consulta devuelve orden determinista.

---

## TASK-006 — Registrar Lesson CPT

Status: Pending

Covers:
- AC-006
- SC-004

### Validación

`kaanbal_lesson` registrado correctamente.

---

## TASK-007 — Implementar relación Lesson → Module

Status: Pending

Covers:
- AC-007
- AC-013
- SC-004
- SC-011

### Trabajo

Validar:

- module ID
- existencia
- tipo correcto

---

## TASK-008 — Implementar orden de lecciones

Status: Pending

Covers:
- AC-008
- SC-005

### Validación

Lecciones recuperadas de forma determinista.

---

## TASK-009 — Implementar modelo de fuente de video

Status: Pending

Covers:
- AC-009
- AC-010
- SC-006
- SC-007

### Trabajo

Definir proveedor y source sin acoplar Curriculum directamente a YouTube.

---

## TASK-010 — Implementar YouTubeVideoProvider

Status: Pending

Covers:
- AC-009
- SC-006

### Trabajo

Soportar formatos YouTube definidos.

Normalizar a una representación consistente.

### Validación

Unit tests.

---

## TASK-011 — Rechazar fuentes/proveedores no válidos

Status: Pending

Covers:
- AC-010
- SC-007

### Validación

Providers desconocidos no se consideran válidos.

---

## TASK-012 — Implementar consultas de módulos por curso

Status: Pending

Covers:
- AC-005
- AC-011
- SC-003
- SC-008

### Validación

Resultado ordenado y limitado al curso correspondiente.

---

## TASK-013 — Implementar consultas de lecciones por módulo

Status: Pending

Covers:
- AC-008
- AC-011
- SC-005
- SC-008

### Validación

Resultado ordenado y limitado al módulo.

---

## TASK-014 — Implementar CurriculumService

Status: Pending

Covers:
- AC-011
- AC-012
- SC-008
- SC-009

### Objetivo

Resolver jerarquía completa del curso.

### Validación

Course → Modules → Lessons correcto.

---

## TASK-015 — Resolver Course desde Lesson

Status: Pending

Covers:
- AC-012
- SC-009

### Validación

Lesson → Module → Course.

---

## TASK-016 — Implementar UI administrativa de Course

Status: Pending

Covers:
- AC-002

### Trabajo

Permitir administrar metadata requerida.

---

## TASK-017 — Implementar UI administrativa de Module

Status: Pending

Covers:
- AC-004
- AC-005

### Trabajo

Permitir seleccionar curso y orden.

---

## TASK-018 — Implementar UI administrativa de Lesson

Status: Pending

Covers:
- AC-007
- AC-008
- AC-009

### Trabajo

Permitir:

- módulo
- orden
- proveedor
- fuente de video

---

## TASK-019 — Implementar seguridad de handlers administrativos

Status: Pending

Covers:
- AC-016
- SC-014

### Trabajo

Validar:

- autosave
- revisions
- nonce
- capability
- sanitización

---

## TASK-020 — Implementar pruebas de CPT

Status: Pending

Covers:
- AC-001
- AC-003
- AC-006

### Validación

Tests de integración pasan.

---

## TASK-021 — Implementar pruebas de relaciones

Status: Pending

Covers:
- AC-004
- AC-007
- AC-012
- AC-013

### Validación

Casos positivos y negativos.

---

## TASK-022 — Implementar pruebas de ordering

Status: Pending

Covers:
- AC-005
- AC-008

### Validación

Incluye casos con mismo `menu_order`.

---

## TASK-023 — Implementar pruebas de YouTube provider

Status: Pending

Covers:
- AC-009
- AC-010

### Validación

Incluir:

- URL estándar
- youtu.be
- ID cuando se soporte
- URL inválida
- provider inválido

---

## TASK-024 — Implementar pruebas de CurriculumService

Status: Pending

Covers:
- AC-011
- AC-012
- AC-014
- AC-015

### Validación

Incluir:

- curso completo
- curso vacío
- módulo vacío

---

## TASK-025 — Implementar pruebas de capabilities

Status: Pending

Covers:
- AC-016

### Validación

Usuario autorizado vs no autorizado.

---

## TASK-026 — Verificar ausencia de scope creep

Status: Pending

Covers:
- AC-017
- SC-015

### Objetivo

Confirmar que SPEC-002 no implementa accidentalmente funcionalidades futuras.

---

## TASK-027 — Ejecutar Quality Gate

Status: Pending

Covers:
- Definition of Done

### Trabajo

Ejecutar:

- tests
- syntax
- Composer validation
- PHPCS
- PHPStan

según tooling disponible.

---

## TASK-028 — Actualizar Implementation Status

Status: Pending

Covers:
- Definition of Done

### Trabajo

Registrar:

- tasks
- AC
- tests
- quality gate
- known issues
- branch
- commit

---

## TASK-029 — Preparar candidato de auditoría

Status: Pending

Covers:
- Definition of Done

### Resultado

Solo cuando todo pase:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```