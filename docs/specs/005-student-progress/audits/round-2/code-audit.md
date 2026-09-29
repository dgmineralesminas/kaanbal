# Auditoría de Código y Cumplimiento

SPEC: `SPEC-005 — Student Progress`
Ronda: `2`
Auditor: Claude Code
Commit auditado: `0a666ddc6f9dc9fc5c5bba6bf7bea3132abbff48` (candidato registrado en `implementation-status.md`), revisado en el HEAD `bb7ae59` (branch `feature/spec-005-student-progress`)
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS, PHPStan y arnés propio. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` (ver §8).

## 1. Resumen

La remediación de Codex resuelve el único blocker de la ronda 1 y atiende todos los findings no bloqueantes.

- **CODE-001 (blocker) → RESOLVED.** Hay una prueba de endpoint que envía un `user_id` forjado y comprueba que el progreso se registra solo para el usuario autenticado. `implementation-status.md` describe ahora la cobertura real.
- **CODE-002 → PARTIALLY RESOLVED.** Se eliminó el defecto material: un curso incompleto ya nunca reporta 100%. Queda, como decisión explícita pero no aprobada por el humano, que un avance muy pequeño se muestre como 0% (p. ej. 1/201).
- **CODE-003 a CODE-008 → RESOLVED.**

No encontré regresiones ni defectos nuevos en los cambios. El diff de producción es pequeño: `ProgressCalculator` (1 línea), `CompleteLessonAction` (manejo de error, textos, redirección) y el texto de un aviso en `Plugin`.

Tasks revisadas: `36` (foco en TASK-003, 007, 022, 026, 027, 031 y las afectadas por la remediación)
Acceptance Criteria revisados: `20`
Escenarios Gherkin revisados: `18`

Blocking findings: `0`
Non-blocking findings: `1` abierto (CODE-002, parcialmente resuelto)

---

## 2. Alcance Auditado

Git:

- `git diff b863db4 0a666dd`: `src/Bootstrap/Plugin.php`, `src/Progress/Application/ProgressCalculator.php`, `src/Progress/Presentation/Frontend/CompleteLessonAction.php`, `tests/Integration/{student-progress,course-access-player}.php`, `tests/Integration/support/progress-endpoint-request.php`, `tests/Unit/ProgressCalculatorTest.php`.
- `git diff 0a666dd bb7ae59`: solo `implementation-status.md`. Verificado `git diff --quiet 0a666dd bb7ae59 -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- `tasks.md`, `spec.md`, `plan.md` y `feature.feature` sin cambios desde la ronda 1.
- En el working tree hay dos archivos sin commitear, ajenos a este reporte: `audits/round-1/architecture-audit.md` (Qwen, `PASS`) y `audits/round-1/security-audit.md` (Mimo, `PASS WITH RECOMMENDATIONS`), ambos sobre `6136722`. No los usé para formar este veredicto.

Revisé íntegramente:

- los archivos de producción y de prueba modificados, listados arriba;
- `implementation-status.md` en `bb7ae59`;
- `templates/frontend/{course,lesson}.php` y `LessonProgressRepository.php`, sin cambios, para confirmar que no hay regresiones.

---

## 3. Trazabilidad (cambios respecto a la ronda 1)

Solo se listan los criterios cuya evidencia cambió. El resto se mantiene como en la ronda 1.

| AC | Scenario | Task | Implemented | Verified (r1 → r2) | Evidence |
|---|---|---|---|---|---|
| AC-003 | SC-003 | TASK-010, 031 | Yes | Yes → Yes | `student-progress.php:164-172`: 5 requests de endpoint → `COUNT(*) = 1` |
| AC-005 | SC-005 | TASK-027 | Yes | Yes/Partial → **Yes** | Caso de endpoint corregido a `array($active, $course_a, $lesson_b, 'valid')` → 403, y `:200-206` comprueba 0 filas |
| AC-006 | SC-006 | TASK-007, 026, 027 | Yes | Static → **Yes** | `student-progress.php:174-183`: `other` autenticado envía `--posted-user=$active` para `lesson_a_four`; se exige exit 0, 0 filas para `active` y 1 para `other`. El helper añade `$_POST['user_id']` (`progress-endpoint-request.php:31-33`) |
| AC-009 / AC-017 | SC-009 / SC-015 | TASK-003, 019 | Yes | Yes (Partial en borde) → **Yes** | `ProgressCalculator.php:19` `min(99, …)`; `ProgressCalculatorTest`: `199/200 → 99`, `200/201 → 99`. Arnés: `CourseProgressService` 200 de 201 → 99 |
| AC-012 | SC-012 | TASK-011, 015 | Yes | Partial → **Yes** | `course-access-player.php:195-213`: con 1 completion, "1 de 2 lecciones completadas", `aria-valuenow="50"`, `is-completed`, "Completada" y "Reproducir" |
| AC-014 / AC-016 | SC-014 | TASK-013, 015 | Yes | Partial → **Yes** | `course-access-player.php:216-224`: lección completada muestra "Lección completada" y no el formulario |

---

## 4. Verificación de Tasks

| Task | r1 | r2 | Evidencia |
|---|---|---|---|
| TASK-003, 019 | PASS con defecto | PASS | CODE-002 (parcial, no bloqueante) |
| TASK-022 | PASS con observación | PASS | `implementation-status.md` § "Concurrency and TOCTOU": orden de validación, `UNIQUE` + `INSERT IGNORE`, ventana residual aceptada |
| TASK-026, 027 | **FAIL** | **PASS** | Prueba de `user_id` forjado; casos de endpoint corregidos; verificación de 0 filas tras rechazos |
| TASK-031 | PASS | PASS | 5 repeticiones |
| TASK-002 | PASS con observación | PASS | Decisión documentada ("Repository API decision") |
| TASK-034, 035, 036 | PASS con observación | PASS | Quality gate reproducido (§8); estado conforme a la evidencia |

---

## 5. Findings

No hay findings nuevos en esta ronda.

---

## 6. Findings de Rondas Anteriores

### CODE-001 — La prueba obligatoria de ownership no existía y AC-006 se declaraba con cobertura automatizada

Previous status: `NEW` (Blocking: Yes)
Current status: `RESOLVED`

Evidence:

- `tests/Integration/support/progress-endpoint-request.php`: nueva opción `--posted-user`, que inyecta `$_POST['user_id']`.
- `tests/Integration/student-progress.php:174-183`: con `other` autenticado y `user_id` forjado de `active`, la prueba exige que la request se acepte (exit 0), que `active` no tenga fila y que `other` sí. Así se prueba que la identidad sale de la sesión, que era la propiedad pedida.
- `implementation-status.md`: `AC-006: IMPLEMENTED — automated endpoint integration coverage with forged user_id`.
- Arnés del auditor sobre `0a666dd`: A con `user_id=2` → fila `1:30` únicamente.

La prueba es de integración y no pude ejecutarla (§8). La verifiqué por lectura, y el comportamiento que asegura lo confirmé con el arnés.

### CODE-002 — Redondeo: 100% con lecciones pendientes y 0% con completions

Previous status: `NEW` (Severity: Medium, Blocking: No)
Current status: `PARTIALLY RESOLVED`

Evidence:

- `ProgressCalculator.php:19`: `return min(99, (int) round(($completed_lessons / $total_lessons) * 100));`. La guarda `completed >= total → 100` sigue antes, así que 100 solo se emite con todas las lecciones completas.
- `ProgressCalculatorTest`: `199/200 → 99`, `200/201 → 99`, `1/201 → 0`.
- Arnés: `999/1000 → 99`, `9999/10000 → 99`, `200/200 → 100`, `1/200 → 1`, `1/201 → 0`; `CourseProgressService` con 200 de 201 → `pct=99`.

Parte resuelta (la material): RF-015 y SC-015 se cumplen para cualquier tamaño de curso. Con eso desaparece el riesgo para SPEC-006/007.

Parte residual (Low): un avance menor a 0.5% se sigue mostrando como "0%" aunque exista al menos una completion. Solo ocurre en cursos de más de 200 lecciones. Codex lo dejó a propósito: el caso de prueba se llama `'small non-zero progress can display zero percent'`. Pero la decisión no aparece en `implementation-status.md`, y la regla del proyecto exige que el humano acepte un riesgo (`audit-standard.md §36`). El impacto es cosmético, porque la UI muestra también el conteo ("1 de 201 lecciones completadas · 0%").

Para cerrarlo, cualquiera de las dos:

- a) Aceptación humana, registrada como `ACCEPTED RISK`.
- b) `max(1, …)` cuando `completed > 0`.

### CODE-003 — Estrategia TOCTOU no documentada

Previous status: `NEW` → Current status: `RESOLVED`

Evidence: `implementation-status.md` § "Concurrency and TOCTOU" describe el orden de validación (lección publicada → jerarquía publicada → matrícula activa), la unicidad impuesta por `UNIQUE(user_id, lesson_id)` + `INSERT IGNORE`, y la ventana residual aceptada para el MVP. Coincide con el código.

### CODE-004 — Casos de prueba de endpoint débiles o mal etiquetados

Previous status: `NEW` → Current status: `RESOLVED`

Evidence (`student-progress.php`):

1. Mismatch real: `array($active, $course_a, $lesson_b, 'valid')`.
2. Anónimo con nonce válido: `array(0, $course_a, $lesson_a_two, 'valid')`.
3. Cinco repeticiones (`:164-168`).
4. Comprobación de 0 filas tras los rechazos de `other`, `unenrolled` y `active`/`lesson_b` (`:200-206`).

### CODE-005 — Estado "completada" de la UI sin prueba automatizada

Previous status: `NEW` → Current status: `RESOLVED`

Evidence: `course-access-player.php:195-224` inserta una completion y renderiza ambas plantillas con el contexto real de `FrontendRouter`. Comprueba el marcador completado y el pendiente en el temario, el porcentaje, y el mensaje de lección completada sin formulario. La limpieza borra la fila en el `finally`. El estado ya no invoca "prior user verification".

### CODE-006 — API del repositorio distinta de TASK-002

Previous status: `NEW` → Current status: `RESOLVED`

Evidence: `implementation-status.md` § "Repository API decision".

### CODE-007 — Manejo de errores y retroalimentación del endpoint

Previous status: `NEW` → Current status: `RESOLVED`

Evidence:

- `CompleteLessonAction.php`: `try/catch (\RuntimeException)` → `wp_die('No fue posible guardar tu progreso. Inténtalo de nuevo.', 500)`. Los rechazos ahora dicen "No puedes completar esta lección." (403).
- La redirección ya no añade `kaanbal_progress`.
- El aviso de esquema de `Plugin.php` menciona que el progreso no podrá guardarse.
- Arnés: un store que lanza `RuntimeException` → `wp_die status=500` con el mensaje en español; mismatch y anónimo → 403 en español; éxito → redirección al referer sin parámetros.

### CODE-008 — Guarda eliminada de la prueba de ciclo de vida

Previous status: `NEW` → Current status: `RESOLVED`

Evidence: `implementation-status.md` § "Lifecycle fixture decision" explica que el contrato de ciclo de vida no depende de integraciones opcionales y que el camino con WooCommerce activo lo cubre su propio fixture.

---

## 7. Recomendaciones

- Cerrar CODE-002 con una decisión humana explícita (aceptar el 0% residual o pedir `max(1, …)`).
- Sin cambios respecto a la ronda 1 en las observaciones sin ID (doble construcción del curriculum en la vista de curso; ubicación de la migración). Siguen siendo opcionales.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (`curso/.tools/`), usando el `vendor/` existente. Comandos ejecutados sobre `bb7ae59`, cuyo código es idéntico a `0a666dd`.

Comandos:

```text
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet 0a666dd bb7ae59 -- src tests templates assets kaanbal.php composer.json composer.lock
```

Resultados:

```text
lint:      0 archivos con errores
PHPUnit:   OK (40 tests, 60 assertions)   ← coincide con implementation-status.md
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD
```

Arnés del auditor (stubs de WordPress, clases reales de `src/` y plantillas reales):

```text
[forged-user]      A con user_id=B          → fila "1:30" (solo A), redirect al referer sin query
[success-redirect] B completa su lección    → fila "2:31", redirect al referer
[mismatch]         curso A + lección de B   → 403 "No puedes completar esta lección."
[anonymous]        sin sesión, nonce válido → 403 "No puedes completar esta lección."
[db-failure]       store lanza excepción    → 500 "No fue posible guardar tu progreso. Inténtalo de nuevo."
[rounding]         199/200→99, 200/201→99, 999/1000→99, 9999/10000→99, 200/200→100,
                   1/200→1, 1/201→0, 10/11→91, 0/5→0, 3/10→30
[rounding-service] 201 lecciones, 200 completadas → pct=99
[templates]        curso 1/2 y lección completada/pendiente: sin cambios respecto a r1 (correcto)
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para `tests/Integration/*.php`, que necesitan `KAANBAL_WP_PATH` y la base MySQL local del equipo de Daniel, inaccesible desde la máquina virtual. Las pruebas nuevas de CODE-001, CODE-004 y CODE-005 son de integración: las verifiqué por lectura y su comportamiento lo confirmé con el arnés. El `PASS` de integración en `implementation-status.md` es declaración de Codex. Recomiendo que Daniel ejecute `composer test:integration:progress` y `composer test:integration:player` antes del merge.

---

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno

Non-blocking findings:

- CODE-002 — Redondeo: parte material resuelta; el 0% residual con completions en cursos grandes requiere aceptación humana o `max(1, …)`

Findings resueltos en esta ronda:

- CODE-001 (blocker), CODE-003, CODE-004, CODE-005, CODE-006, CODE-007, CODE-008

Comentario final:

SPEC-005 cumple los 20 criterios de aceptación con evidencia automatizada o verificada por el auditor. La propiedad de seguridad central (nadie registra progreso ajeno) tiene ahora prueba de regresión, y el porcentaje ya no puede indicar 100% con lecciones pendientes. Desde la perspectiva de requisitos e implementación, la SPEC puede pasar a consolidación y revisión humana, sujeta a los resultados de Qwen y Mimo en la ronda 2 y a la ejecución local de las pruebas de integración.
