# Auditoría de Código y Cumplimiento

SPEC: `SPEC-007 — Student Dashboard`
Ronda: `2`
Auditor: Claude Code
Commit auditado: `4d16911e8cca8058486bb04cade014b24c22ea96` (candidato registrado en `implementation-status.md`), revisado en el HEAD `aa7ad2c` (branch `feature/spec-007-student-dashboard`)
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS y PHPStan. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` para este auditor (ver §8); Codex declara haberlas ejecutado sobre el candidato exacto.

## 1. Resumen

La remediación implementa las dos decisiones humanas registradas en la ronda 1 (§10) y corrige la evidencia del quality gate:

- **CODE-001 → RESOLVED.** La validez del quiz tiene ahora una única definición: `QuizValidityRule` + `QuizValidityService`, con una operación en lote sobre una consulta agregada. La usan `QuizEligibilityService`, `CourseQuizStatusService` y el dashboard. Un quiz requerido sin publicar o inválido no muestra nada, ni en el dashboard ni en la página del curso.
- **CODE-002 → RESOLVED.** El login sigue llevando a "Mi cuenta", como decidió Daniel. "Mi cuenta" ofrece ahora "Mis cursos" en su navegación, desde el módulo WooCommerce y solo si está activo. La prueba afirma el destino real con y sin WooCommerce.
- **CODE-003 → RESOLVED.** El quality gate se ejecutó sobre el candidato exacto, con WooCommerce activo e inactivo. El "Human Review: Approved." sin commitear se descartó con justificación.
- **CODE-004 y CODE-005:** siguen abiertos, registrados como pendientes de decisión humana.

No hay regresiones ni defectos bloqueantes. Registro una observación nueva no bloqueante (`CODE-006`) sobre un efecto lateral de "no mostrar nada".

Tasks revisadas: `40` (foco en las afectadas por la remediación)
Acceptance Criteria revisados: `25`

Blocking findings: `0`
Non-blocking findings: `3` abiertos (CODE-004, CODE-005, CODE-006)

---

## 2. Alcance Auditado

Git:

- `git diff 5ffb366 4d16911`: 20 archivos de código y pruebas. Resumen:
  - `src/Quiz/Application/{QuizValidityRule,QuizValidityService}.php` (nuevos);
  - cambios en `QuizEligibilityService`, `CourseQuizStatusService`, `QuestionRepository` y `QuizModule`;
  - `src/Dashboard/*`, `src/Access/Presentation/Frontend/FrontendRouter.php`;
  - `src/WooCommerce/{WooCommerceModule,Presentation/Frontend/AccountMenuLinks}.php`;
  - las plantillas `course.php` y `dashboard-content.php`;
  - pruebas unitarias y de integración, y `tests/bootstrap.php`.
- `git diff 4d16911 aa7ad2c`: solo `implementation-status.md`. Verificado `git diff --quiet 4d16911 aa7ad2c -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- `spec.md`, `plan.md` y `feature.feature` sin cambios desde `78d7b17`.
- Working tree: solo los reportes de auditoría de este auditor (ronda 1 y ronda 2), sin commitear.
- Independencia: este auditor no participó en la remediación.

Revisé íntegramente todos los archivos de producción y de prueba modificados, e `implementation-status.md` en `aa7ad2c`.

---

## 3. Trazabilidad (cambios respecto a la ronda 1)

| AC / decisión | Implemented | Verified (r1 → r2) | Evidence |
|---|---|---|---|
| AC-013 / AC-014 (estado del quiz) | Yes | Yes → Yes | `QuizDashboardState::fromDashboardData()` recibe `$quiz_valid`; `QuizDashboardStateTest`: "required quiz is invalid" y "not published" → `Unavailable` |
| Decisión CODE-001 (dashboard) | Yes | Yes | `isVisibleFor()` oculta `Unavailable` en cualquier estado de matrícula; integración `student-dashboard.php`: quiz sin preguntas al 100% y quiz sin publicar → `visible = false`; se eliminó el mensaje "aún no está disponible" |
| Decisión CODE-001 (página del curso) | Yes | Yes | `CourseQuizStatusService::forCourse()` devuelve `InvalidQuiz` antes de evaluar el progreso; `course.php:70` no renderiza la sección. Integración: 3 casos (100%, incompleto, sin publicar) sin "Evaluación final" ni "Completa todas las lecciones" |
| Regla única de validez | Yes | Yes | `QuizValidityRuleTest` (7 casos); integración `validQuizIds()` = exactamente los quizzes válidos; `grep hasValidQuestions src` sin resultados |
| AC-025 (N+1) | Yes | Yes | La validez añade una consulta agregada; el estado declara 13 = 13 (1 vs 10 cursos) |
| AC-002 / decisión CODE-002 | Yes | Yes → Yes | Aserción real del destino: permalink de `myaccount` con WooCommerce; `wp_login_url('/mis-cursos/')` sin él |
| Decisión CODE-002 ("Mis cursos" en Mi cuenta) | Yes | Yes | `AccountMenuLinksTest` (3 casos); integración con WooCommerce: `wc_get_account_menu_items()` contiene la entrada, `wc_get_account_endpoint_url()` = `/mis-cursos/` y la navegación renderizada contiene el enlace |

---

## 4. Verificación de Tasks

Las tasks de estado del quiz, acceso denegado y quality gate pasan de "PASS con observación" a **PASS**. El resto sin cambios.

---

## 5. Findings

### CODE-006 — "No mostrar nada" también oculta una aprobación ya obtenida y deja sin explicación a quien no puede completar

Severity: Low
Blocking: No
Classification: Recommendation (UX)
Status: NEW

Affected requirement:

SPEC-006 `RF-029`/`AC-022` (mensaje de aprobación y certificado en la página del curso); decisión humana CODE-001

Affected files:

- `src/Quiz/Application/CourseQuizStatusService.php` (`forCourse`)
- `templates/frontend/course.php:70`
- `src/Dashboard/Application/QuizDashboardState.php` (`fromDashboardData`)

Description:

La implementación aplica la decisión literalmente: con un quiz sin publicar o inválido, no se muestra nada. Eso tiene dos efectos laterales que el responsable humano debería conocer.

1. **Aprobación ya obtenida.** Si un alumno aprobó el quiz (matrícula `completed`) y después el quiz se despublica, `CourseQuizStatusService` devuelve `InvalidQuiz` antes de consultar `hasPassed`. La plantilla oculta entonces toda la sección, incluido "Has completado y aprobado el curso" y el mensaje de certificado. Lo mismo pasa en el dashboard con "Evaluación final aprobada" (caso de prueba "invalid quiz hides even a past pass"). La tarjeta del dashboard sí conserva "Aprobado" y el mensaje de certificado, porque se derivan de la matrícula; la página del curso no.
2. **Alumno que no puede completar.** En un curso que requiere quiz y no tiene uno válido, el alumno al 100% ve "En curso · 100%" sin ninguna indicación, y no tiene forma de completar. Es la consecuencia esperada de la decisión, pero hace más importante el aviso al administrador que propuso SPEC-006 CODE-003 punto 5, hoy pendiente.

Además, en la página del curso la validez se consulta dos veces: en `CourseQuizStatusService` y otra vez dentro de `QuizEligibilityService::check()`. Es una consulta agregada adicional por vista. Menor.

Evidence:

Lectura de `CourseQuizStatusService::forCourse()`: la rama `! $quiz instanceof \WP_Post || ! $this->validity->isValid(...)` precede a `eligibility->check()`, que es donde se evalúa `AlreadyPassed`. `course.php:70` condiciona toda la sección a `'invalid_quiz' !== $quiz['result']`.

Impact:

Bajo. Requiere que el administrador despublique o invalide un quiz, cosa que la UI actual de SPEC-006 no permite hacer con las preguntas (CODE-003 de SPEC-006), pero sí despublicando el quiz.

Required correction (sugerida, decisión humana):

- Mostrar el estado de aprobación cuando la matrícula ya es `completed`, aunque el quiz haya dejado de ser válido.
- Priorizar el aviso en el admin para cursos que requieren quiz sin uno válido.

---

## 6. Findings de Rondas Anteriores

### CODE-001 — El dashboard reimplementaba la elegibilidad y divergía de SPEC-006

Previous status: `NEW` (Medium, non-blocking; decisión humana `VALID`)
Current status: `RESOLVED`

Evidence:

- `QuizValidityRule`: única definición (≥1 pregunta activa; cada una con ≥2 respuestas y exactamente 1 correcta).
- `QuizValidityService::validQuizIds()`: una sola consulta con `LEFT JOIN … GROUP BY q.quiz_id, q.id`, que produce `COUNT(a.id)` y `SUM(CASE WHEN a.is_correct = 1 …)`. Está preparada con placeholders `%d`. Una pregunta sin respuestas da `answers = 0` → inválida.
- `QuizEligibilityService` ya no tiene `hasValidQuestions()`; usa `QuizValidityService`.
- Dashboard y página del curso aplican la decisión (ver §3). Pruebas unitarias y de integración añadidas.

### CODE-002 — El enlace de login no regresaba a "Mis cursos" y su prueba era tautológica

Previous status: `NEW` (Low; decisión humana `VALID` con alcance ajustado)
Current status: `RESOLVED`

Evidence:

- `AccountMenuLinks::menuItems()` inserta "Mis cursos" después de "Escritorio", o primero si ese ítem no existe, y es idempotente (`unset` previo). `endpointUrl()` solo reescribe su propia clave.
- Se registra en `WooCommerceModule::registerWhenWooCommerceIsAvailable()`, que solo se ejecuta con WooCommerce activo. La dependencia va del adapter WooCommerce hacia `DashboardRouter::url()`, y Dashboard no depende de WooCommerce.
- La aserción tautológica se reemplazó por el destino real.
- La adición de alcance quedó registrada en "Human Scope Decisions".

### CODE-003 — Quality gate declarado sobre un commit anterior

Previous status: `NEW` (Low) → Current status: `RESOLVED`

Evidence: `implementation-status.md` declara la ejecución sobre el candidato exacto `4d16911` (con `git archive`), con WooCommerce 11.1.1 activo, incluida `test:integration:woocommerce`, y con WooCommerce inactivo (`dashboard`, `quiz`). Reporta 87 pruebas y 117 aserciones, lo que coincide con mi ejecución (§8). El cambio de "Human Review" se descartó y se explicó por qué.

### CODE-004 — Estados del alumno sin explicación

Previous status: `NEW` → Current status: `OPEN` (pendiente de decisión humana, registrado por Codex)

### CODE-005 — Observaciones menores

Previous status: `NEW` → Current status: `OPEN` (pendiente de decisión humana)

---

## 7. Recomendaciones

- Decidir sobre CODE-006 punto 1 (conservar el mensaje de aprobación con matrícula `completed`) y sobre el aviso al administrador (SPEC-006 CODE-003 punto 5).
- Completar la revisión en navegador del enlace "Mis cursos" en Mi cuenta, pendiente según `implementation-status.md`.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (`curso/.tools/`), usando el `vendor/` existente. Comandos ejecutados sobre `aa7ad2c`, cuyo código es idéntico a `4d16911`.

Comandos:

```text
find src tests templates -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet 4d16911 aa7ad2c -- src tests templates assets kaanbal.php composer.json composer.lock
git diff --stat 5ffb366 aa7ad2c -- docs/specs/007-student-dashboard/{spec.md,plan.md,feature.feature}
```

Resultados:

```text
lint:      0 archivos con errores (incluye templates/)
PHPUnit:   OK (87 tests, 117 assertions)   ← coincide con implementation-status.md
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD; archivos protegidos sin cambios
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para `tests/Integration/*.php` desde la shell de este auditor, porque necesitan WordPress, MySQL y WooCommerce reales. Las evalué por lectura. Codex declara haberlas ejecutado sobre el candidato exacto con WooCommerce activo e inactivo. Es una declaración verificable, pero no la reproduje.

---

## 9. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno

Non-blocking findings (abiertos):

- CODE-004 — Estados del alumno sin explicación (pendiente de decisión humana)
- CODE-005 — Observaciones menores (pendiente de decisión humana)
- CODE-006 — "No mostrar nada" también oculta una aprobación ya obtenida y deja sin explicación a quien no puede completar

Findings resueltos en esta ronda:

- CODE-001, CODE-002, CODE-003

Comentario final:

La remediación aplica fielmente las decisiones humanas y además elimina la duplicación de la regla de validez entre SPEC-006 y SPEC-007, con pruebas en ambos niveles. Desde la perspectiva de requisitos e implementación, SPEC-007 puede pasar a consolidación y revisión humana, sujeta a:

- los resultados de Qwen y Mimo;
- la verificación en navegador del enlace "Mis cursos" en Mi cuenta.
