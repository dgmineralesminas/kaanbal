# Resumen de Ronda de Auditoría

SPEC: `<SPEC-ID>`
Ronda: `<N>`
Commit auditado: `<commit>`
Fecha: `<YYYY-MM-DD>`

Overall verdict: `<PASS | FAIL>`

## 1. Veredictos

Claude Code:

`<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Qwen:

`<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Mimo:

`<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

---

## 2. Blocking Findings

Total: `<n>`

- `<CODE-XXX>`
- `<ARCH-XXX>`
- `<SEC-XXX>`

Si no existen:

`None`

---

## 3. Non-Blocking Findings

Total: `<n>`

- `<finding>`
- `<finding>`

---

## 4. Findings de Rondas Anteriores

| Finding | Estado |
|---|---|
| `<CODE-001>` | `<RESOLVED>` |
| `<ARCH-001>` | `<STILL OPEN>` |
| `<SEC-001>` | `<RESOLVED>` |

---

## 5. Resultado de la Ronda

La ronda se considera:

`<PASS | FAIL>`

Regla:

- cualquier auditor con `FAIL` → Overall `FAIL`
- todos en `PASS` o `PASS WITH RECOMMENDATIONS` → Overall `PASS`

---

## 6. Siguiente Acción

Si Overall es `FAIL`:

```text
Return to Codex for remediation.
Status: Fixing audit findings
Ready for audit: No
```

Si Overall es `PASS`:

```text
Status: Ready for review
Ready for audit: No
Proceed to human review.
```

---

## 7. Observaciones

`<optional notes>`