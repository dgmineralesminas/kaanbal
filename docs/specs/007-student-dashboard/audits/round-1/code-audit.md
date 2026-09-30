# Auditoría de Código y Cumplimiento

SPEC: `SPEC-007 — Student Dashboard`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `d4abd03` (candidato registrado en `implementation-status.md`), revisado en el HEAD `5ffb366` (branch `feature/spec-007-student-dashboard`)
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS, PHPStan y arnés propio. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` (ver §8).

## 1. Resumen

SPEC-007 cumple los 25 criterios de aceptación. La implementación es una capa de lectura bien acotada:

- **Identidad:** `DashboardRouter` toma al usuario de `get_current_user_id()` y ningún código del módulo lee `$_GET`/`$_REQUEST`.
- **Lectura:** `StudentDashboardQuery` compone matrículas (`active`/`completed`; `revoked` excluidas en SQL), cursos publicados, progreso en lote, detalles de quiz y resúmenes de intentos, sin escribir nada.
- **Progreso:** reutiliza `CourseProgressService` (nuevo `forCourses()`, que en el arnés da el mismo resultado que `forCourse()`) y `ProgressCalculator`.
- **Estado formal:** se deriva de `Enrollment.status`, nunca del porcentaje (RB-003/RB-004).
- **Rendimiento:** la carga en lote queda probada midiendo consultas (1 curso vs 8 cursos).
- **Salida:** todo va escapado y no hay descarga de certificado.

La trazabilidad también mejoró respecto a SPEC-006: `spec.md`, `plan.md` y `feature.feature` se commitearon en `78d7b17`, antes de implementar, y no cambiaron después.

No hay findings bloqueantes. Los hallazgos son:

- `CODE-001` (Medium): el dashboard reimplementa la elegibilidad del quiz y diverge de SPEC-006. Con un quiz sin preguntas válidas anuncia "Tu evaluación final está disponible", mientras SPEC-006 lo trata como inválido.
- `CODE-002` (Low): el enlace de inicio de sesión ya no regresa a "Mis cursos", y su prueba quedó tautológica.
- `CODE-003` (Low): el quality gate declarado se ejecutó sobre `ff87511`, no sobre el candidato.
- `CODE-004` y `CODE-005`: observaciones informativas.

Tasks revisadas: `40`
Acceptance Criteria revisados: `25`
Escenarios Gherkin revisados: todos los de `feature.feature`

Blocking findings: `0`
Non-blocking findings: `5` (CODE-001 a CODE-005)

---

## 2. Alcance Auditado

Git:

- Rama creada desde `8a472c9` (merge de SPEC-006 a `main`). Contrato de la SPEC en `78d7b17`.
- `git diff --stat 78d7b17 5ffb366 -- docs/specs/007-student-dashboard/`: solo `implementation-status.md` y `tasks.md`; los archivos protegidos no cambiaron.
- `git diff --stat 78d7b17 d4abd03 -- . ':!docs'`: 19 archivos. Commits de implementación:
  - `4fe0f1e` (feat);
  - `ff87511`: remediación previa a auditoría, hecha por el rol de implementación en otra sesión;
  - `591e7c0`: solo CSS;
  - `d4abd03`: enlace de login y una aserción de prueba.
- `git diff --quiet d4abd03 5ffb366 -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- Working tree: una modificación sin commitear, ajena a este reporte, en `implementation-status.md` (`Human Review: Pendiente…` → `Approved.`). Ver CODE-003.
- Independencia: este auditor no participó en ninguno de los commits de implementación.

Código revisado íntegramente:

- `src/Dashboard/{DashboardModule,Application/QuizDashboardState,Application/StudentDashboardQuery,Presentation/Frontend/DashboardRouter}.php`
- Cambios en:
  - `src/Courses/{Application/CurriculumService,Infrastructure/CurriculumRepository}.php` (`forPublishedCourses`, `modulesForCourses`)
  - `src/Enrollment/Infrastructure/EnrollmentRepository.php` (`forUser`)
  - `src/Progress/Application/CourseProgressService.php` (`forCourses`)
  - `src/Quiz/Infrastructure/{QuizRepository,QuizAttemptRepository}.php` (`dashboardDetailsForCourses`, `summariesForUserAndQuizzes`)
  - `src/Bootstrap/{Activator,Plugin}.php`
- `templates/student/{dashboard,dashboard-access-denied}.php`, `templates/student/parts/dashboard-content.php`
- `assets/css/dashboard.css`, de forma superficial (solo presentación).

Pruebas revisadas: `tests/Unit/QuizDashboardStateTest.php`, `tests/Integration/student-dashboard.php`.

---

## 3. Trazabilidad

Leyenda de "Verified": **Yes** = prueba del proyecto con aserción que lo comprueba (las de integración, evaluadas por lectura; §8); **Static** = verificado por lectura y/o arnés del auditor; **Partial** = prueba débil o incompleta.

| AC | Implemented | Verified | Evidence (`student-dashboard.php` salvo indicación) |
|---|---|---|---|
| AC-001 | Yes | Yes | `:174-196` `template()` autenticado → `dashboard.php`, 200 |
| AC-002 | Yes | Yes | `:152-155` `resolve(0)` → 403 sin `dashboard`; `:157-167` vía `template()` con `user_id` forjado |
| AC-003 | Yes | Yes | `:204` seis cursos activos presentes |
| AC-004 | Yes | Yes | `:216` `completed` → `Aprobado` |
| AC-005 | Yes | Yes | `:204` revoked ausente; `EnrollmentRepository::forUser` filtra `status IN ('active','completed')` |
| AC-006 | Yes | Yes | `:208` 1/2 = 50%. Arnés: `forCourses()` = `forCourse()` en 4 cursos, incluidos vacío y borrador |
| AC-007 | Yes | Static | Usa `CourseProgressService::forCourses()` + `ProgressCalculator`; sin almacenamiento propio |
| AC-008 | Yes | Yes | `:208` `En curso` |
| AC-009 | Yes | Yes | `:216` `Aprobado` |
| AC-010 | Yes | Yes | `:251` "Continuar curso" |
| AC-011 | Yes | Yes | `:251` "Ver curso"; `access_url` = ruta del player de SPEC-004 |
| AC-012 | Yes | Yes | `:208` `not_required`; `isVisibleFor()` lo oculta |
| AC-013 | Yes | Yes | `:212` `locked`; `:251` "Completa las lecciones" |
| AC-014 | Yes | Yes | `:212` `available`; ver CODE-001 sobre quizzes inválidos |
| AC-015 | Yes | Yes | `:216`, `:251` un solo `data-quiz-state="passed"` |
| AC-016 | Yes | Yes | `:212` 2 y 1 restantes; ilimitado sin contador (`:251` "Intentos ilimitados", sin "0 intentos restantes") |
| AC-017 | Yes | Yes | `:212` `no_attempts_left`; `:251` "Intentos agotados" |
| AC-018 | Yes | Static | `completed_at` en el view model y `<time>` en la plantilla (`dashboard-content.php:52-54`); sin aserción de render de la fecha |
| AC-019 | Yes | Yes | `:216`, `:251` mensaje de certificado |
| AC-020 | Yes | Yes | `:251` sin "Descargar certificado" ni `certificado.pdf`; `grep` sin URLs de certificado |
| AC-021 | Yes | Yes | `:216` `completed_plain` sin certificado |
| AC-022 | Yes | Yes | `:255-263` estado vacío |
| AC-023 | Yes | Yes | `:174-204` estudiante autenticado con `user_id` de otro → solo sus cursos; `other_course` ausente |
| AC-024 | Yes | Yes | `:169-196` matrículas, progreso e intentos idénticos antes y después |
| AC-025 | Yes | Yes | `:230-244` diferencia de consultas ≤ 3 entre 1 y 8 cursos (estado: 12 = 12) |

Casos límite: EC-001/EC-002 (`:132` matrícula a curso inexistente, omitida sin error); EC-003 (arnés: curso vacío 0/0 0%); EC-004 (`available` + `En curso`); EC-005 (confía en `Enrollment`); EC-006 (`no_attempts_left`); EC-007 (`:220-223` quiz habilitado después de aprobar no aparece pendiente); EC-008 (certificado según configuración actual).

Pruebas obligatorias del plan §23: anónimo, usuario A no ve a B, `user_id` forjado y revoked excluido. **Todas presentes.** Estados del plan §24: los ocho casos presentes.

---

## 4. Verificación de Tasks

Las 40 tasks están en `Done`. Las verifiqué por grupo contra el código y las pruebas: ruta y controlador, query, carga en lote, view model, plantilla, estados, seguridad, rendimiento y pruebas. Todas quedan como **PASS**, con estas observaciones:

- Las tasks de estado del quiz: CODE-001.
- Las del acceso denegado/login: CODE-002.
- Las del quality gate y estado: CODE-003.

---

## 5. Findings

### CODE-001 — El dashboard reimplementa la elegibilidad del quiz y diverge de SPEC-006: un quiz sin preguntas válidas se anuncia como disponible

Severity: Medium
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

`RNF-003` ("no debe duplicar reglas de negocio … obtener información de Quiz services"), `DEC-003` ("Quiz status se obtiene de SPEC-006"), `RF-014`, `AC-014`

Affected files:

- `src/Dashboard/Application/QuizDashboardState.php:17-40`
- `src/Quiz/Infrastructure/QuizRepository.php` (`dashboardDetailsForCourses`)
- Referencia: `src/Quiz/Application/QuizEligibilityService.php` (`hasValidQuestions`, `InvalidQuiz`)

Description:

`QuizDashboardState::fromDashboardData()` reproduce parte de las reglas de `QuizEligibilityService`: progreso, aprobado e intentos. Omite la validez del quiz: al menos una pregunta activa, cada una con al menos dos respuestas y exactamente una correcta. `dashboardDetailsForCourses()` solo busca el primer quiz publicado del curso. Si ese quiz no tiene preguntas o tiene preguntas mal formadas, el dashboard le dice al alumno "Tu evaluación final está disponible."; SPEC-006 devuelve `InvalidQuiz` y en la página del curso no hay formulario.

El plan §9 permite no cargar preguntas y respuestas, y es razonable no hacerlo por rendimiento. Pero la consecuencia es que ahora hay dos implementaciones de "¿puede presentar el quiz?" que ya no coinciden.

Evidence:

Arnés del auditor con clases reales (`QuizRepository::dashboardDetailsForCourses()` + `QuizDashboardState`): quiz publicado, requerido, con 0 preguntas, curso al 100% → `dashboard state = available`. Para el mismo caso, `QuizEligibilityService::check()` devuelve `InvalidQuiz` (`hasValidQuestions()` → `array() === $questions`).

Impact:

El alumno sigue la invitación del dashboard y en el curso no encuentra el quiz. Por SPEC-006 CODE-002, ahí se le muestra además "Completa todas las lecciones…". Ocurre por un error de configuración del administrador, que SPEC-006 CODE-003 hace fácil de cometer sin aviso. Además, toda regla futura de elegibilidad habrá que mantenerla en dos lugares.

Required correction (sugerida):

Que el estado del dashboard derive de una única fuente de elegibilidad. Por ejemplo, una operación en lote de SPEC-006 que devuelva validez + intentos + aprobado por curso, o que `dashboardDetailsForCourses()` incluya un indicador de validez calculado con una consulta agregada sobre preguntas y respuestas. Añadir el caso `quiz inválido → unavailable` a `QuizDashboardStateTest` y a la integración.

---

### CODE-002 — El enlace de inicio de sesión ya no regresa a "Mis cursos" y su prueba quedó tautológica

Severity: Low
Blocking: No
Classification: Test Gap / Recommendation (UX)
Status: NEW

Affected requirement:

`RF-001`, `AC-002` (experiencia del visitante)

Affected files:

- `src/Dashboard/Presentation/Frontend/DashboardRouter.php:31-42`
- `tests/Integration/student-dashboard.php:153`
- `docs/specs/007-student-dashboard/implementation-status.md` (secciones "Full-page render" y "Pre-audit Remediation" punto 5)

Description:

En `ff87511` el enlace era `wp_login_url(/mis-cursos/)` y la prueba verificaba que contuviera `mis-cursos`. En `d4abd03` el enlace pasa a `wc_get_page_permalink('myaccount')`, lo cual está documentado como decisión para alinearse con el tema. La aserción se reemplazó por `DashboardRouter::loginUrl() !== $anonymous['context']['login_url']`, que compara el método consigo mismo: ya no verifica ningún destino.

Consecuencias:

1. El formulario de "Mi cuenta" de WooCommerce redirige, tras el login, a la propia página de Mi cuenta (usa el referer del formulario). El alumno que intentó abrir "Mis cursos" no vuelve ahí automáticamente.
2. `implementation-status.md` sigue diciendo que el enlace "regresa a `/mis-cursos/`" y que "incluye enlace de login con redirect_to". Esa verificación se hizo sobre `ff87511` y ya no describe el comportamiento del candidato.

Impact:

Menor: un paso extra para el alumno, y una prueba que no protege nada.

Required correction (sugerida):

Si el destino debe ser Mi cuenta, que la prueba lo afirme explícitamente (con y sin WooCommerce), y actualizar el estado. Para conservar el regreso a "Mis cursos", se puede añadir el parámetro `redirect` que WooCommerce respeta en `process_login`, o usar el filtro `woocommerce_login_redirect`. La decisión es de UX y corresponde a Daniel.

---

### CODE-003 — Quality gate declarado sobre un commit anterior al candidato; estado documental con cambios sin commitear

Severity: Low
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected files:

- `docs/specs/007-student-dashboard/implementation-status.md`

Description:

1. La sección "Quality Gate" indica "Ejecutado sobre `ff87511`". Después hubo dos commits en el candidato: `591e7c0` (CSS) y `d4abd03` (PHP en `DashboardRouter` y la aserción de `student-dashboard.php:153`). No consta que las pruebas de integración se reejecutaran sobre `d4abd03` (`agents.md §19`). La declaración es honesta sobre el commit, así que no la trato como representación falsa. La verificación estática y unitaria la reproduje yo sobre el candidato (§8).
2. `Manual Verification` dice "PARTIAL … pendiente confirmar en navegador", mientras el working tree (sin commitear) cambia `Human Review` a `Approved.`.
3. `woocommerce-enrollment` quedó `BLOCKED BY ENVIRONMENT` en el entorno de Codex. SPEC-007 no toca código de WooCommerce, pero `d4abd03` introduce el uso de `wc_get_page_permalink()`.

Impact:

Bajo: la evidencia de integración del candidato exacto no está registrada.

Required correction:

Reejecutar la integración sobre el candidato (al menos `test:integration:dashboard`), actualizar la sección y commitear o descartar el cambio de "Human Review".

---

### CODE-004 — Estados del alumno que el dashboard muestra sin explicación

Severity: Info
Blocking: No
Classification: Recommendation (UX)
Status: NEW

Description:

Ninguno contradice la SPEC, porque el estado formal viene de `Enrollment` (RB-004, EC-005, EC-007). Pero pueden confundir al alumno:

- **Curso aprobado con progreso actual menor a 100%.** Si el curriculum cambia después de aprobar, la tarjeta dice "Aprobado" junto a, por ejemplo, "3 de 4 lecciones completadas · 75%".
- **Curso sin quiz al 100% con matrícula todavía `active`.** Es el escenario de SPEC-006 CODE-007: se agregan o quitan lecciones por el administrador, o se desmarca "requiere quiz". La tarjeta muestra "En curso · 100%" y "Continuar curso", sin que el alumno pueda hacer nada para completar.

Required correction:

Opcional: para cursos aprobados, mostrar "Aprobado" sin barra de progreso, o con el progreso histórico. El segundo caso se resuelve en SPEC-006 CODE-007.

---

### CODE-005 — Observaciones menores

Severity: Info
Blocking: No
Classification: Recommendation
Status: NEW

- `access_url` construye `/courses/{slug}/` en lugar de reutilizar un helper de la ruta del player. Ya está reconocido en "Known Issues".
- Las plantillas usan `get_header()`/`get_footer()`. Con un tema de bloques sin `header.php`, WordPress recurre a la cabecera de compatibilidad y emite un aviso de deprecación. Funciona, pero no se integra con el tema (RNF-007). Es el mismo patrón que las plantillas del player de SPEC-004.
- `completed_at` no tiene aserción de render en la integración, aunque está en el view model y en la plantilla.
- Se consulta `update_meta_cache('post', $course_ids)` en `StudentDashboardQuery` y otra vez en `dashboardDetailsForCourses()`. Es inocuo por la caché, pero redundante.

---

## 6. Findings de Rondas Anteriores

No aplica: es la ronda 1 de SPEC-007.

Relación con SPEC-006: CODE-001 amplifica SPEC-006 CODE-002/003 (mensajes y configuración del quiz), y CODE-004 se relaciona con SPEC-006 CODE-007. Esos findings siguen su propio ciclo.

---

## 7. Recomendaciones

- Atender CODE-001 junto con SPEC-006 CODE-002/003, de modo que "¿puede presentar el quiz?" tenga una sola respuesta en todo el sitio.
- Decidir el destino post-login (CODE-002) y reforzar la prueba en consecuencia.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (`curso/.tools/`), usando el `vendor/` existente. Comandos ejecutados sobre `5ffb366`, cuyo código es idéntico a `d4abd03`.

Comandos:

```text
find src tests templates -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet d4abd03 5ffb366 -- src tests templates assets kaanbal.php composer.json composer.lock
```

Resultados:

```text
lint:      0 archivos con errores (incluye templates/)
PHPUnit:   OK (74 tests, 103 assertions)   ← coincide con implementation-status.md
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD
```

Arnés del auditor (stubs de WordPress, clases reales de `src/`):

```text
forCourse() vs forCourses():
  course 10: 2/3 67% | 2/3 67%     (lección en borrador excluida en ambos)
  course 11: 1/1 100% | 1/1 100%
  course 12: 0/0 0%  | 0/0 0%      (curso vacío)
  course 13: 0/0 0%  | (omitido)   (curso en borrador; el dashboard también lo excluye)
quiz publicado con 0 preguntas, curso al 100% → dashboard state = available   (CODE-001)
```

Búsquedas: `grep -rn "user_id\|\$_GET\|\$_REQUEST" src/Dashboard templates/student` → solo parámetros internos de la identidad de sesión; ninguna lectura del request.

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para `tests/Integration/*.php`, incluida `student-dashboard.php` con su medición de consultas, que necesitan WordPress y MySQL reales. Las evalué por lectura. Recomiendo que Daniel ejecute `composer test:integration:dashboard` sobre `d4abd03` (ver CODE-003).

---

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno

Non-blocking findings:

- CODE-001 — El dashboard reimplementa la elegibilidad del quiz y diverge de SPEC-006: un quiz sin preguntas válidas se anuncia como disponible
- CODE-002 — El enlace de inicio de sesión ya no regresa a "Mis cursos" y su prueba quedó tautológica
- CODE-003 — Quality gate declarado sobre un commit anterior al candidato; estado documental con cambios sin commitear
- CODE-004 — Estados del alumno que el dashboard muestra sin explicación
- CODE-005 — Observaciones menores

Comentario final:

SPEC-007 es la entrega más limpia hasta ahora en trazabilidad y pruebas. Tiene la SPEC versionada antes de implementar, las pruebas de seguridad obligatorias completas y la carga en lote medida. Desde la perspectiva de requisitos e implementación puede pasar a consolidación, sujeta a:

- los resultados de Qwen y Mimo;
- la reejecución local de la integración sobre `d4abd03`;
- la decisión humana sobre CODE-001 y CODE-002.

---

## 10. Decisiones Humanas Registradas

Fecha: `2026-09-29`. Decisor: Daniel (responsable humano, `agents.md §3`). Registradas por el auditor a solicitud del responsable; no cambian el veredicto de esta ronda.

### CODE-001 — Decisión: `VALID`, con comportamiento definido

> "Si no hay preguntas válidas o quiz en un curso no mostrar nada."

Comportamiento esperado: si un curso requiere quiz pero no tiene un quiz publicado, o el quiz no cumple la validez de SPEC-006 (al menos una pregunta activa, cada una con ≥2 respuestas y exactamente una correcta), el dashboard no muestra ningún estado de quiz para ese curso: ni "disponible" ni "aún no está disponible". La validez debe provenir de la misma regla que usa `QuizEligibilityService`, no de una copia.

### CODE-002 — Decisión: `VALID`, con alcance ajustado

> "No hay problema. Puede caer en mi cuenta, por si necesita cambiar algo. Pero debe haber un botón que lleve a Mis cursos."

- Aceptado: tras iniciar sesión el alumno puede aterrizar en "Mi cuenta" de WooCommerce.
- Requerido: "Mi cuenta" debe ofrecer una acción visible que lleve a `/mis-cursos/`.
- La prueba del enlace de login debe afirmar el destino real, no compararse consigo misma.

Nota: la acción en "Mi cuenta" es una adición de alcance aprobada por el humano (SPEC-007 §4 excluía el "frontend completo de cuenta WooCommerce"). Codex debe registrarla como tal en `implementation-status.md`.
