# Tasks — SPEC-001 Plugin Foundation

## Resumen

Status general: Pending

Las tasks deben implementarse únicamente después de aprobación humana de la SPEC.

---

## TASK-001 — Inspeccionar el entorno de desarrollo

Status: Pending

Covers:
- RNF-007
- Quality Gate

### Objetivo

Determinar las versiones y herramientas reales disponibles antes de fijar dependencias.

### Trabajo

Registrar:

- PHP
- WordPress
- servidor
- base de datos
- WooCommerce
- plugins relevantes
- theme
- Composer
- Git

### Validación

La información debe provenir del entorno real.

### Evidencia esperada

Resumen en `implementation-status.md`.

---

## TASK-002 — Configurar Composer y PSR-4

Status: Pending

Covers:
- AC-006
- SC-005

### Objetivo

Habilitar autoloading para `Kaanbal\`.

### Trabajo

- crear `composer.json`
- configurar PSR-4
- generar autoload
- definir scripts iniciales cuando corresponda

### Validación

Una clase bajo `src/` puede resolverse mediante Composer.

---

## TASK-003 — Crear archivo principal del plugin

Status: Pending

Covers:
- AC-001
- AC-002
- AC-007
- SC-001
- SC-006

### Objetivo

Crear el entry point mínimo de Kaanbal.

### Trabajo

- metadata
- text domain
- direct access guard
- autoload
- version bootstrap
- activation/deactivation hooks
- arranque de Plugin

### Validación

WordPress reconoce el plugin y PHP no reporta errores de sintaxis.

---

## TASK-004 — Implementar bootstrap principal

Status: Pending

Covers:
- AC-005
- SC-004

### Objetivo

Centralizar la inicialización de Kaanbal.

### Trabajo

Crear la clase principal de bootstrap y mantener fuera del entry point la lógica de inicialización.

### Validación

Kaanbal activo inicializa el bootstrap sin fatal errors.

---

## TASK-005 — Implementar verificación de requisitos

Status: Pending

Covers:
- AC-008
- AC-009
- SC-007
- SC-008

### Objetivo

Detectar compatibilidad del entorno sin acoplar Foundation a WooCommerce.

### Trabajo

- requisitos PHP
- requisitos WordPress
- comportamiento ante incompatibilidad
- ausencia de WooCommerce segura

### Validación

Pruebas unitarias/integración correspondientes.

---

## TASK-006 — Implementar lifecycle de activación

Status: Pending

Covers:
- AC-002
- AC-003
- AC-011
- AC-012
- SC-001
- SC-002
- SC-010

### Objetivo

Implementar instalación inicial segura e idempotente.

### Trabajo

- Activator
- ejecución de Schema Manager
- versionado
- reactivación

### Validación

Activación y reactivación completan sin duplicados ni fatal errors.

---

## TASK-007 — Implementar lifecycle de desactivación

Status: Pending

Covers:
- AC-004
- SC-003

### Objetivo

Permitir desactivación segura.

### Trabajo

Crear Deactivator mínimo.

### Validación

Desactivar no elimina persistencia ni genera errores fatales.

---

## TASK-008 — Implementar versionado de plugin y schema

Status: Pending

Covers:
- AC-010
- AC-011
- SC-009
- SC-010

### Objetivo

Separar versión de plugin y versión de base de datos.

### Trabajo

- fuente única de plugin version
- almacenamiento de schema version
- lectura de versión instalada

### Validación

Pruebas sobre lectura y persistencia de versiones.

---

## TASK-009 — Implementar Schema Manager base

Status: Pending

Covers:
- AC-011
- AC-012
- SC-010

### Objetivo

Crear infraestructura para migraciones futuras.

### Trabajo

- determinar versión instalada
- determinar versión objetivo
- ejecutar updates necesarios
- guardar versión tras éxito
- garantizar idempotencia

### Validación

Ejecutar repetidamente mantiene estado consistente.

---

## TASK-010 — Implementar registro simple de servicios

Status: Pending

Covers:
- AC-013
- SC-011

### Objetivo

Registrar componentes base sin introducir un container complejo.

### Trabajo

- ServiceRegistry o equivalente
- inicialización explícita
- integración con Plugin bootstrap

### Validación

Servicios configurados se inicializan en orden predecible.

---

## TASK-011 — Crear infraestructura de tests unitarios

Status: Pending

Covers:
- AC-014
- SC-012

### Objetivo

Permitir pruebas aisladas.

### Trabajo

- PHPUnit compatible
- configuración
- bootstrap de tests
- tests iniciales

### Validación

Suite unit ejecutable.

---

## TASK-012 — Crear infraestructura de tests de integración

Status: Pending

Covers:
- AC-014
- SC-012

### Objetivo

Permitir validar comportamiento real con WordPress cuando sea viable.

### Trabajo

Configurar harness de integración compatible con el entorno.

### Validación

Al menos una prueba de integración real puede ejecutarse si el entorno lo permite.

---

## TASK-013 — Crear pruebas de activación y reactivación

Status: Pending

Covers:
- AC-002
- AC-003
- AC-012
- SC-001
- SC-002
- SC-010

### Objetivo

Demostrar lifecycle idempotente.

### Validación

Tests correspondientes pasan.

---

## TASK-014 — Crear prueba de desactivación segura

Status: Pending

Covers:
- AC-004
- SC-003

### Validación

La desactivación preserva persistencia.

---

## TASK-015 — Crear prueba sin WooCommerce

Status: Pending

Covers:
- AC-008
- SC-007

### Objetivo

Demostrar que WooCommerce no es requisito para Foundation.

### Validación

Kaanbal carga sin WooCommerce activo.

---

## TASK-016 — Configurar PHP syntax quality check

Status: Pending

Covers:
- AC-015
- SC-013

### Objetivo

Detectar errores sintácticos automáticamente.

### Validación

Check ejecutable sobre código propio.

---

## TASK-017 — Configurar PHPCS cuando sea compatible

Status: Pending

Covers:
- AC-015
- SC-013

### Objetivo

Aplicar WordPress Coding Standards razonables.

### Validación

Registrar:

- PASS
- FAIL
- o NOT CONFIGURED con justificación válida

---

## TASK-018 — Configurar PHPStan cuando sea compatible

Status: Pending

Covers:
- AC-015
- SC-013

### Objetivo

Agregar análisis estático razonable.

### Validación

Registrar:

- PASS
- FAIL
- o NOT CONFIGURED con justificación

---

## TASK-019 — Configurar scripts de quality gate

Status: Pending

Covers:
- AC-015
- SC-013

### Objetivo

Simplificar ejecución de checks.

### Trabajo

Definir scripts Composer o comandos equivalentes documentados.

### Validación

Los comandos ejecutables reportan correctamente resultados.

---

## TASK-020 — Ejecutar suite y quality gate final

Status: Pending

Covers:
- AC-014
- AC-015
- SC-012
- SC-013

### Objetivo

Validar la implementación antes de auditoría.

### Trabajo

Ejecutar todos los checks configurados.

### Validación

No existen mandatory checks fallando.

---

## TASK-021 — Actualizar estado de implementación

Status: Pending

Covers:
- Definition of Done

### Objetivo

Registrar evidencia real.

### Trabajo

Actualizar:

- tasks
- entorno
- tests
- quality gate
- known issues
- commit
- audit round

### Validación

`implementation-status.md` refleja la realidad.

---

## TASK-022 — Preparar candidato para auditoría

Status: Pending

Covers:
- Definition of Done

### Objetivo

Marcar la SPEC lista únicamente si cumple todos los gates.

### Resultado esperado

```text id="2l1cz1"
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```

solo si todos los requisitos obligatorios están satisfechos.