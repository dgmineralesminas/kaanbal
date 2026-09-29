# Auditoría de Código y Cumplimiento

SPEC: `SPEC-004 — Course Access and Player`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `68c25619f462e309550596aa4e8b7f7584f3b78f` (branch `feature/spec-004-course-access-and-player`)
Fecha: `2026-09-29`

Verdict: `FAIL`

Audit completeness: `Complete` (revisión estática completa; ejecución de PHPUnit/PHPStan/PHPCS/integración `BLOCKED BY ENVIRONMENT`, ver §8)

## 1. Resumen

La implementación de producción satisface los requisitos funcionales de SPEC-004. Lo verifiqué leyendo el código y ejecutando un arnés propio sobre las clases puras. La política de acceso está centralizada en `CourseAccessService`. La autorización ocurre en servidor antes de elegir template y antes de resolver la lección. La relación Course/Lesson se valida contra la jerarquía de SPEC-002. El iframe de YouTube se construye solo desde un ID normalizado, y el flujo no escribe nada.

El veredicto es `FAIL` por un único finding bloqueante (`CODE-001`), que es de evidencia y no de comportamiento. Faltan pruebas marcadas como **obligatorias** en `plan.md §25` para la ruta de lección, y `AC-019` no tiene ninguna prueba. Aun así, `implementation-status.md` declara esos criterios `INTEGRATION-TESTED` y `TASK-022`/`TASK-026` están en `Done`. Es el mismo patrón que `CODE-002` de SPEC-003 ronda 1, clasificado entonces como bloqueante. La corrección es barata: agregar las pruebas y dejar el estado documental conforme a la evidencia real.

Tasks revisadas: `33`
Acceptance Criteria revisados: `22`
Escenarios Gherkin revisados: `19` (SC-001 a SC-019)

Blocking findings: `1` (CODE-001)
Non-blocking findings: `7` (CODE-002 a CODE-008)

---

## 2. Alcance Auditado

Documentos revisados:

- `spec.md`, `plan.md`, `feature.feature`, `tasks.md`, `implementation-status.md` de SPEC-004, tanto en `68c2561` como en el working tree (cambios sin commitear, ver CODE-002)
- `docs/audit-standard.md`, `docs/templates/code-audit-template.md`
- `docs/specs/003-woocomerce-enrollment/audits/round-1/code-audit.md` (precedente de clasificación)

Código revisado íntegramente en `68c2561`:

- `src/Access/Application/{CourseAccessResult,CourseAccessService,LessonNavigationService,YouTubeEmbedRenderer}.php`
- `src/Access/PlayerModule.php`
- `src/Access/Presentation/Frontend/{FrontendRouter,TemplateContext}.php`
- `src/Courses/Application/CurriculumService.php`, `src/Courses/Infrastructure/{CurriculumRepository,ContentTypes}.php`, `src/Courses/Video/YouTubeVideoProvider.php`
- `src/Enrollment/Application/EnrollmentLookup.php`, `src/Enrollment/Infrastructure/EnrollmentRepository.php`
- `src/Bootstrap/{Activator,Deactivator,Plugin}.php`
- `templates/frontend/{course,lesson,access-denied,not-found}.php`
- `assets/css/player.css` (revisión superficial: solo presentación)

Pruebas revisadas:

- `tests/Unit/CourseAccessServiceTest.php`, `tests/Unit/LessonNavigationServiceTest.php`, `tests/Unit/YouTubeEmbedRendererTest.php`, `tests/bootstrap.php`
- `tests/Integration/course-access-player.php`

Git: el árbol no documental del working tree es idéntico a `68c2561` (`git diff --quiet 68c2561 -- . ':!docs'`). Solo hay cambios sin commitear en los cuatro documentos de estado de SPEC-004.

---

## 3. Trazabilidad

Leyenda de "Verified": **Yes** = prueba que ejerce el comportamiento y aserción que lo comprueba; **Static** = verificado por lectura de código o arnés del auditor, sin prueba del proyecto que lo cubra; **Partial** = prueba existente pero con aserción débil o incompleta.

| AC | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001/002/006/021 | Yes | Yes | `CourseAccessService::check()` L31-35; unit `active enrollment`; integración L143-147 |
| AC-002 | SC-002 | TASK-001/002/006/021 | Yes | Yes | match `'completed'`; unit; integración L187 |
| AC-003 | SC-003 | TASK-001/002/006/021 | Yes | Yes | match `'revoked'`; unit; integración L191-197 (solo ruta de curso) |
| AC-004 | SC-004 | TASK-001/002/006/021 | Yes | Yes (curso) / Static (lección) | integración L191-197 solo con `lesson_slug = null` → CODE-001 |
| AC-005 | SC-005 | TASK-001/002/006/021 | Yes | Yes (curso) / Static (lección) | `user_id <= 0 → NotAuthenticated`; mismo hueco → CODE-001 |
| AC-006 | SC-006 | TASK-003/004/008/019/022 | Yes | Yes | integración L143-156 (resolve + render de `course.php`) |
| AC-007 | SC-006 | TASK-008/009/022 | Yes | Partial | orden `menu_order ASC, ID ASC` en `CurriculumRepository`; la prueba cuenta módulos pero no asserta orden → CODE-004 |
| AC-008 | SC-007 | TASK-007/010/023 | Yes | Partial | no existe lógica de bloqueo; `resolve()` de la lección 2 (L177) no se asserta → CODE-004 |
| AC-009 | SC-008 | TASK-005/007/011/019/023 | Yes | Partial | `lesson.php` imprime título, contenido, módulo y curso; la prueba asserta título pero no contenido ni módulo → CODE-004 |
| AC-010 | SC-009 | TASK-012/024 | Yes | Yes | `YouTubeEmbedRenderer`; unit + integración L169 (`youtube-nocookie.com/embed/dQw4w9WgXcQ`) |
| AC-011 | SC-010 | TASK-011/013/023/024 | Yes | Partial | `lesson.php` L31 omite player si `''`; ninguna prueba renderiza una lección sin video → CODE-004 |
| AC-012 | SC-011 | TASK-014/015/025 | Yes | Yes | unit `forLesson(20)` |
| AC-013 | SC-011 | TASK-014/015/025 | Yes | Yes | unit + integración L160 |
| AC-014 | SC-012 | TASK-014/025 | Yes | Yes | unit + integración L160 (`previous === null`) |
| AC-015 | SC-013 | TASK-014/025 | Yes | Yes | unit `forLesson(30)` |
| AC-016 | SC-014 | TASK-005/007/016/026 | Yes | Yes | `FrontendRouter` L124-128; integración L199 (Course A + Lesson B → 404) |
| AC-017 | SC-015 | TASK-004/017/027 | Yes | Yes | integración L203 (`missing-course` → 404) |
| AC-018 | SC-016 | TASK-005/018/027 | Yes | Yes | integración L203 (`missing-lesson` → 404) |
| AC-019 | SC-017 | TASK-004/008/022 | Yes | **Static** | no hay fixture de curso sin módulos → **CODE-001** |
| AC-020 | SC-018 | TASK-008/009/015/022 | Yes | Yes | integración L154 (`Player empty module`) |
| AC-021 | SC-019 | TASK-028/030 | Yes | Partial | solo se compara la fila de enrollment y sin renderizar template; no existe tabla de progreso en el código → CODE-004 |
| AC-022 | SC-014 | TASK-006/007/016/026 | Yes | **Static** | acceso directo a URL de **lección** sin matrícula no probado → **CODE-001** |

Requisitos no funcionales:

| RNF | Resultado | Evidence |
|---|---|---|
| RNF-001 Server-side | Cumple | `FrontendRouter::template()` decide status/template en `template_include`; los CPT son `public => false`, `query_var => false`, `rewrite => false`, así que no existe una vista nativa alternativa |
| RNF-002 Fuente única | Cumple | ningún template consulta matrícula; `grep` sin otras lecturas de estado |
| RNF-003 Player desacoplado | Cumple | `CourseAccessService` depende de la interfaz `EnrollmentLookup`; templates no tocan `$wpdb` |
| RNF-004 No tracking | Cumple | ninguna escritura en `src/Access` ni en templates |
| RNF-005 Escaping | Cumple | `esc_html`/`esc_url`/`esc_attr`/`wp_kses_post`; los dos `phpcs:ignore` están justificados |
| RNF-006 Embed seguro | Cumple | URL construida con `rawurlencode(ID)`; arnés del auditor con 6 entradas hostiles → `''` |
| RNF-007 Theme independence | Cumple | templates propios + `get_header()/get_footer()` |
| RNF-008 Rendimiento | Cumple | curso = 2 `get_posts` (módulos, lecciones con `IN`) con meta cache precargado; sin consultas por ítem en templates |

---

## 4. Verificación de Tasks

| Task | Status documentado | Resultado | Nota |
|---|---|---|---|
| TASK-001 a TASK-020 | Done | PASS | implementación presente y conforme |
| TASK-021 | Done | PASS | casos de política cubiertos en unit + integración (ruta de curso) |
| TASK-022 | Done | **FAIL** | AC-019 (curso sin módulos) sin prueba → CODE-001 |
| TASK-023 | Done | PASS con observaciones | aserciones débiles → CODE-004 |
| TASK-024 | Done | PASS con observaciones | falta el caso listado "sin video" → CODE-004 |
| TASK-025 | Done | PASS con observaciones | falta el caso listado "single lesson" → CODE-004 |
| TASK-026 | Done | **FAIL** | de sus tres casos listados, solo "Course A + Lesson B" tiene prueba → CODE-001 |
| TASK-027 | Done | PASS | |
| TASK-028 | Done | PASS con observaciones | → CODE-004 |
| TASK-029 | Done | PASS | ver RNF-008 |
| TASK-030 | Done | PASS | no hay progreso, completion, quiz ni certificados |
| TASK-031 | Done | Not verified | no reproducible en este entorno (§8) |
| TASK-032 | Done | **FAIL** | el estado declara `INTEGRATION-TESTED` para AC-019 y AC-022 sin prueba → CODE-001; los cambios no están commiteados → CODE-002 |
| TASK-033 | Done (working tree) / Pending (en `68c2561`) | PASS con observaciones | → CODE-002 |

---

## 5. Findings

### CODE-001 — Pruebas de seguridad obligatorias de la ruta de lección y AC-019 ausentes, declaradas como probadas

Severity: Medium
Blocking: Yes
Classification: Test Gap
Status: NEW

Affected requirement:

AC-004, AC-005, AC-019, AC-022; `plan.md §25` (Tests de Seguridad, "Obligatorios"); `plan.md §26`

Affected scenario:

SC-004, SC-005, SC-014, SC-017

Affected task:

TASK-022, TASK-026, TASK-032

Affected files:

- `tests/Integration/course-access-player.php`
- `docs/specs/004-course-access-and-player/implementation-status.md`

Description:

`plan.md §25` enumera como **obligatorias** estas pruebas: "anonymous cannot access protected lesson", "user without enrollment cannot access", "revoked user cannot access" y "user enrolled in course A cannot use access to read lesson from course B". El bucle de denegación de la prueba de integración solo ejerce la ruta de **curso**. No hay ninguna prueba de la ruta `/courses/{c}/lesson/{l}/` para visitante, no matriculado o revocado. `TASK-026` lista además "acceso directo sin matrícula" y "usuario matriculado solo en otro curso", y ninguno de los dos está probado. `AC-019` (curso sin módulos) no tiene fixture: los dos cursos del fixture tienen al menos un módulo con lecciones.

Evidence:

- `tests/Integration/course-access-player.php` L191-197: `$router->resolve($course_a->post_name, null, $denied_user)`. El segundo argumento siempre es `null`.
- No existe ninguna llamada `resolve(<curso>, <lección>, $revoked_user | $unenrolled_user | 0)` en el archivo.
- Tampoco se prueba un usuario matriculado solo en el curso A contra `/courses/B/lesson/B` (legítimo para B, ilegítimo para ese usuario). Solo se prueba el caso cruzado A + B de L199, que termina en 404 por relación, no por matrícula.
- L70-84: `course_a` tiene `module_a` y `module_empty`; `course_b` tiene `module_b`. No hay ningún curso sin módulos.
- `implementation-status.md` declara `AC-019: IMPLEMENTED AND INTEGRATION-TESTED` y `AC-022: IMPLEMENTED AND INTEGRATION-TESTED`, y Security Tests `PASS — anonymous, unenrolled and revoked users receive 403 …`.

Expected:

Las pruebas obligatorias existen, pasan y el estado documental refleja la evidencia real.

Actual:

Por inspección estática, el código de producción se comporta correctamente. `FrontendRouter::resolve()` evalúa `CourseAccessService::check()` (L104-108) antes de resolver la lección (L118), así que la ruta de lección devuelve 403 sin contexto. Pero ninguna prueba lo fija, y la documentación afirma lo contrario.

Impact:

La ruta de lección es la superficie principal de IDOR (RISK-001). Un refactor que mueva la resolución de la lección antes del chequeo de acceso, o que resuelva el curso desde la lección, reabriría la exposición de contenido pagado sin que falle ninguna prueba. Además, el estado `INTEGRATION-TESTED` es inexacto, y `audit-standard.md §24` lo considera un blocker válido ("task marcada Done pero no implementada", "documentación de estado falsa").

Required correction:

1. En `course-access-player.php`, asertar `403`, `template = 'access-denied'` y ausencia de `lesson`/`curriculum` en el contexto para `resolve(course_a, lesson_a_one, X)` con X ∈ {`$revoked_user`, `$unenrolled_user`, `0`}.
2. Asertar `403` para un usuario matriculado solo en A que solicita `resolve(course_b, lesson_b, …)`.
3. Agregar un curso publicado sin módulos y asertar `200`, `template = 'course'`, `modules === []`, y que `course.php` renderiza sin error.
4. Ajustar `implementation-status.md` para que las etiquetas por AC coincidan con la evidencia.

---

### CODE-002 — El registro del candidato de auditoría no está commiteado

Severity: Low
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected task:

TASK-032, TASK-033

Affected files:

- `docs/specs/004-course-access-and-player/{implementation-status,plan,spec,tasks}.md`

Description:

En `68c2561`, `implementation-status.md` dice `Status: In implementation`, `Ready for audit: No`, `Current audit round: 0`, `TASK-033: Pending` y Manual Verification `NOT RUN`. El paso a `Ready for audit: Yes` y el registro de la verificación manual existen solo como cambios sin commitear en el working tree.

Evidence:

`git diff` sobre los cuatro documentos. El árbol no documental sí coincide con `68c2561`, así que el código auditado es estable.

Impact:

Los tres auditores no tienen una referencia versionada que diga que el candidato está listo. El registro de la verificación manual puede perderse. En SPEC-003 este paso se commiteó aparte (`41ec818`).

Required correction:

Commitear el registro del candidato en un commit solo de documentación sobre `68c2561`, sin tocar código.

---

### CODE-003 — Course y Lesson se resuelven por slug global sin filtrar estado; un borrador puede ocultar contenido publicado

Severity: Low
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

AC-008, AC-009, RF-017

Affected files:

- `src/Access/Presentation/Frontend/FrontendRouter.php` (L92, L118)

Description:

`get_page_by_path($slug, 'OBJECT', <CPT>)` busca por `post_name` en todo el post type, sin filtrar `post_status` y sin `ORDER BY`, y devuelve el primer resultado. WordPress no garantiza slugs únicos para posts en `draft`/`pending`: `wp_unique_post_slug()` sale antes para esos estados. Por eso un borrador con el mismo slug que una lección publicada (por ejemplo, un duplicado creado con slug explícito) puede devolverse en su lugar. En ese caso `hierarchyForPublishedLesson()` lo rechaza y el alumno recibe un **404 legítimo-negativo** para una lección publicada y válida. Lo mismo aplica al curso.

Evidence:

Lectura de `get_page_by_path()` en core: consulta `post_name IN (...) AND post_type IN (...)` y hace `break` en el primer match del tipo pedido.

Impact:

Falso 404 en un caso borde editorial. No expone contenido.

Required correction (sugerida):

Resolver la lección dentro del curriculum ya cargado (`$curriculum`), comparando `post_name` entre las lecciones publicadas de ese curso. Eso elimina la ambigüedad, ahorra una consulta y hace intrínseca la validación de pertenencia. Para el curso, consultar con `post_status = 'publish'`.

---

### CODE-004 — Aserciones débiles o casos listados sin prueba (no obligatorios)

Severity: Low
Blocking: No
Classification: Test Gap
Status: NEW

Affected requirement:

AC-007, AC-008, AC-009, AC-011, AC-021, EC-006

Affected task:

TASK-023, TASK-024, TASK-025, TASK-028

Affected files:

- `tests/Integration/course-access-player.php`
- `tests/Unit/YouTubeEmbedRendererTest.php`
- `tests/Unit/LessonNavigationServiceTest.php`

Description / Evidence:

- AC-007: L145 solo cuenta `2` módulos, no asserta su orden (`module_a` antes que `module_empty`).
- AC-008: el `resolve()` de la lección 2 (L177) se usa para comparar la matrícula, pero su `status` no se asserta.
- AC-009: la aserción del markup (L169) no comprueba el contenido (`Protected player content.`) ni el título del módulo.
- AC-011: ninguna prueba renderiza `lesson.php` para una lección sin video (por ejemplo `lesson_a_two`).
- TASK-024 lista "sin video" y el data provider no incluye `('', '')`.
- TASK-025 / EC-006 lista "single lesson" y no hay caso con una sola lección.
- AC-021: la comparación antes/después cubre la fila de enrollment, pero se hace con `resolve()` y no con el render del template. Hoy no existe tabla de progreso en el código, así que el riesgo es nulo.

Mi arnés (§8) confirma el comportamiento correcto de single lesson, módulos vacíos intermedios y provider/source vacíos.

Impact:

Poco riesgo de regresión. La funcionalidad es correcta.

Required correction (sugerida):

Endurecer esas aserciones en la misma pasada de CODE-001.

---

### CODE-005 — UX de estados vacíos y de acceso denegado mejorable

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected files:

- `templates/frontend/access-denied.php`, `templates/frontend/course.php`, `templates/frontend/lesson.php`

Description:

- `access-denied.php` muestra el mismo mensaje para visitante, no matriculado y revocado, sin salida. `FrontendRouter::accessDenied()` pasa `reason` al contexto, pero el template no lo usa. `plan.md §17` sugiere mostrar un login al visitante: por ejemplo `wp_login_url()` con `redirect_to` a la URL actual. Esto no cambia la seguridad (el status sigue siendo 403 y no se revela contenido).
- Un curso sin módulos muestra el encabezado "Comienza tu curso" con nada debajo. El módulo vacío sí tiene mensaje. Falta un estado vacío equivalente a nivel curso.
- Los textos traducibles mezclan idiomas en el string fuente ("Tu curso", "Comienza tu curso", "Reproducir", "Contenido del curso" frente a "Course access required", "This module has no lessons yet.", "Lesson navigation"). Conviene un solo idioma fuente (inglés) y traducir con `.po`.
- `lesson.php`: los enlaces anterior/siguiente muestran solo el título, sin la etiqueta "Anterior"/"Siguiente" ni `rel="prev"`/`rel="next"`.

Impact:

Experiencia de usuario e i18n. Sin impacto en cumplimiento.

---

### CODE-006 — Ciclo de vida de rewrite rules incompleto

Severity: Low
Blocking: No
Classification: Technical Debt
Status: NEW

Affected files:

- `src/Bootstrap/Activator.php`, `src/Bootstrap/Deactivator.php`, `src/Access/PlayerModule.php`

Description:

- **Actualización sin reactivar:** una instalación con Kaanbal ya activo (SPEC-001 a SPEC-003) que recibe este código no obtiene las reglas `/courses/…` hasta reactivar o guardar enlaces permanentes. `flush_rewrite_rules()` solo se llama en `activate()` y no hay flush ligado a `Version`.
- **Desactivación:** durante la request de desactivación, el `init` del plugin ya registró las reglas, así que `flush_rewrite_rules()` las vuelve a persistir. Tras desactivar, `/courses/x` sigue reescribiendo a `index.php?kaanbal_course=x`. Es inofensivo porque el query var ya no existe y WordPress lo ignora, pero no es la limpieza que se pretende.

Impact:

Posible 404 inesperado al desplegar sobre un entorno existente. Esto es más del ámbito de Qwen (lifecycle); lo dejo registrado por su efecto funcional.

---

### CODE-007 — La validación del ID de YouTube acepta un salto de línea final

Severity: Info
Blocking: No
Classification: Future Consideration
Status: NEW

Affected files:

- `src/Courses/Video/YouTubeVideoProvider.php` (L9, código de SPEC-002)

Description:

El patrón `/^[A-Za-z0-9_-]{11}$/` sin modificador `D` acepta `"<11 chars>\n"`. En la rama `watch?v=…%0A`, `parse_str` decodifica el `\n` y pasa la validación. Verificado con `php -r`. No es explotable: `YouTubeEmbedRenderer` aplica `rawurlencode()` (queda `%0A`) y `esc_url()`, y el resultado es solo un embed roto. Se corrige con `/^[A-Za-z0-9_-]{11}\z/` o el modificador `D`.

Además, el parámetro `modestbranding=1` ya no tiene efecto en YouTube. Es inofensivo.

---

### CODE-008 — Administradores/instructores sin matrícula reciben 403

Severity: Info
Blocking: No
Classification: Future Consideration
Status: NEW

Description:

La política no contempla capacidades de WordPress. Un administrador o editor no puede previsualizar la vista de alumno sin matricularse. Es conforme a la SPEC (RB-001/RB-002 solo hablan de matrícula), pero probablemente se necesitará para QA editorial. Si se decide, debe entrar por Change Request y seguir centralizado en `CourseAccessService`.

---

## 6. Findings de Rondas Anteriores

No aplica: primera ronda de SPEC-004.

---

## 7. Recomendaciones

Las recomendaciones están registradas como findings no bloqueantes (CODE-003 a CODE-008), siguiendo el esquema de IDs de `audit-standard.md §10` y el precedente de SPEC-003. Prioridad sugerida al humano: CODE-003 y CODE-005 (UX del alumno), luego CODE-006 (despliegue).

El "Known Issue" de `implementation-status.md` (controles nativos de YouTube que permiten compartir la URL) es correcto y queda fuera de alcance (SPEC §4 excluye DRM y otros proveedores). No constituye finding.

---

## 8. Validación Ejecutada

Comandos o pruebas ejecutadas:

```text
php -l sobre src/, templates/, tests/ (PHP 8.4.21, copia de 68c2561)
arnés del auditor (sin PHPUnit) sobre CourseAccessService, LessonNavigationService y YouTubeEmbedRenderer
php -r (verificación de CODE-007)
lectura estática de todos los archivos listados en §2
```

Resultados:

```text
php -l: sin errores
arnés: ok=30 fail=0
  - active/completed → true; revoked/pending/'ACTIVE'/sin matrícula → false; user 0 y course 0 → false
  - single lesson → previous=null, next=null (EC-006)
  - módulos vacíos intermedios ignorados (EC-007)
  - provider '' / source '' / 'javascript:' / '"><script>' / host ajeno con /embed/ / provider 'YouTube' → ''
  - youtu.be, watch?v=…&t=3 y /embed/ → youtube-nocookie.com/embed/<id>
  - title con comillas y HTML escapado
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT`. No pude ejecutar `composer test`, `composer analyse`, `composer cs` ni las pruebas de integración:

- `composer install` en el entorno del auditor falla al descargar las dependencias desde `api.github.com` (sin autenticación, conexión cortada por el proxy);
- en la máquina del humano no hay `php` en la shell accesible;
- las pruebas de integración requieren WordPress + base de datos local no alcanzables.

Por eso los resultados del Quality Gate declarados en `implementation-status.md` (29 tests / 49 assertions, PHPStan, PHPCS, integración) quedan **Not verified** por este auditor. El veredicto no depende de ellos: CODE-001 se sostiene por lectura de las pruebas, no por su ejecución.

Verificación manual reportada por el humano (2026-09-29, 09:45, `Observed` — reportada, no reproducida por el auditor):

- Usuario autenticado sin matrícula, cambiando la URL a `/courses/{curso-no-matriculado}/` → "Course access required / You do not have access to this course."
- El mismo usuario, cambiando la URL a una lección de ese curso → mismo mensaje.

Esto es consistente con el análisis estático de `FrontendRouter::resolve()` (acceso evaluado antes de resolver la lección) y constituye evidencia manual válida (`audit-standard.md §21`) de que AC-004 y AC-022 se cumplen en la ruta de lección para el caso "sin matrícula". **No resuelve CODE-001**: `plan.md §25` exige pruebas automatizadas que protejan contra regresión, y siguen sin cubrirse visitante, revocado y "matriculado en otro curso". Refuerza, en cambio, que la corrección de CODE-001 es solo de pruebas y documentación.

Nota operativa: un `git status` del auditor dejó un `.git/index.lock` vacío en el repositorio del plugin, porque el entorno no permite borrar archivos. El humano debe eliminarlo (`rm .git/index.lock`) antes de la próxima operación de Git.

---

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- CODE-001 — Pruebas de seguridad obligatorias de la ruta de lección y AC-019 ausentes, declaradas como probadas

Non-blocking findings:

- CODE-002 — El registro del candidato de auditoría no está commiteado
- CODE-003 — Course y Lesson se resuelven por slug global sin filtrar estado
- CODE-004 — Aserciones débiles o casos listados sin prueba
- CODE-005 — UX de estados vacíos y de acceso denegado mejorable
- CODE-006 — Ciclo de vida de rewrite rules incompleto
- CODE-007 — La validación del ID de YouTube acepta un salto de línea final (Info)
- CODE-008 — Administradores/instructores sin matrícula reciben 403 (Info)

Comentario final:

La implementación es correcta, pequeña y respeta los boundaries de la SPEC. No encontré defectos de comportamiento que expongan contenido. El `FAIL` responde a la regla de evidencia: las pruebas que `plan.md` declara obligatorias para la superficie de IDOR de lecciones no existen, y el estado documental afirma lo contrario. Con CODE-001 resuelto (pruebas nuevas, sin cambiar código de producción) y CODE-002 commiteado, espero que la ronda 2 cierre en `PASS WITH RECOMMENDATIONS`.
