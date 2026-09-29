# Tasks — SPEC-004 Course Access and Player

## Resumen

Status general: In implementation

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-003 completada

---

## TASK-001 — Implementar CourseAccessService

Status: Done

Covers:
- AC-001
- AC-002
- AC-003
- AC-004
- AC-005

### Trabajo

Implementar una política central de acceso basada en Enrollment.

### Validación

Tests para:

- active
- completed
- revoked
- sin enrollment
- usuario no autenticado

---

## TASK-002 — Implementar pruebas unitarias de acceso

Status: Done

Covers:
- AC-001
- AC-002
- AC-003
- AC-004
- AC-005

---

## TASK-003 — Definir routing frontend

Status: Done

Covers:
- AC-006
- AC-009

### Trabajo

Definir mecanismo soportado por WordPress para resolver Course y Lesson.

---

## TASK-004 — Implementar Course resolver

Status: Done

Covers:
- AC-006
- AC-017
- AC-019

### Validación

Distinguir correctamente Course válido/inexistente.

---

## TASK-005 — Implementar Lesson resolver

Status: Done

Covers:
- AC-009
- AC-016
- AC-018

### Validación

Resolver:

Lesson → Module → Course.

---

## TASK-006 — Implementar autorización para Course View

Status: Done

Covers:
- AC-001
- AC-002
- AC-003
- AC-004
- AC-005
- AC-022

---

## TASK-007 — Implementar autorización para Lesson View

Status: Done

Covers:
- AC-008
- AC-009
- AC-016
- AC-022

### Objetivo

No exponer contenido por acceso directo a la URL.

---

## TASK-008 — Implementar Course View

Status: Done

Covers:
- AC-006
- AC-007
- AC-019
- AC-020

### Trabajo

Mostrar:

- Course
- metadata
- curriculum

---

## TASK-009 — Implementar Curriculum frontend

Status: Done

Covers:
- AC-007
- AC-008
- AC-020

### Validación

Módulos/lecciones mantienen el orden de SPEC-002.

---

## TASK-010 — Implementar navegación libre

Status: Done

Covers:
- AC-008

### Validación

No consultar progreso ni prerequisitos.

---

## TASK-011 — Implementar Lesson View

Status: Done

Covers:
- AC-009
- AC-011

---

## TASK-012 — Implementar YouTube Embed Renderer

Status: Done

Covers:
- AC-010

### Seguridad

Construir el embed desde video ID normalizado.

No renderizar HTML arbitrario almacenado.

---

## TASK-013 — Implementar manejo de lección sin video

Status: Done

Covers:
- AC-011

---

## TASK-014 — Implementar LessonNavigationService

Status: Done

Covers:
- AC-012
- AC-013
- AC-014
- AC-015

### Trabajo

Resolver previous/next sobre curriculum ordenado.

---

## TASK-015 — Manejar módulos vacíos en navegación

Status: Done

Covers:
- AC-012
- AC-013
- AC-020

---

## TASK-016 — Implementar protección Course/Lesson cruzada

Status: Done

Covers:
- AC-016
- AC-022

### Objetivo

Evitar IDOR por combinación manipulada de identificadores.

---

## TASK-017 — Manejar Course inexistente

Status: Done

Covers:
- AC-017

---

## TASK-018 — Manejar Lesson inexistente

Status: Done

Covers:
- AC-018

---

## TASK-019 — Implementar templates frontend

Status: Done

Covers:
- AC-006
- AC-007
- AC-009

### Trabajo

Templates independientes del theme.

---

## TASK-020 — Implementar escaping de salida

Status: Done

Covers:
- seguridad

### Trabajo

Revisar:

- URLs
- atributos
- metadata
- contenido
- iframe

---

## TASK-021 — Implementar tests Course Access

Status: Done

Covers:
- AC-001
- AC-002
- AC-003
- AC-004
- AC-005

---

## TASK-022 — Implementar tests Course View

Status: Done

Covers:
- AC-006
- AC-007
- AC-019
- AC-020

---

## TASK-023 — Implementar tests Lesson View

Status: Done

Covers:
- AC-008
- AC-009
- AC-011
- AC-018

---

## TASK-024 — Implementar tests YouTube Player

Status: Done

Covers:
- AC-010
- AC-011

### Casos

- source válida
- source inválida
- provider no soportado
- sin video

---

## TASK-025 — Implementar tests de navegación

Status: Done

Covers:
- AC-012
- AC-013
- AC-014
- AC-015

### Casos

- first
- middle
- last
- single lesson
- multiple modules
- empty module

---

## TASK-026 — Implementar tests IDOR

Status: Done

Covers:
- AC-016
- AC-022

### Casos

- acceso directo sin matrícula
- Course A + Lesson B
- usuario matriculado solo en otro curso

---

## TASK-027 — Implementar tests de contenido inexistente

Status: Done

Covers:
- AC-017
- AC-018

---

## TASK-028 — Verificar ausencia de escrituras de progreso

Status: Done

Covers:
- AC-021

### Validación

Abrir Course/Lesson no crea ni modifica progreso.

---

## TASK-029 — Revisar performance de Curriculum

Status: Done

Covers:
- RNF-008

### Objetivo

Evitar consultas N+1 innecesarias.

---

## TASK-030 — Verificar ausencia de scope creep

Status: Done

Covers:
- AC-021

### Confirmar que no se implementa:

- progreso
- completion
- quiz
- certificados

---

## TASK-031 — Ejecutar Quality Gate

Status: Done

Covers:
- Definition of Done

---

## TASK-032 — Actualizar Implementation Status

Status: Done

Covers:
- Definition of Done

---

## TASK-033 — Preparar candidato de auditoría

Status: Pending

Covers:
- Definition of Done

### Resultado esperado

Solo si todos los gates pasan:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```
