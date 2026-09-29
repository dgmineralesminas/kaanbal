# Auditoría de Seguridad

SPEC: `005-student-progress`
Ronda: `1`
Auditor: Mimo
Commit auditado: `6136722`
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `6136722` es el commit candidato registrado en `implementation-status.md`. Verificado que el árbol de trabajo es idéntico a `6136722` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; los commits posteriores (`08e3ce6`, `b863db4`) solo añaden documentación.
- Primera auditoría de seguridad de SPEC-005. Los identificadores SEC inician en `SEC-001`.
- Auditoría realizada de forma independiente: los informes de otros auditores presentes en `audits/round-1/` no fueron consultados.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-005 introduce la primera superficie de escritura del lado del alumno: un endpoint `admin-post` que registra completions de lecciones. Es una superficie de alto valor (falsificación de progreso académico), y la implementación la protege correctamente:

- identidad del actor tomada exclusivamente de la sesión (`get_current_user_id()`), sin parámetro de request;
- CSRF con nonce atado al ID de la lección concreta (`kaanbal_complete_lesson_{lesson_id}`);
- autorización server-side centralizada vía `CourseAccessService` (SPEC-004);
- validación de jerarquía Lesson→Module→Course contra el `course_id` declarado (anti-IDOR);
- SQL preparado y `UNIQUE(user_id, lesson_id)` con `INSERT IGNORE` (idempotencia real);
- fallo uniforme (mensaje único, `403`) sin oráculo entre clases de error;
- las vistas siguen siendo read-only y el progreso solo se calcula tras autorizar.

No se encontró vulnerabilidad explotable dentro del alcance. El único finding es un vacío de prueba sobre un caso obligatorio del plan.

Blocking findings: `0`
Non-blocking findings: `1`

---

## 2. Superficies Revisadas

- autenticación: revisada — gate `is_user_logged_in()` en el endpoint (incluye `admin_post_nopriv_`)
- autorización / ownership / IDOR: revisadas — actor desde sesión, política de acceso, jerarquía curricular, inyección de `user_id`
- CSRF / nonces: revisados — nonce por lección, verificación server-side, cobertura de tests
- XSS / escaping: revisados — templates de Course/Lesson, formulario, porcentajes
- SQL injection / prepared statements: revisados — `LessonProgressRepository`
- manipulación de reglas de negocio: revisada — idempotencia, curriculum dinámico, sin auto-completion de curso, sin des-hacer completion
- exposición de datos: revisada — progreso solo en contextos autorizados; mensajes de error genéricos
- redirecciones: revisada — `wp_safe_redirect` + `wp_get_referer`
- esquema/retención: revisada — migración de `kaanbal_lesson_progress`, completions huérfanas no cuentan
- CSRF sobre GET / métodos alternativos: N/A — el handler lee solo `$_POST`

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authentication | `PASS` | `CompleteLessonAction::handle()` exige `is_user_logged_in()` antes de cualquier otra operación (`src/Progress/Presentation/Frontend/CompleteLessonAction.php:21-23`); las peticiones anónimas llegan por `admin_post_nopriv_` (`src/Progress/ProgressModule.php:27-28`) y terminan en el mismo `wp_die(403)`. Verificado estáticamente y en fixture (`tests/Integration/student-progress.php:178-181`, usuario `0` → rechazo). |
| CSRF | `PASS` | `check_admin_referer('kaanbal_complete_lesson_' . $lesson_id, '_kaanbal_nonce')` (`CompleteLessonAction.php:25`) y `wp_nonce_field` equivalente en `templates/frontend/lesson.php`. El nonce está atado a la lección: un nonce de la lección A no sirve para la B. Tests que aíslan el control: usuario autenticado con nonce ausente → 403 y con nonce inválido → 403 (`student-progress.php:178-187`); retirar `check_admin_referer` rompería la suite. Verificado estáticamente y por inspección de tests. |
| Authorization / Ownership | `PASS` | El actor es siempre `get_current_user_id()` (`CompleteLessonAction.php:27`); ningún campo del request se usa como `user_id` (verificado por inspección: el handler solo lee `lesson_id` y `course_id`). `CompleteLessonService::complete()` valida lección publicada, jerarquía (`$course_id !== $hierarchy['course']->ID` → `CourseMismatch`) y acceso vía `CourseAccessService` (`src/Progress/Application/CompleteLessonService.php:20-37`). Rechazos cubiertos: sin matrícula, matrícula revocada, curso/lección cruzados, lección inexistente (`student-progress.php:88-121`, `:178-187`). |
| IDOR | `PASS` | Un usuario con acceso al Curso A no puede registrar progreso sobre lección del Curso B ni sobre lecciones de otro usuario: la identidad no es manipulable y la pertenencia Lesson→Course se verifica server-side (AC-005, AC-006, RNF-003). Verificado estáticamente y en fixture (`student-progress.php:111-113`, `:185`). |
| SQL Injection | `PASS` | `INSERT IGNORE` y `SELECT ... IN (...)` usan `$wpdb->prepare` con `%d`/`%s`; los IDs de entrada pasan por `absint` y los placeholders se generan por conteo (`src/Progress/Infrastructure/LessonProgressRepository.php:24-51`). Sin concatenación de input en SQL. Verificado estáticamente. |
| XSS | `PASS` | `course.php`/`lesson.php` escapan todo output nuevo: porcentaje entero en `esc_html`/`esc_attr` (`aria-valuenow`, `width`), campos del formulario con `esc_attr`, textos i18n estáticos. El parámetro `kaanbal_progress` del redirect no se refleja en ninguna plantilla (verificado por búsqueda). Verificado estáticamente. |
| Business Rule Manipulation | `PASS` | Escritura idempotente real: `INSERT IGNORE` + `UNIQUE(user_id, lesson_id)` (`EnrollmentSchemaMigration.php`, `user_lesson`), duplicados probados vía endpoint (`student-progress.php:168-176`). Abrir una lección no escribe progreso (`:84-87`); 100% no completa el enrollment (`:134-136`); completions fuera del curriculum no cuentan (`:125-129`); el porcentaje se deriva, no se almacena. |
| Data Exposure | `PASS` | El progreso solo se añade al contexto en respuestas `200` autorizadas (`src/Access/Presentation/Frontend/FrontendRouter.php:117-121`, `:145-148`); los contextos `403`/`404` no contienen progreso ni curriculum. Todos los fallos del endpoint usan un único mensaje genérico con `403` (`CompleteLessonAction.php:22`, `:30-32`) — sin oráculo entre lección inválida, mismatch y acceso denegado. |
| Open Redirect | `PASS` | `wp_safe_redirect(add_query_arg(...))` sobre `wp_get_referer()` (`CompleteLessonAction.php:34-41`); el valor del query arg es un case del enum, no input del usuario. Verificado estáticamente. |

---

## 4. Findings

### SEC-001 — Falta el caso de prueba obligatorio `user A tries user B` sobre el endpoint (AC-006)

Severity: `Low`
Blocking: `No`
Classification: `Test Gap`
Status: `NEW`

Affected requirement:

`AC-006`, `RNF-003`, `plan.md` §31 (Security Tests obligatorios: "user A tries user B"), TASK-027

Affected files:

- `tests/Integration/student-progress.php`
- `tests/Integration/support/progress-endpoint-request.php`
- `src/Progress/Presentation/Frontend/CompleteLessonAction.php`

Attack surface:

`POST /wp-admin/admin-post.php` — `action=kaanbal_complete_lesson`

Description:

La propiedad de ownership (el request no puede fijar el usuario sobre el que se escribe) está implementada y es correcta, pero ningún test la asegura: si una regresión empezara a leer un `user_id` del request, la suite actual seguiría en verde.

Evidence:

Verificado por inspección que hoy la propiedad se cumple estructuralmente: `CompleteLessonAction::handle()` solo lee `lesson_id` y `course_id` (`CompleteLessonAction.php:17-18`) y toma el actor de `get_current_user_id()` (`:27`); `CompleteLessonService` recibe ese `user_id` como parámetro del servidor. Sin embargo:

- `tests/Integration/support/progress-endpoint-request.php` construye `$_POST` únicamente con `action`, `course_id` y `lesson_id` — nunca inyecta un campo `user_id`;
- la lista de casos de TASK-027 (success, duplicate, anonymous, CSRF, no access, mismatched course, invalid lesson) no incluye "diferente usuario", aunque `plan.md` §31 lo marca como obligatorio;
- los tests de servicio ejercitan usuarios distintos (`$active`, `$other`, `$revoked`), pero pasando el `user_id` por parámetro de código, no mediante manipulación del request.

Verificado por razonamiento estático de mutación (la mutación no fue ejecutada): si el handler añadiera `$user_id = absint($_POST['user_id'] ?? 0)` y lo propagara al servicio, ningún test fallaría.

Attack scenario:

Una regresión futura acepta `user_id` del request; un alumno autenticado envía el `user_id` de otra cuenta junto a un nonce válido de su propia sesión y registra completions a nombre de ese usuario (falsificación de progreso académico de terceros).

Expected protection:

Prueba de regresión que fije que un request que incluye `user_id` de otro usuario no escribe filas para ese usuario y solo registra la completion del actor autenticado.

Actual behavior:

Propiedad implementada y verificada estáticamente; sin cobertura de regresión que la asegure.

Impact:

Sin impacto sobre la implementación actual. Riesgo de falsificación de progreso de terceros ante una regresión futura del endpoint.

Required correction:

Añadir en `tests/Integration/student-progress.php` un caso que envíe al endpoint, con nonce válido del usuario A, un payload que incluya `user_id` del usuario B (por ejemplo `--extra-user` soportado en `progress-endpoint-request.php`), y afirme 0 filas en `kaanbal_lesson_progress` para B y 1 para A. No requiere modificar código de producción.

---

## 5. Findings de Rondas Anteriores

Ninguno. Primera ronda de auditoría de seguridad de SPEC-005.

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — Protección clickjacking para las páginas del player con formulario de completar

Severity: `Info`
Blocking: No
Classification: Recommendation

Description:

La Lesson View renderiza el formulario `Marcar como completada` (`templates/frontend/lesson.php`). Un origen externo podría embeber esa página en un iframe e inducir a un usuario autenticado a pulsar el botón. La protección CSRF no aplica en este escenario porque el nonce viaja dentro de la propia página enmarcada, y WordPress no envía `X-Frame-Options` ni CSP `frame-ancestors` por defecto en el frontend.

Evidence:

Verificado estáticamente: no hay cabeceras anti-framing en el módulo (`PlayerModule`, `FrontendRouter` solo emiten `status_header`/`nocache_headers`). Impacto acotado: la víctima marcaría una lección como completada sin intención y el MVP no permite deshacer completions (DEC-007), pero no hay escalada de privilegios ni acceso a contenido ajeno.

Benefit:

Emitir `X-Frame-Options: SAMEORIGIN` (o CSP `frame-ancestors 'self'`) en las rutas del player eliminaría esta superficie.

Scope note:

La recomendación no representa un requisito de la SPEC actual.

---

## 7. Limitaciones

- Ejecución dinámica de fixtures de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/student-progress.php` requiere `KAANBAL_WP_PATH` con un WordPress vivo y ejecuta el endpoint en subprocesos; no se ejecutó durante esta auditoría. Los tests fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: los casos de rechazo exigen código de salida de rechazo; no son placeholders; el nonce ausente/inválido se prueba con usuario autenticado, por lo que el control CSRF está aislado).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (37 tests, 57 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest) contra un sitio en vivo; CSRF, ownership, IDOR, SQL y escaping se verificaron por inspección estática del código y de las pruebas.
- Análisis de mutación referido en SEC-001 es razonamiento estático; la mutación no fue ejecutada.
- Rama adicional sin test observada (no finding): `CompleteLessonService` rechaza lecciones no publicadas (`InvalidLesson`), pero todas las fixtures son `publish`; el impacto de retirar ese control sería menor porque las lecciones no publicadas nunca cuentan para el porcentaje (completions huérfanas se ignoran).

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- `SEC-001` — Falta el caso de prueba obligatorio `user A tries user B` sobre el endpoint (AC-006 / plan §31).

Recommendations:

- `SEC-REC-001` — Protección clickjacking para las páginas del player con formulario de completar.

Comentario final:

SPEC-005 protege correctamente su superficie de escritura: identidad de sesión no manipulable, nonce CSRF atado a la lección, autorización centralizada, validación de jerarquía anti-IDOR, SQL preparado, idempotencia por constraint y respuestas de error uniformes. El control CSRF está además aislado por tests, algo que faltaba en superficies anteriores. El único finding es la ausencia del test obligatorio de inyección de `user_id` (no bloqueante: la propiedad está garantizada estructuralmente y verificada por inspección). La recomendación de clickjacking es hardening opcional sin defecto actual.
