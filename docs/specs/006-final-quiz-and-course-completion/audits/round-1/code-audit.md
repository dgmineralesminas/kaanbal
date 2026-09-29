# Auditoría de Código y Cumplimiento

SPEC: `SPEC-006 — Final Quiz and Course Completion`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `ff1721bdf2766b09777f34e7eb1745ace74caba1` (candidato registrado en `implementation-status.md`), revisado en el HEAD `cf00b4b` (branch `feature/spec-006-final-quiz-and-course-completion`)
Fecha: `2026-09-29`

Verdict: `FAIL`

Audit completeness: `Complete` para revisión estática, PHPUnit, PHPCS, PHPStan y arnés propio. Pruebas de integración contra WordPress/MySQL: `BLOCKED BY ENVIRONMENT` (ver §8).

## 1. Resumen

La implementación de SPEC-006 es sólida en lo esencial:

- **Flujo de envío:** sesión, nonce por quiz, acceso, 100% de lecciones, pertenencia quiz → curso, validación pregunta → respuesta y score calculado en servidor.
- **Registro de intentos:** lock de secuencia + transacción + `UNIQUE(user_id, quiz_id, attempt_number)`, con re-comprobación de "ya aprobado" y del límite dentro del lock, y snapshots de texto.
- **Finalización del curso:** una única `UPDATE … WHERE status = 'active'` atómica que conserva el primer `completed_at`.
- **Certificado:** solo informativo.
- **Seguridad del cliente:** `is_correct` nunca llega al navegador.
- **Pruebas:** la suite de integración cubre la mayoría de los casos de seguridad del plan.

El veredicto es `FAIL` por un único finding bloqueante de comportamiento (`CODE-001`). El score se **redondea antes de compararlo** con el passing score, así que alumnos por debajo del mínimo aprueban. Por ejemplo, 2 de 3 correctas (66.67%) aprueban con mínimo 67%, y 159/200 (79.5%) aprueban con mínimo 80%. Eso contradice RF-013/RF-015 y AC-012. Además, la matrícula pasa a `completed`, que RB-010 declara no reversible automáticamente, y se promete el certificado. La corrección es pequeña.

Los hallazgos no bloqueantes más relevantes son de experiencia de usuario, alineados con la prioridad del proyecto:

- `CODE-002`: el alumno que agota sus intentos ve "Completa todas las lecciones para desbloquear tu evaluación final", no recibe retroalimentación al reprobar, y un doble clic tras aprobar le muestra un 403.
- `CODE-003`: el administrador no puede editar, borrar ni reordenar preguntas, y varios errores de guardado son silenciosos.

Tasks revisadas: `44`
Acceptance Criteria revisados: `27`
Escenarios Gherkin revisados: `21` (SC-001 a SC-021)

Blocking findings: `1` (CODE-001)
Non-blocking findings: `7` (CODE-002 a CODE-008)

---

## 2. Alcance Auditado

Git:

- Rama creada desde `15faf3a` (que registra las auditorías de Qwen/Mimo de SPEC-005 ronda 1).
- `git diff --stat 0a86f1c ff1721b`: 35 archivos de código, pruebas y assets. Incluye además los dos reportes de SPEC-005 que venían en `15faf3a`.
- `git diff ff1721b cf00b4b`: solo los cinco documentos de SPEC-006. Verificado `git diff --quiet ff1721b cf00b4b -- src tests templates assets kaanbal.php composer.json composer.lock` → sin diferencias.
- Working tree: una modificación sin commitear ajena a este reporte, en `docs/specs/005-student-progress/implementation-status.md` (`Status: Ready for audit` → `Completed`). Ver CODE-006.

Documentos revisados: `spec.md`, `plan.md`, `feature.feature`, `tasks.md`, `implementation-status.md` de SPEC-006; `docs/agents.md`, `docs/audit-standard.md`.

Código revisado íntegramente:

- `src/Quiz/Application/*` (CourseCompletionPolicy, CourseCompletionService, CourseQuizStatusService, QuizEligibilityResult/Service, QuizScore, QuizScoreCalculator, QuizSubmissionResult/Service)
- `src/Quiz/Infrastructure/*` (Answer, Question, QuizAttempt, Quiz repositories)
- `src/Quiz/Presentation/{Admin/QuizMetaBoxes,Frontend/QuizSubmissionAction}.php`, `src/Quiz/QuizModule.php`
- Cambios en `src/Access/{PlayerModule,Presentation/Frontend/FrontendRouter}.php`, `src/Enrollment/Infrastructure/EnrollmentRepository.php`, `src/Progress/{Application/CompleteLessonService,ProgressModule}.php`, `src/Shared/Database/EnrollmentSchemaMigration.php`, `src/Courses/Infrastructure/ContentTypes.php`, `src/Bootstrap/{Plugin,Version}.php`
- `templates/frontend/course.php`, `assets/js/assessment.js`; `assets/css/player.css`, esta última de forma superficial.

Pruebas revisadas: `tests/Unit/{CourseCompletionPolicyTest,QuizScoreCalculatorTest}.php`, `tests/Integration/final-quiz-course-completion.php`, `tests/Integration/support/quiz-endpoint-request.php`, y el impacto sobre `tests/Integration/student-progress.php`.

---

## 3. Trazabilidad

Leyenda de "Verified": **Yes** = prueba del proyecto con aserción que lo comprueba (las de integración, evaluadas por lectura; §8); **Static** = verificado por lectura y/o arnés del auditor; **Partial** = prueba débil o incompleta; **No** = la evidencia muestra incumplimiento.

| AC | Scenario | Task | Implemented | Verified | Evidence |
|---|---|---|---|---|---|
| AC-001 | SC-001 | TASK-001 | Yes | Yes | Meta `_kaanbal_requires_final_quiz` (`QuizMetaBoxes::saveCourse`); `final-quiz-course-completion.php:131` completa un curso sin quiz |
| AC-002 | SC-002 | TASK-001, 003 | Yes | Partial | CPT `kaanbal_quiz`, relación por meta. La guarda de un solo quiz publicado no tiene prueba (CODE-005) |
| AC-003 | SC-003 | TASK-008, 012 | Yes | Yes | `:84-105`: publicar el quiz vía `save_post` guarda pregunta y respuestas |
| AC-004 | SC-003 | TASK-009, 012, 013 | Yes | Yes | `:103` (2 respuestas, la correcta marcada). `QuizMetaBoxes.php:128` exige ≥2 respuestas y una correcta; `QuizEligibilityService.php:57-77` rechaza quizzes con preguntas mal formadas (`:187`) |
| AC-005 | — | TASK-003, 011 | Yes | Yes | `passing_score` guardado y acotado a 1-100 (`QuizMetaBoxes.php:99`); ver CODE-003 sobre valores negativos |
| AC-006 | — | TASK-019 | Yes | Yes | `:108-125` guarda `max_attempts = 3`; `:211-214` aplica límite 1 |
| AC-007 | SC-010 | TASK-020 | Yes | Yes | Meta ausente → `null` → ilimitado; `:216-220` tres intentos |
| AC-008 | SC-004 | TASK-014, 031 | Yes | Yes | `:183-185` → `LessonsIncomplete` |
| AC-009 | SC-005 | TASK-016 | Yes | Yes | `:250` endpoint con nonce válido → exit 0 |
| AC-010 | SC-005 | TASK-015, 016 | Yes | Yes | `:250-255`: `score=100`, `passed=1` enviados con respuesta incorrecta → 0 intentos aprobados |
| AC-011 | SC-006 | TASK-015 | Yes | Yes | `QuizScoreCalculatorTest` `8/10 @80 → passed` |
| AC-012 | SC-007 | TASK-015 | Yes | **No** | `7/10 @80 → failed` pasa, pero con umbrales no alcanzables exactamente un score inferior aprueba: `2/3 @67`, `159/200 @80` (CODE-001) |
| AC-013 | SC-008 | TASK-006, 007, 010, 032 | Yes | Yes | `:200` intento + snapshot de pregunta y respuesta |
| AC-014 | SC-009 | TASK-019, 031, 032 | Yes | Yes | `:212` segundo intento con límite 1 → `Rejected`, 1 fila |
| AC-015 | SC-011 | TASK-021-023 | Yes | Partial | Servicio probado (`:130-133`); el disparo real tras la última lección no tiene prueba (CODE-004) |
| AC-016 | SC-012 | TASK-021, 033 | Yes | Yes | `:163-166` |
| AC-017 | SC-013 | TASK-024, 037 | Yes | Yes | `:195-202` aprobar → `completed` |
| AC-018 | SC-014 | TASK-025 | Yes | Yes | `EnrollmentRepository::completeByUserAndCourse` (`UPDATE … WHERE status='active'`); `:134` |
| AC-019 | SC-014 | TASK-025, 040 | Yes | Yes | `:135` reevaluación no cambia `completed_at` |
| AC-020 | SC-015 | TASK-026 | Yes | Yes | `:131` `canAccessCourse` tras completar |
| AC-021 | SC-016 | TASK-002 | Yes | Yes | Meta `_kaanbal_certificate_enabled` independiente del quiz |
| AC-022 | SC-016 | TASK-027, 035 | Yes | Yes | `:150-155` render con mensaje de certificado. Arnés: también en curso con quiz aprobado |
| AC-023 | SC-018 | TASK-029 | Yes | Static | `grep -riE "pdf\|download"` en `src/` y `templates/` sin resultados |
| AC-024 | SC-017 | TASK-028, 035 | Yes | Yes | `:156-161` |
| AC-025 | SC-019 | TASK-038 | Yes | Yes | `:217-218` porcentaje igual antes y después de 3 intentos |
| AC-026 | SC-020 | TASK-016, 036 | Yes | Yes | `:250-255` `user_id` forjado → el intento es del usuario autenticado |
| AC-027 | SC-021 | TASK-016, 017, 036 | Yes | Yes | `:204` y `:261` quiz de otro curso → `Rejected`/403; `:191` respuesta de otra pregunta → `Rejected` |

Casos límite:

| EC | Estado | Evidencia / finding |
|---|---|---|
| EC-001 | OK | `CourseCompletionPolicy.php:11` (`total <= 0 → false`) |
| EC-002, EC-003 | OK | `hasValidQuestions()` |
| EC-004 | OK | Umbral inclusivo |
| EC-005 | OK | Score del cliente ignorado |
| EC-006 | OK | Rechazado |
| EC-007 | Datos coherentes, UX no | Lock + transacción; un doble clic tras aprobar muestra 403 (CODE-002) |
| EC-008 | OK | `AlreadyPassed` |
| EC-009 | Sin prueba | CODE-005 |
| EC-010 | Mensaje incorrecto | CODE-002 |
| EC-011, EC-012 | OK | |

---

## 4. Verificación de Tasks

| Tasks | Resultado | Observaciones |
|---|---|---|
| TASK-001, 002, 003 | PASS | Configuración por meta; CPT no público |
| TASK-004 a 007 | PASS | Cuatro tablas vía `dbDelta`, `DATABASE_SCHEMA` 3 → 4, índices y `UNIQUE KEY user_quiz_attempt` |
| TASK-008 a 010 | PASS | Consultas preparadas; lote de respuestas por preguntas |
| TASK-011, 012 | PASS con observaciones | Crear quiz/preguntas funciona; sin edición, borrado ni orden (CODE-003) |
| TASK-013 | PASS | Validado al crear y en elegibilidad |
| TASK-014, 019, 020 | PASS | CODE-003 sobre validación de `max_attempts` |
| TASK-015 | **FAIL** | CODE-001 |
| TASK-016, 017, 018 | PASS | Pertenencia quiz → curso, pregunta → quiz (solo se leen preguntas activas del quiz) y respuesta → pregunta |
| TASK-021, 022, 025, 040 | PASS | Transición atómica e idempotente |
| TASK-023, 024 | PASS con observación | Integración implementada; TASK-023 sin prueba del flujo real (CODE-004) |
| TASK-026 a 029 | PASS | Mensajes y ausencia de descargas |
| TASK-030 | PASS con observación | Falta el caso `79 → failed` del plan §27 (CODE-005) |
| TASK-031 a 038 | PASS con observaciones | CODE-004, CODE-005 |
| TASK-039 | PASS | `GET_LOCK` + `START TRANSACTION` + `FOR UPDATE` + `UNIQUE`; re-comprobación de límite y aprobado dentro del lock (`QuizAttemptRepository.php:39-89`) |
| TASK-041 | PASS | Sin PDFs, descargas, correo ni analytics |
| TASK-042 a 044 | PASS con observaciones | Quality gate reproducido (§8); CODE-006 |

---

## 5. Findings

### CODE-001 — El score se redondea antes de compararlo con el passing score: alumnos por debajo del mínimo aprueban

Severity: Medium
Blocking: Yes
Classification: Behavioral Defect
Status: NEW

Affected requirement:

`RF-013`, `RF-014`, `RF-015`, `RB-007`, `AC-011`, `AC-012`; indirectamente `AC-017`/`RF-024` (completion) y `RF-029` (promesa de certificado)

Affected scenario:

`SC-006`, `SC-007`

Affected task:

`TASK-015`, `TASK-030`

Affected files:

- `src/Quiz/Application/QuizScoreCalculator.php:16-18`
- `src/Quiz/Infrastructure/QuizAttemptRepository.php:60` (persistencia del score)
- `tests/Unit/QuizScoreCalculatorTest.php`

Description:

`calculate()` hace `$percentage = (int) round(($correct / $total) * 100)` y después `passed = $percentage >= $passing_score`. RF-013 define el score como `correct / total * 100`, y RF-015 dice que reprueba si `score < passing_score`. Al redondear primero, cualquier score real que quede a menos de 0.5 puntos por debajo del mínimo sube hasta el mínimo y aprueba.

Evidence:

Arnés del auditor con la clase real:

```text
2/3     (real 66.67%) passing 67 => score 67 PASSED
7/9     (real 77.78%) passing 78 => score 78 PASSED
11/12   (real 91.67%) passing 92 => score 92 PASSED
159/200 (real 79.50%) passing 80 => score 80 PASSED
79/100  (real 79.00%) passing 80 => score 79 failed
8/10    (real 80.00%) passing 80 => score 80 PASSED
```

Las pruebas unitarias solo usan totales de 10 preguntas (`8/10`, `7/10`), donde el redondeo no se nota. El caso `79 → failed` del plan §27 no está.

Además, la columna `score` es `decimal(5,2)` (`EnrollmentSchemaMigration`), pero se persiste el entero redondeado. El historial pierde la precisión que el esquema sí soporta (RNF-008).

Expected:

`passed` si y solo si `correct / total * 100 >= passing_score` con el valor real (inclusivo, RB-007).

Actual:

Aprueba con `round(correct / total * 100) >= passing_score`.

Impact:

Es la decisión central de la SPEC. Un alumno que no alcanzó el mínimo:

1. queda con un intento `passed`;
2. su matrícula pasa a `completed`, que según RB-010 no se revierte automáticamente;
3. si el curso tiene certificado, se le promete su entrega.

Ocurre con passing scores que no son alcanzables exactamente con el número de preguntas: 67% con 3, 6 o 9 preguntas; 78% con 9; 92% con 12. Son configuraciones plausibles.

Required correction:

1. Decidir la aprobación con aritmética entera exacta, p. ej. `$correct * 100 >= $passing_score * $total`, o comparar el valor real no redondeado.
2. Persistir el score con dos decimales (`round(…, 2)`), acorde a `decimal(5,2)`. El redondeo para mostrar queda a criterio de la UI.
3. Añadir casos unitarios de borde: `2/3 @67 → failed`, `159/200 @80 → failed`, `79/100 @80 → failed`, `160/200 @80 → passed`.

---

### CODE-002 — Estados del alumno engañosos o sin retroalimentación en la evaluación

Severity: Medium
Blocking: No
Classification: Behavioral Defect / Recommendation (UX)
Status: NEW

Affected requirement:

`EC-007`, `EC-010`, `RB-010`; objetivo §1 ("informar al alumno")

Affected task:

`TASK-014`, `TASK-027`, `TASK-028`

Affected files:

- `templates/frontend/course.php:73-116`
- `src/Quiz/Application/QuizEligibilityService.php:26-55`
- `src/Quiz/Presentation/Frontend/QuizSubmissionAction.php:36-38`

Description:

1. **Intentos agotados y quiz inválido muestran el mensaje equivocado.** La plantilla solo distingue `already_passed` y `eligible`. Todo lo demás cae en `elseif ($quiz['requires_quiz'])` (`:114-115`) y muestra "Completa todas las lecciones para desbloquear tu evaluación final.", aunque el alumno ya haya completado todas las lecciones. Pasa con `attempts_exhausted` (EC-010) y con `invalid_quiz`: el curso requiere quiz, pero no hay quiz válido publicado, o sus preguntas están mal formadas. En este último caso el alumno no puede completar el curso y nada le indica por qué.
2. **Sin retroalimentación al reprobar.** Tras un intento `failed`, la redirección vuelve al curso y se muestra otra vez la tarjeta "Demuestra lo que aprendiste". El alumno no ve su score, que reprobó, ni cuántos intentos le quedan.
3. **Doble envío tras aprobar muestra un error.** Un segundo POST (doble clic o reenvío) llega con `AlreadyPassed` → `Rejected` → `wp_die(403, "No puedes presentar este quiz.")`. Los datos quedan coherentes (un solo intento, un solo completion), pero la última respuesta que ve el navegador es un error justo después de aprobar. En SPEC-005 el caso equivalente (`AlreadyCompleted`) redirige con normalidad.
4. **Alumno ya aprobado tras un cambio de curriculum.** `check()` evalúa el progreso (`:36-40`) antes de `hasPassed` (`:46`). Si se agrega una lección después de aprobar, el alumno (matrícula `completed`, RB-010) ve "Completa todas las lecciones para desbloquear tu evaluación final." en lugar de su aprobación.
5. **Sin JavaScript no se puede presentar la evaluación.** El formulario vive dentro de un `<dialog>` cerrado (`:93`) que solo abre `assessment.js`.

Evidence:

Arnés del auditor con la plantilla real (texto de la sección "Evaluación final"):

```text
[attempts_exhausted] ○ Completa todas las lecciones para desbloquear tu evaluación final.
[invalid_quiz]       ○ Completa todas las lecciones para desbloquear tu evaluación final.
[lessons_incomplete tras aprobar y agregarse lección] ○ Completa todas las lecciones para desbloquear…
[already_passed]     ✓ Has completado y aprobado el curso. En breve nos estaremos comunicando contigo…
```

Impact:

Confusión del alumno en los momentos más sensibles del curso: reprobar, quedarse sin intentos, aprobar. Probables tickets de soporte. No afecta la integridad de los datos.

Required correction (sugerida):

- Mensajes específicos para `attempts_exhausted` ("Agotaste tus intentos…") e `invalid_quiz` ("La evaluación aún no está disponible…").
- Mostrar el resultado del último intento (score y aprobado/reprobado) e intentos restantes.
- Tratar `AlreadyPassed` en el endpoint como éxito idempotente (redirigir), igual que SPEC-005.
- Consultar `hasPassed` antes que el progreso para el estado mostrado.
- Opcional: formulario visible sin JS.

---

### CODE-003 — Administración del quiz incompleta y con errores silenciosos

Severity: Medium
Blocking: No
Classification: Implementation Defect / Recommendation (UX)
Status: NEW

Affected requirement:

`RF-005`, `RF-009`, `EC-003` ("hasta corregirse"), `plan.md §21` ("Debe permitir: … orden"), `RF-002`

Affected task:

`TASK-011`, `TASK-012`, `TASK-019`

Affected files:

- `src/Quiz/Presentation/Admin/QuizMetaBoxes.php`
- `src/Quiz/Infrastructure/QuizRepository.php:44-80`

Description:

1. **No se pueden editar, borrar, desactivar ni reordenar preguntas o respuestas.** `renderQuestions()` solo lista y permite "Add question". El orden es el de creación (`position = count(activeForQuiz)`), así que RF-005 se cumple en determinismo, pero el plan §21 pide poder configurar el orden. Un error tipográfico o una respuesta correcta mal marcada no tiene corrección posible desde la UI; la columna `active` existe pero nada la usa.
2. **Pregunta inválida descartada en silencio.** Si al pulsar "Add question" falta texto, faltan respuestas o la correcta está vacía, `saveQuestion()` añade `kaanbal_quiz_error=question` a la URL (`:128-133`), pero ningún código lo lee: `grep -rn kaanbal_quiz_error src` solo encuentra la escritura. El administrador no se entera de que su pregunta no se guardó.
3. **Segundo quiz para el mismo curso desasignado en silencio.** Si ya hay otro quiz publicado para el curso, `saveQuiz()` borra el meta de curso (`:92-93`) sin aviso. Además, `QuizEligibilityService` acepta cualquier quiz publicado que pertenezca al curso (`belongsToCourse`), mientras `CourseCompletionService` solo mira `findForCourse()` (el de menor ID). Si la guarda se rompe (p. ej. por meta directo, como hace el fixture de la prueba), se podría aprobar un quiz que no completa el curso.
4. **RF-009: `0` y valores inválidos se convierten en "ilimitado".** RF-009 declara `0` inválido. `saveQuiz()` usa `absint()` y borra el meta si el resultado es 0, de modo que `0` o `abc` guardan intentos ilimitados en silencio, y `-2` guarda `2`. Lo mismo con el passing score: `-50` → `50`. El `min="1"` del input solo protege en el navegador.
5. **"Requiere quiz" sin quiz válido no avisa.** Marcar el curso como "requiere quiz final" sin quiz publicado válido no genera advertencia en el admin, y los alumnos nunca pueden completar (ver CODE-002 punto 1).

Evidence:

Arnés del auditor con `QuizMetaBoxes::saveQuiz()` real:

```text
max_attempts '3'   => 3
max_attempts '0'   => (meta borrada = ilimitado)
max_attempts '-2'  => 2
max_attempts 'abc' => (meta borrada = ilimitado)
passing '-50'      => 50
```

Impact:

El administrador no puede corregir errores del quiz sin intervenir la base de datos, y puede creer que guardó cosas que no se guardaron. En producción esto se traduce en quizzes con errores que los alumnos presentan.

Required correction (sugerida):

- Edición, desactivación (`active = 0`) y reordenamiento de preguntas.
- Mostrar un `admin_notice` cuando `kaanbal_quiz_error` esté presente y cuando se desasigne un quiz por duplicado.
- Rechazar con aviso `max_attempts` ≤ 0 no vacío y passing scores fuera de 1-100 en lugar de coercionarlos.
- Advertir cuando un curso requiere quiz y no tiene uno válido.
- Opcional: que la elegibilidad use `findForCourse()` como fuente única.

---

### CODE-004 — La finalización del curso al completar la última lección no tiene prueba del flujo real, y la prueba de SPEC-005 quedó obsoleta

Severity: Medium
Blocking: No
Classification: Test Gap / Documentation Mismatch
Status: NEW

Affected requirement:

`AC-015`, `RF-021`, `plan.md §16-17`

Affected task:

`TASK-023`, `TASK-033`, `TASK-034`

Affected files:

- `tests/Integration/final-quiz-course-completion.php:130-133`
- `tests/Integration/student-progress.php:67,136-138`
- `src/Progress/ProgressModule.php`, `src/Progress/Application/CompleteLessonService.php`

Description:

La integración TASK-023 es el flujo principal para cursos sin quiz: `ProgressModule` inyecta `CourseCompletionService` en `CompleteLessonService`, que llama `evaluate()` tras cada lección. Está implementada correctamente, pero ninguna prueba la ejerce:

- `final-quiz-course-completion.php:130-131` escribe el progreso con `LessonProgressRepository::complete()` y llama `evaluate()` a mano.
- La única construcción de `CompleteLessonService` en pruebas (`student-progress.php:67`) no pasa el cuarto argumento, así que `completion` es `null`.

Si se quitara el cableado en `ProgressModule`, ninguna prueba fallaría. `implementation-status.md` declara AC-015 con "integration coverage".

Además, `student-progress.php:136-138` sigue afirmando que alcanzar el 100% **no** cambia la matrícula (AC-020 de SPEC-005). Con SPEC-006, en producción un curso sin quiz sí se completa al 100%. La prueba sigue pasando solo porque construye el servicio sin completion. Hoy prueba una configuración que ya no existe en producción, y lo que afirma contradice RF-021.

Impact:

Regresión no detectable en el flujo principal de cursos sin quiz; confusión sobre qué política está vigente.

Required correction (sugerida):

Una prueba que complete la última lección vía `CompleteLessonService` cableado (o vía el endpoint `admin-post`) y verifique `completed`. Ajustar o anotar la aserción de `student-progress.php` como superada por SPEC-006, p. ej. usando un curso con quiz requerido.

---

### CODE-005 — Casos de prueba del plan ausentes o débiles

Severity: Low
Blocking: No
Classification: Test Gap
Status: NEW

Affected task:

`TASK-030`, `TASK-032`, `TASK-036`

Description:

1. Plan §27 `passing 80 / score 79 → failed`: no está. La prueba usa `7/10`, que no detecta CODE-001.
2. EC-009 "último intento aprobado": el caso limitado (`max_attempts = 1`) solo prueba reprobar.
3. Plan §28 "progress 100 + failed quiz → active": solo a nivel de política unitaria. En integración, tras los intentos fallidos de `limited`/`unlimited` no se consulta el estado de la matrícula.
4. La guarda de un solo quiz publicado por curso (AC-002, "one-published-quiz guard" en el estado) no tiene prueba. El fixture asigna `malformed_quiz` al mismo curso por meta directo (`:127`), así que el curso de prueba tiene dos quizzes publicados.
5. Plan §30 "question from another quiz": no se prueba enviar una clave de pregunta ajena. Por lectura, se ignora sin efecto, lo cual es seguro.
6. Plan §32 "evaluated N times": se evalúa dos veces.

Impact:

Bajo, salvo el punto 1, que habría detectado CODE-001.

Required correction:

Opcional, excepto el punto 1, que conviene incluir con la corrección de CODE-001.

---

### CODE-006 — Trazabilidad documental: archivos protegidos sin versión aprobada previa e inconsistencias de estado

Severity: Low
Blocking: No
Classification: Documentation Mismatch
Status: NEW

Affected files:

- `docs/specs/006-final-quiz-and-course-completion/{spec,plan,feature.feature}` (protegidos, `agents.md §6`)
- `docs/specs/006-final-quiz-and-course-completion/implementation-status.md`
- `docs/specs/005-student-progress/implementation-status.md` (working tree)

Description:

1. `spec.md`, `plan.md` y `feature.feature` entraron a git por primera vez en `cf00b4b`, después de la implementación (`ff1721b`). No existe en el repositorio una versión aprobada previa con la cual verificar que no se ajustaron a la implementación. `spec.md` dice `Status: Ready for audit`, un estado de implementación, no de la SPEC; SPEC-005 decía `Ready for implementation`. Algunos textos coinciden con mucha precisión con decisiones de implementación: RF-009 "La representación persistida es obligatoria", RISK-004/RISK-005 y plan §24. No afirmo que se hayan modificado; no es verificable.
2. `implementation-status.md:23-24`: dependencia `SPEC-002: Ready for audit (implementation available)`, cuando SPEC-002 fue auditada con PASS.
3. La sección "Open Findings" describe decisiones de diseño, no findings. "Human Review" declara la implementación "approved by the user" antes de la auditoría.
4. En el working tree hay un cambio sin commitear en `005-student-progress/implementation-status.md` (`Status: Completed`), con `Ready for audit: Yes` todavía.

Impact:

La cadena de trazabilidad SPEC → implementación depende de que el humano confirme la versión de los documentos protegidos.

Required correction:

Que Daniel confirme que `spec.md`, `plan.md` y `feature.feature` en `cf00b4b` son las versiones aprobadas. En adelante, commitear la SPEC aprobada antes de implementar. Corregir el estado de la dependencia SPEC-002 y commitear o descartar el cambio de SPEC-005.

---

### CODE-007 — La finalización solo se evalúa en eventos del alumno; la UI de cursos sin quiz se deriva del progreso, no de la matrícula

Severity: Low
Blocking: No
Classification: Recommendation
Status: NEW

Affected requirement:

`RF-024`, `plan.md §16` ("Debe evaluarse al menos…")

Affected files:

- `templates/frontend/course.php:73`
- `src/Progress/Application/CompleteLessonService.php`, `src/Quiz/Application/QuizSubmissionService.php`

Description:

Cumple el mínimo del plan: se evalúa tras completar una lección y tras aprobar. Pero hay cambios que satisfacen la política sin acción del alumno, y en esos casos la matrícula queda `active`:

- el administrador elimina la única lección pendiente;
- el administrador desmarca "requiere quiz" en un curso con alumnos al 100%.

Mientras tanto, para cursos sin quiz la plantilla muestra "Has completado y aprobado el curso" en función del progreso (`:73`), no del estado de la matrícula. La UI y el estado formal pueden divergir, y los reportes de SPEC-009 leerán `active`.

Impact:

Bajo en esta SPEC; relevante para reportes posteriores.

Required correction:

Opcional: evaluar la finalización al renderizar la vista del curso, o derivar el mensaje del estado `completed` de la matrícula. Documentar la decisión.

---

### CODE-008 — Observaciones menores

Severity: Info
Blocking: No
Classification: Recommendation
Status: NEW

- `FrontendRouter` calcula `quiz` también para la vista de lección (`:160`), pero `lesson.php` no lo usa: son consultas de elegibilidad, preguntas y respuestas en cada lección.
- `wp_die` 403 para quiz rechazado usa un único mensaje para todos los motivos: acceso, lecciones, intentos, ya aprobado.
- Las cadenas del admin están en inglés y las del alumno en español. Es el patrón de i18n mixto ya registrado en SPEC-004.

---

## 6. Findings de Rondas Anteriores

No aplica: es la ronda 1 de SPEC-006.

---

## 7. Recomendaciones

Atender en la misma remediación: CODE-001 (obligatorio), y CODE-002 puntos 1-3 y CODE-003 puntos 2 y 4, que son cambios pequeños con alto impacto en la experiencia de alumno y administrador. La edición de preguntas (CODE-003 punto 1) puede ir a backlog si el humano lo decide.

---

## 8. Validación Ejecutada

Entorno: shell remota Ubuntu 22.04 aarch64 con PHP 8.5.11 estático (`curso/.tools/`), usando el `vendor/` existente. Comandos ejecutados sobre `cf00b4b`, cuyo código es idéntico a `ff1721b`.

Comandos:

```text
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
php vendor/bin/phpunit --configuration phpunit.xml.dist
php vendor/bin/phpcs --warning-severity=0 --standard=phpcs.xml.dist
php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --debug
composer validate --strict
git diff --quiet ff1721b cf00b4b -- src tests templates assets kaanbal.php composer.json composer.lock
```

Resultados:

```text
lint:      0 archivos con errores
PHPUnit:   OK (49 tests, 73 assertions)   ← coincide con implementation-status.md
PHPCS:     sin errores
PHPStan:   [OK] No errors (nivel 5)
composer:  ./composer.json is valid
git:       sin diferencias de código entre candidato y HEAD
```

Arnés del auditor (stubs de WordPress, clases reales de `src/` y plantilla real):

```text
[score]          2/3@67 PASSED, 7/9@78 PASSED, 11/12@92 PASSED, 159/200@80 PASSED,
                 79/100@80 failed, 8/10@80 PASSED, 5/6@83 PASSED (correcto), 1/3@33 PASSED (correcto)
[template]       attempts_exhausted / invalid_quiz / lessons_incomplete → "Completa todas las lecciones…"
                 eligible → tarjeta + <dialog> con formulario; already_passed → aprobado + certificado
                 ninguna variante expone is_correct en el HTML
[admin-attempts] '3'→3, '0'→ilimitado, '-2'→2, 'abc'→ilimitado; passing '-50'→50
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT` para `tests/Integration/*.php` (incluida `final-quiz-course-completion.php`), que necesitan `KAANBAL_WP_PATH` y la base MySQL local del equipo de Daniel, inaccesible desde la máquina virtual. Las evalué por lectura. Tampoco pude ejercer contra MySQL real el `GET_LOCK`, la transacción, el `FOR UPDATE` y la restricción `UNIQUE`; la estrategia de concurrencia está verificada por lectura. El `PASS` de integración en `implementation-status.md` es declaración de Codex.

---

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- CODE-001 — El score se redondea antes de compararlo con el passing score: alumnos por debajo del mínimo aprueban

Non-blocking findings:

- CODE-002 — Estados del alumno engañosos o sin retroalimentación en la evaluación
- CODE-003 — Administración del quiz incompleta y con errores silenciosos
- CODE-004 — La finalización al completar la última lección no tiene prueba del flujo real; prueba de SPEC-005 obsoleta
- CODE-005 — Casos de prueba del plan ausentes o débiles
- CODE-006 — Trazabilidad documental: archivos protegidos sin versión aprobada previa e inconsistencias de estado
- CODE-007 — La finalización solo se evalúa en eventos del alumno; la UI de cursos sin quiz se deriva del progreso
- CODE-008 — Observaciones menores

Comentario final:

La arquitectura de SPEC-006 es correcta y la parte de seguridad y concurrencia está bien resuelta. El `FAIL` se debe a un defecto puntual en la regla de aprobación que, en configuraciones plausibles, aprueba y completa el curso de alumnos que no alcanzaron el mínimo. Con CODE-001 corregido y probado, este auditor esperaría `PASS WITH RECOMMENDATIONS` en la ronda 2. Recomiendo aprovechar la remediación para CODE-002 y CODE-003, porque hoy la experiencia de reprobar, agotar intentos o equivocarse al crear preguntas es confusa.
