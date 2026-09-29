# Architecture Audit — SPEC-004 Course Access and Player

## Audit Information

- **Auditor:** Qwen (Architecture & Maintainability)
- **Round:** 2
- **SPEC:** SPEC-004 — Course Access and Player
- **Commit audited:** `7ab80cd` (code and tests), `81408d0` (documentation)
- **Branch:** `feature/spec-004-course-access-and-player`
- **Date:** 2026-09-29

---

## Verdict

**PASS**

The implementation respects the approved architecture and maintains good maintainability characteristics. The separation of concerns is clear, authorization is properly centralized, and the code follows the established patterns from previous SPECs.

---

## Summary

SPEC-004 implements the course access and player functionality with a clean architecture that properly separates:

1. **Application layer** (`Access/Application/`): Authorization logic, navigation, video rendering
2. **Presentation layer** (`Access/Presentation/Frontend/`): Routing, template context
3. **Module registration** (`Access/PlayerModule.php`): WordPress integration
4. **Templates** (`templates/frontend/`): View rendering

The implementation correctly centralizes authorization in `CourseAccessService`, uses the `EnrollmentLookup` interface for dependency inversion, and maintains read-only semantics (no progress writes). The routing strategy uses WordPress rewrite rules and `template_include` filter as specified in DEC-008.

---

## Architecture Compliance

### 1. Separation of Concerns — PASS

**Observation:**

The code is organized into clear layers:

```
Access/
├── Application/
│   ├── CourseAccessService.php
│   ├── CourseAccessResult.php
│   ├── LessonNavigationService.php
│   └── YouTubeEmbedRenderer.php
├── Presentation/
│   └── Frontend/
│       ├── FrontendRouter.php
│       └── TemplateContext.php
└── PlayerModule.php
```

**Analysis:**

- Application services contain pure business logic without WordPress dependencies (except where necessary)
- Presentation layer handles routing and template context
- PlayerModule wires everything to WordPress hooks
- Templates are separate from business logic

**Verdict:** PASS

---

### 2. Authorization Boundary — PASS

**Observation:**

`CourseAccessService` is the single source of truth for access decisions:

```php
final class CourseAccessService
{
    public function check(int $user_id, int $course_id): CourseAccessResult
    {
        if ($user_id <= 0) {
            return CourseAccessResult::NotAuthenticated;
        }
        // ...
        return match ($enrollment['status']) {
            'active', 'completed' => CourseAccessResult::Granted,
            'revoked'             => CourseAccessResult::Revoked,
            default               => CourseAccessResult::NotEnrolled,
        };
    }
}
```

**Analysis:**

- Authorization is centralized in a single service (RNF-002)
- The enum `CourseAccessResult` provides type safety
- `FrontendRouter::resolve()` calls `CourseAccessService::check()` before resolving lessons (L104-108)
- Templates don't make independent authorization decisions
- No duplication of access logic across controllers/templates

**Verdict:** PASS

---

### 3. Dependency Direction — PASS

**Observation:**

Dependencies flow correctly:

```
Presentation → Application → Enrollment (via interface)
                    ↓
              Courses (read-only)
```

**Analysis:**

- `CourseAccessService` depends on `EnrollmentLookup` interface, not concrete implementation
- `FrontendRouter` depends on `CourseAccessService`, `CurriculumService`, `LessonNavigationService`
- `PlayerModule` wires dependencies at registration time
- No circular dependencies detected
- Access module doesn't modify Enrollment or Courses (read-only)

**Verdict:** PASS

---

### 4. WooCommerce Decoupling — PASS

**Observation:**

The player module doesn't consult WooCommerce:

- `CourseAccessService` uses `EnrollmentLookup` (from SPEC-003)
- No direct references to `WC_Order`, `WC_Product`, or WooCommerce functions
- `PlayerModule` doesn't register any WooCommerce hooks

**Analysis:**

- Access decisions are based on enrollment status, not order status (DEC-004)
- The player is independent of WooCommerce runtime
- If WooCommerce is deactivated, the player still works for existing enrollments
- This follows the approved architecture from `docs/architecture.md` §16

**Verdict:** PASS

---

### 5. Read-Only Semantics — PASS

**Observation:**

The implementation doesn't write progress:

- No calls to progress-related repositories
- No database writes in `FrontendRouter`, `CourseAccessService`, or templates
- `TemplateContext` only stores data for rendering
- Integration tests verify enrollment state doesn't change (AC-021)

**Analysis:**

- The player is strictly read-only (RNF-004, DEC-007)
- No progress tracking, no "last seen" updates, no lesson completion
- This prevents scope creep into SPEC-005 territory
- The architecture enforces this separation naturally

**Verdict:** PASS

---

### 6. Testability — PASS

**Observation:**

The architecture supports testing:

- `CourseAccessService` accepts `EnrollmentLookup` interface
- `FrontendRouter` accepts optional dependencies in constructor
- `LessonNavigationService` is a pure function (curriculum in, navigation out)
- `YouTubeEmbedRenderer` is a pure function (provider/source in, HTML out)

**Analysis:**

- Unit tests can inject mock implementations
- `CourseAccessServiceTest` uses a fake `EnrollmentLookup`
- `LessonNavigationServiceTest` tests pure logic
- `YouTubeEmbedRendererTest` tests HTML generation
- Integration tests use real WordPress functions

**Verdict:** PASS

---

### 7. Module Registration — PASS

**Observation:**

`PlayerModule` implements `BootableService` and is registered in `Plugin::boot()`:

```php
final class PlayerModule implements BootableService
{
    public function register(): void
    {
        $router = new FrontendRouter();
        add_action('init', array(self::class, 'registerRewriteRules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueStyles'));
        add_filter('query_vars', array($router, 'queryVars'));
        add_filter('template_include', array($router, 'template'));
    }
}
```

**Analysis:**

- Follows the same pattern as `CoursesModule` and `WooCommerceModule`
- Registration order in `Plugin::boot()` is correct (Courses → Player → WooCommerce)
- Hooks are registered with appropriate priorities
- The module is self-contained and can be disabled independently

**Verdict:** PASS

---

### 8. Routing Strategy — PASS

**Observation:**

The routing uses WordPress rewrite rules and `template_include` (DEC-008):

```php
add_rewrite_rule(
    '^courses/([^/]+)/lesson/([^/]+)/?$',
    'index.php?' . FrontendRouter::COURSE_QUERY_VAR . '=$matches[1]&' . FrontendRouter::LESSON_QUERY_VAR . '=$matches[2]',
    'top'
);
```

**Analysis:**

- URLs are clean and SEO-friendly: `/courses/{slug}/lesson/{slug}/`
- Authorization happens before template selection (RNF-001)
- 403/404 responses are handled correctly
- `nocache_headers()` is called for unauthorized responses
- The strategy is compatible with any theme

**Verdict:** PASS

---

### 9. Template Architecture — PASS

**Observation:**

Templates use `TemplateContext` for data passing:

```php
$context = \Kaanbal\Access\Presentation\Frontend\TemplateContext::all();
$curriculum = $context['curriculum'] ?? null;
```

**Analysis:**

- Templates don't execute queries or business logic
- Data is prepared by `FrontendRouter` and passed via `TemplateContext`
- Proper escaping is used throughout (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`)
- Templates use `get_header()` and `get_footer()` for theme compatibility
- The `phpcs:ignore` comments are justified (WordPress thumbnail markup, renderer output)

**Verdict:** PASS

---

### 10. IDOR Protection — PASS

**Observation:**

The implementation validates lesson-course hierarchy:

```php
$hierarchy = $this->curriculum->hierarchyForPublishedLesson($lesson->ID);

if (! is_array($hierarchy) || $course->ID !== $hierarchy['course']->ID) {
    return $this->notFound();
}
```

**Analysis:**

- Lessons are validated against the course context (AC-016, RF-017)
- Cross-course lesson access returns 404 (not 403, to avoid information leakage)
- The validation uses `CurriculumService` from SPEC-002
- Authorization is checked before lesson resolution (L104-108)

**Verdict:** PASS

---

## Detailed Findings

### ARCH-001 — TemplateContext uses static state (Low)

**Severity:** Low
**Blocking:** No
**Category:** Testability

**Description:**

`TemplateContext` uses a static property to store values:

```php
final class TemplateContext
{
    private static array $values = array();

    public static function replace(array $values): void
    {
        self::$values = $values;
    }
}
```

**Analysis:**

Static state can make testing more difficult because state persists across tests. However:

- The context is replaced (not appended) on each request
- WordPress loads each request in isolation
- Integration tests verify the correct context is passed
- The alternative (passing context through WordPress filters) would be more complex

**Recommendation:**

No action needed. The current approach is pragmatic for WordPress and doesn't cause issues in practice. If testing becomes problematic, consider injecting context via a service or using WordPress's `set_query_var()`.

**Impact:** Low. The static state is contained and doesn't leak across requests.

---

### ARCH-002 — FrontendRouter instantiates dependencies directly (Low)

**Severity:** Low
**Blocking:** No
**Category:** Maintainability

**Description:**

`FrontendRouter` creates default instances in the constructor:

```php
$this->curriculum = $curriculum ?? new CurriculumService(new CurriculumRepository());
$this->access     = $access ?? new CourseAccessService(new EnrollmentRepository());
```

**Analysis:**

This is a service locator pattern that makes it harder to:

- Swap implementations for testing
- Configure dependencies externally
- Use dependency injection containers

However:

- The constructor accepts optional parameters, so tests can inject mocks
- The defaults are sensible for production use
- The pattern is consistent with other modules in the codebase
- Introducing a full DI container would be over-engineering for this scope

**Recommendation:**

No action needed. The current approach balances simplicity and testability. If the codebase grows and dependency management becomes complex, consider extracting a factory or using a lightweight container.

**Impact:** Low. The constructor parameters allow testing, and the defaults work for production.

---

### ARCH-003 — PlayerModule::registerRewriteRules is static (Info)

**Severity:** Info
**Blocking:** No
**Category:** Consistency

**Description:**

`registerRewriteRules` is a static method:

```php
public static function registerRewriteRules(): void
{
    add_rewrite_rule(...);
}
```

But it's called via `add_action('init', array(self::class, 'registerRewriteRules'))`.

**Analysis:**

This is a minor inconsistency:

- Other methods in `PlayerModule` are instance methods
- The static method doesn't access instance state, so it could be an instance method
- WordPress allows both patterns

**Recommendation:**

No action needed. The static pattern is valid and doesn't cause issues. If consistency becomes important, convert to an instance method.

**Impact:** Info. No functional impact.

---

### ARCH-004 — Rewrite rules lifecycle (Info)

**Severity:** Info
**Blocking:** No
**Category:** Technical Debt

**Description:**

This finding aligns with CODE-006 from the code audit:

- Rewrite rules are registered on `init` but flushed only on activation
- Existing installations upgrading to SPEC-004 won't get the rules until reactivation
- Deactivation doesn't clean up the rules

**Analysis:**

This is a known WordPress plugin development challenge:

- Flushing rewrite rules on every request is expensive
- The current approach is standard for WordPress plugins
- The issue is documented in CODE-006

**Recommendation:**

Address in CODE-006 remediation. Consider:

- Version-based flush on plugin upgrade
- Explicit documentation for manual permalink refresh
- Accept the limitation as standard WordPress behavior

**Impact:** Info. The issue is documented and doesn't block the SPEC.

---

## Compliance with Approved Architecture

| Principle | Status | Notes |
|-----------|--------|-------|
| Modular structure | PASS | Clear separation of Access module |
| Authorization boundary | PASS | Single source of truth in CourseAccessService |
| Dependency inversion | PASS | EnrollmentLookup interface used |
| WooCommerce decoupling | PASS | Player doesn't consult WooCommerce |
| Read-only semantics | PASS | No progress writes |
| Testability | PASS | Dependencies injectable, pure functions |
| WordPress integration | PASS | Standard hooks and patterns |
| Routing strategy | PASS | Rewrite rules + template_include |
| Template separation | PASS | Templates don't contain business logic |
| IDOR protection | PASS | Lesson-course hierarchy validated |
| Output escaping | PASS | Contextual escaping throughout |

---

## Comparison with Plan

The implementation closely follows the plan defined in `plan.md`:

| Plan Component | Implementation | Status |
|----------------|----------------|--------|
| CourseAccessService | `Access/Application/CourseAccessService.php` | PASS |
| CourseAccessResult | `Access/Application/CourseAccessResult.php` (enum) | PASS |
| Frontend routing | `Access/Presentation/Frontend/FrontendRouter.php` | PASS |
| Lesson navigation | `Access/Application/LessonNavigationService.php` | PASS |
| YouTube renderer | `Access/Application/YouTubeEmbedRenderer.php` | PASS |
| Templates | `templates/frontend/{course,lesson,access-denied,not-found}.php` | PASS |
| Module registration | `Access/PlayerModule.php` | PASS |

The implementation adds appropriate details not explicitly in the plan:

- `TemplateContext` for passing data to templates
- Enum for `CourseAccessResult` (type safety)
- Proper 403/404 handling with `nocache_headers()`
- CSS enqueuing for player styles

---

## Integration with Previous SPECs

### SPEC-001 (Foundation)

- `PlayerModule` implements `BootableService` interface
- Registered in `Plugin::boot()` via `ServiceRegistry`
- Uses WordPress hooks established by foundation

**Status:** PASS

### SPEC-002 (Courses and Curriculum)

- Uses `CurriculumService` for course/module/lesson resolution
- Uses `ContentTypes` constants for post types
- Reuses `CurriculumRepository` for data access

**Status:** PASS

### SPEC-003 (WooCommerce Enrollment)

- Uses `EnrollmentLookup` interface for access decisions
- Uses `EnrollmentRepository` (implements `EnrollmentLookup`)
- Doesn't modify enrollment state (read-only)

**Status:** PASS

---

## Conclusion

The SPEC-004 implementation demonstrates strong architectural discipline:

**Strengths:**

1. Clear separation of concerns across all layers
2. Authorization properly centralized in `CourseAccessService`
3. Dependency inversion via `EnrollmentLookup` interface
4. Read-only semantics enforced (no progress writes)
5. Testable design with injectable dependencies
6. Consistent with patterns established in SPEC-001, SPEC-002, SPEC-003
7. Proper IDOR protection with hierarchy validation
8. Clean routing strategy using WordPress standards
9. Templates separated from business logic
10. Proper output escaping throughout

**No blocking architectural issues were found.**

The implementation is maintainable, extensible, and ready to support future SPECs that will build on the player foundation (progress tracking in SPEC-005, quizzes in SPEC-006, etc.).

The non-blocking findings (ARCH-001 to ARCH-004) are minor and don't affect the correctness or maintainability of the code. They represent opportunities for future refinement if the codebase grows in complexity.

---

## Audit Result

```
Verdict: PASS
Blocking findings: 0
Recommendations: 2 (Low severity)
Informational notes: 2
```

**The implementation respects the approved architecture and is maintainable.**

---

## Findings from Previous Rounds

### Round 1

- No architecture audit was performed in round 1
- Code audit: FAIL (CODE-001 blocking - missing tests)
- No security audit in round 1

### Round 2

- Code audit: PASS WITH RECOMMENDATIONS (CODE-001 resolved)
- Security audit: PASS WITH RECOMMENDATIONS (SEC-001 non-blocking)
- Architecture audit: PASS (this report)

All blocking findings from round 1 have been resolved. The remaining findings are non-blocking and documented in the respective audit reports.
