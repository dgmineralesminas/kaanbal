# Auditoría de Seguridad

SPEC: `006-final-quiz-and-course-completion`
Ronda: `2`
Auditor: Mimo
Commit auditado: `b600fc3`
Fecha: `2026-09-29`

Verdict: `PASS WITH RECOMMENDATIONS`

Audit completeness: `Complete`

Notas de alcance de la ronda:

- `b600fc3` es el commit de implementación registrado en `implementation-status.md` y el candidato de la ronda 2. Verificado que el árbol de trabajo es idéntico a `b600fc3` para `src/`, `tests/`, `templates/`, `assets/` y `kaanbal.php`; los commits posteriores solo añaden documentación.
- Primera auditoría de seguridad de SPEC-006. Los identificadores SEC inician en `SEC-001`.
- Auditoría realizada de forma independiente: los informes de otros auditores en `audits/round-1/` y `audits/round-2/` no fueron consultados.
- La remediación de `CODE-001` (aprobado sin redondeo) se verificó por inspección dentro de esta auditoría.

---

## 1. Resumen

Evaluación de seguridad de la implementación dentro del alcance aprobado.

SPEC-006 añade la superficie de escritura más crítica hasta ahora: la evaluación final y la transición de matrícula a `completed`. Un fallo aquí permitiría aprobar cursos sin responder correctamente o consumir intentos ajenos. La implementación resiste las amenazas previstas:

- puntuación 100% server-side: `is_correct` y `passing_score` provienen de la base de datos; los campos `score`/`passed`/`user_id` enviados por el cliente se ignoran (probado con envíos forjados);
- identidad del actor tomada de la sesión; un `user_id` publicado no altera el dueño del intento (probado);
- CSRF con nonce atado al `quiz_id` concreto, aislado por tests (nonce ausente/inválido con usuario autenticado → rechazo);
- relaciones validadas server-side: quiz→curso, pregunta→quiz, respuesta→pregunta (una respuesta ajena rechaza el envío);
- límite de intentos e "aprobado una vez" revalidados dentro de una transacción con bloqueo `GET_LOCK` + `UNIQUE(user_id, quiz_id, attempt_number)`;
- finalización de curso idempotente y atómica (`UPDATE ... WHERE status = 'active'`, `completed_at` se escribe una sola vez);
- la clave de respuestas correctas no se renderiza en el HTML del formulario.

No se encontró vulnerabilidad explotable dentro del alcance. El único finding es un vacío de prueba sobre un caso del plan.

Blocking findings: `0`
Non-blocking findings: `1`

---

## 2. Superficies Revisadas

- autenticación: revisada — gate `is_user_logged_in()` en el endpoint (incluye `admin_post_nopriv_`)
- autorización / ownership / IDOR: revisadas — actor desde sesión, quiz→curso, pregunta→quiz, respuesta→pregunta, envíos forjados
- CSRF / nonces: revisados — nonce por quiz, verificación server-side, cobertura de tests
- server-side scoring: revisada — `is_correct`, `passing_score`, umbral inclusivo, precisión persistida
- manipulación de intentos: revisada — límite, ilimitados, ya aprobado, concurrencia, snapshots
- manipulación de finalización: revisada — política 100% + quiz, atomicidad, idempotencia de `completed_at`
- XSS / escaping: revisados — preguntas/respuestas en frontend y admin, formularios, JS
- SQL injection / prepared statements: revisados — repositorios de quiz/preguntas/respuestas/intentos
- exposición de datos: revisada — contexto de plantilla, mensajes de error, clave de respuestas
- administración del quiz: revisada — metaboxes con nonce + `edit_post`, sanitización, validación de relaciones
- certificados: N/A funcional — solo mensaje estático, sin artefactos ni descargas (AC-023)

---

## 3. Threat Review

| Superficie | Estado | Evidence |
|---|---|---|
| Authentication | `PASS` | `QuizSubmissionAction::handle()` exige `is_user_logged_in()` antes de procesar (`src/Quiz/Presentation/Frontend/QuizSubmissionAction.php:18-20`); las peticiones anónimas llegan por `admin_post_nopriv_` (`src/Quiz/QuizModule.php:46-47`) y terminan en el mismo `wp_die(403)`. Probado: usuario `0` con nonce válido → rechazo (`tests/Integration/final-quiz-course-completion.php:254`). |
| CSRF | `PASS` | `check_admin_referer('kaanbal_submit_final_quiz_' . $quiz_id, '_kaanbal_nonce')` (`QuizSubmissionAction.php:22`) con nonce equivalente en `templates/frontend/course.php` (`wp_nonce_field('kaanbal_submit_final_quiz_' . $quiz['quiz']->ID, ...)`), atado al quiz concreto. Tests que aíslan el control: nonce ausente e inválido con usuario autenticado → rechazo (`final-quiz-course-completion.php:255-256`). Verificado estáticamente y por inspección de tests. |
| Authorization / Ownership | `PASS` | El actor es siempre `get_current_user_id()` (`QuizSubmissionAction.php:24`); los campos `user_id`/`score`/`passed` del request no se leen en producción (verificado por inspección). Prueba de inyección de identidad: un envío con `user_id` publicado de otra cuenta genera el intento a nombre del actor y ninguna fila extra para la víctima (`final-quiz-course-completion.php:253-254`). |
| IDOR (relaciones) | `PASS` | `QuizRepository::belongsToCourse()` exige quiz publicado y `_kaanbal_course_id` = curso declarado; preguntas vía `activeForQuiz($quiz_id)`; cada respuesta seleccionada debe pertenecer al conjunto de su pregunta o el envío se rechaza (`src/Quiz/Application/QuizSubmissionService.php:43-50`). Probado: quiz de otro curso → Rejected (`:211-212`), respuesta de otra pregunta → Rejected (`:198-199`), pregunta mal formada → InvalidQuiz (`:193-194`). |
| Server-side Scoring | `PASS` | `is_correct` se toma del repositorio, no del cliente (`QuizSubmissionService.php:51-52`); el aprobado se decide con aritmética entera exacta `correct * 100 >= passing_score * total` (`src/Quiz/Application/QuizScoreCalculator.php:15`), umbral inclusivo (RB-007). Prueba de falsificación: envío con respuesta incorrecta + `score=100` + `passed=1` registra el intento con `passed = 0` (`final-quiz-course-completion.php:253-254`). |
| Attempts Integrity | `PASS` | `QuizAttemptRepository::record()` combina `GET_LOCK(user, quiz)`, transacción, `SELECT ... FOR UPDATE`, revalidación de `hasPassed`/`countAttempts` dentro de la transacción y `UNIQUE(user_id, quiz_id, attempt_number)` (`src/Quiz/Infrastructure/QuizAttemptRepository.php:35-85`); el conflicto lanza `DomainException` → Rejected (`QuizSubmissionService.php:62-64`). Límite e "ya aprobado" probados (`final-quiz-course-completion.php:207-208`, `:219-220`). |
| SQL Injection | `PASS` | Todas las consultas usan `$wpdb->prepare` o `$wpdb->insert` (`QuestionRepository`, `AnswerRepository`, `QuizAttemptRepository`); los IDs de entrada pasan por `absint` y los placeholders `IN (...)` se generan por conteo. Verificado estáticamente. |
| XSS | `PASS` | `question_text`/`answer_text` se renderizan con `esc_html` en frontend (`templates/frontend/course.php`) y admin (`QuizMetaBoxes.php`); campos del formulario con `esc_attr`; `assets/js/assessment.js` solo manipula `<dialog>` sin insertar HTML. Sanitización al guardar: `sanitize_textarea_field`/`sanitize_text_field` (`QuizMetaBoxes.php:118-121`). Verificado estáticamente. |
| Course Completion Manipulation | `PASS` | `CourseCompletionPolicy` exige 100% de lecciones y, si hay quiz, intento aprobado (`src/Quiz/Application/CourseCompletionPolicy.php`); `EnrollmentRepository::completeByUserAndCourse()` solo transiciona `active` → `completed` y fija `completed_at` en la misma sentencia (`src/Enrollment/Infrastructure/EnrollmentRepository.php:86-108`), por lo que reevaluaciones no alteran la fecha (AC-018/019, RB-010). Probado: curso con quiz no se completa solo con lecciones (`final-quiz-course-completion.php:170-172`) y la finalización es idempotente (`:206-208`). |
| Data Exposure | `PASS` | El contexto de preguntas solo se construye para usuarios elegibles (`src/Quiz/Application/CourseQuizStatusService.php:27-29`); el template del formulario emite únicamente `id` y `answer_text`, sin `is_correct` (ver SEC-REC-001); los errores del endpoint son genéricos (`QuizSubmissionAction.php:20`, `:28-30`); no existen endpoints de descarga ni artefactos de certificado (AC-023). |

---

## 4. Findings

### SEC-001 — El caso de plan `question from another quiz` no tiene prueba explícita en el endpoint

Severity: `Low`
Blocking: `No`
Classification: `Test Gap`
Status: `NEW`

Affected requirement:

`RNF-003` (enviar preguntas de otro quiz), `AC-027`, `plan.md` §30 (Security Tests: "question from another quiz")

Affected files:

- `tests/Integration/final-quiz-course-completion.php`
- `tests/Integration/support/quiz-endpoint-request.php`
- `src/Quiz/Application/QuizSubmissionService.php`

Attack surface:

`POST /wp-admin/admin-post.php` — `action=kaanbal_submit_final_quiz`, campo `answers[question_id]`

Description:

El control que impide que preguntas ajenas al quiz influyan en la calificación está implementado y es correcto, pero el caso de prueba obligatorio que lo cubre directamente no existe: ninguna petición incluye como clave un `question_id` perteneciente a otro quiz.

Evidence:

Verificado por inspección que hoy la propiedad se cumple estructuralmente: `QuizSubmissionService::submit()` itera únicamente las preguntas de `activeForQuiz($quiz_id)` (`QuizSubmissionService.php:36-38`); las claves extra de `answers` se ignoran y una respuesta que no pertenece a la pregunta rechaza el envío (`:43-50`). Sin embargo:

- el fixture usa `foreign_question` solo para derivar `foreign_answer`, que se envía bajo la clave de la pregunta legítima (`final-quiz-course-completion.php:186-198`) — cubre "answer from another quiz/question", no "question from another quiz";
- `tests/Integration/support/quiz-endpoint-request.php` soporta pares `question:answer`, pero ningún caso envía como clave un `question_id` de `foreign_quiz`;
- los otros 7 casos de `plan.md` §30 sí tienen cobertura explícita (anónimo, identidad forjada, quiz de otro curso, respuesta ajena, score forjado, passed forjado, CSRF ausente/inválido).

Verificado por razonamiento estático de mutación (la mutación no fue ejecutada): si `submit()` iterara `$submitted_answers` y calculara el denominador a partir de las claves enviadas, la suite actual seguiría en verde y un alumno podría omitir preguntas difíciles o inyectar preguntas ajenas para alterar el score.

Attack scenario:

Una regresión futura cambia el bucle para iterar las respuestas enviadas; un alumno envía solo preguntas fáciles (o claves de preguntas de otro quiz con respuesta marcada correcta) y obtiene un score inflado y un curso aprobado indebidamente.

Expected protection:

Prueba de regresión que envíe un `question_id` de otro quiz (por ejemplo `foreign_question`) y afirme que el envío se rechaza o que el score no se ve alterado por esa clave.

Actual behavior:

Control implementado y verificado estáticamente; sin cobertura de regresión que lo asegure.

Impact:

Sin impacto sobre la implementación actual. Riesgo de manipulación silenciosa de calificaciones ante una regresión futura del servicio de envío.

Required correction:

Añadir un caso en `final-quiz-course-completion.php` que envíe `answers` incluyendo la clave `foreign_question` (por ejemplo junto a las respuestas legítimas, y también en aislamiento), afirmando que la presencia de esa clave no modifica el score ni crea filas de respuesta para preguntas ajenas. No requiere modificar código de producción.

---

## 5. Findings de Rondas Anteriores

Ningún finding SEC previo. La remediación de `CODE-001` (auditoría de código) se verificó en esta ronda por inspección: el umbral usa comparación entera exacta y `tests/Unit/QuizScoreCalculatorTest.php` cubre los casos límite 2/3 @ 67, 7/9 @ 78, 159/200 @ 80, 160/200 @ 80 y 79/100 @ 80. Sin objeciones de seguridad.

---

## 6. Recomendaciones de Hardening

### SEC-REC-001 — No exponer `is_correct` en el contexto de plantilla del quiz

Severity: `Info`
Blocking: No
Classification: Recommendation

Description:

`CourseQuizStatusService::forCourse()` adjunta a cada respuesta de cada pregunta el indicador `is_correct` en el array de contexto que llega a las plantillas (`src/Quiz/Application/CourseQuizStatusService.php:29-35`). El template actual NO lo renderiza (verificado: `templates/frontend/course.php` emite solo `answer['id']` y `answer['answer_text']`), por lo que la clave de respuestas no es visible para el alumno hoy.

Evidence:

El indicador es necesario para el cálculo server-side, pero no para la presentación. Mantener la clave de respuestas en el contexto de presentación crea un riesgo latente de exposición: cualquier plantilla futura, tema hijo o depuración que vuelque el array revelaría las respuestas correctas a los alumnos elegibles, anulando la validez de la evaluación.

Benefit:

Reducir el objeto enviado a presentación a `id` + `answer_text` (por ejemplo, excluyendo `is_correct` en `CourseQuizStatusService` o preparando un DTO de presentación) elimina la superficie de filtrado de la clave de respuestas.

Scope note:

La recomendación no representa un requisito de la SPEC actual y no hay vulnerabilidad explotable en el código actual.

---

## 7. Limitaciones

- Ejecución dinámica del fixture de integración: `BLOCKED BY ENVIRONMENT`. `tests/Integration/final-quiz-course-completion.php` requiere `KAANBAL_WP_PATH` con un WordPress vivo y ejecuta el endpoint en subprocesos; no se ejecutó durante esta auditoría. Los tests fueron revisados estáticamente y sus aserciones consideradas significativas (verificado: los casos de rechazo exigen código de salida de rechazo; los envíos forjados de identidad y score afirman el estado persistido en base de datos, no solo la respuesta).
- Suite unitaria ejecutada por el auditor: `composer test` → `OK (54 tests, 83 assertions)` (verificado, 2026-09-29).
- No se realizó explotación dinámica (pentest) contra un sitio en vivo; CSRF, ownership, IDOR, scoring y SQL se verificaron por inspección estática del código y de las pruebas.
- El endurecimiento clickjacking registrado como `SEC-REC-001` en la auditoría de SPEC-005 aplica igualmente al diálogo de evaluación de esta SPEC (mismo `<dialog>` embebible y botón de envío con nonce en página); no se duplica como finding nuevo.
- La remediación de `CODE-001` se verificó por inspección estática y lectura de tests; no se re-ejecutó el cálculo contra una base de datos real.

---

## 8. Conclusión

Verdict:

`PASS WITH RECOMMENDATIONS`

Blocking findings:

- Ninguno.

Findings no bloqueantes:

- `SEC-001` — El caso de plan "question from another quiz" no tiene prueba explícita en el endpoint.

Recommendations:

- `SEC-REC-001` — No exponer `is_correct` en el contexto de plantilla del quiz.

Comentario final:

SPEC-006 protege correctamente su superficie crítica: la calificación y la aprobación se deciden exclusivamente en servidor con datos persistidos, la identidad del intento no es manipulable, el CSRF está atado al quiz y aislado por tests, las relaciones curriculares se validan en cada nivel, los intentos están blindados contra duplicación y agotamiento con mecanismos atómicos, y la finalización del curso es idempotente y no reversible. El único finding es un vacío de prueba menor sobre un caso del plan; la recomendación busca impedir que la clave de respuestas llegue a la capa de presentación en el futuro.
