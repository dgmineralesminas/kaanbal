# Auditoría de Código y Cumplimiento

SPEC: `SPEC-004 — Course Access and Player`
Ronda: `2`
Auditor: Claude Code
Commit auditado: `7ab80cdd703b43a3495a62212d876c985feb1b63` (código y pruebas), con el registro documental `81408d0` encima (branch `feature/spec-004-course-access-and-player`)
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` (revisión estática completa y arnés de mutación del auditor; ejecución de PHPUnit/PHPStan/PHPCS/integración `BLOCKED BY ENVIRONMENT`, ver §8)

## 1. Resumen

La ronda 2 revisa la remediación de la ronda 1 (`FAIL` por CODE-001). Entre `68c2561` y `7ab80cd` **no cambió código de producción** (`src/`, `templates/`, `kaanbal.php` y `composer.json` son idénticos). Solo se agregaron pruebas, y `81408d0` registra el candidato. Por eso la verificación funcional de la ronda 1 sigue vigente.

CODE-001 queda **RESOLVED**. Las pruebas nuevas cubren los casos obligatorios de `plan.md §25` en la ruta de lección y el curso sin módulos (AC-019). Transcribí sus aserciones a un arnés propio y comprobé que detectan tres mutaciones deliberadas de la regla de acceso (§8). CODE-002 y CODE-004 quedan parcialmente resueltos. CODE-003 y CODE-005 a CODE-008 siguen abiertos, sin cambios, y ninguno bloquea.

Tasks revisadas: `33`
Acceptance Criteria revisados: `22`
Escenarios Gherkin revisados: `19`

Blocking findings: `0` (1 de la ronda anterior: RESOLVED)
Non-blocking findings: `7` abiertos o parciales (CODE-002 a CODE-008)

---

## 2. Alcance Auditado

Documentos revisados:

- `implementation-status.md` y `tasks.md` en `81408d0`
- `plan.md` y `spec.md` en el working tree (cambios sin commitear, ver CODE-002)
- `audits/round-1/code-audit.md` (esta misma autoría)

Diff revisado íntegro:

- `git diff 68c2561 7ab80cd`: `tests/Integration/course-access-player.php` (+65), `tests/Unit/LessonNavigationServiceTest.php` (+19), `tests/Unit/YouTubeEmbedRendererTest.php` (+1)
- `git diff 7ab80cd 81408d0`: solo `implementation-status.md` y `tasks.md`
- `git diff --quiet 68c2561 81408d0 -- src templates kaanbal.php composer.json` → sin cambios

Git: el working tree coincide con `7ab80cd` en `src/`, `templates/` y `tests/`. Hay cambios sin commitear en `plan.md` y `spec.md`, y `audits/` está sin versionar. El `.git/index.lock` que dejé en la ronda 1 ya no existe.

---

## 3. Trazabilidad (solo cambios respecto a la ronda 1)

| AC | Ronda 1 | Ronda 2 | Evidence |
|---|---|---|---|
| AC-004 | Yes (curso) / Static (lección) | **Yes** | bucle de denegación: `resolve(course_a, lesson_a_one, $unenrolled_user)` → 403, `access-denied`, sin `lesson`/`curriculum`/`course` |
| AC-005 | Yes (curso) / Static (lección) | **Yes** | mismo bucle con `user_id = 0` |
| AC-003 (lección) | Static | **Yes** | mismo bucle con `$revoked_user` |
| AC-007 | Partial | **Yes** | asserta `[module_a, module_empty]` en orden |
| AC-008 | Partial | **Yes** | `resolve(course_a, lesson_a_two)` → 200, template `lesson` |
| AC-009 | Partial | **Yes** | markup de la lección 2 contiene título, módulo y `Protected player content.` |
| AC-011 | Partial | **Yes** | lección B (sin video) → 200 y markup sin `kaanbal-player`; unit `('', '')` → `''` |
| AC-019 | **Static** | **Yes** | curso publicado sin módulos → 200, `modules === []`, render con título |
| AC-021 | Partial | Partial (declarado así) | `implementation-status.md` lo etiqueta ahora `PARTIALLY INTEGRATION-TESTED` con justificación; es honesto y aceptable, porque no existe almacenamiento de progreso en esta SPEC |
| AC-022 | **Static** | **Yes** | ruta de lección sin matrícula → 403; matriculado solo en A contra `/courses/B/lesson/B` → 403 |
| EC-006 | Static | **Yes** | unit `testItReturnsNoNeighborsForTheOnlyLesson` |

El resto de la tabla de la ronda 1 se mantiene sin cambios.

Task status:

- `TASK-022`, `TASK-026` y `TASK-032` pasan de FAIL a **PASS**.
- `TASK-024` y `TASK-025` quedan en PASS sin observaciones.
- `TASK-033` queda en PASS con la observación de CODE-002.

---

## 4. Findings de Rondas Anteriores

| Finding | Tipo | Estado ronda 2 |
|---|---|---|
| CODE-001 | Blocking | **RESOLVED** |
| CODE-002 | Non-blocking | **PARTIALLY RESOLVED** |
| CODE-003 | Non-blocking | OPEN |
| CODE-004 | Non-blocking | **PARTIALLY RESOLVED** |
| CODE-005 | Non-blocking | OPEN |
| CODE-006 | Non-blocking | OPEN |
| CODE-007 | Info | OPEN |
| CODE-008 | Info | OPEN |

### CODE-001 — Pruebas de seguridad obligatorias de la ruta de lección y AC-019

Previous status: `NEW` (Blocking)
Current status: `RESOLVED`

Evidence:

Los cuatro puntos de la corrección requerida están cubiertos en `7ab80cd`:

1. Dentro del bucle `foreach (array($revoked_user, $unenrolled_user, 0) …)`, la prueba llama `resolve($course_a->post_name, $lesson_a_one->post_name, $denied_user)` y exige `403`, `access-denied` y ausencia de `lesson`, `curriculum` y `course` en el contexto.
2. `resolve($course_b->post_name, $lesson_b->post_name, $active_user)` exige `403` (el usuario solo está matriculado en A y en el curso sin módulos).
3. Hay un curso sin módulos con matrícula `active`: exige `200`, template `course`, `modules === array()`, y que el render de `course.php` contenga el título.
4. `implementation-status.md` y `tasks.md` están versionados en `81408d0`, con etiquetas por AC acordes a la evidencia (AC-021 rebajado a parcial).

Verificación de que las pruebas discriminan: transcribí esas aserciones a un arnés con stubs de WordPress y lo ejecuté contra el código real y contra tres mutantes (§8):

- M1, acceso evaluado solo en la ruta de curso: 4 aserciones fallan.
- M2, `revoked` concede acceso: falla `denied lesson u=revoked`.
- M3, visitante y sin matrícula conceden acceso: 3 aserciones fallan.

El código real pasa todas.

### CODE-002 — El registro del candidato de auditoría no está commiteado

Previous status: `NEW`
Current status: `PARTIALLY RESOLVED`

Evidence:

`81408d0` versiona `implementation-status.md` (candidato `7ab80cd`, `Ready for audit: Yes`, `Current audit round: 2`) y `tasks.md`. Siguen sin commitear:

- `plan.md` §36, que además **nombra el candidato anterior `68c2561`** y ya está desactualizado;
- el cambio de status en `spec.md` (`Ready for code audit`).

Required correction:

Actualizar `plan.md` §36 al candidato vigente, o eliminarlo, porque `implementation-status.md` ya es la fuente del candidato. Después, commitear `plan.md` y `spec.md` en un commit solo de documentación. No bloquea: el candidato auditable ya está registrado de forma versionada.

### CODE-003 — Course y Lesson se resuelven por slug global sin filtrar estado

Previous status: `NEW`
Current status: `OPEN`

Evidence:

`FrontendRouter.php` no cambió (L92 y L118 siguen usando `get_page_by_path`). Sin cambios en severidad ni recomendación.

### CODE-004 — Aserciones débiles o casos listados sin prueba

Previous status: `NEW`
Current status: `PARTIALLY RESOLVED`

Evidence:

Resueltos:

- orden de módulos (AC-007);
- 200 de la lección 2 (AC-008);
- contenido y módulo en el markup (AC-009);
- render de una lección sin video (AC-011);
- caso `('', '')` en el data provider;
- caso de lección única;
- AC-021 re-etiquetado con honestidad.

Pendientes, todos menores:

- **La aserción de orden no distingue `menu_order` de `ID`.** En el fixture, `module_a` (`menu_order 0`) se crea antes que `module_empty` (`menu_order 1`), así que ambos criterios producen el mismo orden. Un regreso a "ordenar por ID" pasaría la prueba. Se corrige creando un módulo con `menu_order` bajo **después** de otro con `menu_order` alto.
- **La aserción de módulo no apunta a la cabecera.** `'Player module A'` también aparece en el índice lateral (`<h3>` del aside), así que la prueba pasaría aunque faltara la cabecera `kaanbal-lesson__module`.
- **Falta asertar el contenido de la lección sin video.** Solo se comprueba el título de la lección B, no su contenido textual (AC-011 pide "conserva su contenido textual").
- **La redacción de Security Tests promete un caso que no se prueba.** `implementation-status.md` dice "an enrollment in Course A cannot open Course B or its Lesson", pero solo se prueba la lección. La ruta de curso para ese usuario no tiene aserción. El comportamiento está cubierto indirectamente por el caso "sin matrícula" en la ruta de curso.

### CODE-005 a CODE-008

Previous status: `NEW`
Current status: `OPEN`

Evidence:

Sin cambios en producción. Mantienen la severidad y la clasificación de la ronda 1.

---

## 5. Findings Nuevos

Ninguno.

---

## 6. Recomendaciones

Prioridad sugerida al humano, sin cambios respecto a la ronda 1: CODE-003 y CODE-005 (experiencia del alumno), luego CODE-006 (despliegue sobre instalaciones existentes). Los pendientes de CODE-002 y CODE-004 son de bajo costo y pueden cerrarse en el mismo commit documental o de pruebas antes del merge.

---

## 7. Validación Ejecutada

Comandos o pruebas ejecutadas:

```text
git diff --quiet 68c2561 81408d0 -- src templates kaanbal.php composer.json   → sin cambios
git diff 68c2561 7ab80cd / 7ab80cd 81408d0                                     → revisión línea por línea
php -l sobre los tres archivos de prueba modificados (PHP 8.4.21)               → sin errores
arnés de mutación del auditor: FrontendRouter + CurriculumService +
  CurriculumRepository + CourseAccessService reales sobre stubs en memoria
  de get_post / get_posts / get_post_meta / get_page_by_path, con las
  aserciones nuevas de course-access-player.php transcritas
```

Resultados:

```text
orig: PASS
m1 (acceso solo en ruta de curso): FAIL -> denied lesson u=revoked, u=unenrolled, u=0, cross enrollment
m2 (revoked concede acceso):       FAIL -> denied lesson u=revoked
m3 (anónimo y sin matrícula conceden acceso): FAIL -> denied lesson u=unenrolled, u=0, cross enrollment
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT`, igual que en la ronda 1:

- `composer install` falla contra `api.github.com` desde el entorno del auditor;
- no hay `php` en la shell accesible de la máquina del humano;
- no hay WordPress ni base de datos alcanzables.

Por eso **no ejecuté** la prueba de integración real ni el Quality Gate. Los resultados declarados (`31 tests, 51 assertions`, integración, PHPStan, PHPCS) quedan `Not verified` por este auditor. El arnés demuestra que las aserciones nuevas discriminan, pero no sustituye la corrida real contra WordPress. Se recomienda al humano ejecutar `composer test` y `composer test:integration:player` antes del merge.

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno. CODE-001: RESOLVED.

Non-blocking findings:

- CODE-002 — Registro del candidato: `plan.md` §36 (desactualizado) y `spec.md` sin commitear (PARTIALLY RESOLVED)
- CODE-003 — Resolución por slug global sin filtrar estado (OPEN)
- CODE-004 — Aserciones débiles restantes (PARTIALLY RESOLVED)
- CODE-005 — UX de estados vacíos y acceso denegado (OPEN)
- CODE-006 — Ciclo de vida de rewrite rules (OPEN)
- CODE-007 — Regex del ID de YouTube con `$` (Info, OPEN)
- CODE-008 — Administradores sin preview (Info, OPEN)

Comentario final:

La remediación se limitó a lo pedido: pruebas y documentación, sin tocar producción. Cierra el único bloqueante con aserciones que sí detectan una regresión de la regla de acceso. Desde el ámbito de Claude Code, SPEC-004 puede pasar a las auditorías de Qwen y Mimo y después a revisión humana. Ellos deben auditar el mismo candidato: `7ab80cd` con `81408d0`.
