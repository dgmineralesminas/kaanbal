# Auditoría de Seguridad

SPEC: `008-course-administration-and-reporting`
Ronda: `3`
Auditor: Mimo
Commit auditado: `213ef01`
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `213ef01` es el commit de implementación registrado en `implementation-status.md` para la ronda 3. Verificado que el árbol de trabajo es idéntico a `213ef01` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; el HEAD (`cfd8a72`) solo añade documentación.
- Primera auditoría de seguridad de SPEC-008. Los identificadores SEC inician en `SEC-001`.
- Auditoría realizada de forma independiente: los informes de otros auditores en `audits/round-1/`, `round-2/` y `round-3/` no fueron consultados.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-008 expone en wp-admin un reporte con datos privados de alumnos (nombre, email, progreso, estado de quiz). Los riesgos son autorización, exposición de datos personales, inyección SQL vía búsqueda/filtros y XSS por datos reflejados. La implementación los cubre correctamente:

- acceso restringido con `manage_options` en `add_submenu_page` y re-verificado dentro de `render()` (`wp_die(403)`);
- búsqueda SQL con `esc_like()` + `prepare()` (probado con entrada tipo SQL: resultados literales, sin error ni filtración);
- filtros `status`/`quiz` con whitelist estricta; `course_id`/`paged` con `absint`; `is_scalar` + `sanitize_text_field` en todo `$_GET`;
- emails visibles solo tras la comprobación de capability;
- salida uniformemente escapada (`esc_html`/`esc_attr`/`esc_url`) y URLs de paginación con `rawurlencode` + `add_query_arg` + `esc_url` (el término de búsqueda sobrevive `+` y se codifica);
- reporte estrictamente read-only (probado comparando estado antes/después).

No se encontraron vulnerabilidades explotables. El único finding es un vacío de prueba sobre la regresión del escaping de salida.

Blocking findings: `0`
Non-blocking findings: `1`

---

## 2. Superficies Revisadas

- autorización / capabilities: revisada — `manage_options` en menú y en `render()`, anónimo y suscriptor
- privacidad de datos: revisada — emails/nombres solo para usuarios autorizados
- SQL injection: revisada — búsqueda LIKE, filtros, IDs, joins, paginación
- XSS / escaping: revisado — nombres, emails, términos de búsqueda reflejados, URLs de paginación, fechas
- validación de entrada: revisada — `is_scalar`, `sanitize_text_field`, `absint`, whitelists
- CSRF: N/A — el reporte es read-only (filtros GET sin escritura)
- manipulación de datos: revisada — el reporte no escribe estado académico
- IDOR de curso: revisada — `course_id` inexistente o no publicado → error seguro

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authorization | `PASS` | `CourseReportsPage::CAPABILITY = 'manage_options'` en `add_submenu_page` y `canAccess()` re-verificado en `render()` con `wp_die(403)` (`src/Reporting/Presentation/Admin/CourseReportsPage.php:10`, `:37-45`). Probado: suscriptor y visitante sin acceso, administrador con acceso (`tests/Integration/course-administration-reporting.php:193-203`). La entrada real de wp-admin aplica además el check de capability del menú de core. |
| Privacy / Data Exposure | `PASS` | Los emails solo se renderizan dentro de `renderDetail()`, alcanzable únicamente tras `canAccess()` (RNF-004). El resumen contiene solo agregados. Usuarios eliminados muestran nombre genérico y email `—` (`CourseReportingQuery.php:127-135`). Verificado estáticamente. |
| SQL Injection | `PASS` | Búsqueda: `'%' . esc_like($search) . '%'` con `%s` preparado (`src/Enrollment/Infrastructure/EnrollmentRepository.php:212-217`); filtros `status`/`quiz` por whitelist `in_array(..., true)` (`CourseReportingQuery.php:87-88`); IDs con `absint`; subquery del quiz con `%d` y orden de argumentos verificado; `LIMIT/OFFSET` con `%d` (`EnrollmentRepository.php:232-234`); agregados con placeholders `%d` por conteo. Prueba con entrada tipo SQL `"' OR 1=1 --"`: 0 resultados, sin error (`course-administration-reporting.php:256-259`). |
| XSS | `PASS` (control verificado, ver SEC-001) | Todo el output usa `esc_html`/`esc_attr`/`esc_url` (`CourseReportsPage.php`); el término de búsqueda reflejado pasa por `esc_attr($filters['search'])` (`:141`); URLs de paginación con `rawurlencode` sobre cada parámetro + `add_query_arg` + `esc_url` (`:199-205`), probado con búsqueda `a+b-page` → `search=a%2Bb-page` (`:274-284`). `formatDate()` con `wp_date` + `esc_html` (`:214-222`). Verificado estáticamente; sin prueba de regresión (SEC-001). |
| Input Validation | `PASS` | `requestValue()` exige `is_scalar` y aplica `sanitize_text_field(wp_unslash())` (`CourseReportsPage.php:225-233`) — un `search[]=x` no llega a la consulta. `course_id`/`paged` con `absint`; filtros inválidos degradan a `'all'` (probado, `course-administration-reporting.php:256-259`). |
| Read Only | `PASS` | `ReportingModule` solo registra `admin_menu`; `render()` no ejecuta escrituras; el formulario de filtros es GET. Probado: matrículas, progreso e intentos idénticos antes y después de abrir el reporte (`course-administration-reporting.php:290-297`, AC-023). |
| CSRF | `N/A` | No hay operaciones de escritura; los filtros GET no cambian estado. |
| IDOR de curso | `PASS` | `detail()` exige curso existente, tipo `kaanbal_course` y `publish` (`CourseReportingQuery.php:79-83`); un `course_id` inválido produce mensaje de error sin datos (`CourseReportsPage.php:87-92`, probado con `999999999`). |

---

## 4. Findings

### SEC-001 — El escaping de salida del reporte no tiene prueba de regresión

Severity: `Low`
Blocking: `No`
Classification: `Test Gap`
Status: `NEW`

Affected requirement:

`RNF-007` (escaping), `AC-024`, `plan.md` §26 (output escaping como control a validar)

Affected files:

- `tests/Integration/course-administration-reporting.php`
- `src/Reporting/Presentation/Admin/CourseReportsPage.php`

Attack surface:

`GET /wp-admin/edit.php?post_type=kaanbal_course&page=kaanbal-course-reports` (campo `search` reflejado; nombres y emails de alumnos renderizados)

Description:

El escaping está implementado y es correcto, pero ninguna prueba fallaría si desaparece: el fixture no usa datos hostiles (nombres, emails o términos de búsqueda con HTML) y no hay aserciones de entidades escapadas.

Evidence:

Verificado por inspección que el escaping es uniforme: `search` se refleja con `esc_attr` (`CourseReportsPage.php:141`), `name`/`email`/etiquetas con `esc_html` (`:114-122`), URLs con `esc_url` tras `rawurlencode` (`:199-205`). Sin embargo:

- los usuarios del fixture tienen nombres y emails benignos (`course-administration-reporting.php:44-55`, `:173`);
- la única aserción de marcado relacionada comprueba la codificación de URL en paginación (`:283`), no el escaping HTML;
- no existe ningún caso que renderice un `display_name`, un email o un término de búsqueda como `"><script>…` y afirme que aparece como texto escapado.

Verificado por razonamiento estático de mutación (la mutación no fue ejecutada): si `renderDetail()` imprimiera `$filters['search']` o `$student['name']` sin escapar, la suite actual seguiría en verde y un usuario con `manage_options` podría ejecutar script en wp-admin (o un término de búsqueda en un enlace compartido podría hacerlo), afectando a otros administradores que abran el reporte.

Attack scenario:

Una regresión retira el escaping de un campo reflejado; un alumno con un `display_name` hostil (editable por el propio usuario en su perfil de WordPress) o un término de búsqueda manipulado ejecuta JavaScript en la sesión de un administrador que consulta el reporte.

Expected protection:

Pruebas de regresión que rendericen datos hostiles (nombre/email del alumno y término de búsqueda) y afirmen que el marcado contiene entidades escapadas, sin etiquetas HTML activas.

Actual behavior:

Control implementado y verificado estáticamente; sin cobertura de regresión que lo asegure.

Impact:

Sin impacto sobre la implementación actual. Riesgo de XSS almacenado/reflejado en wp-admin ante una regresión futura del template del reporte.

Required correction:

Añadir en `course-administration-reporting.php` un caso que cree un alumno con `display_name`/email que contengan HTML (p. ej. `<img src=x onerror=alert(1)>`) y una búsqueda con `<script>`, renderice `render()` y afirme que el marcado contiene las entidades escapadas y no las etiquetas crudas. No requiere modificar código de producción.

---

## 5. Findings de Rondas Anteriores

Ningún finding SEC previo (primera auditoría de seguridad de SPEC-008).

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — Capability Kaanbal dedicada cuando exista infraestructura de capabilities

Severity: `Info`
Blocking: No
Classification: Future Consideration

Description:

El reporte usa `manage_options` (`CourseReportsPage::CAPABILITY`), que limita el acceso a administradores. `plan.md` §27 pide evitar hardcodear exclusivamente `manage_options` si existe una opción más adecuada y preferir una capability Kaanbal específica.

Evidence:

Verificado que hoy no existe infraestructura de capabilities propias en el plugin (sin roles ni caps personalizados), por lo que `manage_options` es la opción adecuada y no hay defecto. Si en el futuro se necesita dar acceso al reporte a un rol de soporte o instructor sin otorgar `manage_options`, convendría registrar una capability tipo `kaanbal_view_course_reports` mapeada a los roles autorizados.

Benefit:

Principio de mínimo privilegio y separación entre administración del sitio y consulta de reportes académicos.

Scope note:

La recomendación no representa un requisito de la SPEC actual; requiere decisión humana si se amplía el alcance.

---

## 7. Limitaciones

- Ejecución dinámica del fixture de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/course-administration-reporting.php` requiere `KAANBAL_WP_PATH` con un WordPress vivo; no se ejecutó durante esta auditoría. Los tests fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: la prueba de entrada tipo SQL y la de paginación afirman resultados concretos, no solo ausencia de errores).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (89 tests, 122 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest); autorización, SQLi, XSS y privacidad se verificaron por inspección estática del código y de las pruebas.
- La prueba de autorización cubre `canAccess()` (el guard exacto que usa `render()`); no existe un caso que invoque `render()` como suscriptor y afirme el `wp_die(403)` — el riesgo residual es bajo porque `canAccess()` es la misma función y el menú de wp-admin aplica su propio check de capability.

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- `SEC-001` — El escaping de salida del reporte no tiene prueba de regresión.

Recommendations:

- `SEC-REC-001` — Capability Kaanbal dedicada cuando exista infraestructura de capabilities.

Comentario final:

SPEC-008 protege adecuadamente un reporte con datos personales: acceso con capability administrativa verificado en dos capas, búsqueda y filtros parametrizados y whitelistados (con prueba explícita de entrada tipo SQL), datos de alumnos visibles solo para autorizados, salida escapada y operación estrictamente read-only. Los seis casos de seguridad obligatorios del plan tienen cobertura significativa. El único finding es la ausencia de una prueba que fije el escaping HTML ante datos hostiles; la recomendación de capability dedicada es una mejora futura alineada con el propio plan.
