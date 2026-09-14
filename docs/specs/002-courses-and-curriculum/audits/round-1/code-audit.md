# Auditoría de Código y Cumplimiento

SPEC: `SPEC-002 — Courses and Curriculum`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `NINGUNO — candidato sin commitear` (branch `feature/spec-002-courses-and-curriculum`)
Fecha: `2026-09-14`

Verdict: `FAIL`

Audit completeness: `Partial`

## 1. Resumen

**Esta ronda no puede cerrarse como una auditoría formal válida.** `implementation-status.md` declara `Ready for audit: Yes`, `Current audit round: 1`, pero en el mismo documento identifica el candidato como `Current commit: Uncommitted implementation candidate`. `git status` confirma que el branch `feature/spec-002-courses-and-curriculum` tiene cambios sin commitear (archivos modificados y no rastreados). No existe ningún commit Git que fije el estado exacto que se está auditando.

Esto viola la regla de estabilidad del candidato de auditoría (`docs/development-workflow.md` §9-10, §30 "Regla de Estabilidad de Auditoría"; `docs/audit-standard.md` §3 "los tres auditores deben revisar el mismo commit"; `docs/definition-of-done.md` §7 "Estabilidad del Candidato de Auditoría"). Sin un commit fijo, Qwen y Mimo no tienen garantía de revisar exactamente el mismo código que yo, y el propio candidato puede seguir cambiando mientras se audita. Por instrucción explícita de este proceso, no debo emitir un `PASS` engañoso sobre un candidato que no puede identificarse de forma estable — de modo que este round se reporta como `FAIL` por un único finding de proceso/precondición, no por un defecto funcional.

**Importante para no malinterpretar el veredicto:** revisé íntegramente el código y las pruebas del working tree (todo lo que Codex parece haber implementado para SPEC-002) como cortesía, para adelantar trabajo. Esa revisión **no encontró ningún defecto funcional bloqueante** — al contrario, la implementación luce sólida: registra los tres CPT, valida relaciones contra el tipo de post esperado, ordena de forma determinista con desempate por `ID`, aísla YouTube detrás de una interfaz `VideoProvider`, y protege los handlers de guardado con nonce + capability + guardas de autosave/revisión, incluyendo una prueba de integración que ejercita explícitamente el camino "sin autorización" y el camino autorizado. Incluso resuelve la recomendación `CODE-REC-001` de la ronda 1 de SPEC-001 (ahora `ServiceRegistry` sí registra un servicio real, `CoursesModule`). Esta revisión de código se reporta en las secciones 3–7 como **preliminar y no vinculante**: debe repetirse contra un commit real antes de que esta ronda pueda considerarse válida.

Tasks revisadas: 29 (documentadas como `Done`, revisión de código preliminar)
Acceptance Criteria revisados: 17 (preliminar)
Escenarios Gherkin revisados: 15 (preliminar)

Blocking findings: 1 (de proceso/precondición)
Non-blocking findings: 0
Recomendaciones: 3

## 2. Alcance Auditado

Documentos revisados:

- `docs/specs/002-courses-and-curriculum/spec.md`
- `docs/specs/002-courses-and-curriculum/plan.md`
- `docs/specs/002-courses-and-curriculum/feature.feature`
- `docs/specs/002-courses-and-curriculum/tasks.md`
- `docs/specs/002-courses-and-curriculum/implementation-status.md`
- (releídos como contexto de gobierno: `docs/agents.md`, `docs/development-workflow.md`, `docs/definition-of-done.md`, `docs/audit-standard.md`)

Código relevante revisado (working tree, sin commit):

- `src/Courses/CoursesModule.php`
- `src/Courses/Infrastructure/ContentTypes.php`
- `src/Courses/Infrastructure/CurriculumRepository.php`
- `src/Courses/Application/CurriculumService.php`
- `src/Courses/Presentation/Admin/CurriculumMetaBoxes.php`
- `src/Courses/Video/VideoProvider.php`
- `src/Courses/Video/VideoProviders.php`
- `src/Courses/Video/YouTubeVideoProvider.php`
- diffs en `src/Bootstrap/Plugin.php`, `tests/bootstrap.php`, `composer.json`
- diffs en `docs/specs/002-courses-and-curriculum/{spec.md,plan.md,tasks.md,implementation-status.md}`

Pruebas revisadas:

- `tests/Unit/YouTubeVideoProviderTest.php`
- `tests/Integration/courses-curriculum.php`

No se ejecutó ninguna prueba en este entorno de auditoría (mismo sandbox sin PHP/Composer que en la auditoría de SPEC-001; ver sección 8).

## 3. Precondición de Auditoría — Resultado: NO CUMPLIDA

| Verificación | Resultado |
|---|---|
| `Ready for audit: Yes` en `implementation-status.md` | Sí, declarado |
| Branch identificado | Sí — `feature/spec-002-courses-and-curriculum` |
| Commit candidato identificable | **No.** `implementation-status.md` registra literalmente `Current commit: Uncommitted implementation candidate`. `git status` muestra `composer.json`, `docs/specs/002-courses-and-curriculum/{implementation-status.md,plan.md,spec.md,tasks.md}`, `src/Bootstrap/Plugin.php` y `tests/bootstrap.php` como modificados sin commitear, más `src/Courses/`, `tests/Integration/courses-curriculum.php` y `tests/Unit/YouTubeVideoProviderTest.php` como no rastreados. |
| Working tree estable (sin cambios pendientes) | **No.** Ver arriba. |
| Tasks completas según `tasks.md` | Sí — las 29 tasks están marcadas `Status: Done`, y el conteo agregado en `implementation-status.md` (29/29) coincide (a diferencia del round 1 de SPEC-001, aquí no hay discrepancia). |

Dado que el candidato no puede fijarse a un commit, **esta auditoría no cumple la precondición exigida por el propio workflow del proyecto** para considerarse un round válido y cerrable.

## 4. Trazabilidad (preliminar, sobre working tree — repetir contra commit real)

| Acceptance Criterion | Scenario | Task | Implemented | Verified (preliminar) | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001 | Yes | Yes (revisión estática) | `ContentTypes::register()` registra `kaanbal_course`. |
| AC-002 | SC-001 | TASK-002, TASK-016 | Yes | Yes | `CurriculumMetaBoxes::renderCourse/saveCourse`; integration test guarda y relee `_kaanbal_duration`/`_kaanbal_instructor_name`. |
| AC-003 | SC-002 | TASK-003 | Yes | Yes | `ContentTypes::register()` registra `kaanbal_module`. |
| AC-004 | SC-002 | TASK-004, TASK-017 | Yes | Yes | `saveRelationship()` valida `get_post($related_id)->post_type === ContentTypes::COURSE` antes de persistir `_kaanbal_course_id`. |
| AC-005 | SC-003 | TASK-005, TASK-012 | Yes | Yes | `CurriculumRepository::modulesForCourse()` ordena `menu_order ASC, ID ASC`; integration test crea dos módulos con `menu_order` distinto y confirma el orden. |
| AC-006 | SC-004 | TASK-006 | Yes | Yes | `ContentTypes::register()` registra `kaanbal_lesson`. |
| AC-007 | SC-004 | TASK-007, TASK-018 | Yes | Yes | `saveRelationship()` valida tipo `ContentTypes::MODULE` para `_kaanbal_module_id`. |
| AC-008 | SC-005 | TASK-008, TASK-013 | Yes | Yes | `CurriculumRepository::lessonsForModule()/lessonsForModules()` mismo criterio de orden; integration test verifica orden de lecciones. |
| AC-009 | SC-006 | TASK-009, TASK-010, TASK-018 | Yes | Yes | `YouTubeVideoProvider::normalize()`; `YouTubeVideoProviderTest` (4 formatos válidos); integration test guarda una URL `watch` real y confirma el ID normalizado. |
| AC-010 | SC-007 | TASK-009, TASK-011 | Yes | Yes | `VideoProviders::forKey()` devuelve `null` para claves no soportadas; `saveVideo()` no persiste si `normalize()` devuelve `null`; integration test envía `provider = 'unsupported'` y confirma que el valor previo válido no se sobrescribe. |
| AC-011 | SC-008 | TASK-012, TASK-013, TASK-014 | Yes | Yes | `CurriculumService::forCourse()`; integration test valida estructura `course → modules → lessons` completa, incluyendo un módulo vacío. |
| AC-012 | SC-009 | TASK-014, TASK-015 | Yes | Yes | `CurriculumService::hierarchyForLesson()`; integration test resuelve lesson → module → course. |
| AC-013 | SC-010, SC-011 | TASK-004, TASK-007, TASK-021 | Yes | Yes | `saveRelationship()` rechaza IDs cuyo post type no coincide (probado en integration test con un `module_id` que en realidad es un `course_id`). No hay un caso de prueba explícito con un ID **totalmente inexistente** (ver CODE-REC-005), aunque `get_post()` devolvería `null` y el mismo guard lo cubre por construcción. |
| AC-014 | SC-012 | TASK-024 | Yes | Yes | `CurriculumService::forCourse()` devuelve `modules: []` cuando no hay módulos asociados; integration test lo ejercita explícitamente (borra la relación y vuelve a consultar). |
| AC-015 | SC-013 | TASK-024 | Yes | Yes | Un módulo devuelto por `modulesForCourse()` sin lecciones asociadas produce `lessons: []` en `CurriculumService::forCourse()`; integration test lo cubre (el "Second module" queda sin lecciones). |
| AC-016 | SC-014 | TASK-019, TASK-025 | Yes | Yes | `canSave()` exige nonce válido + `current_user_can('edit_post', ...)` + no autosave/revisión; integration test ejercita el camino no autorizado (usuario 0, nonce inválido) y confirma que la metadata no cambia, y el camino autorizado con un administrador real. |
| AC-017 | SC-015 | TASK-026 | Yes | Yes (revisión de código) | No se encontró ninguna tabla propia, hook de WooCommerce, lógica de progreso, quiz ni certificado en el código de SPEC-002; `RNF-002` (sin tablas transaccionales) se respeta. |

## 5. Verificación de Tasks (preliminar)

Las 29 tasks de `tasks.md` están marcadas `Status: Done`. La revisión de código no encontró ninguna task marcada `Done` sin entregable correspondiente. No se detalla una tabla task-por-task exhaustiva en esta ronda dado que el hallazgo de la sección 3 hace que esta verificación sea preliminar por definición; se repetirá con el mismo nivel de detalle que la auditoría de SPEC-001 una vez exista un commit candidato.

## 6. Findings

### CODE-003 — No existe un commit candidato estable para el round 1 de SPEC-002

Severity: High
Blocking: Yes
Classification: Requirement Violation
Status: NEW

Affected requirement:
Regla de estabilidad del candidato de auditoría (`docs/development-workflow.md` §9, §10, §30; `docs/audit-standard.md` §3; `docs/definition-of-done.md` §7)

Affected files:
- docs/specs/002-courses-and-curriculum/implementation-status.md
- (todo el working tree sin commitear del branch `feature/spec-002-courses-and-curriculum`)

Description:

`implementation-status.md` declara `Ready for audit: Yes` y `Current audit round: 1`, pero registra el candidato como `Current commit: Uncommitted implementation candidate`. `git status` confirma que el branch tiene archivos modificados (`composer.json`, `src/Bootstrap/Plugin.php`, `tests/bootstrap.php`, y los cuatro documentos de la SPEC) y archivos no rastreados (`src/Courses/`, dos archivos de test) — es decir, no hay ningún commit Git que capture el estado exacto que se pretende auditar.

Evidence:

`git status --short` sobre `feature/spec-002-courses-and-curriculum` lista 7 archivos modificados y 3 rutas no rastreadas; `implementation-status.md` sección "Current commit: Uncommitted implementation candidate" (comparar con SPEC-001 round 1, donde el mismo campo apuntaba a un commit real, `a9e91ec`, verificable con `git rev-parse HEAD`).

Expected:

Antes de declarar `Ready for audit: Yes`, Codex debe commitear el trabajo en el branch de la SPEC y registrar el hash real (`git rev-parse HEAD` o equivalente) como "Current commit" en `implementation-status.md`, dejando el working tree limpio (`git status` sin cambios pendientes), tal como se hizo para SPEC-001.

Actual:

El candidato es un estado mutable del working tree, no una revisión Git fija.

Impact:

Sin un commit fijo: (1) no hay garantía de que Qwen y Mimo auditen exactamente el mismo código que yo revisé; (2) el código podría seguir cambiando durante la ronda, invalidando cualquier veredicto; (3) no existe un artefacto reproducible al que puedan referirse los tres reportes de auditoría ni el `round-summary.md`; (4) se rompe la trazabilidad exigida por `docs/agents.md` §4 y §9 (rondas de auditoría atadas a una revisión concreta).

Required correction:

Codex debe: `git add` de los archivos relevantes, crear un commit (p. ej. `feat: implement SPEC-002 courses and curriculum`), y actualizar `implementation-status.md` con el hash real del commit resultante. Solo entonces puede abrirse formalmente un round 1 válido. La revisión de código de este reporte (secciones 4–5) puede reutilizarse como base una vez exista el commit, re-verificando que el diff commiteado coincide con lo aquí revisado.

## 7. Recomendaciones

### CODE-REC-005 — Sin caso de prueba con un ID de relación totalmente inexistente

Severity: Low
Blocking: No
Classification: Recommendation

Description:

`tests/Integration/courses-curriculum.php` prueba el rechazo de una relación cuando el ID apunta a un post de **tipo incorrecto** (usa el `course_id` como si fuera `module_id`), pero no prueba explícitamente un ID que no corresponda a ningún post existente (por ejemplo, `999999999`). El guard en `CurriculumMetaBoxes::saveRelationship()` (`get_post($related_id) instanceof \WP_Post`) cubre ambos casos por construcción, pero un test explícito para el ID inexistente cerraría del todo EC-003/EC-004 tal como están redactados en `spec.md`.

Reason:

Cobertura de regresión más completa para AC-013 sin alcance adicional.

---

### CODE-REC-006 — Numeración duplicada de secciones en `plan.md`

Severity: Info
Blocking: No
Classification: Recommendation

Description:

`plan.md` tiene ahora dos secciones tituladas `## 21.` ("21. Decisiones de Implementación", añadida durante la implementación, y la preexistente "21. Riesgos Técnicos"). Es una discrepancia puramente cosmética en la numeración, no afecta contenido ni trazabilidad.

Reason:

Fácil de corregir renumerando la sección añadida (p. ej. como un nuevo `## 23.` al final, o renumerando las secciones posteriores) la próxima vez que se edite `plan.md`.

---

### CODE-REC-007 — Estado parcial de metadata de video ante entrada mixta (proveedor vacío + fuente no vacía)

Severity: Low
Blocking: No
Classification: Recommendation

Description:

En `CurriculumMetaBoxes::saveVideo()`, si el administrador deja el selector de proveedor en "No video yet" (`provider_key = ''`) pero el campo de texto de fuente todavía contiene algo, la condición de limpieza (`'' === $provider_key && '' === $source`) no se cumple, `VideoProviders::forKey('')` devuelve `null`, y por lo tanto no se guarda nada — pero tampoco se limpia una fuente/proveedor previamente válido que ya existiera en la base de datos. El resultado es que el formulario "parece" mostrar la intención de borrar el video, pero el valor persistido no cambia.

Reason:

No es un defecto de seguridad ni contradice ningún AC (RB-006 solo exige que una lección pueda existir sin video, lo cual se cumple), pero podría confundir a un administrador. Vale la pena considerar limpiar ambos metas cuando el proveedor quede vacío, independientemente del contenido del campo de fuente.

## 8. Validación Ejecutada

Comandos intentados por este auditor en su propio entorno (mismo sandbox del round 1 de SPEC-001):

```text
php -l ...    → no disponible (mismo entorno sin PHP/Composer que en la auditoría de SPEC-001)
composer ...  → no disponible
```

Resultados:

```text
No fue posible ejecutar PHPUnit, PHPCS, PHPStan ni el script de integración
en el entorno de este auditor. La revisión se basó en lectura estática
completa del código y de las pruebas, y en lo documentado por Codex en
implementation-status.md (15 tests / 19 assertions unitarios — la aritmética
es consistente: 7 tests/11 assertions de SPEC-001 + 8 tests/8 assertions de
YouTubeVideoProviderTest, lo cual es una señal de consistencia interna, no
una verificación independiente).
```

Limitaciones ambientales:

`Verification status: BLOCKED BY ENVIRONMENT` para la re-ejecución de la suite. Adicionalmente, y de forma más importante para el veredicto de esta ronda: `Verification status: BLOCKED BY MISSING COMMIT` — no existe una revisión Git fija que auditar formalmente (ver sección 3 y CODE-003). Todo lo revisado en las secciones 4–7 corresponde al working tree tal como estaba en el momento de esta auditoría y debe tratarse como una revisión preliminar, no como evidencia atada a una revisión inmutable.

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- CODE-003 — No existe un commit candidato estable e identificable para el round 1 de SPEC-002; el working tree tiene cambios sin commitear en `feature/spec-002-courses-and-curriculum`.

Non-blocking findings:

- Ninguno.

Recomendaciones:

- CODE-REC-005, CODE-REC-006, CODE-REC-007 (ver sección 7).

Comentario final:

El único motivo de este `FAIL` es de proceso: falta un commit que fije el candidato de auditoría, lo cual es una precondición explícita del propio workflow del proyecto antes de que cualquier auditor pueda emitir un veredicto vinculante. **No refleja un juicio negativo sobre la calidad de la implementación** — la revisión preliminar de código de las secciones 4–7 no encontró ningún defecto funcional, de seguridad ni de trazabilidad dentro del alcance de SPEC-002, y la cobertura de pruebas (unitaria + integración real contra WordPress, incluyendo casos negativos de autorización y de relaciones inválidas) es sólida. Recomiendo a Codex commitear el trabajo, registrar el hash real en `implementation-status.md`, y volver a abrir el round 1; en ese punto la trazabilidad ya reunida en este reporte puede reutilizarse casi íntegramente.
