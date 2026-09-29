# Auditoría de Código y Cumplimiento

SPEC: `SPEC-005 — Student Progress`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `613672254c89fdb67f87cd021efa2617a6a832f5` (candidato registrado en `implementation-status.md`), revisado en el HEAD `08e3ce68cabd174e860e48390bd0e9c57a698dd4` (branch `feature/spec-005-student-progress`)
Fecha: `2026-09-29`

Verdict: `FAIL`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS, PHPStan y arnés propio. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` (ver §8).

## 1. Resumen

La implementación de producción de SPEC-005 satisface los requisitos funcionales. Lo verifiqué leyendo el código completo del módulo `Progress`, ejecutando la suite unitaria y un arnés propio que ejercita el endpoint, el servicio y las plantillas con stubs de WordPress.

- La escritura pasa por `admin-post` con usuario autenticado, nonce ligado a la lección, validación de jerarquía publicada Lesson → Module → Course y `CourseAccessService` de SPEC-004.
- La identidad sale siempre de `get_current_user_id()`; el endpoint no lee ningún `user_id` del request.
- La idempotencia descansa en `UNIQUE KEY user_lesson (user_id, lesson_id)` + `INSERT IGNORE`.
- El porcentaje se deriva del curriculum publicado actual con una sola consulta en lote; no se persiste.
- Abrir una lección no escribe progreso y el 100% no toca la matrícula.

El veredicto es `FAIL` por un único finding bloqueante (`CODE-001`), que es de evidencia y no de comportamiento. `plan.md §31` declara **obligatoria** la prueba "user A tries user B" y no existe, pese a que `implementation-status.md` marca `AC-006` como "automated integration coverage" y `TASK-026`/`TASK-027` están en `Done`. Es el mismo patrón clasificado como bloqueante en SPEC-003 `CODE-002` y SPEC-004 `CODE-001`. La corrección es barata: agregar la prueba y dejar el estado conforme a la evidencia.

El hallazgo funcional más relevante es `CODE-002` (no bloqueante): el redondeo del porcentaje muestra `100%` con lecciones pendientes en cursos de 200 lecciones o más. Conviene corregirlo antes de que SPEC-006/007 dependan del porcentaje.

Tasks revisadas: `36`
Acceptance Criteria revisados: `20`
Escenarios Gherkin revisados: `18` (SC-001 a SC-018)

Blocking findings: `1` (CODE-001)
Non-blocking findings: `7` (CODE-002 a CODE-008)

---

## 2. Alcance Auditado

Documentos revisados:

- `spec.md`, `plan.md`, `feature.feature`, `tasks.md`, `implementation-status.md` de SPEC-005 (en `6136722` y en `08e3ce6`)
- `docs/agents.md`, `docs/audit-standard.md`, `docs/templates/code-audit-template.md`, `docs/data-model.md §32-37`
- `docs/specs/004-course-access-and-player/audits/round-1/code-audit.md` (precedente de clasificación)

Git:

- `git diff --stat 8bdc87c 6136722`: 27 archivos; el código de la SPEC se lista abajo.
- `git diff 6136722 08e3ce6`: solo `implementation-status.md` y `tasks.md`. Verificado `git diff --quiet 6136722 08e3ce6 -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- Working tree limpio en `08e3ce6`.
- En `6136722`, `implementation-status.md` todavía muestra todo `NOT IMPLEMENTED`/`Pending` y `Current commit: aceec25…`; el registro del candidato vive en `08e3ce6`. Es el mismo esquema candidato + commit documental aceptado en SPEC-004 ronda 2; no lo trato como finding.

Código revisado íntegramente:

- `src/Progress/Application/{CompleteLessonResult,CompleteLessonService,CourseProgress,CourseProgressService,LessonProgressStore,ProgressCalculator}.php`
- `src/Progress/Infrastructure/LessonProgressRepository.php`
- `src/Progress/Presentation/Frontend/CompleteLessonAction.php`, `src/Progress/ProgressModule.php`
- `src/Shared/Database/{EnrollmentSchemaMigration,SchemaManager}.php`, `src/Bootstrap/{Plugin,Activator,Version}.php`
- `src/Access/Presentation/Frontend/FrontendRouter.php` (cambios), `src/Access/Application/CourseAccessService.php`, `src/Courses/Application/CurriculumService.php`, `src/Courses/Infrastructure/CurriculumRepository.php`
- `templates/frontend/{course,lesson}.php`
- `assets/css/player.css` (revisión superficial: solo presentación)

Pruebas revisadas:

- `tests/Unit/ProgressCalculatorTest.php`
- `tests/Integration/student-progress.php`, `tests/Integration/support/progress-endpoint-request.php`
- Cambios en `tests/Integration/course-access-player.php` y `tests/Integration/wordpress-lifecycle.php`

---

## 3. Trazabilidad

Leyenda de "Verified": **Yes** = prueba del proyecto que ejerce el comportamiento con aserción que lo comprueba; **Static** = verificado por lectura de código y/o arnés del auditor, sin prueba del proyecto que lo cubra; **Partial** = prueba existente pero con aserción débil o incompleta. Las pruebas de integración se evalúan por lectura (no pude ejecutarlas, §8).

| AC | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-005, 009, 023, 026, 027 | Yes | Yes | `student-progress.php:92` (servicio) y `:161-170` (endpoint con nonce válido, fila escrita) |
| AC-002 | SC-002 | TASK-016 | Yes | Yes | `student-progress.php:80-83`: `FrontendRouter::resolve()` de una lección no crea filas. `FrontendRouter` solo llama `forCourse()` (lectura) |
| AC-003 | SC-003 | TASK-001, 010, 031 | Yes | Yes | `student-progress.php:92` (`AlreadyCompleted`) y `:161-170` (dos requests → `COUNT(*) = 1`). Ver CODE-004 sobre "many times" |
| AC-004 | SC-004 | TASK-006, 026, 027 | Yes | Yes | `student-progress.php:85-90` (sin matrícula y `revoked` → `AccessDenied`), `:177` (endpoint sin matrícula → 403) |
| AC-005 | SC-005 | TASK-005, 026, 027 | Yes | Yes (servicio) / Partial (endpoint) | `student-progress.php:102` (`course_a` + `lesson_b` → `CourseMismatch`). El caso de endpoint `:178` no prueba mismatch (CODE-004). Arnés: endpoint con curso A + lección B → 403 |
| AC-006 | SC-006 | TASK-007, 026, 027 | Yes | **Static** | `CompleteLessonAction.php:20-29` no lee `user_id`; usa `get_current_user_id()`. Arnés: A envía `user_id=B` → fila solo para A; nonce emitido para B → 403. **Sin prueba del proyecto** (CODE-001) |
| AC-007 | SC-007 | TASK-002, 004 | Yes | Yes | `CourseProgress::isLessonCompleted()`; `student-progress.php:96-98` |
| AC-008 | SC-008 | TASK-003, 024 | Yes | Yes | `ProgressCalculatorTest` `3/10 → 30` |
| AC-009 | SC-009 | TASK-003, 024 | Yes | Yes | `ProgressCalculatorTest` `10/10 → 100`; `student-progress.php:129-134`. Ver CODE-002 |
| AC-010 | SC-010 | TASK-017 | Yes | Yes | `ProgressCalculatorTest` `0/0 → 0`; `student-progress.php:70-72` |
| AC-011 | SC-011 | TASK-020 | Yes | Static | `CourseProgressService` solo recorre `modules[].lessons[]`; aún no existe CPT de quiz (`ContentTypes`), no hay nada que excluir que se pueda probar |
| AC-012 | SC-012 | TASK-011, 015 | Yes | Partial | `course-access-player.php` solo renderiza el estado 0 completadas. Arnés: 1 de 2 → un `is-completed`/"Completada" y un "Reproducir" (CODE-005) |
| AC-013 | SC-012 | TASK-011 | Yes | Yes | `course-access-player.php`: "0 de 2 lecciones completadas" + `role="progressbar"`. Arnés: "1 de 2 lecciones completadas · 50%", `aria-valuenow="50"` |
| AC-014 | SC-013, SC-014 | TASK-013, 015 | Yes | Partial | Estado pendiente con prueba (formulario renderizado). Estado completado solo por arnés: "Lección completada" y sin formulario (CODE-005) |
| AC-015 | SC-013 | TASK-014 | Yes | Yes | `course-access-player.php`: `name="action" value="kaanbal_complete_lesson"` y `_kaanbal_nonce` |
| AC-016 | SC-003, SC-014 | TASK-010 | Yes | Yes | Igual que AC-003 |
| AC-017 | SC-015 | TASK-019, 029 | Yes | Yes (Partial en borde) | `student-progress.php:110-114` (1/3 → 33). Con ≥200 lecciones el caso de la SPEC devuelve 100% (CODE-002) |
| AC-018 | SC-016 | TASK-018, 029 | Yes | Yes | `student-progress.php:123-126`: lección desasignada → su completion deja de contar |
| AC-019 | SC-017 | TASK-028 | Yes | Yes | `student-progress.php:96-99`: `other` queda en 0 tras completar `active` |
| AC-020 | SC-018 | TASK-021, 030 | Yes | Yes | `student-progress.php:136-138`: matrícula sigue `active` al 100% |

Casos límite: EC-001/EC-002 cubiertos por la restricción única + `INSERT IGNORE` (no depende de un check previo en aplicación, así que la concurrencia no crea duplicados); EC-003 cubierto por AC-004; EC-004/EC-005/EC-006 por `CompleteLessonService` (`InvalidLesson` / `CourseMismatch`, ambos exigen jerarquía publicada); EC-007 por AC-010; EC-008/EC-009/EC-010 por `findCompletedLessonIds()` filtrando contra los IDs del curriculum actual.

---

## 4. Verificación de Tasks

Todas las tasks están en `Done`. Resultado por grupo:

| Tasks | Resultado | Observaciones |
|---|---|---|
| TASK-001 migración | PASS | Tabla con `UNIQUE KEY user_lesson`, índices, `dbDelta`, verificación de existencia, `DATABASE_SCHEMA` 2 → 3. `Plugin::boot()` ejecuta `installOrUpgrade()` en cada request, así que las instalaciones existentes migran sin reactivar. Sin `course_id` ni `status`, conforme a DEC-003 y `data-model.md §34` |
| TASK-002 repositorio | PASS con observación | `complete()` y `findCompletedLessonIds()` implementados y preparados. `isCompleted()`/`countCompleted()` no existen en el repositorio; la necesidad se cubre en `CourseProgress` (CODE-006) |
| TASK-003, 017 calculadora | PASS con defecto no bloqueante | CODE-002 |
| TASK-004, 018, 019, 020 servicio de progreso | PASS | Deriva del curriculum publicado actual |
| TASK-005, 006, 007, 008, 009, 010 escritura | PASS | Orden: logged-in → nonce → lección publicada → jerarquía publicada = curso enviado → acceso → `INSERT IGNORE` |
| TASK-011 a 016 UI | PASS | Verificado por arnés (CODE-005 sobre cobertura) |
| TASK-012, 032 N+1 | PASS | Una sola consulta de completions por vista (arnés: 1 lectura del store). Ver observación en §7 |
| TASK-021, 030 sin Course Completion | PASS | No hay escrituras en `kaanbal_enrollments` en el módulo |
| TASK-022 TOCTOU | PASS con observación | La estrategia se aplica, pero no está documentada como pide la task (CODE-003) |
| TASK-023, 024, 025, 028, 029 pruebas | PASS | Cubiertas por `ProgressCalculatorTest` y `student-progress.php` (el plan listaba pruebas unitarias como "Candidatos", no obligatorias) |
| TASK-026, 027 pruebas de servicio/endpoint | **FAIL** | Falta la prueba obligatoria de ownership (CODE-001); casos débiles (CODE-004) |
| TASK-031 concurrencia | PASS | Restricción única comprobada indirectamente: dos requests → `COUNT(*) = 1` con `INSERT IGNORE` |
| TASK-033 scope | PASS | Sin quiz, certificados, finalización de matrícula ni tracking de video |
| TASK-034, 035, 036 quality gate y estado | PASS con observación | Reproduje lint/PHPUnit/PHPCS/PHPStan (§8). El estado sobredeclara cobertura de AC-006 (CODE-001) y AC-012/014 (CODE-005) |

---

## 5. Findings

### CODE-001 — La prueba obligatoria de ownership ("user A tries user B") no existe y AC-006 se declara con cobertura automatizada

Severity: Medium
Blocking: Yes
Classification: Test Gap / Documentation Mismatch
Status: NEW

Affected requirement:

`AC-006`, `RF-005`, `RNF-003`, `RB-010`; `plan.md §31` (Security Tests, "Obligatorios")

Affected scenario:

`SC-006`

Affected task:

`TASK-007`, `TASK-026`, `TASK-027`

Affected files:

- `tests/Integration/student-progress.php`
- `tests/Integration/support/progress-endpoint-request.php`
- `docs/specs/005-student-progress/implementation-status.md`

Description:

`plan.md §31` lista como obligatorias seis pruebas de seguridad, entre ellas "user A tries user B". Ninguna prueba intenta registrar progreso para otro usuario. El helper de endpoint solo acepta `--user/--course/--lesson/--nonce` y nunca envía un `user_id` en `$_POST`; el servicio recibe el `user_id` como parámetro, así que las llamadas directas no modelan el ataque. Aun así, `implementation-status.md:92` declara `AC-006: IMPLEMENTED — automated integration coverage`, y `TASK-026` (que cubre AC-006) está en `Done`.

La comprobación de `student-progress.php:96-99` (el usuario `other` sigue en 0 tras completar `active`) es la de aislamiento de AC-019; no ejerce un intento de A sobre B.

Evidence:

- `grep -n "user_id" tests/Integration/support/progress-endpoint-request.php` → solo `$user_id` local para `wp_set_current_user()`; `$_POST` (`:24-28`) no incluye `user_id`.
- Ningún caso de `student-progress.php:172-185` apunta a otro usuario.
- Comportamiento real (verificado por el auditor, no por el proyecto): `CompleteLessonAction.php:20-29` no lee `user_id` y usa `get_current_user_id()`. En el arnés, A autenticado con `user_id=2` en el POST escribe la fila `1:30` (solo A); un nonce emitido para B es rechazado con 403.

Expected:

Una prueba automatizada que, con A autenticado, intente registrar completion para B (p. ej. `user_id=B` en el POST y/o un nonce de B) y compruebe que B no obtiene filas; y un `implementation-status.md` que describa la cobertura real.

Actual:

La propiedad se cumple solo por inspección; la prueba obligatoria no existe y el estado documenta cobertura automatizada inexistente.

Impact:

AC-006 protege la manipulación del progreso de otros usuarios (clasificado `High` como tipo de defecto en `audit-standard.md §13`). Sin la prueba no hay protección contra una regresión, p. ej. si una SPEC futura (dashboard, reporting) introduce un parámetro de usuario. Además, el estado documental es incorrecto (`audit-standard.md §24`).

Required correction:

1. Añadir al menos una prueba de endpoint que envíe `user_id` de otro usuario (y, opcionalmente, un nonce emitido para él) y verifique que el progreso de B no cambia y que la fila, si se crea, pertenece a A.
2. Actualizar `implementation-status.md` para que AC-006 refleje la evidencia real.

---

### CODE-002 — El redondeo muestra 100% con lecciones pendientes (y 0% con completions) en cursos grandes

Severity: Medium
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

`RF-015`, `AC-009`, `AC-017` (SC-015: "ya no debe ser 100 por ciento"), `plan.md §10-11`

Affected task:

`TASK-003`, `TASK-019`

Affected files:

- `src/Progress/Application/ProgressCalculator.php:15-19`
- `tests/Unit/ProgressCalculatorTest.php`

Description:

`percentage()` devuelve `(int) round(completed / total * 100)`. La guarda `completed >= total → 100` es correcta, pero cuando `completed < total` y el cociente es ≥ 0.995, `round()` también produce `100`. Simétricamente, un cociente < 0.005 produce `0` aunque haya completions.

Evidence:

Arnés del auditor con la clase real:

```text
199/200  => 100
200/201  => 100
999/1000 => 100
1/201    => 0
```

Y con `CourseProgressService` real sobre un curriculum de 201 lecciones con 200 completadas (el escenario de SC-015 tras agregar una lección a un curso al 100%): `total=201 completed=200 pct=100`. La UI mostraría "200 de 201 lecciones completadas · 100%".

Expected:

100% solo cuando todas las lecciones válidas estén completadas (RF-015) y, tras agregar una lección a un curso completo, un valor distinto de 100 (SC-015).

Actual:

Con 200 lecciones o más, el caso de SC-015 sigue mostrando 100%.

Impact:

Acotado en esta SPEC (solo visual; AC-020 no depende del porcentaje). El riesgo real es futuro: si SPEC-006/007 condiciona el quiz o el certificado a `percentage === 100`, un alumno con lecciones pendientes lo cumpliría. No es bloqueante porque cursos de 200+ lecciones son el extremo de la escala del proyecto; lo decide el humano.

Required correction:

Truncar en lugar de redondear para valores intermedios (p. ej. `floor`, o limitar a `99` si `completed < total` y a `1` si `completed > 0`), añadir casos de borde (`199/200`, `200/201`, `1/201`) en `ProgressCalculatorTest`. Alternativa: documentar que las SPEC posteriores deben usar `completed_lessons === total_lessons` y nunca el porcentaje como condición.

---

### CODE-003 — La estrategia TOCTOU no está documentada como exige TASK-022

Severity: Low
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected requirement:

`plan.md §9` ("La implementación debe documentar cómo protege: matrícula válida, Lesson → Course, uniqueness"), `RISK-002`

Affected task:

`TASK-022` ("Documentar y aplicar la estrategia…")

Affected files:

- `docs/specs/005-student-progress/implementation-status.md`

Description:

La estrategia sí se aplica: `CompleteLessonService::complete()` valida lección, jerarquía y acceso inmediatamente antes de una única sentencia `INSERT IGNORE`, y la unicidad la garantiza la base de datos. Pero no hay documentación en ningún archivo (`grep -rniE "toctou|revalid|invariant"` sin resultados fuera de spec/plan/tasks).

Evidence:

`implementation-status.md` no tiene sección de TOCTOU ni de concurrencia; `TASK-022` está en `Done`.

Impact:

Menor. La ventana residual (una revocación que ocurra entre el check de acceso y el INSERT del mismo request) es de milisegundos y aceptable para la escala del proyecto, pero es una decisión que el humano debería ver escrita.

Required correction:

Una sección breve en `implementation-status.md` que describa: qué invariantes se revalidan, en qué orden, que la unicidad la impone `UNIQUE(user_id, lesson_id)`, y la ventana residual aceptada.

---

### CODE-004 — Casos de prueba de endpoint débiles o mal etiquetados

Severity: Low
Blocking: No
Classification: Test Gap
Status: NEW

Affected requirement:

`AC-003`, `AC-005`, `plan.md §30-31`

Affected task:

`TASK-027`, `TASK-031`

Affected files:

- `tests/Integration/student-progress.php:161-185`

Description:

1. El caso `array($active, $course_b, $lesson_b, 'valid')` (`:178`), que por su posición corresponde al "mismatched course" de TASK-027, envía una combinación **consistente** (lección B dentro de curso B) para un usuario no matriculado en B: devuelve 403 por `AccessDenied`, no por `CourseMismatch`. El mismatch real (curso A + lección B) solo se prueba a nivel de servicio (`:102`).
2. El caso anónimo (`:174`) se combina con nonce ausente, así que no distingue qué control lo rechaza.
3. `plan.md §30` pide "same lesson many times"; se prueba dos veces.
4. Todas las respuestas de rechazo se comprueban solo por código de salida `3` (cualquier `wp_die` 403); no se verifica que no se haya escrito ninguna fila.

Evidence:

Lectura de la prueba. En el arnés, el endpoint con `course_id=A` y `lesson_id` de B (nonce válido) devuelve 403, así que el comportamiento es correcto.

Impact:

Bajo: el comportamiento es correcto, pero una regresión en la validación de jerarquía del endpoint no la detectaría la prueba de endpoint.

Required correction:

Opcional: cambiar el caso a `array($active, $course_a, $lesson_b, 'valid')`, añadir anónimo con nonce válido, un bucle de N repeticiones y una comprobación de `COUNT(*) = 0` tras los rechazos.

---

### CODE-005 — El estado "completada" de la UI no tiene prueba automatizada, pero se declara "rendered coverage"

Severity: Low
Blocking: No
Classification: Test Gap / Documentation Mismatch
Status: NEW

Affected requirement:

`AC-012`, `AC-014`, `AC-016`

Affected scenario:

`SC-012`, `SC-014`

Affected task:

`TASK-011`, `TASK-013`, `TASK-015`

Affected files:

- `tests/Integration/course-access-player.php` (cambios de SPEC-005)
- `docs/specs/005-student-progress/implementation-status.md:98,100`

Description:

Las aserciones añadidas renderizan solo el estado inicial ("0 de 2 lecciones completadas", formulario presente). Ninguna prueba renderiza una lección completada: ni el marcador `is-completed`/"Completada" del temario (lo que permite *distinguir* completadas de pendientes, AC-012) ni el mensaje "✓ Lección completada" sin formulario (SC-014). El estado dice "rendered course/lesson-template coverage and prior user verification".

Evidence:

Arnés del auditor (plantillas reales con stubs): con 1 de 2 completadas, el temario muestra un `class="is-completed"`, un "Completada" y un "Reproducir", "1 de 2 lecciones completadas · 50%" y `aria-valuenow="50"`. La lección completada muestra "Lección completada" y no el formulario; la pendiente muestra el formulario con `lesson_id` correcto y sin campo `user_id`.

Impact:

Bajo: el comportamiento es correcto y hay verificación manual previa del usuario, pero la evidencia automatizada está sobredeclarada.

Required correction:

Opcional: renderizar las plantillas con al menos una completion en `course-access-player.php` o `student-progress.php`, o ajustar el texto del estado a "Static/manual".

---

### CODE-006 — La API del repositorio difiere de TASK-002 / plan §6

Severity: Info
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected task:

`TASK-002`

Affected files:

- `src/Progress/Infrastructure/LessonProgressRepository.php`
- `src/Progress/Application/LessonProgressStore.php`

Description:

TASK-002 lista `complete`, `isCompleted`, `findCompletedLessonIds` y `countCompleted cuando corresponda`. Solo existen `complete()` y `findCompletedLessonIds()`. AC-007 se satisface con `CourseProgress::isLessonCompleted()` sobre el lote ya cargado, lo cual es coherente con RNF-006.

Impact:

Ninguno funcional. Solo trazabilidad.

Required correction:

Anotar la decisión en `implementation-status.md` (o implementar `isCompleted()` si una SPEC posterior la necesita).

---

### CODE-007 — Manejo de errores y retroalimentación del endpoint mejorables

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected requirement:

`plan.md §15` ("respuesta clara, manejo de errores")

Affected files:

- `src/Progress/Presentation/Frontend/CompleteLessonAction.php:24,32,41`
- `src/Progress/Infrastructure/LessonProgressRepository.php:36-38`
- `src/Bootstrap/Plugin.php` (texto del aviso de esquema)

Description:

1. Si el INSERT falla (p. ej. la migración del esquema 3 no pudo crear la tabla, caso que `Plugin::boot()` tolera con un aviso), `complete()` lanza `RuntimeException` que nadie captura en `handle()`: el alumno ve la pantalla genérica de error crítico de WordPress.
2. Los rechazos muestran "You cannot complete this lesson." en inglés, mientras toda la UI del player está en español (mismo patrón que SPEC-004 `CODE-005`).
3. El parámetro `kaanbal_progress=completed|already_completed` que se añade a la redirección no lo consume ninguna plantilla (`grep` sin resultados): no hay mensaje de confirmación y el parámetro queda en la URL.
4. El aviso de esquema de `Plugin` sigue diciendo "Course purchases will not grant access", sin mencionar que el progreso tampoco se guardará.

Impact:

Solo UX en fallos o casos de rechazo; el flujo normal funciona (la lección recargada muestra "✓ Lección completada").

Required correction:

Opcional: capturar la excepción y responder con `wp_die()` legible (500), usar textos fuente coherentes con el idioma del player, mostrar un aviso breve cuando `kaanbal_progress` esté presente (o no añadirlo), y actualizar el texto del aviso de esquema.

---

### CODE-008 — Se eliminó una guarda de la prueba de ciclo de vida sin documentarlo

Severity: Info
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected files:

- `tests/Integration/wordpress-lifecycle.php` (diff `8bdc87c..6136722`, 4 líneas eliminadas)

Description:

Se eliminó `if (class_exists('WooCommerce')) throw … 'This integration fixture expects WooCommerce to be inactive.'`. Es una precondición del fixture, no una aserción de comportamiento, y es plausible que se quitara porque el entorno local ahora tiene WooCommerce activo. El cambio está fuera del alcance de SPEC-005 y no aparece en `implementation-status.md`.

Impact:

Ninguno sobre SPEC-005. Lo registro por `audit-standard.md §33` (modificaciones de pruebas).

Required correction:

Anotar el motivo en `implementation-status.md`.

---

## 6. Findings de Rondas Anteriores

No aplica: es la ronda 1 de SPEC-005. Los findings abiertos no bloqueantes de SPEC-004 (`CODE-003` a `CODE-008` de esa SPEC) siguen su propio ciclo; no los reclasifico aquí. El único que se repite en código nuevo es el patrón de i18n mixto (ver CODE-007, punto 2).

---

## 7. Recomendaciones

Observaciones sin ID (no requieren acción para esta SPEC):

- **Consultas de la vista de curso.** RNF-006 se cumple: una sola consulta de completions por vista. Pero `FrontendRouter` construye el curriculum y `CourseProgressService::forCourse()` lo vuelve a construir (arnés: 2 `get_posts` adicionales por vista). Es acotado, no N+1; se podría pasar el curriculum ya cargado al servicio.
- **Ubicación de la migración.** La tabla de progreso se crea dentro de `EnrollmentSchemaMigration`. Funciona y es idempotente; la conveniencia de separarla corresponde a la auditoría de arquitectura.
- **SPEC-006/007.** Independientemente de CODE-002, conviene que las SPEC de quiz y certificados usen `completed_lessons === total_lessons` como condición, no el porcentaje redondeado.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (FrankenPHP) en `curso/.tools/`, usando el `vendor/` existente del plugin. Todos los comandos sobre `08e3ce6`, cuyo código es idéntico a `6136722`.

Comandos:

```text
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet 6136722 08e3ce6 -- src tests templates assets kaanbal.php composer.json composer.lock
```

Resultados:

```text
lint:      0 archivos con errores
PHPUnit:   OK (37 tests, 57 assertions)
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD
```

`PHPStan` se ejecutó con `--debug` porque el binario estático no expone `PHP_BINARY` y los workers paralelos no pueden arrancar; el análisis es el mismo.

Arnés del auditor (fuera del repo, stubs de WordPress, clases reales de `src/` y plantillas reales):

```text
[idor-userid-param]  A con user_id=B en POST      → fila "1:30" (solo A), redirect completed
[idor-nonce-of-B]    A con nonce emitido para B   → wp_die 403
[nonce-other-lesson] nonce de lección 31 para 30  → wp_die 403
[endpoint-mismatch]  curso A + lección de B       → wp_die 403
[many-times]         5 × complete()               → completed, already_completed ×4; 1 fila
[rounding]           199/200→100, 200/201→100, 999/1000→100, 1/201→0, 10/11→91, 2/3→67
[rounding-service]   201 lecciones, 200 completadas → pct=100
[batch-reads]        forCourse(): 1 lectura de completions, 2 get_posts
[templates]          curso 1/2: "1 de 2 … 50%", is-completed=1, Completada=1, Reproducir=1, aria-valuenow=50
                     lección completada: mensaje=1, formulario=0
                     lección pendiente: mensaje=0, formulario=1, lesson_id correcto, user_id=0
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para las pruebas de integración (`tests/Integration/*.php`, incluidas `student-progress.php` y `course-access-player.php`). Requieren `KAANBAL_WP_PATH` y la base MySQL local (`DB_HOST=localhost`) del equipo de Daniel, que no es accesible desde la máquina virtual. Las evalué por lectura; su resultado `PASS` en `implementation-status.md` es declaración de Codex, no verificado por este auditor. El arnés sustituye `$wpdb` por un store en memoria, así que la restricción `UNIQUE` y el `INSERT IGNORE` reales solo están verificados por lectura del esquema y de la prueba `COUNT(*) = 1`.

---

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- CODE-001 — La prueba obligatoria de ownership ("user A tries user B") no existe y AC-006 se declara con cobertura automatizada

Non-blocking findings:

- CODE-002 — El redondeo muestra 100% con lecciones pendientes (y 0% con completions) en cursos grandes
- CODE-003 — La estrategia TOCTOU no está documentada como exige TASK-022
- CODE-004 — Casos de prueba de endpoint débiles o mal etiquetados
- CODE-005 — El estado "completada" de la UI no tiene prueba automatizada, pero se declara "rendered coverage"
- CODE-006 — La API del repositorio difiere de TASK-002 / plan §6
- CODE-007 — Manejo de errores y retroalimentación del endpoint mejorables
- CODE-008 — Se eliminó una guarda de la prueba de ciclo de vida sin documentarlo

Comentario final:

La implementación de SPEC-005 es correcta y está bien acotada. Autorización server-side delegada a SPEC-004, identidad desde la sesión, nonce por lección, idempotencia garantizada por la base de datos y porcentaje derivado del curriculum actual. El `FAIL` se debe únicamente a que falta la prueba obligatoria de ownership y el estado la declara cubierta. Con esa prueba y el estado corregido, este auditor esperaría `PASS WITH RECOMMENDATIONS` en la ronda 2. Recomiendo atender también CODE-002 en la misma remediación, porque es barato y evita que SPEC-006/007 hereden un 100% incorrecto.
