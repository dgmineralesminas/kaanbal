# Auditoría de Código y Cumplimiento

SPEC: `SPEC-008 — Course Administration and Reporting`
Ronda: `3`
Auditor: Claude Code
Commit auditado: `213ef01f23fab61458895271da6f3223dbe6857c` (código y pruebas), con el registro documental `cfd8a72` encima (branch `feature/spec-008-course-administration-and-reporting`)
Fecha: `2026-09-30`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` (revisión estática completa y simulación del auditor para CODE-003; ejecución del Quality Gate `BLOCKED BY ENVIRONMENT`, ver §5)

## 1. Resumen

Cadena revisada: `8c3ec6c → 42de843 (fix) → 6e7dc45 (docs) → 213ef01 (fix) → cfd8a72 (docs)`. El working tree está limpio.

Los cambios de producción desde la ronda 1 se limitan a `CourseReportsPage.php`:

- `url()` codifica los valores con `rawurlencode()` antes de llamar a `add_query_arg()`.
- Nuevo método `formatDate()`, que usa `wp_date()` con la zona y los formatos de WordPress.

El resto del código auditado en la ronda 1 no cambió.

Resultado:

- Los dos bloqueantes (CODE-001 y CODE-002) quedan **RESOLVED**.
- CODE-003 y CODE-004 quedan **RESOLVED**.
- CODE-005 a CODE-010 quedan **ACCEPTED RISK** por decisión humana registrada.

La justificación registrada para CODE-006, CODE-007 y CODE-009 no coincide con el comportamiento real del código (§3). No es bloqueante, pero conviene que el humano lo reconfirme con la información correcta.

Blocking findings: `0` (2 de rondas anteriores: RESOLVED)
Non-blocking findings: `6`, todos ACCEPTED RISK. No hay findings nuevos.

---

## 2. Estado de Findings

| Finding | Tipo | Ronda 2 | Ronda 3 |
|---|---|---|---|
| CODE-001 | Blocking | PARTIALLY RESOLVED | **RESOLVED** |
| CODE-002 | Blocking | STILL OPEN | **RESOLVED** |
| CODE-003 | Non-blocking (Medium) | OPEN | **RESOLVED** |
| CODE-004 | Non-blocking | OPEN | **RESOLVED** |
| CODE-005 | Non-blocking | OPEN | **ACCEPTED RISK** (humano) |
| CODE-006 | Non-blocking | OPEN | **ACCEPTED RISK** (humano); ver nota |
| CODE-007 | Non-blocking | OPEN | **ACCEPTED RISK** (humano); ver nota |
| CODE-008 | Non-blocking | OPEN | **ACCEPTED RISK** (humano) |
| CODE-009 | Info | OPEN | **ACCEPTED RISK** (humano); ver nota |
| CODE-010 | Info | OPEN | **ACCEPTED RISK** (humano) |

### CODE-001 — RESOLVED

Evidence:

En `cfd8a72`:

- `SPEC approved for implementation: Yes` y `spec.md` en `Status: Approved`.
- `Human Review: Pending`, que es el valor correcto antes de la consolidación.
- Manual Verification registrada como `OBSERVED`.
- 43 de 43 tasks en `Done` (`grep` en `tasks.md`).
- Los 26 AC con su evidencia.
- Quality Gate con las 10 entradas en `PASS`.
- `Current implementation commit: 213ef01`.

### CODE-002 — RESOLVED

Evidence:

`tests/Integration/course-administration-reporting.php` en `213ef01` cubre los cinco casos exigidos en la ronda 1:

| Caso | Aserción |
|---|---|
| Visitante | `wp_set_current_user(0)` y `canAccess() === false` |
| Filtros inválidos | `detail(..., 'invalid-status', 'invalid-quiz')` devuelve `total 6` con `filters.status === 'all'` y `filters.quiz === 'all'` |
| Quiz reprobado (AC-016) | `$failed`: 100%, 1 intento reprobado, `Reprobado`, `attempts_used 1` |
| Intentos agotados | `$exhausted`: 3 reprobados con máximo 3, `Intentos agotados`, `attempts_used 3` |
| Intentos ilimitados (EC-007) | Curso sin `_kaanbal_max_attempts`: `max_attempts === null`, `attempts_used 2`, `Reprobado` |

Recalculé las métricas actualizadas del fixture y cuadran:

- 4 activos al 100, 50, 100 y 100 → promedio 87.5, redondeado a **88**.
- Tasa de aprobación: 1 / (4 + 1) = **20%**.
- `not_passed`: 6 − 1 = **5**.

### CODE-003 — RESOLVED

Evidence:

`url()` aplica `rawurlencode()` a los parámetros antes de `add_query_arg()`.

La prueba crea 26 alumnos con email `a+b-page-…`. Comprueba que la búsqueda `a+b-page` devuelve los 26 en 2 páginas, y que el markup de paginación contiene `search=a%2Bb-page`.

Mi simulación con la cadena `ana+curso@gmail.com & Cía #1` hace el recorrido completo `rawurlencode → query string → parse_str` y recupera el valor original intacto. Cubre `+`, `&`, `#` y acentos.

### CODE-004 — RESOLVED

Evidence:

`formatDate()` interpreta `completed_at` como UTC y lo formatea con `wp_date(date_format . ' ' . time_format)`. Si la fecha está vacía o no se puede interpretar, muestra `—`.

La prueba fija `America/Mexico_City`, renderiza `render()` y busca la fecha formateada en el markup. La configuración se restaura en el `finally`.

La aserción calcula el valor esperado con la misma fórmula que el código. Aun así detecta la regresión que importa: el valor crudo `Y-m-d H:i:s` no coincide con los formatos de WordPress.

### CODE-005 a CODE-010 — ACCEPTED RISK

Evidence:

`implementation-status.md`, secciones "Open Findings" y "Change Requests", registra una decisión humana para cada uno. `audit-standard.md §36` permite `ACCEPTED RISK` con decisión humana, y este auditor no la reabre. Registro los estados como `ACCEPTED RISK`, porque `NOT APPLICABLE` no es un estado permitido en `§36`.

---

## 3. Nota sobre las justificaciones registradas (informativa, no bloqueante)

Para que la decisión humana se tome sobre hechos correctos, señalo dónde la justificación registrada no coincide con el código de `213ef01`:

| Finding | Justificación registrada | Comportamiento real del código |
|---|---|---|
| CODE-005 | "los estudiantes deben finalizar el quiz para aprobar el curso" | Es correcto en el flujo normal. El caso reportado era otro: un curso que **empieza a exigir quiz después** de que el alumno aprobó. Ese alumno sigue `Aprobado` con Quiz `No presentado`. La aceptación es razonable. |
| CODE-006 | "el reporte solo se muestra si hay alumnos inscritos" | `summary()` lista **todos** los cursos publicados, incluidos los que tienen 0 alumnos, y cada uno tiene el botón "Ver alumnos". Al abrir uno sin alumnos aparece "No hay alumnos que coincidan con los filtros." |
| CODE-007 | Igual que CODE-006 | El finding no dependía de que hubiera 0 alumnos: el selector de quiz aparece en cursos **sin quiz** que sí tienen alumnos, y al usarlo se obtiene una lista vacía. |
| CODE-008 | "los datos del reporte se muestran en inglés por ahora" | Los encabezados están en **español** (`Curso`, `Alumnos`, `Todos`…). El problema era que no se pueden traducir, no el idioma. Posponerlo es válido. |
| CODE-009 | "el reporte se publica automáticamente" | El finding trataba de que los **cursos** en borrador o privados, con matrículas, no aparecen en el reporte. No trataba de publicar el reporte. |
| CODE-010 | "Accepted risk" (Open Findings) / "el reporte se muestra automáticamente" (Change Requests) | Las dos secciones dicen cosas distintas. La aceptación es clara, pero el texto de Change Requests no corresponde al finding. |

Además, la sección `Change Requests` se usa para registrar la disposición de findings. En el flujo del proyecto, un Change Request es un cambio de requisitos, y solo CODE-004 lo es. Conviene mover el resto a "Open Findings".

Recomendación: el humano revisa estas filas y ratifica o cambia la decisión. Si ratifica, el estado queda `ACCEPTED RISK` sin más acción.

---

## 4. Findings Nuevos

Ninguno.

Revisé el diff completo de producción (`8c3ec6c..213ef01 -- src`: 19 líneas en un archivo) y de pruebas (98 líneas). No introduce comportamiento nuevo fuera de CODE-003 y CODE-004.

Observación informativa: el Quality Gate registra una "advertencia no fatal de WordPress core en `wp-admin/includes/plugin.php:1873`" durante el runner de reporting. Es coherente con invocar `render()` y el menú de administración fuera de una request real de wp-admin. No afecta al comportamiento en producción y no requiere acción.

---

## 5. Validación Ejecutada

```text
git log --oneline -6; git status                          → cadena confirmada, working tree limpio
git diff 8c3ec6c 213ef01 -- src tests                     → revisión línea por línea
grep "Status:" tasks.md                                   → 43 Done
php -l CourseReportsPage.php y la prueba de integración   → sin errores (PHP 8.4.21)
simulación rawurlencode + query string + parse_str        → valor recuperado idéntico
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT`. No pude ejecutar `composer test`, `composer analyse`, `composer cs` ni `composer test:integration:reporting`, por las mismas causas de rondas anteriores. Los resultados del Quality Gate declarados en `implementation-status.md` quedan `Not verified` por este auditor. Mi veredicto se basa en la lectura de las aserciones y del código.

---

## 6. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno. CODE-001 y CODE-002: RESOLVED.

Non-blocking findings:

- CODE-005 a CODE-010: ACCEPTED RISK (decisión humana). Se recomienda ratificar CODE-006, CODE-007 y CODE-009 con la información de §3.

Comentario final:

La remediación se hizo completa y en el orden correcto: primero el estado documental y las pruebas obligatorias, después las correcciones funcionales, cada una con prueba y con un commit de documentación que registra el candidato. Desde el ámbito de Claude Code, SPEC-008 puede pasar a las auditorías de Qwen y Mimo sobre `213ef01` + `cfd8a72`, y después a revisión humana.
