# Architecture Audit — SPEC-008 Course Administration and Reporting

## Audit Information

- **Auditor:** Qwen (Architecture & Maintainability)
- **Round:** 3
- **SPEC:** SPEC-008 — Course Administration and Reporting
- **Commit audited:** `213ef01f23fab61458895271da6f3223dbe6857c`
- **Branch:** `feature/spec-008-course-administration-and-reporting`
- **Date:** 2026-09-30

---

## Verdict

**PASS**

La implementación respeta la arquitectura aprobada y mantiene buenas características de mantenibilidad. La separación de responsabilidades es clara, el módulo es read-only por diseño, y la integración con módulos existentes (Enrollment, Progress, Quiz) sigue los patrones establecidos. No hay findings bloqueantes.

---

## Summary

SPEC-008 implementa el sistema de reportes administrativos como una capa de lectura que consume servicios de módulos existentes. La arquitectura sigue el patrón de módulos independiente establecido en SPECs anteriores:

1. **Application layer** (`Reporting/Application/`): Query service y cálculo de métricas
2. **Presentation layer** (`Reporting/Presentation/Admin/`): Página administrativa
3. **Module registration** (`Reporting/ReportingModule.php`): Integración con WordPress admin

La implementación es estrictamente read-only, reutiliza `CourseProgressService` de SPEC-005, `QuizValidityService` de SPEC-006, y deriva el estado formal de `Enrollment.status` (no del porcentaje). El rendimiento es eficiente con consultas batch y paginación server-side.

---

## Architecture Compliance

### 1. Separation of Concerns — PASS

**Observation:**

El módulo Reporting está organizado en capas claras:

```
Reporting/
├── Application/
│   ├── CourseReportingQuery.php
│   └── CourseReportMetrics.php
├── Presentation/
│   └── Admin/
│       └── CourseReportsPage.php
└── ReportingModule.php
```

**Analysis:**

- `CourseReportingQuery` es responsable de componer el view model desde múltiples servicios
- `CourseReportMetrics` encapsula la lógica de cálculo de métricas (approval rate, average progress)
- `CourseReportsPage` maneja la presentación administrativa (registro de menú, renderizado, autorización)
- `ReportingModule` registra hooks de WordPress y wire dependencies

Cada componente tiene una responsabilidad única y bien definida. No hay mezcla de lógica de presentación con lógica de negocio.

**Verdict:** PASS

---

### 2. Read-Only Design — PASS

**Observation:**

El módulo Reporting es estrictamente read-only:

- `CourseReportingQuery::summary()` y `detail()` solo leen datos, no escriben
- `CourseReportsPage::render()` solo consulta y renderiza
- No hay endpoints de escritura en el módulo
- Los filtros y búsqueda son read-only (no modifican estado)

**Analysis:**

Esto cumple con el principio de que los reportes son vistas, no controladores de acciones. Las acciones administrativas (si las hubiera) ocurrirían en otros módulos a través de sus propios endpoints.

El reporte solo consume:
- `EnrollmentRepository::summariesForCourses()`, `activeUserIdsForCourses()`, `studentsForCourse()` (lectura)
- `CourseProgressService::forUsersOnCourses()` (lectura)
- `QuizRepository::dashboardDetailsForCourses()` (lectura)
- `QuizAttemptRepository::summariesForUsersAndQuizzes()` (lectura)
- `QuizValidityService::validQuizIds()` (lectura)

**Verdict:** PASS

---

### 3. Dependency Direction — PASS

**Observation:**

Las dependencias fluyen correctamente:

```
Reporting → Enrollment (SPEC-003)
Reporting → Progress (SPEC-005)
Reporting → Quiz (SPEC-006)
Reporting → Courses (SPEC-002)
```

**Analysis:**

- Reporting depende de módulos de dominio (Enrollment, Progress, Quiz, Courses)
- No hay dependencias circulares
- Reporting no es dependiente de otros módulos de presentación (Access, Dashboard, WooCommerce)
- La única dependencia cruzada es el uso de `QuizDashboardState` de Dashboard, pero esto es aceptable porque es una enum de estado reutilizable

**Verdict:** PASS

---

### 4. Reuse of Existing Services — PASS

**Observation:**

Reporting reutiliza servicios existentes en lugar de reimplementar lógica:

- `CourseProgressService::forUsersOnCourses()` para progreso (SPEC-005)
- `QuizValidityService::validQuizIds()` para validez de quiz (SPEC-006)
- `QuizDashboardState::fromDashboardData()` para estados del quiz (SPEC-007)
- `EnrollmentRepository` para matrículas (SPEC-003)

**Analysis:**

Esto cumple con el principio de no duplicar lógica de negocio. El reporte usa una única fuente de verdad para:
- Progreso: `CourseProgressService`
- Validez de quiz: `QuizValidityService`
- Estado de quiz: `QuizDashboardState`
- Estado de matrícula: `Enrollment.status`

**Verdict:** PASS

---

### 5. Authorization Boundary — PASS

**Observation:**

La autorización es simple y clara:

```php
public const CAPABILITY = 'manage_options';

public function canAccess(): bool
{
    return current_user_can(self::CAPABILITY);
}

public function render(): void
{
    if (! $this->canAccess()) {
        wp_die(esc_html__('No tienes permisos para consultar estos reportes.', 'kaanbal'), '', array('response' => 403));
    }
    // ...
}
```

**Analysis:**

- Se verifica la capability `manage_options` (administrador)
- La verificación ocurre antes de cualquier renderizado
- El acceso no autorizado termina con `wp_die()` y response 403
- No hay lógica de autorización compleja (no se verifica ownership de cursos, eso lo hace el contexto administrativo)

**Verdict:** PASS

---

### 6. Performance Design — PASS

**Observation:**

El reporte usa consultas batch para evitar N+1:

```php
// Summary
$summaries = $this->enrollments->summariesForCourses($course_ids);
$active_users = $this->enrollments->activeUserIdsForCourses($course_ids);
$progress = $this->progress->forUsersOnCourses($active_users);

// Detail
$page_data = $this->enrollments->studentsForCourse(...);
$user_ids = array_column($page_data['items'], 'user_id');
$progress = $this->progress->forUsersOnCourses(array($course->ID => $user_ids));
$attempts = $this->attempts->summariesForUsersAndQuizzes($user_ids, array($quiz_id));
```

**Analysis:**

- Summary: consultas batch para todos los cursos
- Detail: paginación server-side (25 alumnos por página)
- Progress y attempts se cargan en batch para los usuarios de la página actual
- No hay consultas individuales por alumno dentro del loop

Esto escala bien y cumple con RNF-005 (rendimiento) y RNF-006 (paginación).

**Verdict:** PASS

---

### 7. Metrics Calculation — PASS

**Observation:**

`CourseReportMetrics` encapsula la lógica de cálculo:

```php
final class CourseReportMetrics
{
    public function approvalRate(int $active, int $completed): int
    {
        $relevant = $active + $completed;
        return $relevant <= 0 ? 0 : (int) round(($completed / $relevant) * 100);
    }

    public function averageProgress(array $percentages): int
    {
        return array() === $percentages ? 0 : (int) round(array_sum($percentages) / count($percentages));
    }
}
```

**Analysis:**

- `approvalRate()` usa la fórmula aprobada: `completed / (active + completed) * 100`
- Excluye `revoked` del denominador (cumple DEC-002)
- `averageProgress()` calcula sobre `active` enrollments (cumple DEC-003)
- Ambos métodos manejan casos edge (0 alumnos, array vacío)
- La lógica está centralizada en una clase pura, fácilmente testeable

**Verdict:** PASS

---

### 8. Admin Integration — PASS

**Observation:**

`CourseReportsPage` se integra correctamente con WordPress admin:

```php
public function register(): void
{
    add_submenu_page(
        'edit.php?post_type=kaanbal_course',
        __('Reportes de cursos', 'kaanbal'),
        __('Reportes', 'kaanbal'),
        self::CAPABILITY,
        self::PAGE_SLUG,
        array($this, 'render'),
    );
}
```

**Analysis:**

- Se registra como submenú bajo el CPT de cursos
- Usa `add_submenu_page()` (patrón estándar de WordPress)
- La capability se pasa directamente a WordPress (autorización nativa)
- El slug es único y descriptivo

**Verdict:** PASS

---

### 9. Output Escaping — PASS

**Observation:**

Todo output está escapado correctamente:

```php
echo '<td>' . esc_html((string) $course['title']) . '</td>';
echo '<td>' . esc_html((string) $student['name']) . '</td>';
echo '<td>' . esc_html($progress) . '</td>';
echo '<a href="' . esc_url($this->url(...)) . '">';
```

**Analysis:**

- `esc_html()` para texto
- `esc_url()` para URLs
- `esc_attr()` para atributos HTML
- `wp_date()` para fechas formateadas
- No hay output sin escapar

**Verdict:** PASS

---

### 10. Input Validation — PASS

**Observation:**

Los inputs se validan y sanitizan:

```php
$course_id = absint($this->requestValue('course_id'));
$status = in_array($status, array('all', 'active', 'completed', 'revoked'), true) ? $status : 'all';
$quiz_filter = in_array($quiz_filter, array('all', 'passed', 'not_passed'), true) ? $quiz_filter : 'all';
$search = trim($search);
```

**Analysis:**

- `absint()` para IDs
- Whitelist para filtros (valores permitidos)
- `trim()` para búsqueda
- `sanitize_text_field()` y `wp_unslash()` en `requestValue()`
- Paginación con `max(1, absint(...))`

**Verdict:** PASS

---

### 11. URL Generation — PASS

**Observation:**

Las URLs se generan correctamente con encoding:

```php
private function url(array $parameters = array()): string
{
    $parameters = array_map(static fn (int|string $value): string => rawurlencode((string) $value), $parameters);
    return add_query_arg(array_merge(array('post_type' => 'kaanbal_course', 'page' => self::PAGE_SLUG), $parameters), admin_url('edit.php'));
}
```

**Analysis:**

- `rawurlencode()` para todos los parámetros (resuelve CODE-003 de round 2)
- `add_query_arg()` para construir URLs seguras
- `admin_url()` para base URL administrativa
- Los filtros se conservan en la paginación

**Verdict:** PASS

---

### 12. Date Formatting — PASS

**Observation:**

Las fechas se formatean correctamente:

```php
private function formatDate(?string $date): string
{
    if (null === $date || '' === $date) {
        return '—';
    }

    $timestamp = strtotime($date . ' UTC');

    if (false === $timestamp) {
        return '—';
    }

    return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
}
```

**Analysis:**

- Interpreta `completed_at` como UTC
- Usa `wp_date()` con formatos de WordPress (resuelve CODE-004 de round 2)
- Maneja fechas inválidas o vacías
- Respeta la zona horaria configurada en WordPress

**Verdict:** PASS

---

### 13. Module Registration — PASS

**Observation:**

`ReportingModule` sigue el patrón de módulos establecido:

```php
final class ReportingModule implements BootableService
{
    public function register(): void
    {
        add_action('admin_menu', array(new CourseReportsPage($this->query()), 'register'));
    }
}
```

**Analysis:**

- Implementa `BootableService` (patrón de SPEC-001)
- Registra el menú administrativo en `admin_menu`
- Wire dependencies en el composition root (`query()`)
- Simple y directo

**Verdict:** PASS

---

## Detailed Findings

### ARCH-001 — ReportingModule instantiates dependencies directly (Low)

**Severity:** Low  
**Blocking:** No  
**Category:** Maintainability

**Description:**

`ReportingModule::query()` crea instancias concretas de repositories y services:

```php
private function query(): CourseReportingQuery
{
    $curriculum = new CurriculumService(new CurriculumRepository());

    return new CourseReportingQuery(
        new EnrollmentRepository(),
        new CourseProgressService($curriculum, new LessonProgressRepository()),
        new QuizRepository(),
        new QuizAttemptRepository(),
        new QuizValidityService(new QuestionRepository()),
    );
}
```

**Analysis:**

Esto es el mismo patrón service locator visto en módulos anteriores. Hace más difícil:

- Swap implementations for testing
- Configure dependencies externally
- Use dependency injection containers

Sin embargo:

- El module es el composition root para Reporting
- Las dependencias son implementaciones concretas (no interfaces)
- El patrón es consistente con otros módulos (PlayerModule, ProgressModule, QuizModule, DashboardModule, WooCommerceModule)
- Introducir un DI container completo sería over-engineering para este alcance

**Recommendation:**

No action needed. El enfoque actual balancea simplicidad y mantenibilidad. Si el codebase crece y la gestión de dependencias se vuelve compleja, considerar extraer una factory o usar un container ligero.

**Impact:** Low. El module es el composition root, y las dependencias son estables.

---

### ARCH-002 — Reuse of QuizDashboardState from Dashboard module (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Dependency

**Description:**

`CourseReportingQuery` usa `QuizDashboardState` del módulo Dashboard:

```php
$quiz_state = \Kaanbal\Dashboard\Application\QuizDashboardState::fromDashboardData(
    $quiz['required'],
    $quiz_id,
    is_int($quiz_id) && isset($valid_quizzes[$quiz_id]),
    $course_progress->percentage,
    $attempt['passed'],
    $attempt['attempts_used'],
    $quiz['max_attempts'],
);
```

**Analysis:**

Esto crea una dependencia Reporting → Dashboard. Sin embargo:

- `QuizDashboardState` es una enum de estado reutilizable
- No es una dependencia funcional (no llama a métodos de Dashboard)
- Es aceptable porque representa un concepto compartido (estado del quiz)
- La alternativa sería duplicar la enum o extraerla a un namespace compartido

**Recommendation:**

No action needed para esta SPEC. Si en el futuro hay más módulos que necesitan `QuizDashboardState`, considerar extraerla a un namespace compartido (ej: `Kaanbal\Quiz\Application\QuizState`).

**Impact:** Info. La dependencia es aceptable y no afecta la mantenibilidad.

---

### ARCH-003 — Hardcoded Spanish labels in presentation (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Internationalization

**Description:**

`CourseReportsPage` tiene algunos labels hardcodeados en español:

```php
foreach (array('Curso', 'Alumnos', 'En curso', 'Aprobados', 'Revocados', 'Progreso promedio', 'Tasa de aprobación', 'Acción') as $heading) {
    echo '<th scope="col">' . esc_html($heading) . '</th>';
}
```

**Analysis:**

Esto es consistente con CODE-008 de round 2, que fue marcado como ACCEPTED RISK por decisión humana. Los labels del reporte se muestran en español por ahora.

Sin embargo, otros labels usan `__()` para traducción:

```php
echo '<td>' . esc_html((string) $student['status_label']) . '</td>';
// status_label viene de __('Aprobado', 'kaanbal'), __('Revocado', 'kaanbal'), __('En curso', 'kaanbal')
```

**Recommendation:**

No action needed. La decisión humana fue aceptar los labels en español por ahora. Si se requiere internacionalización completa en el futuro, se puede abordar en una SPEC posterior.

**Impact:** Info. La inconsistencia es conocida y aceptada.

---

## Compliance with Approved Architecture

| Principle | Status | Notes |
|-----------|--------|-------|
| Modular structure | PASS | Clear separation of Reporting module |
| Read-only design | PASS | Reporting only reads, never writes |
| Dependency direction | PASS | Reporting depends on domain modules, not vice versa |
| Reuse of services | PASS | Uses CourseProgressService, QuizValidityService, QuizDashboardState |
| Authorization boundary | PASS | manage_options capability check |
| Performance design | PASS | Batch queries, pagination server-side |
| Metrics calculation | PASS | CourseReportMetrics encapsulates logic |
| Admin integration | PASS | Standard WordPress admin_menu pattern |
| Output escaping | PASS | esc_html, esc_url, esc_attr throughout |
| Input validation | PASS | absint, whitelist, sanitize_text_field |
| URL generation | PASS | rawurlencode + add_query_arg |
| Date formatting | PASS | wp_date with WordPress formats |
| Module registration | PASS | Follows BootableService pattern |

---

## Comparison with Plan

La implementación sigue closely el plan definido en `plan.md`:

| Plan Component | Implementation | Status |
|----------------|----------------|--------|
| CourseReportingQuery | `Reporting/Application/CourseReportingQuery.php` | PASS |
| CourseReportMetrics | `Reporting/Application/CourseReportMetrics.php` | PASS |
| CourseReportsPage | `Reporting/Presentation/Admin/CourseReportsPage.php` | PASS |
| ReportingModule | `Reporting/ReportingModule.php` | PASS |
| Admin menu | `add_submenu_page()` under `edit.php?post_type=kaanbal_course` | PASS |
| Summary query | `summary()` with batch aggregates | PASS |
| Detail query | `detail()` with pagination, filters, search | PASS |
| Batch progress | `forUsersOnCourses()` | PASS |
| Batch quiz | `summariesForUsersAndQuizzes()` | PASS |
| Pagination | 25 per page, server-side | PASS |
| Filters | Status, quiz, search | PASS |
| Metrics | approvalRate, averageProgress | PASS |

La implementación añade detalles apropiados no explícitos en el plan:

- `formatDate()` con `wp_date()` y zonas horarias
- `url()` con `rawurlencode()` para caracteres especiales
- `QuizDashboardState` reutilizado de Dashboard
- `certificate_enabled` indicator en el detalle

---

## Integration with Previous SPECs

### SPEC-001 (Foundation)

- `ReportingModule` implementa `BootableService` interface
- Registrado en `Plugin::boot()` vía `ServiceRegistry`
- Usa WordPress admin hooks establecidos por foundation

**Status:** PASS

### SPEC-002 (Courses and Curriculum)

- Usa `ContentTypes` constants para post types
- Reporting no modifica cursos, solo los lee

**Status:** PASS

### SPEC-003 (WooCommerce Enrollment)

- Usa `EnrollmentRepository` para matrículas y agregaciones
- Reporting no modifica matrículas, solo las lee
- Estado formal viene de `Enrollment.status`

**Status:** PASS

### SPEC-004 (Course Access and Player)

- Reporting no interfiere con el player
- URLs de acceso al curso apuntan al player de SPEC-004

**Status:** PASS

### SPEC-005 (Student Progress)

- Usa `CourseProgressService::forUsersOnCourses()` para progreso
- Reporting no modifica progreso, solo lo lee
- Progreso se deriva de lecciones completadas

**Status:** PASS

### SPEC-006 (Final Quiz and Course Completion)

- Usa `QuizValidityService::validQuizIds()` para validez de quiz
- Reporting no modifica quizzes ni intentos, solo los lee
- Estado del quiz se deriva de intentos y configuración

**Status:** PASS

### SPEC-007 (Student Dashboard)

- Usa `QuizDashboardState` de Dashboard para estados del quiz
- Reporting no interfiere con el dashboard
- Ambos son capas de lectura sobre los mismos datos

**Status:** PASS

---

## Conclusion

La implementación de SPEC-008 demuestra fuerte disciplina arquitectónica:

**Strengths:**

1. Separación clara de concerns a través de todas las capas
2. Diseño read-only estricto (reporting solo lee, nunca escribe)
3. Reutilización de servicios existentes (CourseProgressService, QuizValidityService, QuizDashboardState)
4. Rendimiento eficiente con consultas batch y paginación server-side
5. Cálculo de métricas centralizado en `CourseReportMetrics`
6. Autorización simple y correcta (manage_options capability)
7. Output escaping correcto en todo el renderizado
8. Input validation con whitelist y sanitization
9. URL generation con encoding correcto (rawurlencode)
10. Date formatting con wp_date y zonas horarias de WordPress
11. Consistente con patrones establecidos en SPEC-001 a SPEC-007
12. Integración limpia con WordPress admin (add_submenu_page)

**Non-blocking findings:**

- ARCH-001 (Low): `ReportingModule` instancia dependencias directamente - patrón service locator
- ARCH-002 (Info): Reutiliza `QuizDashboardState` de Dashboard - dependencia aceptable
- ARCH-003 (Info): Labels hardcodeados en español - decisión humana aceptada

La implementación es mantenible, extensible, y lista para soportar futuras SPECs que construirán sobre la foundation de reportes (exportación CSV en SPEC-009, analytics avanzados en SPEC-010, etc.).

Los findings no bloqueantes (ARCH-001 a ARCH-003) son menores y no afectan la correctness o mantenibilidad del código. Representan oportunidades para refinamiento futuro si el codebase crece en complejidad.

---

## Audit Result

```
Verdict: PASS
Blocking findings: 0
Recommendations: 1 (Low severity)
Informational notes: 2
```

**La implementación respeta la arquitectura aprobada y es mantenible.**

---

## Findings from Previous Rounds

### Round 1

- Code audit: PASS WITH RECOMMENDATIONS (CODE-001 a CODE-010)
- No security audit in round 1
- No architecture audit in round 1

### Round 2

- Code audit: PASS WITH RECOMMENDATIONS (CODE-001 a CODE-004 resolved; CODE-005 a CODE-010 accepted risk)
- No security audit in round 2
- No architecture audit in round 2

### Round 3

- Code audit: PASS WITH RECOMMENDATIONS (all blocking findings resolved)
- No security audit in round 3
- Architecture audit: PASS (this report)

Los findings de code audit (CODE-001 a CODE-010) fueron principalmente de documentación, testing y UX. Todos los findings bloqueantes fueron resueltos en round 3. Los findings no bloqueantes fueron aceptados como riesgo por decisión humana.

No hay findings arquitectónicos bloqueantes. La implementación es sólida y sigue los patrones establecidos en SPECs anteriores.
