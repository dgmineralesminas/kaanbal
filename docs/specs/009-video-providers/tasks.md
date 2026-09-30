# Tasks — SPEC-009 Video Providers

## Resumen

Status general: Pending

Dependencias:

- SPEC-001 completada
- SPEC-002 completada
- SPEC-004 completada

---

## TASK-001 — Auditar implementación actual de YouTube

Status: Pending

Covers:
- AC-016

### Objetivo

Determinar:

- almacenamiento actual
- parser
- renderer
- admin fields
- tests existentes

antes de modificar arquitectura.

---

## TASK-002 — Definir contrato VideoProvider

Status: Pending

Covers:
- AC-001
- AC-002
- AC-017

---

## TASK-003 — Implementar VideoProviderRegistry

Status: Pending

Covers:
- AC-001
- AC-002
- AC-003
- AC-017

---

## TASK-004 — Adaptar YouTubeVideoProvider al nuevo contrato

Status: Pending

Covers:
- AC-004
- AC-005
- AC-007
- AC-009
- AC-016

---

## TASK-005 — Implementar VimeoVideoProvider

Status: Pending

Covers:
- AC-002
- AC-006
- AC-008
- AC-010

---

## TASK-006 — Implementar allowlist de hosts YouTube

Status: Pending

Covers:
- AC-007
- seguridad

---

## TASK-007 — Implementar allowlist de hosts Vimeo

Status: Pending

Covers:
- AC-008
- seguridad

---

## TASK-008 — Implementar normalización YouTube

Status: Pending

Covers:
- AC-004
- AC-005

---

## TASK-009 — Implementar normalización Vimeo

Status: Pending

Covers:
- AC-006

---

## TASK-010 — Implementar Video Embed Renderer común

Status: Pending

Covers:
- AC-009
- AC-010
- AC-011
- AC-017

---

## TASK-011 — Implementar URL de embed YouTube

Status: Pending

Covers:
- AC-009

---

## TASK-012 — Implementar URL de embed Vimeo

Status: Pending

Covers:
- AC-010

---

## TASK-013 — Actualizar Lesson Admin UI

Status: Pending

Covers:
- AC-012
- AC-013
- AC-014
- AC-015

### Opciones

- Sin video
- YouTube
- Vimeo

---

## TASK-014 — Validar Provider + Source al guardar

Status: Pending

Covers:
- AC-007
- AC-008
- AC-015

---

## TASK-015 — Manejar Sin video

Status: Pending

Covers:
- AC-014

---

## TASK-016 — Actualizar Lesson View

Status: Pending

Covers:
- AC-009
- AC-010
- AC-017

### Objetivo

Eliminar lógica de provider específica dispersa en presentation.

---

## TASK-017 — Evaluar necesidad de migración de datos

Status: Pending

Covers:
- AC-016

### Resultado

Documentar:

- migration required
o
- migration not required

---

## TASK-018 — Implementar migración si es necesaria

Status: Pending

Covers:
- AC-016

### Regla

Debe ser idempotente.

Puede marcarse `Not Applicable` si TASK-017 demuestra que no se necesita.

---

## TASK-019 — Implementar tests VideoProviderRegistry

Status: Pending

Covers:
- AC-001
- AC-002
- AC-003

---

## TASK-020 — Implementar tests YouTube Provider

Status: Pending

Covers:
- AC-004
- AC-005
- AC-007
- AC-009

---

## TASK-021 — Implementar tests Vimeo Provider

Status: Pending

Covers:
- AC-006
- AC-008
- AC-010

---

## TASK-022 — Implementar tests de combinaciones Provider/Source

Status: Pending

Covers:
- AC-015

### Casos

- YouTube + YouTube
- Vimeo + Vimeo
- YouTube + Vimeo URL
- Vimeo + YouTube URL
- empty

---

## TASK-023 — Implementar tests de seguridad de sources

Status: Pending

Covers:
- AC-011

### Casos

- iframe payload
- script payload
- javascript URL
- foreign host
- malformed URL

---

## TASK-024 — Implementar test lección sin video

Status: Pending

Covers:
- AC-014

---

## TASK-025 — Implementar test de compatibilidad YouTube existente

Status: Pending

Covers:
- AC-016

---

## TASK-026 — Implementar test Lesson View YouTube

Status: Pending

Covers:
- AC-009
- AC-017

---

## TASK-027 — Implementar test Lesson View Vimeo

Status: Pending

Covers:
- AC-010
- AC-017

---

## TASK-028 — Verificar que video no modifica progreso

Status: Pending

Covers:
- AC-018

---

## TASK-029 — Verificar regresión Course Completion

Status: Pending

### Objetivo

Confirmar que provider no afecta reglas académicas.

---

## TASK-030 — Verificar ausencia de APIs externas

Status: Pending

### Confirmar que no se implementa:

- YouTube API
- Vimeo API
- OAuth
- API keys

---

## TASK-031 — Verificar ausencia de scope creep

Status: Pending

### Confirmar ausencia de:

- uploads
- playback tracking
- analytics
- private video auth
- DRM
- nuevos providers

---

## TASK-032 — Ejecutar Quality Gate

Status: Pending

---

## TASK-033 — Actualizar Implementation Status

Status: Pending

---

## TASK-034 — Preparar candidato de auditoría

Status: Pending

### Resultado esperado

Solo si todos los gates pasan:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```