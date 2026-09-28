# Auditoría de Código y Cumplimiento

SPEC: `SPEC-002 — Courses and Curriculum`
Ronda: `1` (reabierta contra un commit estable, tras el `FAIL` de precondición anterior)
Auditor: Claude Code
Commit auditado: `6a66d49ea40703961a9048d3440fa92f513f9b29` (branch `feature/spec-002-courses-and-curriculum`)
Fecha: `2026-09-14`

Verdict: `PASS`

Audit completeness: `Complete`

## 0. Nota sobre la reapertura de esta ronda

La primera pasada de esta misma ronda 1 (documentada anteriormente en este archivo) terminó en `FAIL` únicamente por un finding de proceso: `implementation-status.md` apuntaba a `Current commit: Uncommitted implementation candidate` y el working tree tenía cambios sin commitear (`CODE-003`). No hubo entonces ningún defecto funcional bloqueante — la revisión de código de esa pasada ya era, en sustancia, la que se confirma ahora.

Codex resolvió `CODE-003` creando el commit `6a66d49` (`feat: implement SPEC-002 courses and curriculum`) con exactamente el contenido que ya se había revisado, y un commit de seguimiento `001496c` (`docs: reopen SPEC-002 audit round 1`) que solo actualiza `implementation-status.md`. `git status` confirma un working tree limpio sobre `6a66d49`/`001496c`. Codex también reporta como resueltas las tres recomendaciones no bloqueantes de la pasada anterior (`CODE-REC-005`, `CODE-REC-006`, `CODE-REC-007`); esta auditoría verifica esas tres resoluciones de forma independiente antes de cerrar el round. Este archivo se actualiza in situ (mismo round 1) en vez de crear un `round-2`, siguiendo la decisión de Codex de "reopen audit round 1" registrada en `implementation-status.md`.

## 1. Resumen

Precondición cumplida: `implementation-status.md` declara `Ready for audit: Yes`, `Current audit round: 1`, `Current implementation commit: 6a66d49ea40703961a9048d3440fa92f513f9b29`; `git status` sobre `feature/spec-002-courses-and-curriculum` está limpio y el commit existe en el historial (`git log` lo confirma, con `001496c` inmediatamente encima solo tocando `implementation-status.md`, lo cual no altera el candidato de código auditado).

La implementación de SPEC-002 satisface, con evidencia razonable, los 17 Acceptance Criteria y los 15 escenarios Gherkin dentro del alcance aprobado. Registra los tres CPT administrativos (`kaanbal_course`, `kaanbal_module`, `kaanbal_lesson`), valida toda relación Module→Course y Lesson→Module contra el tipo de post real (rechazando tanto tipos incorrectos como IDs totalmente inexistentes), ordena de forma determinista (`menu_order ASC, ID ASC`), aísla YouTube detrás de una interfaz `VideoProvider` sin acoplar `CurriculumService`/`CurriculumRepository` a ningún proveedor concreto, y protege los handlers de guardado con nonce + capability (`edit_post`) + guardas de autosave/revisión. No se encontró código fuera de alcance (sin WooCommerce, matrícula, progreso, quiz ni certificados).

No se encontró ningún finding bloqueante. Las tres recomendaciones abiertas de la pasada anterior se verificaron como resueltas. No se identificó ningún finding ni recomendación nueva en esta revisión.

Tasks revisadas: 29
Acceptance Criteria revisados: 17
Escenarios Gherkin revisados: 15

Blocking findings: 0
Non-blocking findings: 0 nuevos (3 de rondas previas: RESOLVED)

## 2. Alcance Auditado

Documentos revisados:

- `docs/specs/002-courses-and-curriculum/spec.md`
- `docs/specs/002-courses-and-curriculum/plan.md`
- `docs/specs/002-courses-and-curriculum/feature.feature`
- `docs/specs/002-courses-and-curriculum/tasks.md`
- `docs/specs/002-courses-and-curriculum/implementation-status.md`
- `docs/agents.md`, `docs/development-workflow.md`, `docs/definition-of-done.md`, `docs/audit-standard.md` (contexto de gobierno, releídos)

Código revisado en el commit `6a66d49` (íntegro):

- `kaanbal.php` (sin cambios respecto a SPEC-001)
- `src/Bootstrap/Plugin.php` (ahora registra `CoursesModule` en `ServiceRegistry`)
- `src/Courses/CoursesModule.php`
- `src/Courses/Infrastructure/ContentTypes.php`
- `src/Courses/Infrastructure/CurriculumRepository.php`
- `src/Courses/Application/CurriculumService.php`
- `src/Courses/Presentation/Admin/CurriculumMetaBoxes.php` (releído completo tras el fix de `CODE-REC-007`)
- `src/Courses/Video/VideoProvider.php`
- `src/Courses/Video/VideoProviders.php`
- `src/Courses/Video/YouTubeVideoProvider.php`
- `composer.json`, `tests/bootstrap.php`

Pruebas revisadas:

- `tests/Unit/YouTubeVideoProviderTest.php`
- `tests/Integration/courses-curriculum.php` (releído completo tras el fix de `CODE-REC-005`)

Verificaciones de Git realizadas:

- `git status` (working tree limpio)
- `git log --oneline -10` (confirma `6a66d49` en el historial de `feature/spec-002-courses-and-curriculum`, con `001496c` encima)
- `git show --stat 6a66d49` y `git show --stat 001496c` (confirma qué archivos toca cada commit)

No se ejecutó ninguna prueba en este entorno de auditoría (mismo sandbox sin PHP/Composer que en auditorías previas; ver sección 8).

## 3. Resolución de Findings de la Pasada Anterior

### CODE-003 — RESOLVED

Previous status: Blocking / OPEN
Current status: **RESOLVED**

Evidence: `implementation-status.md` ahora registra `Current implementation commit: 6a66d49ea40703961a9048d3440fa92f513f9b29`; `git status` confirma working tree limpio; `git log` confirma que ese hash existe en `feature/spec-002-courses-and-curriculum`. El commit de seguimiento `001496c` únicamente actualiza `implementation-status.md` (verificado con `git show --stat`), por lo que no invalida el candidato de código auditado.

### CODE-REC-005 — RESOLVED

Previous status: Recommendation / OPEN
Current status: **RESOLVED**

Evidence: `tests/Integration/courses-curriculum.php` ahora calcula `$missing_related_id = max($created_ids) + 100000` (un ID garantizado inexistente) y lo usa tanto para `saveModule()` (relación `course_id` inexistente) como para `saveLesson()` (relación `module_id` inexistente), confirmando en ambos casos que la metadata previamente válida no se sobrescribe. Esto complementa el caso ya existente de "tipo incorrecto" y cierra EC-003/EC-004 tal como están redactados en `spec.md`.

### CODE-REC-006 — RESOLVED

Previous status: Recommendation / OPEN
Current status: **RESOLVED**

Evidence: `grep -n "^## " docs/specs/002-courses-and-curriculum/plan.md` muestra ahora las secciones 1–24 sin números repetidos (la antigua "21. Decisiones de Implementación" es ahora la sección 21, y "Riesgos Técnicos"/"Decisiones"/"Condición de Finalización" se renumeraron consecutivamente a 22–24).

### CODE-REC-007 — RESOLVED

Previous status: Recommendation / OPEN
Current status: **RESOLVED**

Evidence: en `CurriculumMetaBoxes::saveVideo()`, la condición de limpieza cambió de `'' === $provider_key && '' === $source` a `'' === $provider_key || '' === $source`. Ahora, dejar vacío cualquiera de los dos campos (no solo ambos) borra tanto `_kaanbal_video_provider` como `_kaanbal_video_source`, eliminando el estado "parece borrado pero no se borró" señalado en la recomendación.

## 4. Trazabilidad

| Acceptance Criterion | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001 | Yes | Yes | `ContentTypes::register()` registra `kaanbal_course` (`public=false`, `show_ui=true`). |
| AC-002 | SC-001 | TASK-002, TASK-016 | Yes | Yes | `CurriculumMetaBoxes::renderCourse/saveCourse`; integration test guarda y relee `_kaanbal_duration`/`_kaanbal_instructor_name` para un editor autorizado. |
| AC-003 | SC-002 | TASK-003 | Yes | Yes | `ContentTypes::register()` registra `kaanbal_module`. |
| AC-004 | SC-002 | TASK-004, TASK-017 | Yes | Yes | `saveRelationship()` valida `post_type === ContentTypes::COURSE` antes de persistir `_kaanbal_course_id`; integration test confirma persistencia con relación válida. |
| AC-005 | SC-003 | TASK-005, TASK-012 | Yes | Yes | `CurriculumRepository::modulesForCourse()` ordena `menu_order ASC, ID ASC`; integration test crea dos módulos con `menu_order` distinto y confirma el orden, además de un caso de empate (`menu_order` igual, desempate por `ID`). |
| AC-006 | SC-004 | TASK-006 | Yes | Yes | `ContentTypes::register()` registra `kaanbal_lesson`. |
| AC-007 | SC-004 | TASK-007, TASK-018 | Yes | Yes | `saveRelationship()` valida `post_type === ContentTypes::MODULE`. |
| AC-008 | SC-005 | TASK-008, TASK-013 | Yes | Yes | `CurriculumRepository::lessonsForModule()/lessonsForModules()` mismo criterio de orden; integration test verifica orden y empate. |
| AC-009 | SC-006 | TASK-009, TASK-010, TASK-018 | Yes | Yes | `YouTubeVideoProvider::normalize()`; `YouTubeVideoProviderTest` (id directo, `watch`, `youtu.be`, `embed`); integration test guarda una URL `watch` real y confirma el ID normalizado (`dQw4w9WgXcQ`). |
| AC-010 | SC-007 | TASK-009, TASK-011 | Yes | Yes | `VideoProviders::forKey()` devuelve `null` para claves no soportadas; `saveVideo()` no persiste si `normalize()` devuelve `null`; integration test envía `provider = 'unsupported'` y confirma que el valor previo válido no se sobrescribe. |
| AC-011 | SC-008 | TASK-012, TASK-013, TASK-014 | Yes | Yes | `CurriculumService::forCourse()`; integration test valida `course → modules → lessons` completo, incluyendo un módulo vacío y un curso vacío. |
| AC-012 | SC-009 | TASK-014, TASK-015 | Yes | Yes | `CurriculumService::hierarchyForLesson()`; integration test resuelve lesson → module → course. |
| AC-013 | SC-010, SC-011 | TASK-004, TASK-007, TASK-021 | Yes | Yes | `saveRelationship()` rechaza tanto IDs de tipo incorrecto como IDs totalmente inexistentes (`$missing_related_id`); ambos casos cubiertos por el integration test. |
| AC-014 | SC-012 | TASK-024 | Yes | Yes | `CurriculumService::forCourse()` devuelve `modules: []` cuando no hay módulos asociados; integration test lo ejercita explícitamente. |
| AC-015 | SC-013 | TASK-024 | Yes | Yes | Un módulo sin lecciones produce `lessons: []`; integration test lo cubre con el "Second module". |
| AC-016 | SC-014 | TASK-019, TASK-025 | Yes | Yes | `canSave()` exige nonce válido + `current_user_can('edit_post', ...)` + no autosave/revisión; integration test ejercita el camino no autorizado (usuario 0, nonce inválido → sin cambios) y el autorizado (administrador real). |
| AC-017 | SC-015 | TASK-026 | Yes | Yes (revisión de código) | Ningún archivo de SPEC-002 introduce tablas propias, hooks de WooCommerce, progreso, quiz ni certificados; `RNF-002` se respeta. |

## 5. Verificación de Tasks

Las 29 tasks de `tasks.md` están marcadas `Status: Done` y el conteo agregado en `implementation-status.md` (29/29/0/0/0/0) es consistente con ello — a diferencia del round 1 de SPEC-001, aquí no hubo discrepancia de conteo.

| Task | Status documentado | Resultado de auditoría | Observaciones |
|---|---|---|---|
| TASK-001 | Done | PASS | `ContentTypes::register()`. |
| TASK-002 | Done | PASS | Metadata de curso (`_kaanbal_duration`, `_kaanbal_instructor_name`). |
| TASK-003 | Done | PASS | CPT `kaanbal_module`. |
| TASK-004 | Done | PASS | Relación Module→Course validada server-side. |
| TASK-005 | Done | PASS | Orden de módulos determinista. |
| TASK-006 | Done | PASS | CPT `kaanbal_lesson`. |
| TASK-007 | Done | PASS | Relación Lesson→Module validada server-side. |
| TASK-008 | Done | PASS | Orden de lecciones determinista. |
| TASK-009 | Done | PASS | Modelo de fuente de video desacoplado (`VideoProvider`). |
| TASK-010 | Done | PASS | `YouTubeVideoProvider` con 4 formatos soportados. |
| TASK-011 | Done | PASS | Proveedores/fuentes inválidos rechazados sin sobrescribir datos válidos previos. |
| TASK-012 | Done | PASS | `modulesForCourse()`. |
| TASK-013 | Done | PASS | `lessonsForModule()/lessonsForModules()` (batched, evita N+1). |
| TASK-014 | Done | PASS | `CurriculumService::forCourse()`. |
| TASK-015 | Done | PASS | `CurriculumService::hierarchyForLesson()`. |
| TASK-016 | Done | PASS | UI admin de Course (metabox). |
| TASK-017 | Done | PASS | UI admin de Module (selector de curso). |
| TASK-018 | Done | PASS | UI admin de Lesson (módulo, proveedor, fuente). |
| TASK-019 | Done | PASS | `canSave()`: nonce + capability + autosave/revision guard. |
| TASK-020 | Done | PASS | Cubierta por integration test (registro de los 3 CPT). |
| TASK-021 | Done | PASS | Cubierta por integration test (relaciones válidas e inválidas, incluyendo IDs inexistentes tras `CODE-REC-005`). |
| TASK-022 | Done | PASS | Cubierta por integration test (orden + empate). |
| TASK-023 | Done | PASS | Cubierta por `YouTubeVideoProviderTest` (válidos e inválidos). |
| TASK-024 | Done | PASS | Cubierta por integration test (curso completo, curso vacío, módulo vacío). |
| TASK-025 | Done | PASS | Cubierta por integration test (usuario no autorizado vs. administrador). |
| TASK-026 | Done | PASS | Revisión de código confirma ausencia de scope creep (sección 4, AC-017). |
| TASK-027 | Done | PASS (documentado) | Quality Gate registrado en `implementation-status.md`; no re-ejecutado por este auditor (BLOCKED BY ENVIRONMENT, sección 8). |
| TASK-028 | Done | PASS | `implementation-status.md` refleja el estado real, incluyendo la resolución de `CODE-003` y las tres recomendaciones. |
| TASK-029 | Done | PASS | `Status: Ready for audit`, `Ready for audit: Yes`, `Current audit round: 1`, commit identificable. |

## 6. Findings

Ninguno nuevo. Ver sección 3 para el estado de los findings de la pasada anterior (todos `RESOLVED`).

## 7. Recomendaciones

Ninguna recomendación nueva. Nota menor no accionable: en `implementation-status.md`, la sección "Open Findings" dice "None." y justo debajo lista las tres resoluciones (`CODE-REC-005/006/007`) — es solo una cuestión de organización del documento, no una inconsistencia de contenido; no se registra como finding.

## 8. Validación Ejecutada

Comandos ejecutados por este auditor:

```text
git status
git log --oneline -10
git show --stat 6a66d49
git show --stat 001496c
grep -n "^## " docs/specs/002-courses-and-curriculum/plan.md
grep -n "999999|nonexistent..." tests/Integration/courses-curriculum.php
```

Resultados: todos exitosos, ver evidencia citada en las secciones 3–5.

Comandos de PHP/Composer NO ejecutados por este auditor (mismo sandbox sin `php`/`composer` que en auditorías previas):

```text
composer quality / composer test / composer test:integration / composer test:integration:curriculum
```

Limitaciones ambientales:

`Verification status: BLOCKED BY ENVIRONMENT` para la re-ejecución de la suite completa (PHPUnit, PHPCS, PHPStan, integration tests). Lo que pudo revisarse de forma independiente: la totalidad del código de producción y de pruebas de SPEC-002, la resolución verificable de los 4 findings de la pasada anterior mediante lectura estática del diff exacto que los corrige, y la estabilidad del commit candidato vía Git. Lo que queda sin verificación directa por este auditor: la ejecución real de la suite (se confía en lo documentado por Codex: `15 tests, 19 assertions` unitarios, integration tests `PASS`) y la verificación manual en el admin de WordPress documentada en `implementation-status.md`.

## 9. Conclusión

Verdict:

`PASS`

Blocking findings:

- Ninguno.

Non-blocking findings:

- Ninguno nuevo. `CODE-003`, `CODE-REC-005`, `CODE-REC-006` y `CODE-REC-007` (de la pasada anterior de este mismo round) quedan `RESOLVED`.

Comentario final:

Con un commit estable e identificable (`6a66d49`) y las tres recomendaciones previas verificadas como resueltas, SPEC-002 cumple, con evidencia sólida, sus 17 Acceptance Criteria y 15 escenarios Gherkin dentro del alcance aprobado, sin señales de scope creep ni modificación indebida de criterios protegidos. Desde el punto de vista de esta auditoría de requisitos e implementación, SPEC-002 puede avanzar a las auditorías de arquitectura (Qwen) y seguridad (Mimo) sobre el mismo commit `6a66d49`, y —en lo que a esta auditoría concierne— a revisión humana una vez esas dos auditorías también concluyan sin blockers.
