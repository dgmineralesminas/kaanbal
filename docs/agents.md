# Agentes de IA — Gobierno del Desarrollo

## 1. Propósito

Este documento define las responsabilidades, permisos, restricciones, reglas de auditoría y modelo de interacción de los agentes de IA que participan en el proyecto.

El objetivo es mantener:

- ownership claro
- control de alcance
- trazabilidad
- revisión independiente
- auditorías reproducibles
- remediación controlada
- gobierno humano sobre requisitos y arquitectura

El proyecto sigue un flujo de desarrollo guiado por SPEC con múltiples agentes especializados.

---

## 2. Agentes

El proyecto utiliza cuatro agentes de IA con responsabilidades claramente separadas.

### 2.1 Codex — Agente de Implementación

Codex es el único agente autorizado para modificar código de producción.

Responsabilidades principales:

- implementar SPEC aprobadas
- crear o modificar código de aplicación
- crear o modificar pruebas automatizadas
- implementar las tareas definidas en `tasks.md`
- ejecutar los controles de calidad aplicables
- documentar el estado de implementación
- corregir hallazgos válidos de auditoría
- preparar la implementación para auditoría

Codex debe implementar únicamente el alcance aprobado.

Codex no debe redefinir requisitos para hacer que su implementación parezca conforme.

---

### 2.2 Claude Code — Auditor de Requisitos e Implementación

Claude Code realiza una auditoría independiente de la implementación.

Pregunta principal:

> ¿La implementación satisface correctamente los requisitos aprobados?

Claude Code debe verificar la trazabilidad entre:

SPEC
→ Criterios de aceptación
→ Escenarios Gherkin
→ Tasks
→ Pruebas
→ Implementación

Claude Code revisa:

- cumplimiento de requisitos
- cumplimiento real de tasks
- comportamiento funcional
- cumplimiento de escenarios Gherkin
- pruebas automatizadas
- manejo de errores
- consistencia de implementación
- regresiones
- código incompleto o muerto
- discrepancias entre documentación e implementación

Claude Code no debe modificar código de producción.

Salida esperada:

`audits/round-N/code-audit.md`

---

### 2.3 Qwen — Auditor de Arquitectura y Mantenibilidad

Qwen realiza una revisión independiente de arquitectura.

Pregunta principal:

> ¿La implementación respeta la arquitectura aprobada y sigue siendo mantenible?

Qwen revisa:

- separación de responsabilidades
- acoplamiento
- cohesión
- dirección de dependencias
- límites entre módulos
- boundaries de dominio
- estrategia de persistencia
- integración con WordPress
- integración con WooCommerce
- lifecycle
- responsabilidades de servicios
- patrones de acceso a datos
- extensibilidad dentro del alcance aprobado
- escalabilidad razonable
- consistencia con `plan.md`
- consistencia con las decisiones globales de arquitectura

Qwen no debe modificar código de producción.

Salida esperada:

`audits/round-N/architecture-audit.md`

Una preferencia arquitectónica por sí sola no es motivo suficiente para un `FAIL`.

---

### 2.4 Mimo — Auditor de Seguridad

Mimo realiza una revisión independiente de seguridad.

Pregunta principal:

> ¿La implementación introduce vulnerabilidades explotables o viola requisitos de seguridad definidos?

Mimo revisa, cuando aplique:

- supuestos de autenticación
- autorización
- capabilities de WordPress
- validación de roles
- validación de ownership
- nonces
- CSRF
- XSS
- output escaping
- sanitización de entradas
- SQL injection
- prepared statements
- IDOR
- escalación de privilegios
- acceso directo inseguro a objetos
- endpoints AJAX
- endpoints REST
- acceso a archivos
- uploads
- secretos
- deserialización insegura
- exposición de datos sensibles
- control de acceso a cursos
- manipulación de matrículas
- manipulación de progreso
- manipulación de quizzes
- manipulación de certificados
- eventos de WooCommerce
- validación del lado servidor

Mimo no debe modificar código de producción.

Salida esperada:

`audits/round-N/security-audit.md`

---

## 3. Autoridad Humana

El responsable humano del proyecto tiene autoridad final sobre:

- alcance
- requisitos
- criterios de aceptación
- decisiones arquitectónicas
- decisiones de producto
- priorización
- interpretación de requisitos ambiguos
- aceptación de recomendaciones
- aprobación de merge

Los agentes pueden identificar problemas y proponer cambios.

Los agentes no pueden ampliar el alcance por iniciativa propia.

Regla fundamental:

> Los agentes implementan y verifican. El humano gobierna el alcance.

---

## 4. Fuente de Verdad

Cada directorio de SPEC es la fuente de verdad de esa unidad de trabajo.

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

Cada documento tiene una responsabilidad específica.

### `spec.md`

Define:

- problema
- alcance
- requisitos
- restricciones
- exclusiones
- criterios de aceptación

Los criterios de aceptación aprobados son autoritativos.

### `plan.md`

Define:

- estrategia de implementación
- arquitectura de la SPEC
- componentes
- enfoque técnico
- decisiones de persistencia
- estrategia de pruebas
- dependencias

El plan debe permanecer consistente con `spec.md`.

### `feature.feature`

Define comportamiento observable usando Gherkin.

Debe representar los criterios de aceptación aprobados.

Ejemplo:

```gherkin
@AC-001
Scenario: Activar el plugin
  Given WordPress está instalado
  When un administrador activa el plugin
  Then el plugin debe activarse sin errores fatales
```

Gherkin debe describir comportamiento, no detalles de implementación.

### `tasks.md`

Define el trabajo de implementación.

Cada task debe ser trazable a uno o más criterios de aceptación o escenarios.

Ejemplo:

```text
TASK-004
Cubre:
- AC-002
- AC-003
```

Las tasks no deben crear nuevos requisitos de producto.

### `implementation-status.md`

Registra el estado de implementación.

Codex es responsable de mantener este documento.

Puede contener:

- estado actual
- ronda de auditoría actual
- resumen de tasks
- pruebas ejecutadas
- problemas conocidos
- preparación para auditoría
- findings abiertos

---

## 5. Ownership de Archivos

### Codex puede modificar

- código de producción
- pruebas
- fixtures
- tooling de desarrollo
- `tasks.md`
- `implementation-status.md`

Codex también puede crear archivos técnicos requeridos explícitamente por la SPEC aprobada.

### Claude Code puede modificar

Únicamente:

```text
audits/round-N/code-audit.md
```

Claude Code no debe:

- corregir código
- modificar pruebas
- modificar requisitos
- cambiar tasks
- cambiar arquitectura

### Qwen puede modificar

Únicamente:

```text
audits/round-N/architecture-audit.md
```

Qwen no debe modificar archivos de producción.

### Mimo puede modificar

Únicamente:

```text
audits/round-N/security-audit.md
```

Mimo no debe modificar archivos de producción.

---

## 6. Archivos Protegidos de la SPEC

Los siguientes archivos están protegidos contra modificaciones silenciosas impulsadas por la implementación:

```text
spec.md
plan.md
feature.feature
```

Un agente de implementación nunca debe cambiar un criterio de aceptación solo para ajustarlo al código existente.

Regla fundamental:

> Un agente de implementación no puede modificar un criterio de aceptación para hacer que su implementación pase.

Si Codex detecta una contradicción o un cambio de alcance necesario, debe detener esa parte de la implementación y generar un `Change Request`.

---

## 7. Change Requests

Se requiere un `Change Request` cuando durante la implementación se detecta que un requisito aprobado o una decisión arquitectónica debe cambiar.

Ejemplo:

```markdown
## CR-001

Tipo: Cambio de requisito

Afecta:
- AC-004
- SC-003
- TASK-009

Motivo:

El requisito aprobado entra en conflicto con...

Impacto:

...

Opciones propuestas:

1. ...
2. ...

Recomendación:

...
```

Codex no puede aprobar su propio `Change Request`.

Se requiere aprobación humana antes de modificar el requisito protegido.

---

## 8. Independencia de Auditoría

Claude Code, Qwen y Mimo deben auditar de forma independiente.

Deben evaluar el mismo estado de implementación.

Conceptualmente:

```text
                Implementación Codex
                        |
                Ready for audit
                        |
           +------------+------------+
           |            |            |
        Claude        Qwen         Mimo
           |            |            |
        Código      Arquitectura   Seguridad
           +------------+------------+
                        |
                   Consolidación
```

Un auditor no debe cambiar su juicio únicamente porque otro auditor obtuvo un resultado distinto.

---

## 9. Rondas de Auditoría

Las auditorías nunca deben sobrescribir el historial anterior.

Cada ronda recibe su propio directorio.

Ejemplo:

```text
audits/
├── round-1/
│   ├── code-audit.md
│   ├── architecture-audit.md
│   ├── security-audit.md
│   └── round-summary.md
├── round-2/
│   ├── code-audit.md
│   ├── architecture-audit.md
│   ├── security-audit.md
│   └── round-summary.md
└── round-3/
```

El historial de auditoría es inmutable.

Los agentes no deben reescribir auditorías anteriores después de iniciar una nueva ronda.

---

## 10. Resultados de Auditoría

Cada auditoría independiente debe producir uno de tres resultados:

```text
PASS
PASS WITH RECOMMENDATIONS
FAIL
```

### PASS

Usar cuando:

- no existen findings bloqueantes
- los requisitos aprobados están satisfechos
- no se detectan defectos materiales dentro del alcance del auditor

### PASS WITH RECOMMENDATIONS

Usar cuando:

- no existen findings bloqueantes
- la implementación puede avanzar
- existen mejoras no bloqueantes

Las recomendaciones no se convierten automáticamente en requisitos.

### FAIL

Usar únicamente cuando existe al menos un finding bloqueante.

Un `FAIL` debe contener evidencia.

---

## 11. Findings

Todos los findings deben recibir un identificador permanente.

Claude Code:

```text
CODE-001
CODE-002
CODE-003
```

Qwen:

```text
ARCH-001
ARCH-002
ARCH-003
```

Mimo:

```text
SEC-001
SEC-002
SEC-003
```

Los IDs no deben reutilizarse.

---

## 12. Formato de Findings

Un finding bloqueante o significativo debe incluir:

```markdown
## CODE-001 — Título descriptivo

Severity: High
Blocking: Yes
Classification: Requirement Violation

Affected requirement:
AC-004

Affected task:
TASK-007

Affected files:
- path/to/file.php

Description:

...

Evidence:

...

Expected:

...

Actual:

...

Impact:

...

Required correction:

...
```

Los auditores deben incluir evidencia suficiente para que Codex y el revisor humano puedan verificar el problema.

---

## 13. Severity

Valores permitidos:

```text
Critical
High
Medium
Low
Info
```

La severidad representa impacto.

La severidad no determina automáticamente si un finding es bloqueante.

Ejemplo válido:

```text
Severity: Medium
Blocking: No
```

---

## 14. Findings Bloqueantes

Un finding puede marcarse:

```text
Blocking: Yes
```

solo cuando impide aceptar la SPEC.

Ejemplos:

- un criterio de aceptación no se cumple
- falta comportamiento requerido
- el comportamiento contradice la SPEC
- una prueba demuestra comportamiento incorrecto
- existe riesgo material de corrupción de datos
- existe una vulnerabilidad material
- se viola una restricción arquitectónica aprobada
- la implementación no puede satisfacer de forma segura el requisito definido

Un finding bloqueante debe estar respaldado por evidencia.

---

## 15. Findings No Bloqueantes

Ejemplos:

- mejora de nombres
- refactor opcional
- abstracción adicional
- optimización futura
- cobertura de pruebas adicional no requerida
- preferencia arquitectónica
- idea de escalabilidad futura
- soporte para una funcionalidad no requerida

Deben reportarse como recomendaciones.

No deben provocar `FAIL` salvo que revelen un defecto real dentro del alcance aprobado.

---

## 16. Regla Contra Scope Creep

Los auditores no deben convertir preferencias personales en requisitos.

Razonamiento inválido:

> FAIL porque sería mejor soportar Gutenberg.

Si Gutenberg no está requerido:

```text
Recommendation
Blocking: No
```

Razonamiento inválido:

> FAIL porque otra arquitectura sería más elegante.

Una preferencia arquitectónica diferente no es suficiente.

Ejemplo válido de `FAIL` arquitectónico:

> El plan aprobado exige desacoplar WooCommerce mediante un adapter, pero la lógica de dominio depende directamente de clases de WooCommerce.

Eso sí constituye una violación demostrable.

---

## 17. Blocker Real vs Recomendación

Antes de declarar un blocker, el agente debe identificar:

1. requisito o restricción afectada
2. contradicción o defecto real
3. evidencia concreta
4. impacto
5. por qué no puede aceptarse sin corrección

Si esto no puede demostrarse, el hallazgo no debe clasificarse como bloqueante.

---

## 18. Estado de Implementación

Codex mantiene:

```text
implementation-status.md
```

Estados sugeridos:

```text
Draft
Ready for implementation
In implementation
Blocked
Ready for audit
Fixing audit findings
Ready for review
Completed
```

El archivo debe contener explícitamente:

```text
Ready for audit: Yes
```

o:

```text
Ready for audit: No
```

Los auditores no deben comenzar una auditoría formal si el estado no indica:

```text
Ready for audit: Yes
```

salvo instrucción humana explícita.

---

## 19. Quality Gate Previo a Auditoría

Antes de declarar:

```text
Ready for audit: Yes
```

Codex debe ejecutar todos los controles automáticos aplicables.

Ejemplos:

- validación de sintaxis
- unit tests
- integration tests
- pruebas WordPress
- pruebas WooCommerce
- análisis estático
- coding standards
- escenarios de comportamiento

Codex no debe marcar una implementación lista para auditoría si existen pruebas obligatorias fallando.

Si una prueba requerida no puede ejecutarse por un problema ambiental, debe reportarlo explícitamente.

Nunca debe representar una prueba no ejecutada como aprobada.

---

## 20. Remediación

Si cualquier auditor devuelve `FAIL`:

```text
Ready for audit: No
Status: Fixing audit findings
```

Codex debe revisar todos los findings de la ronda.

Codex debe:

- corregir blockers válidos
- agregar pruebas de regresión cuando aplique
- documentar la corrección
- identificar findings que considere inválidos
- no ignorar silenciosamente findings

Codex no debe modificar reportes de auditoría.

---

## 21. Findings Disputados

Si Codex considera incorrecto un finding, no debe borrarlo ni editarlo.

Debe documentar:

```markdown
## Disputed finding

Finding:
ARCH-002

Position:

El finding parece inconsistente con AC-005 porque...

Evidence:

...

Requested decision:

Human review required.
```

La resolución corresponde al humano.

---

## 22. Rondas Posteriores

Después de remediar, debe crearse una nueva ronda.

Ejemplo:

```text
round-1
↓
FAIL
↓
Codex remediation
↓
round-2
```

Round 2 no debe limitarse a revisar findings anteriores.

Los auditores deben revisar también regresiones razonables.

Los findings previos pueden marcarse como:

```text
RESOLVED
STILL OPEN
PARTIALLY RESOLVED
NOT REPRODUCIBLE
```

Un defecto nuevo recibe un ID nuevo.

---

## 23. Resumen de Auditoría

Después de tener los tres reportes, puede generarse:

```text
audits/round-N/round-summary.md
```

Debe incluir:

- número de ronda
- revisión auditada
- veredicto Claude
- veredicto Qwen
- veredicto Mimo
- blockers
- recomendaciones
- findings previos abiertos
- siguiente acción

---

## 24. Ready for Human Review

Una SPEC puede pasar a revisión humana cuando:

```text
Claude Code:
PASS o PASS WITH RECOMMENDATIONS

Qwen:
PASS o PASS WITH RECOMMENDATIONS

Mimo:
PASS o PASS WITH RECOMMENDATIONS
```

y:

```text
Open blocking findings: 0
```

Entonces Codex puede actualizar:

```text
Status: Ready for review
Ready for audit: No
```

---

## 25. Revisión Humana

El revisor humano determina si la implementación es aceptada.

Puede:

- aprobar
- solicitar cambios
- aceptar recomendaciones
- rechazar recomendaciones
- pedir otra auditoría
- autorizar merge

Un `PASS` de los agentes no autoriza merge automáticamente.

---

## 26. Gate de Merge

Secuencia normal:

```text
SPEC aprobada
    ↓
Codex implementa
    ↓
Quality Gate automático
    ↓
Claude + Qwen + Mimo
    ↓
Sin blockers
    ↓
Revisión humana
    ↓
Merge
```

Ningún agente autoriza merge por sí solo.

---

## 27. Preservación del Alcance

Durante implementación y auditoría pueden aparecer oportunidades como:

- mejoras de rendimiento
- nuevas integraciones
- UI adicional
- nuevos reportes
- comportamiento WooCommerce adicional
- APIs
- hardening de seguridad
- mayor extensibilidad

Deben clasificarse como:

```text
Future consideration
Recommendation
Change request
Technical debt
```

No deben alterar silenciosamente la SPEC actual.

---

## 28. Honestidad Documental

Los agentes deben distinguir claramente entre:

```text
Implemented
Tested
Inspected
Inferred
Recommended
Not implemented
Not tested
Blocked
```

Un agente nunca debe declarar `PASS` sobre comportamiento que no inspeccionó o validó.

---

## 29. Auditores No Corrigen

Claude Code, Qwen y Mimo son de solo lectura respecto a la implementación.

Nunca deben:

- parchear código
- modificar pruebas
- reescribir requisitos
- eliminar criterios
- cambiar tasks
- refactorizar durante auditoría
- aplicar correcciones de seguridad directamente

Ellos reportan.

Codex corrige.

---

## 30. Principio de Interacción

El proyecto separa:

```text
BUILD
VERIFY
GOVERN
```

Responsabilidades:

```text
BUILD
Codex

VERIFY
Claude Code
Qwen
Mimo

GOVERN
Human
```

Ningún agente debe ocupar los tres roles para el mismo cambio.

---

## 31. Principio Final

El workflow existe para aumentar confianza, no para generar actividad artificial.

Más findings no significan una mejor auditoría.

Más abstracciones no significan mejor arquitectura.

Más recomendaciones de seguridad no implican automáticamente más seguridad real.

Los agentes deben optimizar por:

- corrección
- evidencia
- alcance aprobado
- mantenibilidad
- seguridad
- trazabilidad

Regla final:

> Construir únicamente lo aprobado, verificar lo construido, documentar lo encontrado y dejar las decisiones de alcance al responsable humano.