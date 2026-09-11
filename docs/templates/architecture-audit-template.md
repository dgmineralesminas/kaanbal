# Auditoría de Arquitectura y Mantenibilidad

SPEC: `<SPEC-ID>`
Ronda: `<N>`
Auditor: Qwen
Commit auditado: `<commit>`
Fecha: `<YYYY-MM-DD>`

Verdict: `<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Audit completeness: `<Complete | Partial>`

## 1. Resumen

Evaluación breve de la calidad arquitectónica de la implementación frente a:

- `plan.md`
- `docs/architecture.md`
- restricciones de `spec.md`
- decisiones arquitectónicas aprobadas

Blocking findings: `<n>`
Non-blocking findings: `<n>`

---

## 2. Áreas Revisadas

- separación de responsabilidades
- cohesión
- acoplamiento
- dirección de dependencias
- boundaries
- persistencia
- lifecycle WordPress
- integración con WooCommerce
- servicios
- repositorios
- extensibilidad dentro del alcance
- escalabilidad razonable

---

## 3. Conformidad con Arquitectura Aprobada

| Decisión / Restricción | Estado | Evidence |
|---|---|---|
| `<decision>` | `<PASS | FAIL>` | `<evidence>` |
| `<decision>` | `<PASS | FAIL>` | `<evidence>` |

---

## 4. Findings

### ARCH-001 — `<título>`

Severity: `<Critical | High | Medium | Low | Info>`
Blocking: `<Yes | No>`
Classification: `<Architecture Violation | Data Integrity Risk | Recommendation | ...>`
Status: `<NEW | OPEN | ...>`

Affected architecture decision:

`<reference>`

Affected files:

- `<path>`

Description:

`<descripción>`

Evidence:

`<evidence>`

Expected:

`<arquitectura esperada>`

Actual:

`<implementación observada>`

Impact:

`<impacto>`

Required correction:

`<corrección mínima>`

---

## 5. Findings de Rondas Anteriores

### ARCH-XXX

Previous status: `<OPEN>`
Current status: `<RESOLVED | STILL OPEN | PARTIALLY RESOLVED | NOT REPRODUCIBLE>`

Evidence:

`<evidence>`

---

## 6. Recomendaciones Arquitectónicas

### ARCH-REC-001 — `<título>`

Severity: `<Low | Info>`
Blocking: No
Classification: Recommendation

Description:

`<recommendation>`

Benefit:

`<beneficio esperado>`

Scope note:

Esta recomendación no modifica el alcance aprobado de la SPEC.

---

## 7. Riesgos Técnicos

Riesgos relevantes identificados:

- `<risk>`
- `<risk>`

Si no existen riesgos materiales:

`None`

---

## 8. Conclusión

Verdict:

`<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Blocking findings:

- `<finding>`

Recommendations:

- `<recommendation>`

Comentario final:

`<resumen breve>`