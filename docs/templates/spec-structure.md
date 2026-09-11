# Estructura Base de una SPEC

## 1. Propósito

Cada SPEC debe representar una unidad de trabajo suficientemente pequeña como para:

- comprenderse con claridad
- implementarse de forma incremental
- auditarse de manera independiente
- remediarse sin afectar innecesariamente otras áreas
- aprobarse y hacer merge con trazabilidad

La estructura estándar será:

```text
docs/specs/<SPEC-ID>-<slug>/
├── spec.md
├── plan.md
├── feature.feature
├── tasks.md
├── implementation-status.md
└── audits/
```

Ejemplo:

```text
docs/specs/001-plugin-foundation/
├── spec.md
├── plan.md
├── feature.feature
├── tasks.md
├── implementation-status.md
└── audits/
```

---

# 2. Convención de Identificadores

Cada SPEC utiliza un número incremental de tres dígitos.

Ejemplos:

```text
001-plugin-foundation
002-courses-and-curriculum
003-woocommerce-enrollment
```

Dentro de la SPEC se utilizarán identificadores estables.

Criterios de aceptación:

```text
AC-001
AC-002
AC-003
```

Escenarios:

```text
SC-001
SC-002
SC-003
```

Tasks:

```text
TASK-001
TASK-002
TASK-003
```

Change Requests:

```text
CR-001
CR-002
```

Los IDs no deben renumerarse después de iniciar implementación.

---

# 3. `spec.md`

`spec.md` define QUÉ debe construirse.

No debe describir innecesariamente cómo implementarlo.

Estructura recomendada:

```markdown
# SPEC-XXX — Nombre

Status: Draft

## 1. Objetivo

...

## 2. Contexto

...

## 3. Alcance

...

## 4. Fuera de Alcance

...

## 5. Requisitos Funcionales

...

## 6. Requisitos No Funcionales

...

## 7. Reglas de Negocio

...

## 8. Criterios de Aceptación

### AC-001 — ...

...

### AC-002 — ...

...

## 9. Casos Límite

...

## 10. Dependencias

...

## 11. Riesgos

...

## 12. Decisiones Pendientes

...

## 13. Referencias

...
```

---

# 4. Status de SPEC

El campo:

```text
Status:
```

representa el estado documental de la especificación, no el estado de implementación.

Valores sugeridos:

```text
Draft
Approved
Superseded
```

El estado operativo del desarrollo vive en:

```text
implementation-status.md
```

---

# 5. Objetivo

Debe responder:

> ¿Qué problema resuelve esta SPEC?

Debe ser breve.

Ejemplo:

```markdown
## 1. Objetivo

Proporcionar la infraestructura mínima necesaria para que Kaanbal pueda
cargarse como plugin WordPress de forma segura y servir como base para
módulos posteriores.
```

---

# 6. Contexto

Describe únicamente la información necesaria para comprender la SPEC.

Debe referenciar documentos globales cuando sea posible:

```text
docs/project-context.md
docs/architecture.md
docs/data-model.md
```

No debe copiar grandes secciones de esos documentos.

---

# 7. Alcance

Debe definir explícitamente qué SÍ forma parte de la SPEC.

Ejemplo:

```markdown
## 3. Alcance

Esta SPEC incluye:

- bootstrap del plugin
- autoloading
- lifecycle de activación
- schema manager base
- infraestructura de testing
```

---

# 8. Fuera de Alcance

Debe proteger la SPEC contra scope creep.

Ejemplo:

```markdown
## 4. Fuera de Alcance

Esta SPEC no incluye:

- creación de cursos
- matrícula WooCommerce
- progreso
- quizzes
- certificados
```

Un auditor no puede exigir funcionalidades declaradas fuera de alcance.

---

# 9. Requisitos Funcionales

Describen comportamiento observable.

Ejemplo:

```text
RF-001
Kaanbal debe poder activarse desde WordPress Admin.

RF-002
Una segunda activación no debe duplicar infraestructura persistente.
```

El uso de IDs `RF-*` es opcional.

Los Acceptance Criteria siguen siendo la referencia principal para auditoría.

---

# 10. Requisitos No Funcionales

Ejemplos:

- compatibilidad
- seguridad
- idempotencia
- rendimiento
- mantenibilidad

Solo deben incluir requisitos verificables y relevantes.

Evitar:

> El código debe ser excelente.

Preferir:

> La activación no debe modificar WordPress Core ni WooCommerce Core.

---

# 11. Reglas de Negocio

Deben documentarse cuando exista comportamiento de dominio.

Ejemplo futuro:

```text
RB-001
Un usuario no puede tener más de una matrícula para el mismo curso.
```

No todas las SPEC necesitan reglas de negocio.

---

# 12. Acceptance Criteria

Todo criterio debe:

- tener ID
- ser verificable
- describir una condición de aceptación
- evitar depender de detalles internos salvo que sean una restricción explícita

Ejemplo:

```markdown
### AC-001 — Activación segura

Given una instalación compatible de WordPress
When un administrador activa Kaanbal
Then la activación finaliza sin errores fatales.
```

También puede escribirse en prosa si `feature.feature` contiene el Gherkin correspondiente.

---

# 13. Casos Límite

Solo incluir edge cases relevantes.

Ejemplos:

```text
plugin ya activado anteriormente
tabla ya existente
WooCommerce desactivado
autoload incompleto
```

No inflar la SPEC con escenarios hipotéticos sin impacto real.

---

# 14. Dependencias

Debe distinguir:

```text
Required
Optional
Future
```

Ejemplo:

```markdown
WordPress:
Required

WooCommerce:
Optional para SPEC-001.
La ausencia de WooCommerce no debe provocar fatal error.
```

---

# 15. Riesgos

Debe registrar riesgos conocidos antes de implementar.

Ejemplo:

```text
RISK-001
Una activación no idempotente puede duplicar datos.
```

No todo riesgo debe convertirse en AC.

---

# 16. Decisiones Pendientes

Solo deben existir decisiones que no impidan implementar la SPEC actual.

Si una decisión impide implementación correcta, la SPEC no está lista para implementación.

---

# 17. `plan.md`

`plan.md` define CÓMO se propone implementar la SPEC.

Estructura:

```markdown
# Plan — SPEC-XXX

## 1. Resumen Técnico

...

## 2. Componentes Afectados

...

## 3. Arquitectura

...

## 4. Persistencia

...

## 5. Flujo de Datos

...

## 6. Interfaces / Servicios

...

## 7. Seguridad

...

## 8. Estrategia de Pruebas

...

## 9. Secuencia de Implementación

...

## 10. Riesgos Técnicos

...

## 11. Decisiones

...
```

---

# 18. Resumen Técnico

Debe explicar la estrategia de implementación en pocas líneas.

No debe repetir `spec.md`.

---

# 19. Componentes Afectados

Ejemplo:

```text
kaanbal.php
src/Bootstrap/
src/Shared/
tests/
composer.json
```

Ayuda a limitar el impacto esperado.

---

# 20. Arquitectura

Debe indicar:

- responsabilidades
- dependencias
- boundaries
- componentes nuevos
- decisiones estructurales

Debe mantenerse consistente con:

```text
docs/architecture.md
```

---

# 21. Persistencia

Debe explicar:

- tablas
- CPT
- post meta
- índices
- migraciones

si aplican.

Si no aplica:

```text
No persistence changes.
```

---

# 22. Flujo de Datos

Puede utilizar diagramas simples.

Ejemplo:

```text
WordPress activation
    ↓
Kaanbal Activator
    ↓
Schema Manager
    ↓
Version storage
```

---

# 23. Seguridad

Debe identificar controles relevantes.

Ejemplo:

```text
No frontend endpoints are introduced by this SPEC.
Direct file access must be prevented.
```

---

# 24. Estrategia de Pruebas

Debe identificar las pruebas específicas de la SPEC.

Ejemplo:

```text
Unit:
- RequirementsTest

Integration:
- PluginActivationTest
- SchemaInstallTest

Manual:
- plugin visible in WP Admin
```

---

# 25. Secuencia de Implementación

Orden recomendado de tasks.

No necesita duplicar completamente `tasks.md`.

Ejemplo:

```text
1. bootstrap
2. autoload
3. activation
4. schema
5. tests
```

---

# 26. `feature.feature`

Contiene comportamiento observable en Gherkin.

Estructura:

```gherkin
Feature: <nombre>

  <descripción breve>

  @AC-001 @SC-001
  Scenario: <nombre>
    Given ...
    When ...
    Then ...

  @AC-002 @SC-002
  Scenario: <nombre>
    Given ...
    When ...
    Then ...
```

---

# 27. Reglas de Gherkin

Usar:

```text
Given
When
Then
And
```

La redacción puede estar en español.

Ejemplo:

```gherkin
@AC-001 @SC-001
Scenario: Activar Kaanbal
  Given WordPress está correctamente instalado
  And Kaanbal está inactivo
  When un administrador activa Kaanbal
  Then el plugin debe activarse sin errores fatales
```

No es necesario traducir keywords de Gherkin si el runner utilizado espera keywords en inglés.

---

# 28. Escenarios Independientes

Cada escenario debe representar un comportamiento concreto.

Evitar escenarios gigantes que validen toda la SPEC de una vez.

Preferir:

```text
SC-001 activation
SC-002 reactivation
SC-003 missing dependency
```

---

# 29. Gherkin y Detalles Técnicos

Evitar:

```gherkin
Then Installer::run() must execute dbDelta()
```

Preferir:

```gherkin
Then the required database schema should exist
```

La implementación concreta vive en `plan.md`.

---

# 30. `tasks.md`

Define las unidades de implementación para Codex.

Estructura:

```markdown
# Tasks — SPEC-XXX

## Resumen

Total: X

## TASK-001 — Nombre

Status: Pending

Covers:
- AC-001
- SC-001

### Objetivo

...

### Trabajo

- ...
- ...

### Validación

- ...

### Evidencia esperada

- ...

---

## TASK-002 — Nombre

...
```

---

# 31. Estados de Task

Permitidos:

```text
Pending
In Progress
Done
Blocked
Not Applicable
```

---

# 32. Task Scope

Una task debe ser suficientemente pequeña para:

- entender qué cambia
- verificar resultado
- relacionarla con AC

Pero no tan pequeña que genere burocracia artificial.

Evitar:

```text
TASK-001 crear archivo
TASK-002 agregar namespace
TASK-003 poner llave de cierre
```

Preferir unidades coherentes.

---

# 33. Covers

Cada task debe indicar:

```text
Covers:
- AC-XXX
- SC-XXX
```

cuando exista relación directa.

Una task de tooling puede indicar:

```text
Covers:
- Quality Gate
```

---

# 34. Validación

Debe indicar cómo demostrar que está terminada.

Ejemplo:

```text
Validation:
- PluginActivationTest
- php -l
```

---

# 35. Evidencia Esperada

Puede incluir:

- archivo creado
- clase
- test
- resultado
- tabla

Esto ayuda a Claude durante auditoría.

---

# 36. `implementation-status.md`

Es el tablero operativo de Codex.

Estructura estándar:

```markdown
# Implementation Status

SPEC: SPEC-XXX
Branch: `<branch>`
Current commit: `<commit>`

Status: Draft
Ready for audit: No
Current audit round: 0

## Tasks

Total: X
Done: 0
Pending: X
Blocked: 0

## Quality Gate

PHP Syntax:
NOT RUN

Unit Tests:
NOT RUN

Integration Tests:
NOT RUN

PHPCS:
NOT CONFIGURED

PHPStan:
NOT CONFIGURED

## Known Issues

None.

## Open Findings

None.

## Change Requests

None.

## Notes

...
```

---

# 37. Actualización de Status

Codex debe actualizarlo cuando cambie el estado.

Ejemplo al comenzar:

```text
Status: In implementation
Ready for audit: No
```

Al entregar:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: 1
```

---

# 38. Calidad de Información

No usar:

```text
Tests: OK
```

Preferir:

```text
Unit Tests:
PASS — 18 tests, 42 assertions
```

La evidencia debe ser concreta.

---

# 39. Known Issues

Debe contener problemas conocidos no resueltos.

Ejemplo:

```text
KNOWN-001
PHPCS cannot execute because dependency is not installed.
```

No ocultar limitaciones.

---

# 40. Change Requests

Si existe uno:

```text
CR-001 — OPEN
```

Una SPEC con `Change Request` bloqueante no debe pasar a auditoría.

---

# 41. Directorio `audits/`

Antes de la primera auditoría puede estar vacío:

```text
audits/
```

Cuando inicia:

```text
audits/
└── round-1/
```

---

# 42. Estructura de Ronda

```text
round-1/
├── code-audit.md
├── architecture-audit.md
├── security-audit.md
└── round-summary.md
```

Los archivos pueden crearse conforme se ejecutan las auditorías.

---

# 43. Inmutabilidad de Auditorías

Una vez cerrada una ronda:

```text
round-1/
```

no debe reescribirse para aparentar que el finding nunca existió.

Las correcciones aparecen en:

```text
round-2/
```

---

# 44. Referencia a Commit

Toda ronda debería identificar:

```text
Audit commit:
```

para garantizar que todos revisaron la misma implementación.

---

# 45. Revisión Humana

No se requiere un archivo adicional obligatorio para revisión humana.

La decisión puede reflejarse en:

```text
implementation-status.md
```

Ejemplo:

```text
Human review: Approved
```

Si posteriormente se requiere mayor trazabilidad puede incorporarse:

```text
human-review.md
```

pero no se implementará por defecto.

---

# 46. SPEC con Cambios Posteriores

Si una SPEC ya completada necesita nuevo comportamiento significativo:

No modificar silenciosamente la SPEC histórica.

Preferir nueva SPEC.

Ejemplo:

```text
006-final-quiz
```

posteriormente:

```text
014-question-bank
```

En lugar de reescribir retrospectivamente SPEC-006.

---

# 47. Correcciones Pequeñas

Un bug directamente relacionado con una SPEC previa puede referenciarla.

Pero si requiere comportamiento nuevo, debe evaluarse crear una nueva SPEC.

---

# 48. Tamaño de SPEC

Una SPEC está probablemente demasiado grande cuando:

- tiene demasiados dominios distintos
- necesita semanas de trabajo
- requiere múltiples decisiones independientes
- sus auditores no pueden revisarla de forma comprensible
- contiene decenas de escenarios no relacionados

Debe dividirse.

---

# 49. SPEC Demasiado Pequeña

Una SPEC puede ser demasiado pequeña cuando:

- solo crea una clase trivial
- no entrega comportamiento verificable
- depende completamente de otra SPEC que podría contenerla
- genera más documentación que implementación

---

# 50. Plantilla de Directorio

Para una nueva SPEC:

```text
docs/specs/XXX-name/
├── spec.md
├── plan.md
├── feature.feature
├── tasks.md
├── implementation-status.md
└── audits/
```

---

# 51. Plantilla `spec.md`

```markdown
# SPEC-XXX — <Nombre>

Status: Draft

## 1. Objetivo

## 2. Contexto

## 3. Alcance

## 4. Fuera de Alcance

## 5. Requisitos Funcionales

## 6. Requisitos No Funcionales

## 7. Reglas de Negocio

## 8. Criterios de Aceptación

### AC-001 — <Nombre>

### AC-002 — <Nombre>

## 9. Casos Límite

## 10. Dependencias

## 11. Riesgos

## 12. Decisiones Pendientes

## 13. Referencias
```

---

# 52. Plantilla `plan.md`

```markdown
# Plan — SPEC-XXX

## 1. Resumen Técnico

## 2. Componentes Afectados

## 3. Arquitectura

## 4. Persistencia

## 5. Flujo de Datos

## 6. Interfaces y Servicios

## 7. Seguridad

## 8. Estrategia de Pruebas

## 9. Secuencia de Implementación

## 10. Riesgos Técnicos

## 11. Decisiones
```

---

# 53. Plantilla `feature.feature`

```gherkin
Feature: <Nombre>

  @AC-001 @SC-001
  Scenario: <Nombre>
    Given ...
    When ...
    Then ...

  @AC-002 @SC-002
  Scenario: <Nombre>
    Given ...
    When ...
    Then ...
```

---

# 54. Plantilla `tasks.md`

```markdown
# Tasks — SPEC-XXX

## TASK-001 — <Nombre>

Status: Pending

Covers:
- AC-001
- SC-001

### Objetivo

### Trabajo

### Validación

### Evidencia esperada
```

---

# 55. Plantilla `implementation-status.md`

```markdown
# Implementation Status

SPEC: SPEC-XXX
Branch:
Current commit:

Status: Draft
Ready for audit: No
Current audit round: 0

## Tasks

Total:
Done:
Pending:
Blocked:

## Quality Gate

PHP Syntax:
NOT RUN

Unit Tests:
NOT RUN

Integration Tests:
NOT RUN

PHPCS:
NOT CONFIGURED

PHPStan:
NOT CONFIGURED

## Known Issues

None.

## Open Findings

None.

## Change Requests

None.

## Human Review

Pending.

## Notes
```

---

# 56. Regla de Sincronización

Los documentos deben ser consistentes.

Ejemplo inválido:

```text
spec.md:
AC-004 requiere X

feature.feature:
SC-004 exige Y

tasks.md:
implementa Z
```

La SPEC no está lista hasta resolver la inconsistencia.

---

# 57. Regla de Trazabilidad

Debe ser posible responder:

```text
¿Qué requisito originó este código?
```

y también:

```text
¿Dónde está implementado este requisito?
```

La documentación debe permitir ambas direcciones.

---

# 58. Regla de Scope

Una task no puede introducir un requirement que no exista en la SPEC.

Un `plan.md` tampoco puede ampliar silenciosamente `spec.md`.

Si el plan descubre una necesidad adicional:

```text
Change Request
```

o:

```text
Future Consideration
```

según corresponda.

---

# 59. Regla de Auditoría

Los auditores verifican contra el conjunto aprobado:

```text
spec.md
plan.md
feature.feature
tasks.md
```

pero la prioridad en caso de discrepancia es:

```text
spec.md
↓
approved global architecture
↓
feature.feature
↓
plan.md
↓
tasks.md
```

Una task nunca puede modificar implícitamente un criterio de aceptación.

---

# 60. Regla Final

Cada SPEC debe contar una historia completa:

```text
Qué queremos
→ cómo planeamos construirlo
→ cómo debe comportarse
→ qué trabajo debe hacerse
→ cuál es su estado
→ qué encontraron los auditores
```

Si esa historia no puede seguirse claramente, la SPEC necesita mejorar antes de continuar.