# Auditoría de Seguridad

SPEC: `004-course-access-and-player`
Ronda: `2`
Auditor: Mimo
Commit auditado: `7ab80cd`
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `7ab80cd` es el commit candidato registrado en `implementation-status.md`. Verificado que el árbol de trabajo es idéntico a `7ab80cd` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; el HEAD (`84fc770`) solo añade documentación.
- Primera auditoría de seguridad de SPEC-004. La ronda 1 no contiene `security-audit.md` porque Mimo no fue invocado en ella; los identificadores SEC inician aquí en `SEC-001`.
- Auditoría realizada de forma independiente, sin consultar los informes de otros auditores de esta SPEC.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-004 es la superficie más sensible del proyecto: expone rutas públicas (`/courses/{course-slug}/`, `/courses/{course-slug}/lesson/{lesson-slug}/`) que sirven contenido pagado. La implementación cumple el requisito central (RNF-001, AC-022):

- la política de acceso está centralizada en `CourseAccessService` y se aplica en servidor antes de resolver o entregar contenido;
- la autorización precede a la resolución de la lección, por lo que un usuario sin acceso no puede sondear la existencia de lecciones;
- la validación de jerarquía Course/Lesson bloquea el IDOR cruzado con `404`;
- el embed de YouTube se construye solo desde un ID normalizado y escapado;
- las vistas son estrictamente read-only (sin escrituras de progreso ni de matrícula).

No se encontró vulnerabilidad explotable dentro del alcance. El único finding es un vacío de pruebas de regresión sobre un control de acceso ya implementado.

Blocking findings: `0`
Non-blocking findings: `1`

---

## 2. Superficies Revisadas

- autorización: revisada — política única server-side (active/completed/revoked/ausente/desconocido)
- ownership / IDOR: revisada — pertenencia Lesson→Module→Course y combinaciones cruzadas
- autenticación: revisada — usuario anónimo vs autenticado en rutas públicas
- CSRF: N/A — la SPEC es estrictamente read-only
- XSS / escaping / output: revisados — templates, metadata, embed, contenido
- sanitización: revisada — query vars y slugs
- SQL injection: revisada — sin SQL directo en el módulo Access
- exposición de datos: revisada — respuestas 403/404, contexto de plantilla, headers de caché
- manipulación de reglas de negocio: revisada — abrir curso/lección no altera estado académico
- accesos directos a archivos: revisada — guardas `ABSPATH` en templates
- integración WooCommerce: N/A — el player no consulta WooCommerce (DEC-004)

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authorization | `PASS` | Política única en `CourseAccessService::check()` (`src/Access/Application/CourseAccessService.php:22-44`): `user_id <= 0` → NotAuthenticated; sin matrícula o status desconocido → NotEnrolled (fail-closed); `active`/`completed` → Granted; `revoked` → Revoked. Aplicada en `FrontendRouter::resolve()` antes de resolver lección (`src/Access/Presentation/Frontend/FrontendRouter.php:90-96`). Cobertura de pruebas significativa (`tests/Unit/CourseAccessServiceTest.php`, `tests/Integration/course-access-player.php:242-257`). |
| IDOR | `PASS` | La lección solo se resuelve con acceso ya concedido y se valida la jerarquía: `hierarchyForPublishedLesson()` exige lección→módulo→curso y `$course->ID !== $hierarchy['course']->ID` → `404` (`FrontendRouter.php:105-112`, AC-016). Verificado en fixture (`course-access-player.php:259-265`): curso A + lección de curso B → 404; matrícula en A contra curso B → 403. |
| XSS | `PASS` | Todo output dinámico usa `esc_html`/`esc_attr`/`esc_url` (`templates/frontend/course.php`, `lesson.php`); el iframe se construye solo desde ID normalizado (`^[A-Za-z0-9_-]{11}$`) con `rawurlencode` + `esc_url` + `esc_attr` (`src/Access/Application/YouTubeEmbedRenderer.php:17-25`); `wp_kses_post` envuelve `the_content`. Tests unitarios aseguran escaping del título y ausencia de `<script` y de fuentes arbitrarias (`tests/Unit/YouTubeEmbedRendererTest.php`). |
| SQL Injection | `PASS` | El módulo Access no ejecuta SQL directo: usa `get_post`/`get_posts`/`get_page_by_path` (core) y `EnrollmentRepository::findByUserAndCourse` preparado. Los slugs llegan por query vars y solo se pasan como parámetros de búsqueda de posts. Verificado estáticamente. |
| Data Exposure | `PASS` | `access-denied.php`/`not-found.php` renderizan solo strings estáticos (sin título ni contenido); el fixture afirma que el contexto 403 no contiene `curriculum`/`lesson`/`course` (`course-access-player.php:246-254`). `nocache_headers()` en 403/404 (`FrontendRouter.php:66-78`); para respuestas 200, WP core emite `wp_get_nocache_headers()` cuando `is_user_logged_in()` (`wp-includes/class-wp.php:443-444`), y todo usuario con acceso está autenticado. Verificado leyendo core local. |
| Business Rule Manipulation | `PASS` | Las rutas son read-only: el fixture compara la fila de matrícula antes/después de abrir curso y lección y exige igualdad (`course-access-player.php:224-237`, AC-021). No hay escritura de progreso en esta SPEC. |
| Unpublished Content | `PASS` (control presente, ver SEC-001) | `get_page_by_path()` de core NO filtra `post_status` (`wp-includes/post.php:6273-6315`, verificado); la puerta real es `forPublishedCourse()`/`hierarchyForPublishedLesson()`, que exigen `post_status === 'publish'` en curso, módulo y lección (`src/Courses/Application/CurriculumService.php:51-53,79-81`; `CurriculumRepository::relatedPost`). Contenido draft/private/trashed responde 404 incluso para matriculados. |

## 4. Findings

### SEC-001 — La exclusión de contenido no publicado en las rutas del player no tiene pruebas de regresión

Severity: `Low`
Blocking: `No`
Classification: `Test Gap`
Status: `NEW`

Affected requirement:

`plan.md` §5 y §6 (validar estado de publicación al resolver Course/Lesson), `EC-002`, `EC-004`, `RNF-001`

Affected files:

- `src/Courses/Application/CurriculumService.php`
- `src/Access/Presentation/Frontend/FrontendRouter.php`
- `tests/Integration/course-access-player.php`

Attack surface:

`GET /courses/{course-slug}/` y `GET /courses/{course-slug}/lesson/{lesson-slug}/` (plantillas `course` / `lesson`)

Description:

El control que impide servir contenido no publicado está implementado y es correcto, pero ninguna prueba automatizada fallaría si se elimina o se relaja. Toda la cobertura actual trabaja exclusivamente con contenido `publish`.

Evidence:

La puerta real no es la resolución por slug: `get_page_by_path()` de WP core no filtra `post_status` (verificado en `wp-includes/post.php:6273-6315`) y puede devolver drafts, `private` o `trash`. El control es `CurriculumService::forPublishedCourse()` / `hierarchyForPublishedLesson()`, que devuelven `null` salvo `post_status === 'publish'` en curso, módulo y lección (`CurriculumService.php:51-53` y `:79-81`; `CurriculumRepository::relatedPost`), y `FrontendRouter::resolve()` traduce ese `null` a `404` (`FrontendRouter.php:84-87`, `:105-112`). Verificado estáticamente que hoy un curso/lección draft, `private` o en papelera responde 404 incluso para un usuario con matrícula `active`.

Sin embargo:

- todas las fixtures de `tests/Integration/course-access-player.php` se crean con `post_status => 'publish'` (línea 35);
- `tests/Integration/courses-curriculum.php` solo ejercita las variantes `'any'` (`hierarchyForLesson`, línea 55), no las `Published`;
- no existe ningún caso que cree curso o lección no publicados y afirme `404`.

Verificado por inspección: si un refactor futuro hiciera que el camino del player usara `forCourse()`/`hierarchyForLesson()` (variante `'any'`), la suite actual permanecería en verde y el material no publicado (lecciones en draft, cursos `private`, contenido en papelera) sería legible para cualquier alumno matriculado. Como nota secundaria, la rama fail-called `default => NotEnrolled` de `CourseAccessService::check()` para statuses de matrícula desconocidos tampoco tiene caso de test.

Attack scenario:

Una regresión retira la verificación de publicación; un alumno matriculado solicita la URL de una lección draft (material aún no liberado, evaluaciones preparadas, curso en estado `private`) y obtiene su contenido.

Expected protection:

Pruebas de regresión que fijen que contenido no publicado responde `404` incluso para usuarios con matrícula activa, y que un status de matrícula desconocido deniega el acceso.

Actual behavior:

Control implementado y funcional (verificado estáticamente); sin cobertura de regresión que lo asegure.

Impact:

Sin impacto sobre la implementación actual. Riesgo de exposición silenciosa de material no publicado ante una regresión futura del camino de resolución.

Required correction:

Añadir en `tests/Integration/course-access-player.php` un caso con curso y/o lección en `draft` (y opcionalmente `trash` o `private`) con matrícula `active`, afirmando `404` y template `not-found`. Opcionalmente, un caso unitario para el `default` fail-closed de `CourseAccessService::check()`. No requiere modificar código de producción.

---

## 5. Findings de Rondas Anteriores

Ninguno. Primera ronda de auditoría de seguridad de SPEC-004.

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — Oráculo de enumeración de cursos por diferencia 403/404

Severity: `Info`
Blocking: No
Classification: Future Consideration

Description:

Un usuario sin acceso puede distinguir si un slug de curso existe: curso existente responde `403` y curso inexistente responde `404`. La existencia de lecciones NO se filtra (la autorización se evalúa antes de resolver la lección y toda petición sin acceso responde `403`), pero los slugs de cursos sí son enumerables por diferencia de estado.

Evidence:

`FrontendRouter::resolve()` devuelve `notFound()` (404) cuando `get_page_by_path` no encuentra el curso (`FrontendRouter.php:80-83`) y `accessDenied()` (403) cuando el curso existe pero no hay acceso (`:90-96`). Verificado estáticamente; no hay prueba que lo afirme ni contradiga.

Scope note:

Este comportamiento sigue la decisión aprobada en `spec.md` §12 (403 para acceso denegado, 404 para contenido inexistente) y no constituye una vulnerabilidad dentro del alcance actual. Si la enumeración de catálogo llegara a ser una preocupación, una opción futura sería uniformizar la respuesta para usuarios sin acceso. Decisión humana requerida para cualquier cambio.

---

## 7. Limitaciones

- Ejecución dinámica del fixture de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/course-access-player.php` requiere `KAANBAL_WP_PATH` con un WordPress vivo; no se ejecutó durante esta auditoría. Los tests fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: los casos de rechazo lanzan excepción si el control desaparece; no son placeholders).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (31 tests, 51 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest) contra un sitio en vivo; autorización, IDOR, XSS y escaping se verificaron por inspección estática del código, de las pruebas y del comportamiento de WP core relevante (`get_page_by_path`, `WP::send_headers`).
- Comportamiento observado y fuera de alcance (ya registrado como Known Issue por la implementación): el iframe de YouTube puede exponer enlaces/controles nativos de compartir; impedirlo requiere otro proveedor y queda fuera de la SPEC YouTube-only.

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- `SEC-001` — La exclusión de contenido no publicado en las rutas del player no tiene pruebas de regresión.

Recommendations:

- `SEC-REC-001` — Oráculo de enumeración de cursos por 403/404 (consistente con la decisión aprobada; futura consideración).

Comentario final:

La implementación de SPEC-004 satisface su requisito de seguridad central: el acceso se decide en servidor, en un único servicio, antes de exponer cualquier contenido; el IDOR cruzado se rechaza con 404; los usuarios sin acceso reciben 403 sin rastro del contenido ni de su existencia curricular; el embed es construido y escapado desde un ID normalizado; y las vistas no escriben estado. El único finding es un vacío de pruebas de regresión sobre la exclusión de contenido no publicado (no bloqueante): el control está implementado y verificado estáticamente, pero ninguna prueba impediría que una regresión lo retire.
