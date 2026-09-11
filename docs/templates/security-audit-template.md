# Auditoría de Seguridad

SPEC: `<SPEC-ID>`
Ronda: `<N>`
Auditor: Mimo
Commit auditado: `<commit>`
Fecha: `<YYYY-MM-DD>`

Verdict: `<PASS | PASS WITH RECOMMENDATIONS | FAIL>`

Audit completeness: `<Complete | Partial>`

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

Blocking findings: `<n>`
Non-blocking findings: `<n>`

---

## 2. Superficies Revisadas

Cuando aplique:

- autenticación
- autorización
- ownership
- capabilities
- nonces
- CSRF
- XSS
- escaping
- sanitización
- SQL injection
- prepared statements
- IDOR
- REST
- AJAX
- uploads
- acceso a archivos
- secretos
- exposición de datos
- manipulación de reglas de negocio
- integración WooCommerce

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authorization | `<PASS | FAIL | N/A>` | `<evidence>` |
| CSRF | `<PASS | FAIL | N/A>` | `<evidence>` |
| XSS | `<PASS | FAIL | N/A>` | `<evidence>` |
| SQL Injection | `<PASS | FAIL | N/A>` | `<evidence>` |
| IDOR | `<PASS | FAIL | N/A>` | `<evidence>` |

---

## 4. Findings

### SEC-001 — `<título>`

Severity: `<Critical | High | Medium | Low | Info>`
Blocking: `<Yes | No>`
Classification: `<Security Vulnerability | Data Exposure | Authorization Defect | ...>`
Status: `<NEW | OPEN | ...>`

Affected requirement:

`<AC-ID or N/A>`

Affected files:

- `<path>`

Attack surface:

`<endpoint / action / flow>`

Description:

`<descripción>`

Evidence:

`<evidence>`

Attack scenario:

`<cómo podría explotarse>`

Expected protection:

`<protección esperada>`

Actual behavior:

`<comportamiento observado>`

Impact:

`<impacto>`

Required correction:

`<condición mínima necesaria>`

---

## 5. Findings de Rondas Anteriores

### SEC-XXX

Previous status: `<OPEN>`
Current status: `<RESOLVED | STILL OPEN | PARTIALLY RESOLVED | NOT REPRODUCIBLE>`

Evidence:

`<evidence>`

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — `<título>`

Severity: `<Low | Info>`
Blocking: No
Classification: Recommendation

Description:

`<recommendation>`

Benefit:

`<beneficio>`

Scope note:

La recomendación no representa un requisito de la SPEC actual.

---

## 7. Limitaciones

Limitaciones de la auditoría:

`<None | description>`

Ejemplos:

- endpoint no ejecutable en entorno local
- dependencia externa no disponible
- prueba dinámica no reproducible

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