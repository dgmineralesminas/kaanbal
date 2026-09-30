# Auditoría de Código y Cumplimiento

SPEC: `SPEC-008 — Course Administration and Reporting`
Ronda: `1`
Auditor: Claude Code
Commit auditado: `8c3ec6c3e5ce32d3c49d30ae2eab2a7a8a45c7fc` (branch `feature/spec-008-course-administration-and-reporting`, base `353f9bd`)
Fecha: `2026-09-30`

Verdict: `FAIL`

Audit completeness: `Complete` (revisión estática completa; ejecución de PHPUnit, PHPStan, PHPCS e integración `BLOCKED BY ENVIRONMENT`, ver §8)

## 1. Resumen

La implementación de producción es sólida y cubre casi todo el alcance funcional:

- Métricas agregadas con una sola consulta `GROUP BY`.
- Estado académico tomado de `Enrollment.status`.
- Progreso, intentos y usuarios cargados en lote.
- Filtros por lista blanca, búsqueda con `esc_like` y `prepare`.
- Paginación del lado del servidor con clamp de página fuera de rango.
- Escaping consistente, y ninguna escritura sobre el estado académico.

La prueba de integración verifica bien las métricas, incluida la exclusión de `revoked` del denominador. También acota el número de consultas por página a 16 o menos.

El veredicto es `FAIL` por dos findings bloqueantes:

- **CODE-001:** el estado documental del candidato es contradictorio. `implementation-status.md` declara `Ready for audit: Yes`, pero también declara la SPEC **no aprobada**, las 43 tasks en `Pending`, los 26 AC en `NOT IMPLEMENTED` y el Quality Gate en `NOT RUN`. `spec.md` sigue en `Status: Draft`.
- **CODE-002:** faltan pruebas que `plan.md §33–34` marca como obligatorias: visitante, filtro de estado inválido, quiz reprobado, intentos agotados e intentos ilimitados. Tampoco hay prueba de AC-016.

Hay además un defecto funcional no bloqueante con impacto real (CODE-003). La paginación pierde la búsqueda cuando contiene `+` (común en emails, `nombre+tag@…`) o `&`.

Tasks revisadas: `43`
Acceptance Criteria revisados: `26`
Escenarios Gherkin revisados: `19` (SC-001 a SC-019)

Blocking findings: `2` (CODE-001, CODE-002)
Non-blocking findings: `8` (CODE-003 a CODE-010)

---

## 2. Alcance Auditado

Documentos revisados:

- `spec.md`, `plan.md`, `feature.feature`, `tasks.md` e `implementation-status.md` de SPEC-008, en `8c3ec6c` y en `2a218d4`
- `docs/audit-standard.md`

Código revisado íntegramente en `8c3ec6c`:

- `src/Reporting/ReportingModule.php`
- `src/Reporting/Application/{CourseReportingQuery,CourseReportMetrics}.php`
- `src/Reporting/Presentation/Admin/CourseReportsPage.php`
- `src/Enrollment/Infrastructure/EnrollmentRepository.php`: `summariesForCourses`, `activeUserIdsForCourses`, `studentsForCourse` y `completeByUserAndCourse` (para `completed_at`)
- `src/Progress/Application/{CourseProgressService,LessonProgressStore}.php`, `src/Progress/Infrastructure/LessonProgressRepository.php`
- `src/Quiz/Infrastructure/{QuizAttemptRepository,QuizRepository}.php` (`summariesForUsersAndQuizzes`, `dashboardDetailsForCourses`), `src/Quiz/Application/QuizValidityService.php`
- `src/Dashboard/Application/QuizDashboardState.php` (reutilizado)
- `src/Courses/Application/CurriculumService.php` (`forPublishedCourses`)
- `src/Bootstrap/Plugin.php`, `composer.json`

Pruebas revisadas:

- `tests/Unit/CourseReportMetricsTest.php`
- `tests/Integration/course-administration-reporting.php`

Git: working tree limpio sobre `8c3ec6c`.

---

## 3. Trazabilidad

Leyenda de "Verified":

- **Yes:** hay prueba y aserción que lo comprueba.
- **Partial:** la prueba existe pero es incompleta.
- **Static:** verificado por lectura de código, sin prueba del proyecto.
- **No:** sin prueba ni evidencia.

| AC | Scenario | Implemented | Verified | Evidence |
|---|---|---|---|---|
| AC-001 | SC-001 | Yes | Partial | `canAccess()` es true para administrator y el submenú se registra. `render()` no se ejerce. |
| AC-002 | SC-002 | Yes | Partial | Solo se prueba el subscriber. El visitante (user 0) no, y es obligatorio en `plan §34` → CODE-002. |
| AC-003 | SC-003 | Yes | Yes | Los 3 cursos del fixture aparecen en `summary()`. Solo se incluyen cursos `publish` → CODE-009. |
| AC-004 | SC-003 | Yes | Yes | `total = 4` |
| AC-005 | SC-003 | Yes | Yes | `active = 2` |
| AC-006 | SC-003 | Yes | Yes | `completed = 1` |
| AC-007 | SC-003 | Yes | Yes | `revoked = 1` |
| AC-008 | SC-004 | Yes | Yes | `detail($course)` devuelve un array |
| AC-009 | SC-004 | Yes | Yes | `total = 4`. El curso de paginación tiene otros 26 alumnos que no aparecen. |
| AC-010 | SC-005 | Yes | Partial | La búsqueda por email devuelve el `user_id` correcto, pero no se asertan `name` ni `email`. |
| AC-011 | SC-006 | Yes | Yes | Etiquetas En curso, Aprobado y Revocado asertadas |
| AC-012 | SC-007 | Yes | Yes | 100% frente a 50% para dos alumnos distintos |
| AC-013 | SC-008 | Yes | Yes | `active_full` al 100% con quiz pendiente queda en `En curso` / `Disponible` |
| AC-014 | SC-009 | Yes | Yes | Curso sin quiz: `No aplica`, `attempts_used = null` |
| AC-015 | SC-010 | Yes | Yes | `completed` con intento aprobado queda en `Aprobado` |
| AC-016 | SC-011 | Yes | **No** | No existe ningún intento reprobado en el fixture → CODE-002 |
| AC-017 | SC-012 | Yes | Partial | Se asserta `1 / 3`. Faltan intentos agotados e ilimitados → CODE-002 |
| AC-018 | SC-013 | Yes | Partial | Se asserta el valor crudo en UTC. No se convierte a la zona del sitio → CODE-004 |
| AC-019 | SC-014 | Yes | Partial | Se prueba por email, no por nombre |
| AC-020 | SC-015 | Yes | Partial | Se prueba `completed`. No `active` ni `revoked`, ni un valor inválido (obligatorio) → CODE-002 |
| AC-021 | SC-016 | Yes | Partial | Páginas 1 y 2 sin duplicados y página 99 limitada. La persistencia del filtro no se prueba y falla con `+` y `&` → CODE-003 |
| AC-022 | SC-017 | Yes | Yes | Unit (50/100/0) e integración: 33% y 75% sobre active |
| AC-023 | SC-018 | Yes | Yes | Comparación antes/después de enrollments, progress y attempts |
| AC-024 | SC-002 | Yes | Partial | Solo existe la página de admin (no hay REST/AJAX). Visitante no probado → CODE-002 |
| AC-025 | SC-019 | Yes | Yes | `detail(999999999) === null` y aviso en la UI |
| AC-026 | — | Yes | Yes | 16 consultas o menos por página de 25. Carga en lote de usuarios (JOIN), progreso y attempts. |

Casos límite:

- **EC-001 (curso sin alumnos):** Static. El resumen muestra 0, pero el detalle dice "No hay alumnos que coincidan con los filtros" → CODE-006.
- **EC-003 (alumno eliminado):** Static. `LEFT JOIN` más `Usuario eliminado` / `—`.
- **EC-006:** Static. El estado sale de Enrollment.
- **EC-007 (quiz ilimitado):** Static. Se muestra `n / —`. Sin prueba.
- **EC-009:** Yes.

---

## 4. Verificación de Tasks

Las **43 tasks están en `Status: Pending`** en `tasks.md`, aunque el código de casi todas existe. `TASK-041` (Quality Gate), `TASK-042` (Implementation Status) y `TASK-043` (candidato) no se ejecutaron: el Quality Gate figura como `NOT RUN`. No es posible dar por hechas tasks que el propio documento declara pendientes → CODE-001.

Evaluación del código contra cada task, independiente de su status documentado:

| Tasks | Resultado del código |
|---|---|
| TASK-001 a TASK-011, TASK-013 a TASK-020, TASK-022, TASK-025 a TASK-027, TASK-029, TASK-030, TASK-036 a TASK-040 | Implementado y conforme |
| TASK-012 (filtro de quiz) | Implementado. En un curso sin quiz el selector se muestra igual y devuelve vacío → CODE-007 |
| TASK-021 (certificado) | Conforme. Muestra "contempla certificado externo. Kaanbal no registra su entrega" (RF-016/017). |
| TASK-023 (empty state) | Parcial → CODE-006 |
| TASK-024 (usuario eliminado) | Implementado, sin prueba |
| TASK-028 (tests de autorización) | Incompleta → CODE-002 |
| TASK-031 a TASK-035 (tests de detalle, quiz, búsqueda, filtros y paginación) | Incompletas → CODE-002 y CODE-003 |
| TASK-041 a TASK-043 | No ejecutadas → CODE-001 |

---

## 5. Findings

### CODE-001 — El candidato se declara listo para auditoría, pero su estado documental dice lo contrario

Severity: Medium
Blocking: Yes
Classification: Documentation Mismatch
Status: NEW

Affected task:

TASK-041, TASK-042, TASK-043 (y el status de las 43)

Affected files:

- `docs/specs/008-course-administration-and-reporting/implementation-status.md`
- `docs/specs/008-course-administration-and-reporting/tasks.md`
- `docs/specs/008-course-administration-and-reporting/spec.md`

Description:

El candidato no cumple la precondición de auditoría (`development-workflow.md`, `definition-of-done.md`). Su propio estado documental se contradice.

Evidence:

`implementation-status.md` en `8c3ec6c` dice:

- `Status: Ready for audit`, `Ready for audit: Yes`, `Current audit round: 1`.
- Al mismo tiempo: `SPEC approved for implementation: No`.
- `Human Review: Approved`, aunque no ha habido auditoría ni revisión.
- `Done: 0 / Pending: 43`.
- Los 26 AC en `NOT IMPLEMENTED`.
- Las 10 entradas del Quality Gate en `NOT RUN`.
- La nota final: "SPEC-008 has been defined but is not yet approved for implementation. Implementation must not begin until … explicit human approval".

`spec.md` sigue en `Status: Draft`. En `2a218d4` el mismo archivo decía `SPEC approved for implementation: Yes` y `Human Review: Pending`. El diff de `8c3ec6c` invierte ambos valores, lo que parece un error de edición. `tasks.md` tiene las 43 tasks en `Pending`.

Expected:

- Aprobación humana registrada de forma inequívoca.
- Tasks con su status real.
- AC con su estado de implementación y prueba.
- Quality Gate ejecutado y con resultado.
- Un solo candidato declarado.

Actual:

El documento describe una SPEC sin aprobar y sin implementar, mientras el commit contiene la implementación y declara `Ready for audit`.

Impact:

No se puede establecer, desde la documentación versionada, que la implementación estaba autorizada ni que pasó el Quality Gate. `audit-standard.md §24` cita "documentación de estado falsa" como blocker. El precedente es SPEC-003 ronda 1, CODE-001, donde faltaba un candidato válido.

Required correction:

1. **Decisión humana:** Daniel confirma si SPEC-008 está aprobada. Si lo está, se registra `SPEC approved for implementation: Yes` y `spec.md` pasa de `Draft` a su estado real. Si no, la implementación no debió empezar y se decide cómo proceder.
2. Codex ejecuta el Quality Gate y registra sus resultados.
3. Codex actualiza el status de las 43 tasks y de los 26 AC según la evidencia real.
4. `Human Review` vuelve a su valor real (`Pending`).
5. Se registra el candidato en un commit de documentación.

---

### CODE-002 — Faltan pruebas obligatorias de seguridad y de estados de quiz; AC-016 sin prueba

Severity: Medium
Blocking: Yes
Classification: Test Gap
Status: NEW

Affected requirement:

AC-002, AC-016, AC-017, AC-020, AC-024, EC-007; `plan.md §33` y `§34` ("Obligatorios")

Affected scenario:

SC-002, SC-011, SC-012, SC-015

Affected task:

TASK-028, TASK-032, TASK-034

Affected files:

- `tests/Integration/course-administration-reporting.php`

Description:

`plan.md §34` enumera como **obligatorias** seis pruebas de seguridad; faltan dos:

| Caso obligatorio (§34) | Estado |
|---|---|
| anonymous admin endpoint | **Falta.** No hay `wp_set_current_user(0)` más `canAccess()` o `render()`. |
| student user | Cubierto (L164-167) |
| user without capability | Cubierto (el mismo subscriber) |
| forged course ID | Cubierto (L226) |
| invalid status filter | **Falta.** Ninguna llamada `detail(..., status: 'x')` o `quiz: 'x'`. |
| SQL-like search input | Cubierto (L209) |

`plan.md §33` enumera los casos de quiz; tres no tienen prueba:

- `failed`: no hay ningún intento con `passed = false` en el fixture, así que **AC-016 no tiene evidencia**.
- `attempts exhausted`
- `unlimited attempts` (EC-007)

Evidence:

L124-141 del fixture crean un solo intento: `$attempt($completed, $course, $quiz, true)`.

Por inspección, el código se comporta bien en todos los casos:

- La lista blanca de `detail()` (L82-83) normaliza status y quiz inválidos a `all`.
- `QuizDashboardState` devuelve `FailedCanRetry`, `NoAttemptsLeft` y `max = null` según corresponde.
- `render()` hace `wp_die(403)` sin capability.

Pero ninguna prueba fija ese comportamiento.

Impact:

- AC-016 es un criterio central sin ninguna evidencia (`audit-standard.md §34`).
- La ruta del visitante protege datos personales (RISK-003 y AC-024).
- Una regresión en el mapeo de estados de quiz o en la lista blanca pasaría inadvertida.

Required correction:

En `course-administration-reporting.php`:

1. Con `wp_set_current_user(0)`: `canAccess() === false`.
2. `detail($course, 1, '', 'bogus', 'bogus')`: debe devolver `filters.status === 'all'`, `filters.quiz === 'all'` y `total === 4`.
3. Un alumno active al 100% con un intento reprobado: `quiz_label === 'Reprobado'`.
4. Un alumno active al 100% con 3 intentos reprobados sobre un máximo de 3: `Intentos agotados`.
5. Un curso con quiz sin `_kaanbal_max_attempts`: `max_attempts === null`, y el render muestra `n / —`.

---

### CODE-003 — La paginación pierde o altera la búsqueda cuando contiene `+`, `&` o `#`

Severity: Medium
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

AC-021 ("sin … perder el filtro actual"), RF-018

Affected files:

- `src/Reporting/Presentation/Admin/CourseReportsPage.php` (L186 y L193-196)

Description:

`renderPagination()` construye los enlaces con `add_query_arg(array_merge(..., $detail['filters']), ...)`. `add_query_arg()` **no codifica los valores**. La documentación de WordPress pide pasarlos ya codificados con `rawurlencode`. `esc_url()` no corrige esto.

Evidence:

Ejemplos de lo que ocurre al ir a la página 2:

- Búsqueda `ana+curso@gmail.com`: el enlace lleva `search=ana+curso@gmail.com`, y PHP decodifica `+` como espacio. La búsqueda se convierte en `ana curso@gmail.com` y deja de encontrar al alumno.
- Búsqueda con `&` (por ejemplo `Pérez & Cía`): el valor se corta en el `&`.
- Búsqueda con `#`: todo lo que sigue se trata como fragmento y no llega al servidor.

La página 1 funciona porque el formulario GET sí codifica los valores.

Impact:

El administrador que busca por email con `+` (práctica común con Gmail) o por nombres con `&` ve resultados distintos al paginar. No es un problema de seguridad: la entrada es del propio administrador y la consulta es parametrizada.

Required correction:

Codificar los valores antes de `add_query_arg`, por ejemplo con `array_map('rawurlencode', …)` o `urlencode_deep()` sobre los filtros. Agregar una prueba de render con búsqueda `a+b`.

---

### CODE-004 — La fecha de aprobación se muestra en UTC sin formatear

Severity: Low
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

AC-018, RF-015; `spec.md §14` deja el "formato de fechas" como decisión pendiente, pero el plan no la resuelve.

Affected files:

- `src/Reporting/Presentation/Admin/CourseReportsPage.php` (L138)

Description:

`completed_at` se guarda con `current_time('mysql', true)`, es decir en GMT (`EnrollmentRepository::completeByUserAndCourse`). El reporte lo imprime tal cual: `2026-09-30 17:42:00`. Para un sitio en `America/Mexico_City` son 6 horas de diferencia. Un alumno que aprobó a las 20:00 del día 30 aparece aprobado el día 31.

Required correction (sugerida):

`wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($completed_at . ' UTC'))`, o bien `get_date_from_gmt()`, más el formato del sitio. Registrar la decisión de formato en `plan.md`.

---

### CODE-005 — Un alumno aprobado puede mostrar un quiz pendiente

Severity: Low
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

RB-004, RB-005, RISK-004, EC-006

Affected files:

- `src/Reporting/Application/CourseReportingQuery.php` (L116-124)

Description:

El reporte reutiliza `QuizDashboardState::fromDashboardData()` pero no aplica `isVisibleFor()`. En SPEC-007 esa regla oculta cualquier estado de quiz distinto de `Passed` para matrículas `completed` ("a completed course never advertises a pending quiz").

Ejemplo: un curso que no exigía quiz cuando el alumno lo aprobó y después pasa a exigirlo. El alumno aparece con Estado `Aprobado` y Quiz `No presentado` o `Disponible`. Esa contradicción es justo la ambigüedad que RISK-004 pide evitar.

Required correction (sugerida):

Para `completed`, si el estado no es `Passed`, mostrar `—` o `No aplica`, reutilizando `isVisibleFor()`.

---

### CODE-006 — Estado vacío incorrecto para un curso sin alumnos

Severity: Low
Blocking: No
Classification: Behavioral Defect
Status: NEW

Affected requirement:

EC-001, EC-008

Affected files:

- `src/Reporting/Presentation/Admin/CourseReportsPage.php` (L113-119)

Description:

Con 0 alumnos y sin filtros, el detalle dice "No hay alumnos que coincidan con los filtros." y nunca muestra el contador "0 alumnos" que pide EC-001. Así el administrador no distingue entre "curso sin alumnos" y "mi búsqueda no encontró nada".

Required correction (sugerida):

Mostrar siempre el contador, y un mensaje distinto según haya o no filtros activos.

---

### CODE-007 — El filtro de quiz aparece en cursos sin quiz

Severity: Low
Blocking: No
Classification: Recommendation

Affected files:

- `src/Reporting/Presentation/Admin/CourseReportsPage.php` (L157-159)

Description:

El selector "Quiz: Aprobado / No aprobado" se muestra aunque el curso no requiera quiz. Al elegir una opción se devuelve una lista vacía (`CourseReportingQuery` L94-95), lo que parece un error. Conviene ocultar el selector cuando `quiz_required` es false (RF-020: "cuando corresponda").

---

### CODE-008 — Textos de la tabla no traducibles

Severity: Low
Blocking: No
Classification: Recommendation

Affected files:

- `src/Reporting/Presentation/Admin/CourseReportsPage.php` (L66, L121, L156, L159)

Description:

Los encabezados de columna y las opciones de los selectores se pasan a `esc_html()` como literales, sin `__()`. Por eso no aparecen en el catálogo de traducción del text domain `kaanbal`. Usar `esc_html__()` o envolver los arrays con `__()`.

---

### CODE-009 — El resumen omite cursos no publicados sin decirlo

Severity: Info
Blocking: No
Classification: Documentation Mismatch

Description:

`summary()` y `detail()` filtran `post_status = 'publish'`. Es razonable, pero RF-001 y AC-003 hablan de "los cursos Kaanbal" en general. Un curso en borrador o privado con matrículas históricas desaparece del reporte, y sus alumnos no se pueden consultar. Registrar la decisión en `plan.md`, o incluir los cursos privados.

---

### CODE-010 — Observaciones para arquitectura y seguridad

Severity: Info
Blocking: No
Classification: Future Consideration

Description:

- **Capability:** se usa `manage_options`. `plan §27` lo acepta si no hay infraestructura de capabilities Kaanbal, y no la hay (no existe ningún `add_cap`). Consecuencia: un `shop_manager` de WooCommerce no ve los reportes. Queda para una SPEC futura de permisos.
- **Límite entre módulos:** `EnrollmentRepository::studentsForCourse()` consulta directamente la tabla `kaanbal_quiz_attempts` para el filtro de quiz. Corresponde evaluarlo a Qwen.
- **Carga del resumen:** `summary()` carga con `posts_per_page => -1` y resuelve el progreso de **todos** los alumnos activos de todos los cursos en una sola consulta con `IN (...)`. Es correcto para la escala declarada del proyecto (miles de alumnos, cientos de cursos), pero el resumen no está paginado. Es una observación, no un defecto.

---

## 6. Findings de Rondas Anteriores

No aplica: primera ronda de SPEC-008.

---

## 7. Recomendaciones

Prioridad sugerida:

1. Antes de la ronda 2: CODE-001 (decisión humana y documentación) y CODE-002 (pruebas).
2. Conviene corregir en la misma pasada CODE-003, que es barato y afecta un criterio de aceptación.
3. Después, según el criterio del humano: CODE-004 a CODE-008.

---

## 8. Validación Ejecutada

Comandos o pruebas ejecutadas:

```text
git show / git diff 353f9bd..8c3ec6c (revisión completa del diff)
git show 2a218d4:…/implementation-status.md (comparación de los campos de aprobación)
lectura estática de todos los archivos listados en §2
revisión manual del orden de placeholders de studentsForCourse(): el %d de la subconsulta de quiz va antes del WHERE y array_unshift coloca quiz_id primero, así que el orden es correcto
```

Resultados:

```text
Producción: SQL parametrizado en todas las consultas nuevas; filtros por lista blanca; escaping en todas las salidas; sin escrituras.
Pruebas: la prueba de integración cubre métricas, estados principales, búsqueda por email, filtros completed/passed/not_passed, paginación y el conteo de consultas (16 o menos); faltan los casos de CODE-002.
```

Limitaciones ambientales:

`BLOCKED BY ENVIRONMENT`, igual que en SPEC-004:

- No hay `php` en la shell accesible de la máquina del humano.
- `composer install` falla desde el entorno del auditor.
- No hay WordPress ni base de datos alcanzables.

Aquí esto pesa menos, porque el propio `implementation-status.md` declara el Quality Gate completo como `NOT RUN`. No hay resultados que verificar.

---

## 9. Conclusión

Verdict:

`FAIL`

Blocking findings:

- CODE-001 — El candidato se declara listo para auditoría, pero su estado documental dice lo contrario (aprobación `No`, 43/43 tasks `Pending`, AC `NOT IMPLEMENTED`, Quality Gate `NOT RUN`, `spec.md` en `Draft`)
- CODE-002 — Faltan pruebas obligatorias de seguridad y de estados de quiz; AC-016 sin prueba

Non-blocking findings:

- CODE-003 — La paginación pierde la búsqueda con `+`, `&` o `#` (Medium)
- CODE-004 — Fecha de aprobación en UTC sin formatear
- CODE-005 — Alumno aprobado con quiz pendiente visible
- CODE-006 — Estado vacío de curso sin alumnos
- CODE-007 — Filtro de quiz visible en cursos sin quiz
- CODE-008 — Textos de tabla no traducibles
- CODE-009 — Cursos no publicados omitidos sin documentar (Info)
- CODE-010 — Observaciones para Qwen y Mimo (Info)

Comentario final:

El código de reportes está bien construido y cumple las reglas centrales de la SPEC: fuente única de aprobación, métricas documentadas, consultas en lote, datos protegidos y solo lectura. El `FAIL` se debe a gobernanza y evidencia, no a calidad del código. Hace falta que la aprobación humana quede registrada de forma inequívoca, que el Quality Gate se ejecute y que existan las pruebas obligatorias. Con eso, más CODE-003, espero una ronda 2 en `PASS WITH RECOMMENDATIONS`.
