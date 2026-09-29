# Architecture Audit — SPEC-005 Student Progress

## Audit Information

- **Auditor:** Qwen (Architecture & Maintainability)
- **Round:** 1
- **SPEC:** SPEC-005 — Student Progress
- **Commit audited:** `613672254c89fdb67f87cd021efa2617a6a832f5`
- **Branch:** `feature/spec-005-student-progress`
- **Date:** 2026-09-29

---

## Verdict

**PASS WITH RECOMMENDATIONS**

The implementation respects the approved architecture and maintains good maintainability characteristics. The separation of concerns is clear, authorization is properly delegated to SPEC-004, and the code follows the established patterns from previous SPECs. There is one non-blocking architectural concern regarding the coupling between FrontendRouter and Progress module.

---

## Summary

SPEC-005 implements student progress tracking with a clean architecture that properly separates:

1. **Application layer** (`Progress/Application/`): Business logic for completing lessons and calculating progress
2. **Infrastructure layer** (`Progress/Infrastructure/`): Persistence via LessonProgressRepository
3. **Presentation layer** (`Progress/Presentation/Frontend/`): HTTP endpoint handling via admin-post
4. **Module registration** (`Progress/ProgressModule.php`): WordPress integration

The implementation correctly delegates authorization to `CourseAccessService` (SPEC-004), uses dependency inversion via `LessonProgressStore` interface, and maintains read-only semantics for progress calculation (no percentage persistence). The progress is derived dynamically from the current curriculum, ensuring consistency when course content changes.

---

## Architecture Compliance

### 1. Separation of Concerns — PASS

**Observation:**

The code is organized into clear layers following the established pattern:

```
Progress/
├── Application/
│   ├── CompleteLessonService.php
│   ├── CompleteLessonResult.php
│   ├── CourseProgressService.php
│   ├── CourseProgress.php
│   ├── LessonProgressStore.php (interface)
│   └── ProgressCalculator.php
├── Infrastructure/
│   └── LessonProgressRepository.php
├── Presentation/
│   └── Frontend/
│       └── CompleteLessonAction.php
└── ProgressModule.php
```

**Analysis:**

- Application services contain pure business logic without WordPress HTTP concerns
- Infrastructure handles persistence with proper use of prepared statements
- Presentation layer handles HTTP concerns (nonce, redirect, wp_die)
- Module wires everything to WordPress hooks

**Verdict:** PASS

---

### 2. Authorization Boundary — PASS

**Observation:**

`CompleteLessonService` delegates authorization to `CourseAccessService`:

```php
if (! $this->access->canAccessCourse($user_id, $course_id)) {
    return CompleteLessonResult::AccessDenied;
}
```

**Analysis:**

- Authorization is delegated to SPEC-004's `CourseAccessService` (RNF-008)
- Progress module doesn't make independent authorization decisions
- The service validates lesson hierarchy before completing (RF-007)
- Ownership is enforced via `get_current_user_id()` in the endpoint (RF-005)

**Verdict:** PASS

---

### 3. Dependency Direction — PASS

**Observation:**

Dependencies flow correctly:

```
Presentation → Application → Infrastructure
                    ↓
              Access (SPEC-004)
              Courses (SPEC-002)
```

**Analysis:**

- `CompleteLessonService` depends on `CourseAccessService` (interface from SPEC-004)
- `CourseProgressService` depends on `CurriculumService` (SPEC-002) and `LessonProgressStore` (interface)
- `LessonProgressRepository` implements `LessonProgressStore` interface
- No circular dependencies detected
- Progress module doesn't modify Enrollment or Courses (read-only for calculation)

**Verdict:** PASS

---

### 4. Dependency Inversion — PASS

**Observation:**

`LessonProgressStore` interface enables testability:

```php
interface LessonProgressStore
{
    public function complete(int $user_id, int $lesson_id): bool;
    public function findCompletedLessonIds(int $user_id, array $lesson_ids): array;
}
```

**Analysis:**

- Application layer depends on abstraction, not concrete implementation
- `LessonProgressRepository` implements the interface
- Tests can inject mock implementations
- Follows the same pattern as `EnrollmentLookup` from SPEC-003

**Verdict:** PASS

---

### 5. Idempotency Strategy — PASS

**Observation:**

The implementation uses multiple layers of idempotency:

1. **Database constraint:**
   - `UNIQUE KEY user_lesson (user_id, lesson_id)` in schema

2. **Application logic:**
   - `INSERT IGNORE` in `LessonProgressRepository::complete()`
   - Returns `false` if row already exists

3. **Service layer:**
   - `CompleteLessonService` returns `AlreadyCompleted` if `complete()` returns false

**Analysis:**

- The idempotency strategy is comprehensive and defense-in-depth
- Database constraint is the final safety net
- Application logic prevents unnecessary operations
- The pattern is consistent with SPEC-003 enrollment idempotency

**Verdict:** PASS

---

### 6. Schema Migration — PASS

**Observation:**

The lesson_progress table is added to `EnrollmentSchemaMigration`:

```php
dbDelta(
    "CREATE TABLE {$progress} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        lesson_id bigint(20) unsigned NOT NULL,
        completed_at datetime NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY lesson_id (lesson_id),
        UNIQUE KEY user_lesson (user_id, lesson_id)
    ) {$charset_collate};"
);
```

**Analysis:**

- Uses `dbDelta()` for safe table creation
- Proper indexes for performance
- Unique constraint prevents duplicates
- Schema version bumped to 3
- Migration is idempotent

**Verdict:** PASS

---

### 7. Progress Calculation — PASS

**Observation:**

Progress is derived dynamically from the current curriculum:

```php
public function forCourse(int $user_id, int $course_id): CourseProgress
{
    $curriculum = $this->curriculum->forPublishedCourse($course_id);
    // ... extract lesson_ids from curriculum
    $completed = $this->progress->findCompletedLessonIds($user_id, $lesson_ids);
    
    return new CourseProgress(
        count($lesson_ids),
        count($completed),
        $this->calculator->percentage(count($completed), count($lesson_ids)),
        $completed,
    );
}
```

**Analysis:**

- Progress is calculated on-the-fly, not persisted (RNF-005, DEC-004)
- Uses current published curriculum (RF-020)
- Orphan completions are ignored (RF-021)
- Percentage is derived via `ProgressCalculator` (pure function)
- No course_id stored in lesson_progress (DEC-003)

**Verdict:** PASS

---

### 8. Module Registration — PASS

**Observation:**

`ProgressModule` implements `BootableService` and is registered in `Plugin::boot()`:

```php
final class ProgressModule implements BootableService
{
    public function register(): void
    {
        $action = new CompleteLessonAction(new CompleteLessonService(
            new CourseAccessService(new EnrollmentRepository()),
            new CurriculumService(new CurriculumRepository()),
            new LessonProgressRepository(),
        ));

        add_action('admin_post_' . CompleteLessonAction::ACTION, array($action, 'handle'));
        add_action('admin_post_nopriv_' . CompleteLessonAction::ACTION, array($action, 'handle'));
    }
}
```

**Analysis:**

- Follows the same pattern as other modules
- Registration order is correct (Courses → Player → Progress → WooCommerce)
- Uses `admin_post` hooks for form submission
- Registers both authenticated and nopriv handlers (nopriv will fail authentication)

**Verdict:** PASS

---

### 9. Endpoint Security — PASS

**Observation:**

`CompleteLessonAction` implements proper security:

```php
public function handle(): void
{
    $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

    if ($lesson_id <= 0 || ! is_user_logged_in()) {
        wp_die(..., array('response' => 403));
    }

    check_admin_referer('kaanbal_complete_lesson_' . $lesson_id, '_kaanbal_nonce');

    $result = $this->service->complete(get_current_user_id(), $course_id, $lesson_id);
    // ...
}
```

**Analysis:**

- Authentication required (`is_user_logged_in()`)
- Nonce verification (`check_admin_referer`)
- Ownership enforced via `get_current_user_id()` (not from request)
- Input sanitization with `absint()`
- Proper error handling with `wp_die()`

**Verdict:** PASS

---

### 10. Integration with SPEC-004 — PASS (with concern)

**Observation:**

`FrontendRouter` (from SPEC-004) now depends on `CourseProgressService`:

```php
public function __construct(
    ?CurriculumService $curriculum = null,
    ?CourseAccessService $access = null,
    ?LessonNavigationService $navigation = null,
    ?string $templates_path = null,
    ?CourseProgressService $progress = null
) {
    // ...
    $this->progress = $progress ?? new CourseProgressService($this->curriculum, new LessonProgressRepository());
}
```

And passes progress to templates:

```php
'context'  => array(
    'curriculum' => $curriculum,
    'progress'   => $this->progress->forCourse($user_id, $course->ID),
),
```

**Analysis:**

- The integration works correctly
- Progress is calculated and passed to templates
- Templates use progress to display completion status

**Concern:**

`FrontendRouter` (in Access module) now directly instantiates `LessonProgressRepository` (from Progress module). This creates a dependency from Access → Progress, which violates the principle that modules should be independent.

However, this is mitigated by:

1. The dependency is on the infrastructure layer (repository), not the application layer
2. The integration is necessary for the player to display progress
3. The alternative (passing progress via hooks or events) would be more complex
4. This is a pragmatic choice for the current scope

**Recommendation:**

See ARCH-001 below.

**Verdict:** PASS (with concern)

---

## Detailed Findings

### ARCH-001 — FrontendRouter directly instantiates Progress infrastructure (Low)

**Severity:** Low  
**Blocking:** No  
**Category:** Module Coupling

**Description:**

`FrontendRouter` (in Access module) directly instantiates `LessonProgressRepository` (from Progress module):

```php
$this->progress = $progress ?? new CourseProgressService($this->curriculum, new LessonProgressRepository());
```

This creates a dependency from Access → Progress infrastructure.

**Analysis:**

This violates the principle that modules should be independent. Ideally:

- Access module should not know about Progress implementation details
- Progress module should provide progress data via a contract
- The wiring should happen at the composition root (Plugin.php)

However:

- The dependency is on the infrastructure layer (repository), which is more stable
- The integration is necessary for the player to display progress
- The alternative (passing progress via hooks or events) would be more complex
- This is a pragmatic choice for the current scope
- The constructor accepts an optional parameter, so tests can inject mocks

**Recommendation:**

Consider extracting a progress provider interface in the Access module:

```php
namespace Kaanbal\Access\Application;

interface ProgressProvider
{
    public function forCourse(int $user_id, int $course_id): array;
}
```

Then have Progress module implement it, and wire it in Plugin.php. This would decouple Access from Progress implementation details.

However, this is a low-priority refactoring. The current approach is acceptable for the current scope and can be improved in a future SPEC if the codebase grows.

**Impact:** Low. The coupling is contained and doesn't affect correctness or testability.

---

### ARCH-002 — ProgressModule instantiates dependencies directly (Low)

**Severity:** Low  
**Blocking:** No  
**Category:** Maintainability

**Description:**

`ProgressModule::register()` creates the entire dependency graph:

```php
$action = new CompleteLessonAction(new CompleteLessonService(
    new CourseAccessService(new EnrollmentRepository()),
    new CurriculumService(new CurriculumRepository()),
    new LessonProgressRepository(),
));
```

**Analysis:**

This is a service locator pattern that makes it harder to:

- Swap implementations for testing
- Configure dependencies externally
- Use dependency injection containers

However:

- The module is the composition root for Progress
- The dependencies are concrete implementations (not interfaces)
- The pattern is consistent with other modules (PlayerModule, WooCommerceModule)
- Introducing a full DI container would be over-engineering for this scope

**Recommendation:**

No action needed. The current approach balances simplicity and maintainability. If the codebase grows and dependency management becomes complex, consider extracting a factory or using a lightweight container.

**Impact:** Low. The module is the composition root, and the dependencies are stable.

---

### ARCH-003 — CompleteLessonAction uses wp_die for errors (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Consistency

**Description:**

`CompleteLessonAction::handle()` uses `wp_die()` for error responses:

```php
if ($lesson_id <= 0 || ! is_user_logged_in()) {
    wp_die(esc_html__('You cannot complete this lesson.', 'kaanbal'), '', array('response' => 403));
}
```

**Analysis:**

This is a standard WordPress pattern for admin-post handlers. However:

- `wp_die()` terminates execution immediately
- It's not easily testable without output buffering
- The error message is generic (doesn't distinguish between different failure modes)

However:

- This is consistent with WordPress conventions
- The endpoint is simple and doesn't require complex error handling
- The service returns detailed results, but the endpoint simplifies them for the user

**Recommendation:**

No action needed. The current approach is appropriate for the scope. If the endpoint becomes more complex, consider returning JSON or using a more structured error handling approach.

**Impact:** Info. No functional impact.

---

### ARCH-004 — ProgressCalculator uses integer rounding (Info)

**Severity:** Info  
**Blocking:** No  
**Category:** Precision

**Description:**

`ProgressCalculator::percentage()` returns an integer:

```php
return (int) round(($completed_lessons / $total_lessons) * 100);
```

**Analysis:**

This means:

- 1/3 = 33% (not 33.33%)
- 2/3 = 67% (not 66.67%)
- 199/200 = 100% (not 99.5%)

This is noted in CODE-002 from the code audit.

**Recommendation:**

This is a design decision, not an architectural issue. The current approach is simple and user-friendly. If precision becomes important in the future, consider returning a float or a more detailed result object.

**Impact:** Info. The rounding behavior is documented in the code audit.

---

## Compliance with Approved Architecture

| Principle | Status | Notes |
|-----------|--------|-------|
| Modular structure | PASS | Clear separation of Progress module |
| Authorization boundary | PASS | Delegates to CourseAccessService (SPEC-004) |
| Dependency inversion | PASS | LessonProgressStore interface used |
| Idempotency | PASS | UNIQUE constraint + INSERT IGNORE |
| No percentage persistence | PASS | Calculated dynamically from curriculum |
| Testability | PASS | Dependencies injectable, pure functions |
| WordPress integration | PASS | admin-post hooks, standard patterns |
| Schema migration | PASS | dbDelta() with proper constraints |
| Ownership enforcement | PASS | get_current_user_id() used |
| Curriculum validation | PASS | hierarchyForPublishedLesson used |

---

## Comparison with Plan

The implementation closely follows the plan defined in `plan.md`:

| Plan Component | Implementation | Status |
|----------------|----------------|--------|
| LessonProgressRepository | `Progress/Infrastructure/LessonProgressRepository.php` | PASS |
| ProgressCalculator | `Progress/Application/ProgressCalculator.php` | PASS |
| CourseProgressService | `Progress/Application/CourseProgressService.php` | PASS |
| CompleteLessonService | `Progress/Application/CompleteLessonService.php` | PASS |
| CompleteLessonResult | `Progress/Application/CompleteLessonResult.php` (enum) | PASS |
| CourseProgress | `Progress/Application/CourseProgress.php` (DTO) | PASS |
| Write endpoint | `Progress/Presentation/Frontend/CompleteLessonAction.php` | PASS |
| Module registration | `Progress/ProgressModule.php` | PASS |
| Schema migration | `Shared/Database/EnrollmentSchemaMigration.php` (version 3) | PASS |

The implementation adds appropriate details not explicitly in the plan:

- Enum for `CompleteLessonResult` (type safety)
- DTO for `CourseProgress` (structured result)
- Interface for `LessonProgressStore` (testability)
- Proper error handling with `wp_die()`

---

## Integration with Previous SPECs

### SPEC-001 (Foundation)

- `ProgressModule` implements `BootableService` interface
- Registered in `Plugin::boot()` via `ServiceRegistry`
- Uses WordPress hooks established by foundation
- Schema migration uses `SchemaManager` from SPEC-001

**Status:** PASS

### SPEC-002 (Courses and Curriculum)

- Uses `CurriculumService` for course/module/lesson resolution
- Uses `ContentTypes` constants for post types
- Reuses `CurriculumRepository` for data access
- Progress calculation uses published curriculum only

**Status:** PASS

### SPEC-003 (WooCommerce Enrollment)

- Uses `EnrollmentRepository` for enrollment lookup
- Doesn't modify enrollment state (read-only)
- Progress is independent of enrollment status (except for access check)

**Status:** PASS

### SPEC-004 (Course Access and Player)

- Uses `CourseAccessService` for authorization
- Integrates with `FrontendRouter` for progress display
- Templates receive progress in context
- Progress is calculated but not written during player navigation

**Status:** PASS (with concern, see ARCH-001)

---

## Conclusion

The SPEC-005 implementation demonstrates strong architectural discipline:

**Strengths:**

1. Clear separation of concerns across all layers
2. Authorization properly delegated to SPEC-004's `CourseAccessService`
3. Dependency inversion via `LessonProgressStore` interface
4. Idempotency enforced at multiple layers (constraint + application)
5. Progress calculated dynamically (no persistence of derived data)
6. Testable design with injectable dependencies
7. Consistent with patterns established in SPEC-001, SPEC-002, SPEC-003, SPEC-004
8. Proper ownership enforcement via `get_current_user_id()`
9. Curriculum validation using `hierarchyForPublishedLesson`
10. Schema migration with proper constraints and indexes

**Non-blocking findings:**

- ARCH-001: FrontendRouter directly instantiates Progress infrastructure (Low)
- ARCH-002: ProgressModule instantiates dependencies directly (Low)
- ARCH-003: CompleteLessonAction uses wp_die for errors (Info)
- ARCH-004: ProgressCalculator uses integer rounding (Info)

The implementation is maintainable, extensible, and ready to support future SPECs that will build on the progress foundation (course completion in SPEC-006, certificates in SPEC-007, etc.).

The non-blocking findings (ARCH-001 to ARCH-004) are minor and don't affect the correctness or maintainability of the code. They represent opportunities for future refinement if the codebase grows in complexity.

---

## Audit Result

```
Verdict: PASS WITH RECOMMENDATIONS
Blocking findings: 0
Recommendations: 2 (Low severity)
Informational notes: 2
```

**The implementation respects the approved architecture and is maintainable.**

---

## Findings from Previous Rounds

### Round 1

- Code audit: FAIL (CODE-001 blocking - missing test for "user A tries user B")
- No security audit in round 1
- Architecture audit: PASS WITH RECOMMENDATIONS (this report)

The blocking finding from the code audit (CODE-001) is a test gap, not an architectural issue. The architecture correctly enforces ownership via `get_current_user_id()`, but the test coverage is incomplete.

All other findings from the code audit (CODE-002 to CODE-008) are non-blocking and don't affect the architectural assessment.
