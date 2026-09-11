# Auditoría de Código y Cumplimiento

SPEC: `<SPEC-ID>`
Ronda: `<N>`
Auditor: Claude Code
Commit auditado: `<commit>`
Fecha: `<YYYY-MM-DD>`

Verdict: `<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Audit completeness: `<Complete | Partial>`

## 1. Resumen

Resumen breve del estado de la implementación y del resultado de la auditoría.

Tasks revisadas: `<n>`
Acceptance Criteria revisados: `<n>`
Escenarios Gherkin revisados: `<n>`

Blocking findings: `<n>`
Non-blocking findings: `<n>`

---

## 2. Alcance Auditado

Documentos revisados:

- `spec.md`
- `plan.md`
- `feature.feature`
- `tasks.md`
- `implementation-status.md`

Código relevante revisado:

- `<path>`
- `<path>`

Pruebas revisadas o ejecutadas:

- `<test>`
- `<test>`

---

## 3. Trazabilidad

| Acceptance Criterion | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001 | Yes | Yes | `<evidence>` |
| AC-002 | SC-002 | TASK-003 | Yes | Yes | `<evidence>` |

---

## 4. Verificación de Tasks

### TASK-001 — `<nombre>`

Status documentado: `<Done | Pending | ...>`

Resultado de auditoría:

`<PASS | FAIL>`

Acceptance Criteria relacionados:

- `<AC-ID>`

Escenarios relacionados:

- `<SC-ID>`

Evidence:

`<evidence>`

Observaciones:

`<notes>`

---

## 5. Findings

### CODE-001 — `<título>`

Severity: `<Critical | High | Medium | Low | Info>`
Blocking: `<Yes | No>`
Classification: `<classification>`
Status: `<NEW | OPEN | ...>`

Affected requirement:

`<AC-ID>`

Affected scenario:

`<SC-ID>`

Affected task:

`<TASK-ID>`

Affected files:

- `<path>`

Description:

`<descripción>`

Evidence:

`<evidencia concreta>`

Expected:

`<comportamiento esperado>`

Actual:

`<comportamiento observado>`

Impact:

`<impacto>`

Required correction:

`<condición mínima de corrección>`

---

## 6. Findings de Rondas Anteriores

### CODE-XXX

Previous status: `<OPEN>`
Current status: `<RESOLVED | STILL OPEN | PARTIALLY RESOLVED | NOT REPRODUCIBLE>`

Evidence:

`<evidence>`

---

## 7. Recomendaciones

### CODE-REC-001 — `<título>`

Severity: `<Low | Info>`
Blocking: No
Classification: Recommendation

Description:

`<recommendation>`

Reason:

`<why it could help>`

---

## 8. Validación Ejecutada

Comandos o pruebas ejecutadas:

```text
<command>
<command>
```

Resultados:

```text
<result>
```

Limitaciones ambientales:

`<None | description>`

---

## 9. Conclusión

Verdict:

`<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Blocking findings:

- `<finding>`
- `<finding>`

Non-blocking findings:

- `<finding>`
- `<finding>`

Comentario final:

`<resumen breve>`