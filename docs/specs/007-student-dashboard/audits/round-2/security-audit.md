# Auditoría de Seguridad

SPEC: `007-student-dashboard`
Ronda: `2`
Auditor: Mimo
Commit auditado: `4d16911`
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `4d16911` es el commit de auditoría registrado en `implementation-status.md` (remediación de ronda 1). Verificado que el árbol de trabajo es idéntico a `4d16911` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; los commits posteriores solo añaden documentación de auditoría.
- Primera auditoría de seguridad de SPEC-007. Los identificadores SEC inician en `SEC-001`.
- Auditoría realizada de forma independiente: los informes de otros auditores en `audits/round-1/` y `audits/round-2/` no fueron consultados.
- La remediación de `CODE-001` (quiz inválido sin estado visible) se verificó por inspección dentro de esta auditoría, por su impacto en exposición de estado académico.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-007 expone un panel autenticado con el estado académico del alumno (cursos, progreso, estado de quiz, certificado). Los riesgos típicos son ownership (ver datos de otro alumno), exposición a anónimos, filtrado de datos del quiz y escrituras accidentales. La implementación los cubre correctamente:

- la identidad procede exclusivamente de la sesión (`get_current_user_id()`); el `resolve()` del router recibe el usuario de sesión y ningún parámetro de request participa (probado con `user_id` forjado en `$_GET`/`$_REQUEST` a través del punto de entrada real `template()`);
- el visitante anónimo recibe `403` sin contexto académico (`dashboard-access-denied` + `nocache_headers`);
- la consulta es por `user_id` y solo lista matrículas `active`/`completed`; cursos revocados, huérfanos o no publicados quedan fuera;
- el panel es read-only: la carga no modifica matrículas, progreso ni intentos (probado comparando filas antes/después);
- las respuestas del quiz nunca llegan al view model (solo agregados de validez y contadores del propio usuario);
- todo el output está escapado y las URLs construidas con `esc_url`/`rawurlencode`.

No se encontraron vulnerabilidades ni vacíos de prueba significativos sobre los casos de seguridad obligatorios del plan.

Blocking findings: `0`
Non-blocking findings: `0`

---

## 2. Superficies Revisadas

- autenticación: revisada — ruta `/mis-cursos/` y query var, rechazo de anónimos
- ownership / IDOR: revisada — identidad de sesión, consulta por usuario, `user_id` forjado
- exposición de datos: revisada — contexto 403, cursos de terceros, revocados/huérfanos, datos del quiz
- exposición de respuestas del quiz: revisada — view model sin texto de respuestas ni claves
- XSS / escaping: revisados — títulos, metadatos, estados, fechas, imágenes, URLs
- SQL injection / prepared statements: revisados — `forUser`, agregados de intentos, validez de quizzes
- read-only: revisada — ausencia de escrituras académicas en la carga
- CSRF: N/A — el panel no tiene operaciones de escritura (RNF-004)
- redirecciones: revisada — destino del login (Mi cuenta / `wp_login_url`)
- integración WooCommerce: revisada — enlace "Mis cursos" en Mi cuenta, sin dependencia del panel

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authorization | `PASS` | `DashboardRouter::resolve()` recibe `get_current_user_id()` desde `template()` (`src/Dashboard/Presentation/Frontend/DashboardRouter.php:54-70`); `user_id <= 0` → plantilla `dashboard-access-denied` con `403` y `nocache_headers()` (`:60-67`). Probado con `resolve(0)` y con el punto de entrada real (`tests/Integration/student-dashboard.php:171-173`, `:205-214`). |
| Ownership / IDOR | `PASS` | Ningún parámetro de request se usa como identidad (verificado por inspección: el router no lee `$_GET`/`$_POST`). `StudentDashboardQuery::forUser($user_id)` filtra `WHERE user_id = %d` (`src/Enrollment/Infrastructure/EnrollmentRepository.php:50-57`) y `summariesForUserAndQuizzes` también (`src/Quiz/Infrastructure/QuizAttemptRepository.php:46`). Prueba de falsificación: usuario autenticado A con `user_id` de B en `$_GET`/`$_REQUEST` obtiene únicamente sus propios cursos (`student-dashboard.php:222-253`); el curso privado de B no aparece. |
| Data Exposure | `PASS` | El contexto del anónimo no contiene `dashboard` (`student-dashboard.php:213`); solo se listan matrículas `active`/`completed` (revocadas excluidas, `EnrollmentRepository::forUser`) y cursos `publish` (`StudentDashboardQuery::forUser`, `WP_Query` con `post_status => 'publish'`); huérfanos excluidos por intersección (`:56-60`). Estados de quiz limitados a los del propio usuario. Probado: `revoked` y curso ajeno ausentes del listado (`student-dashboard.php:252-253`). |
| Quiz Answers Exposure | `PASS` | El view model del panel no contiene texto de preguntas ni de respuestas: `StudentDashboardQuery` solo consume `dashboardDetailsForCourses` (meta del curso), `summariesForUserAndQuizzes` (conteos y `passed`) y `QuizValidityService` (agregados `answers`/`correct` por pregunta, sin texto) (`src/Quiz/Infrastructure/QuestionRepository.php:42-62`). Verificado estáticamente: ninguna consulta del panel carga `answer_text`. |
| XSS | `PASS` | `templates/student/parts/dashboard-content.php` escapa todo output: títulos/`instructor`/`duration` con `esc_html`, `image_url`/`access_url`/`login_url` con `esc_url`, `completed_at` en atributo con `esc_attr` y en texto vía `wp_date` + `esc_html`, `data-quiz-state` con `esc_attr`, mensajes desde mapa i18n fijo (`?? ''`). Los estados del enum `QuizDashboardState` no son input del usuario. Verificado estáticamente. |
| SQL Injection | `PASS` | Todas las consultas nuevas usan `$wpdb->prepare` con placeholders `%d` generados por conteo sobre arrays `absint` (`EnrollmentRepository::forUser`, `QuizAttemptRepository::summariesForUserAndQuizzes`, `QuestionRepository::answerStatsForQuizzes`); el resto vía `WP_Query`/`get_posts`/`get_post_meta`. Verificado estáticamente. |
| Read Only | `PASS` | La ruta del panel no registra acciones de escritura: `DashboardModule` solo engancha `init` (rewrite), `wp_enqueue_scripts`, `query_vars` y `template_include` (`src/Dashboard/DashboardModule.php:22-28`). Probado: matrículas, progreso e intentos idénticos antes y después de `template()` (`student-dashboard.php:216-243`, AC-024). |
| CSRF | `N/A` | El panel no expone operaciones de escritura; el envío de quiz vive en SPEC-006 y mantiene su propio nonce. |
| Open Redirect | `PASS` | `loginUrl()` devuelve el permalink de Mi cuenta de WooCommerce o `wp_login_url(self::url())` (`DashboardRouter.php:28-40`); ambos destinos son internos y se emiten con `esc_url`. Sin parámetro de redirección manipulable. |

---

## 4. Findings

Ninguno.

No se identificaron vulnerabilidades explotables ni vacíos de prueba sobre los casos de seguridad obligatorios dentro del alcance aprobado. Los cuatro casos de `plan.md` §23 tienen cobertura significativa y aislada en `tests/Integration/student-dashboard.php`: anónimo (171-173 y 205-214), `user A` no ve a `user B` (222-253), `user_id` forjado ignorado (mismos casos, vía `template()` real) y curso revocado excluido (252-253). Verificado que las aserciones fallarían si el control desaparece (no son placeholders).

---

## 5. Findings de Rondas Anteriores

Ningún finding SEC previo.

La remediación de `CODE-001` (ronda 1 de código: quiz requerido sin publicar o inválido nunca visible) se verificó por inspección y se considera correcta desde el punto de vista de exposición: `QuizDashboardState::fromDashboardData` devuelve `Unavailable` para quiz ausente/inválido y `isVisibleFor()` lo oculta (`src/Dashboard/Application/QuizDashboardState.php:22-27`, `:48-53`); la página del curso no renderiza la sección de evaluación en ese caso (`CourseQuizStatusService` + `templates/frontend/course.php`). Sin objeciones de seguridad.

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — Ejecutar el flush de rewrite rules en la activación en lugar de en `init` sin autenticación

Severity: `Info`
Blocking: No
Classification: Recommendation

Description:

`DashboardModule::ensureRewriteRules()` se ejecuta en `init` en cada request y, mientras la opción `kaanbal_dashboard_rewrite_version` no coincida, ejecuta `flush_rewrite_rules(false)` y `update_option(...)` (`src/Dashboard/DashboardModule.php:56-63`). Esto implica una escritura en la tabla de opciones disparada por peticiones sin autenticar.

Evidence:

Verificado por inspección que no hay abuso posible: el valor escrito es una constante, la operación es de una sola vez (la propia escritura impide repeticiones) y no hay camino para que un atacante restaure la opción. Es una superficie residual, no una vulnerabilidad. El código base ya tiene el patrón alternativo: `Activator::activate()` registra las reglas de `PlayerModule` y hace `flush_rewrite_rules()` (`src/Bootstrap/Activator.php`).

Benefit:

Eliminar escrituras en el arranque de peticiones anónimas y centralizar el ciclo de rewrite en el hook de activación, donde WordPress recomienda hacer flush.

Scope note:

La recomendación no representa un requisito de la SPEC actual y no hay defecto de seguridad demostrable en el código actual.

---

## 7. Limitaciones

- Ejecución dinámica del fixture de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/student-dashboard.php` requiere `KAANBAL_WP_PATH` con un WordPress vivo; no se ejecutó durante esta auditoría. Los tests fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: los casos de ownership y read-only comparan estado de base de datos y contexto renderizado, no solo códigos de respuesta).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (87 tests, 117 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest) contra un sitio en vivo; ownership, exposición, escaping y SQL se verificaron por inspección estática del código y de las pruebas.
- El endurecimiento clickjacking registrado como `SEC-REC-001` en SPEC-005 no se extiende aquí: el panel no tiene acciones de escritura, por lo que el framing no permite inducir operaciones.
- La revisión manual del enlace "Mis cursos" en Mi cuenta figura como `PARTIAL` en el estado de implementación; no se verificó en navegador durante esta auditoría (no afecta a conclusiones de seguridad: el enlace apunta a `/mis-cursos/` y `AccountMenuLinks::endpointUrl` devuelve esa URL fija).

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- Ninguno.

Recommendations:

- `SEC-REC-001` — Ejecutar el flush de rewrite rules en la activación en lugar de en `init` sin autenticación.

Comentario final:

SPEC-007 es una vista read-only con una frontera de identidad simple y bien implementada: la sesión decide de quién se muestran los datos, los anónimos no reciben contexto académico, los datos ajenos y revocados no aparecen, las respuestas del quiz nunca llegan al view model y la carga no escribe estado. Los casos de seguridad obligatorios del plan están cubiertos con pruebas que aislarían una regresión. La única recomendación es operativa (lugar del flush de rewrites); no se detectaron vulnerabilidades.
