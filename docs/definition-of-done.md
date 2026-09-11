# Definition of Done

## 1. Propósito

Este documento define las condiciones mínimas necesarias para que una SPEC avance dentro del flujo de desarrollo.

Establece gates objetivos para:

- preparación de implementación
- preparación de auditoría
- preparación de revisión humana
- finalización

El objetivo es evitar cambios de estado subjetivos y prevenir que un agente declare trabajo terminado sin evidencia suficiente.

---

## 2. Principio General

Una SPEC no está terminada porque exista código.

Está terminada solo cuando:

```text
Requisitos definidos
→ implementación completa
→ validación obligatoria aprobada
→ auditorías independientes aprobadas
→ blockers resueltos
→ revisión humana aprobada
```

---

## 3. Ready for Implementation

Una SPEC puede marcarse:

```text
Status: Ready for implementation
```

solo cuando se cumplan las condiciones aplicables.

### Requerido

- existe `spec.md`
- existe `plan.md`
- existe `feature.feature`
- existe `tasks.md`
- existe `implementation-status.md`
- alcance explícitamente definido
- fuera de alcance definido cuando aplique
- criterios de aceptación numerados
- criterios verificables
- escenarios Gherkin representan comportamiento requerido
- tasks trazables a criterios o escenarios
- contradicciones conocidas resueltas
- decisiones arquitectónicas necesarias documentadas
- aprobación humana otorgada

### No permitido

No debe marcarse lista para implementación cuando:

- los criterios son materialmente ambiguos
- existen requisitos contradictorios
- falta una decisión de alcance indispensable
- dependencias desconocidas impiden planear
- el plan viola un requisito aprobado

---

## 4. In Implementation

Cuando Codex inicia:

```text
Status: In implementation
Ready for audit: No
```

Estados permitidos de task:

```text
Pending
In Progress
Done
Blocked
Not Applicable
```

`Not Applicable` requiere justificación.

---

## 5. Criterio para Task Done

Una task puede marcarse:

```text
Status: Done
```

solo cuando:

- existe el código requerido
- el comportamiento está implementado
- existen pruebas aplicables o justificación
- las pruebas aplicables pasan
- se realizó la validación requerida
- no hay defectos bloqueantes conocidos
- la implementación coincide con el alcance aprobado

Escribir código no basta para marcar una task como `Done`.

---

## 6. Ready for Audit

Codex puede establecer:

```text
Status: Ready for audit
Ready for audit: Yes
```

solo cuando se cumplen todas las condiciones obligatorias.

### 6.1 Alcance

- todas las tasks requeridas están `Done`
- ninguna task obligatoria está `Pending`
- ninguna está `In Progress`
- no existe blocker de implementación sin resolver
- no hay criterio de aceptación conocido como no implementado
- no hay escenario requerido conocido como incumplido

### 6.2 Trazabilidad

Para cada criterio debe existir evidencia identificable.

Cadena deseada:

```text
Acceptance Criterion
→ Gherkin Scenario
→ Task
→ Code
→ Test o Evidence
```

No todos los criterios necesitan una prueba separada, pero ninguno puede considerarse satisfecho sin evidencia.

### 6.3 Quality Gate Automático

Todos los checks configurados y aplicables deben pasar.

Ejemplos:

- PHP syntax validation
- PHPUnit
- WordPress integration tests
- WooCommerce integration tests
- PHPCS
- PHPStan
- Composer validation
- behavior tests
- JavaScript tests
- build validation

El tooling exacto se define en:

`docs/testing-strategy.md`

### 6.4 Integridad de Pruebas

Antes de auditoría:

- las pruebas obligatorias deben ejecutarse
- los fallos deben corregirse
- los tests skipped deben explicarse
- las pruebas no ejecutadas no pueden declararse aprobadas
- no se deben debilitar pruebas para obtener verde
- las assertions deben representar comportamiento real

### 6.5 Integridad de Código

Antes de auditoría:

- no hay errores de sintaxis conocidos
- no hay runtime fatal errors conocidos
- no hay caminos obligatorios incompletos
- no hay bypass temporales
- no queda debug code salvo autorización
- no se modificó silenciosamente un requisito
- Codex no modificó artefactos de auditoría

### 6.6 Documentación

Antes de auditoría:

- `tasks.md` refleja la realidad
- `implementation-status.md` está actualizado
- problemas conocidos documentados
- limitaciones ambientales documentadas
- decisiones relevantes registradas
- ronda actual identificada
- commit de auditoría identificado cuando Git esté disponible

---

## 7. Estabilidad del Candidato de Auditoría

Cuando:

```text
Ready for audit: Yes
```

la implementación pasa a ser un candidato estable.

Durante la ronda:

- no cambia código de producción
- no cambian pruebas
- no cambian archivos protegidos
- todos los auditores revisan la misma revisión

Si hay cambios materiales:

```text
Ready for audit: No
```

y debe prepararse un nuevo candidato.

---

## 8. Auditoría Completa

Una auditoría individual está completa solo cuando:

- existe el archivo esperado
- identifica la revisión auditada
- se revisaron requisitos relevantes
- se revisó código relevante
- se revisaron o ejecutaron pruebas cuando fue posible
- findings contienen evidencia
- findings están bien clasificados
- el veredicto es explícito

Veredictos permitidos:

```text
PASS
PASS WITH RECOMMENDATIONS
FAIL
```

---

## 9. Criterio de FAIL

Una auditoría puede devolver:

```text
FAIL
```

solo si existe al menos un blocker válido.

Debe identificar:

- requisito o restricción afectada
- defecto concreto
- evidencia
- impacto
- corrección requerida o condición de aceptación

Preferencias y mejoras opcionales no son suficientes.

---

## 10. Ready for Human Review

Una SPEC puede marcarse:

```text
Status: Ready for review
Ready for audit: No
```

solo si:

Claude Code:

```text
PASS
o
PASS WITH RECOMMENDATIONS
```

Qwen:

```text
PASS
o
PASS WITH RECOMMENDATIONS
```

Mimo:

```text
PASS
o
PASS WITH RECOMMENDATIONS
```

y:

```text
Open blocking findings: 0
```

---

## 11. Estado de Remediación

También debe cumplirse:

- todos los blockers aceptados están resueltos
- blockers disputados tienen decisión humana
- la última remediación fue auditada
- no existe blocker sin resolver
- historial de auditoría preservado
- último round summary actualizado

---

## 12. Revisión Humana

La revisión humana termina cuando el responsable decide explícitamente:

```text
Approved
Changes requested
Rejected
Deferred
```

Solo:

```text
Approved
```

permite avanzar a merge.

---

## 13. Completed

Una SPEC puede marcarse:

```text
Status: Completed
```

solo cuando:

- revisión humana aprobada
- implementación completa
- pruebas obligatorias pasan
- última ronda de auditoría pasa
- no hay blockers
- correcciones aceptadas incluidas
- documentación actualizada
- merge aprobado o completado

---

## 14. Checklist Final

### Requisitos

- [ ] Alcance aprobado
- [ ] Criterios de aceptación completos
- [ ] Fuera de alcance documentado cuando aplique
- [ ] Gherkin alineado con aceptación
- [ ] Sin contradicciones sin resolver

### Implementación

- [ ] Todas las tasks requeridas `Done`
- [ ] Código requerido implementado
- [ ] Sin defectos bloqueantes conocidos
- [ ] Sin scope creep no autorizado
- [ ] Sin modificación silenciosa de requisitos

### Validación

- [ ] Checks obligatorios pasan
- [ ] Tests obligatorios pasan
- [ ] Fallos resueltos
- [ ] Checks no ejecutables documentados
- [ ] Existe trazabilidad

### Auditoría

- [ ] Claude pasa
- [ ] Qwen pasa
- [ ] Mimo pasa
- [ ] Sin blockers abiertos
- [ ] Los reportes apuntan a la revisión correcta
- [ ] Round summary actualizado

### Humano

- [ ] Revisión humana completada
- [ ] Recomendaciones revisadas cuando aplique
- [ ] Aprobación humana otorgada

### Final

- [ ] Documentación actualizada
- [ ] SPEC marcada `Completed`
- [ ] Merge aprobado o completado

---

## 15. Definition of Not Done

Una SPEC NO está terminada si:

- falta comportamiento obligatorio
- un criterio no tiene evidencia
- pruebas requeridas fallan
- existe blocker abierto
- un auditor devuelve `FAIL` sobre la implementación actual
- los auditores revisaron commits distintos
- los estados de tasks no reflejan la realidad
- falta aprobación humana
- se cambió alcance sin autorización

---

## 16. Fallos Ambientales

Un fallo ambiental no es automáticamente un fallo de implementación.

Ejemplos:

- base de datos no disponible
- contenedor de pruebas no disponible
- dependencia externa caída
- extensión PHP faltante

Debe registrarse como:

```text
Validation status: BLOCKED BY ENVIRONMENT
```

Codex debe documentar:

- comando ejecutado
- validación esperada
- error ambiental
- qué pudo verificarse
- qué permanece sin verificar

No debe declarar la validación como pasada.

---

## 17. Recomendaciones y Deuda Técnica

Una SPEC puede estar completa con recomendaciones no bloqueantes.

Ejemplos:

```text
ARCH-REC-001
SEC-REC-002
CODE-REC-004
```

Pueden convertirse posteriormente en:

- backlog
- future SPEC
- technical debt
- security hardening
- architecture consideration

No impiden completar la SPEC salvo promoción explícita por parte del humano.

---

## 18. Cambio de Alcance Durante Implementación

Si se detecta un cambio requerido:

```text
Status: Blocked
Ready for audit: No
```

hasta resolver el `Change Request`.

Después de aprobación deben sincronizarse:

```text
spec.md
plan.md
feature.feature
tasks.md
```

---

## 19. Principio de Evidencia Mínima

El workflow debe exigir evidencia suficiente sin producir documentación innecesaria.

Preferir:

```text
poca evidencia fuerte
```

sobre:

```text
mucha documentación débil
```

Una prueba aprobada, una referencia clara de código y trazabilidad directa tienen más valor que explicaciones extensas sin evidencia.

---

## 20. Regla Final

La Definition of Done se basa en evidencia.

El proyecto no acepta:

> Parece terminado.

Acepta:

> El requisito aprobado fue implementado, validado, auditado de forma independiente y aprobado por el responsable humano.