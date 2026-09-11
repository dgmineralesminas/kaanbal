# Flujo de Desarrollo

## 1. Propósito

Este documento define el flujo operativo utilizado para desarrollar, auditar, corregir, revisar y hacer merge de cada SPEC.

El proyecto sigue un proceso de Spec Driven Development con múltiples agentes especializados y aprobación humana.

Las responsabilidades globales de los agentes están definidas en:

`docs/agents.md`

---

## 2. Flujo Principal

Cada SPEC sigue este ciclo:

```text
Definición de SPEC
    ↓
Aprobación humana
    ↓
Ready for implementation
    ↓
Implementación Codex
    ↓
Quality Gate automático
    ↓
Ready for audit
    ↓
Auditorías independientes
    ↓
Consolidación
    ↓
PASS ───────────────→ Revisión humana → Merge
    ↓
FAIL
    ↓
Remediación Codex
    ↓
Nueva ronda
```

El proceso es iterativo.

Una SPEC puede requerir múltiples rondas de auditoría.

---

## 3. Estados de la SPEC

Estados permitidos:

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

El estado actual se registra en:

`implementation-status.md`

---

## 4. Draft

Una SPEC comienza como:

```text
Status: Draft
Ready for audit: No
```

Archivos esperados:

```text
spec.md
plan.md
feature.feature
tasks.md
implementation-status.md
```

No debe comenzar implementación de producción hasta que exista aprobación humana.

---

## 5. Ready for Implementation

Después de aprobación humana:

```text
Status: Ready for implementation
Ready for audit: No
```

Deben estar suficientemente definidos:

- alcance
- fuera de alcance
- criterios de aceptación
- escenarios Gherkin
- plan de implementación
- tasks

Codex puede comenzar.

---

## 6. Implementación

Cuando Codex inicia:

```text
Status: In implementation
Ready for audit: No
```

Codex debe:

- implementar solo el alcance aprobado
- completar las tasks
- crear pruebas relevantes
- actualizar estados
- registrar decisiones importantes
- reportar blockers en lugar de cambiar alcance

---

## 7. Quality Gate Automático

Antes de pasar a auditoría, Codex debe ejecutar los checks aplicables.

Ejemplos:

```text
PHP syntax checks
Unit tests
Integration tests
WordPress tests
WooCommerce tests
Static analysis
Coding standards
Behavior/Gherkin tests
```

La estrategia detallada vive en:

`docs/testing-strategy.md`

---

## 8. Quality Gate Fallido

Si falla un check obligatorio:

```text
Status: In implementation
Ready for audit: No
```

Codex debe:

- corregir el problema
- volver a ejecutar checks
- documentar blockers ambientales si existen

Una prueba obligatoria fallando impide declarar `Ready for audit: Yes`.

---

## 9. Ready for Audit

Cuando la implementación y los checks están completos:

```text
Status: Ready for audit
Ready for audit: Yes
Current audit round: N
```

La implementación pasa a ser un candidato estable de auditoría.

No debe modificarse durante la ronda salvo que la ronda sea abortada.

---

## 10. Objetivo de Auditoría

Cada ronda debe auditar un estado concreto del código.

Preferiblemente debe corresponder a un commit Git.

Ejemplo:

```text
Audit round: 1
Audit commit: a4d182f
```

Claude, Qwen y Mimo deben revisar exactamente la misma revisión.

---

## 11. Creación de Ronda

Primera auditoría:

```text
audits/round-1/
```

Archivos esperados:

```text
code-audit.md
architecture-audit.md
security-audit.md
round-summary.md
```

Rondas posteriores:

```text
audits/round-2/
audits/round-3/
```

Nunca se sobrescriben las anteriores.

---

## 12. Auditorías Independientes

Cuando:

```text
Ready for audit: Yes
```

se ejecutan las tres auditorías.

### Claude Code

Genera:

```text
audits/round-N/code-audit.md
```

Enfoque:

- requisitos
- implementación
- tasks
- pruebas
- Gherkin

### Qwen

Genera:

```text
audits/round-N/architecture-audit.md
```

Enfoque:

- arquitectura
- mantenibilidad
- coupling
- cohesion
- boundaries
- plan técnico aprobado

### Mimo

Genera:

```text
audits/round-N/security-audit.md
```

Enfoque:

- seguridad
- WordPress security
- autorización
- validación
- superficies de ataque
- protección de datos

---

## 13. Independencia

Las auditorías se consideran conceptualmente paralelas.

Aunque se ejecuten una detrás de otra, cada auditor debe emitir juicio independiente.

---

## 14. Veredictos

Permitidos:

```text
PASS
PASS WITH RECOMMENDATIONS
FAIL
```

`FAIL` requiere al menos un finding bloqueante.

---

## 15. Consolidación

Después de las tres auditorías:

```text
audits/round-N/round-summary.md
```

Debe incluir:

- ronda
- commit auditado
- veredicto Claude
- veredicto Qwen
- veredicto Mimo
- blockers
- recomendaciones
- findings previos abiertos
- resultado global
- siguiente acción

---

## 16. Resultado Global

La ronda pasa si los tres auditores devuelven:

```text
PASS
```

o:

```text
PASS WITH RECOMMENDATIONS
```

y:

```text
Open blocking findings: 0
```

---

## 17. Ronda Fallida

Si cualquier auditor reporta blocker:

```text
Overall: FAIL
```

La implementación vuelve a Codex.

Actualizar:

```text
Status: Fixing audit findings
Ready for audit: No
```

---

## 18. Remediación

Codex debe leer todos los reportes y clasificar findings como:

```text
Accepted
Disputed
Already resolved
Requires human decision
```

Los blockers válidos deben corregirse.

Las recomendaciones no son obligatorias salvo aprobación humana.

---

## 19. Reglas de Remediación

Para cada blocker aceptado:

- identificar causa raíz
- corregir
- agregar o actualizar pruebas
- documentar la solución
- evitar refactors no relacionados
- evitar ampliar alcance

Debe preferirse la corrección más pequeña que sea técnicamente correcta.

---

## 20. Findings Disputados

Si Codex considera inválido un finding:

```text
Status: Disputed
Human decision required: Yes
```

No debe ignorarlo.

Un blocker disputado impide avanzar hasta resolución humana.

---

## 21. Nueva Ronda

Tras corregir:

```text
Current audit round: N + 1
Status: Ready for audit
Ready for audit: Yes
```

Crear:

```text
audits/round-(N+1)/
```

Debe existir un nuevo commit candidato.

---

## 22. Auditorías Posteriores

Los auditores deben revisar:

1. findings anteriores
2. correcciones
3. criterios afectados
4. regresiones
5. nuevos defectos

Estados posibles de finding previo:

```text
RESOLVED
STILL OPEN
PARTIALLY RESOLVED
NOT REPRODUCIBLE
```

---

## 23. Regresión

Una ronda posterior no se limita a revisar si desapareció el finding.

También debe comprobar que la corrección no rompió comportamiento válido.

---

## 24. Ready for Human Review

Cuando la última ronda pasa:

```text
Status: Ready for review
Ready for audit: No
```

Condiciones:

```text
Claude: PASS / PASS WITH RECOMMENDATIONS
Qwen: PASS / PASS WITH RECOMMENDATIONS
Mimo: PASS / PASS WITH RECOMMENDATIONS

Open blocking findings: 0
```

---

## 25. Revisión Humana

El responsable humano revisa:

- funcionalidad
- intención de producto
- UX
- decisiones técnicas importantes
- recomendaciones
- deuda técnica
- cumplimiento de alcance

Puede:

```text
Approve
Request changes
Accept recommendations
Reject recommendations
Request another audit
Modify future scope
```

---

## 26. Cambios Solicitados por Humano

Si revisión humana solicita cambios:

```text
Status: In implementation
Ready for audit: No
```

Si afectan código ya auditado, normalmente deben pasar por una nueva ronda.

---

## 27. Merge

Después de aprobación humana:

```text
Status: Completed
Ready for audit: No
```

Puede hacerse merge.

Debe representar:

- SPEC aprobada
- tasks implementadas
- quality gate exitoso
- auditoría exitosa
- blockers resueltos
- aprobación humana

---

## 28. Git Flow Recomendado

Cada SPEC debería usar su propia rama.

Ejemplo:

```text
main
  \
   feature/spec-001-plugin-foundation
```

La rama contiene:

```text
SPEC
implementación
pruebas
auditorías
remediación
```

Después de aprobación:

```text
feature/spec-001-plugin-foundation
        ↓
       main
```

---

## 29. Límites de Commit

Commits sugeridos:

```text
docs: define SPEC-001
feat: implement SPEC-001
test: validate SPEC-001
audit: add round-1 reports
fix: remediate round-1 findings
audit: add round-2 reports
docs: mark SPEC-001 ready for review
```

---

## 30. Regla de Estabilidad de Auditoría

Una vez iniciada una ronda, no debe cambiar la implementación auditada.

Inválido:

```text
Claude audita commit A
Codex cambia código
Qwen audita commit B
```

Válido:

```text
Claude audita commit A
Qwen audita commit A
Mimo audita commit A
```

Si cambia el código:

```text
Abort round
o
Start new round
```

---

## 31. Finalización de SPEC

Una SPEC se considera completada solo cuando:

```text
Implementación completa
Checks obligatorios pasan
Última ronda pasa
No hay blockers
Revisión humana aprueba
Merge autorizado
```

---

## 32. Ejemplo

```text
SPEC-003 aprobada
        ↓
Codex implementa
        ↓
48 tests pass
        ↓
Ready for audit
        ↓
Audit commit: f122bd1
        ↓
Claude → PASS
Qwen   → FAIL ARCH-004
Mimo   → PASS
        ↓
Round 1 → FAIL
        ↓
Codex corrige ARCH-004
        ↓
Agrega regression test
        ↓
Audit commit: a72ce10
        ↓
Round 2
        ↓
Claude → PASS
Qwen   → PASS
Mimo   → PASS WITH RECOMMENDATIONS
        ↓
Overall PASS
        ↓
Revisión humana
        ↓
Approved
        ↓
Merge
```

---

## 33. Manejo de Recomendaciones

Las recomendaciones pueden convertirse en:

```text
future SPEC
backlog
technical debt
architecture consideration
security hardening backlog
```

El humano decide si pasan a futuro alcance.

---

## 34. Excepción de Emergencia

Si existe un defecto crítico de producción, el humano puede autorizar un proceso expedito.

Debe preservarse en lo posible:

- documentación
- pruebas
- auditoría
- trazabilidad
- aprobación humana

---

## 35. Principio Operativo

El flujo debe optimizar confianza, no burocracia.

Principio:

> Definir claramente, construir una vez, verificar de forma independiente, corregir con evidencia y hacer merge solo después de aprobación humana.