# Implementation Status

SPEC: SPEC-005 — Student Progress

Branch: feature/spec-005-student-progress
Current commit: 0a666ddc6f9dc9fc5c5bba6bf7bea3132abbff48

Status: Ready for audit
Ready for audit: Yes
Current audit round: 2

## Human Approval

SPEC approved for implementation: Yes

---

## Dependencies

SPEC-001:
Passed

SPEC-002:
Passed

SPEC-003:
Passed

SPEC-004:
Passed

---

## Tasks

Total: 36
Done: 36
In Progress: 0
Pending: 0
Blocked: 0
Not Applicable: 0

---

## Quality Gate

PHP Syntax:

`PASS — composer lint`

Unit Tests:

`PASS — composer test (40 tests, 60 assertions)`

Integration Tests:

`PASS — wordpress-lifecycle, courses-curriculum, course-access-player and woocommerce-enrollment`

Progress Tests:

`PASS — student-progress (persistencia, ownership, idempotencia, porcentaje, curriculum dinámico y 100%)`

Security Tests:

`PASS — endpoint admin-post: nonce válido, nonce ausente/inválido, anónimo, user_id forjado, sin matrícula, curso/lección incompatibles, lección inválida y rechazos sin escritura`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (0 errors)`

Manual Verification:

`Previously approved by user; not repeated as part of this code-audit candidate.`

---

## Acceptance Criteria

AC-001: IMPLEMENTED — automated integration coverage
AC-002: IMPLEMENTED — automated integration coverage
AC-003: IMPLEMENTED — automated integration coverage
AC-004: IMPLEMENTED — automated integration coverage
AC-005: IMPLEMENTED — automated integration coverage
AC-006: IMPLEMENTED — automated endpoint integration coverage with forged user_id
AC-007: IMPLEMENTED — automated integration coverage
AC-008: IMPLEMENTED — automated unit and integration coverage
AC-009: IMPLEMENTED — automated unit and integration coverage
AC-010: IMPLEMENTED — automated unit and integration coverage
AC-011: IMPLEMENTED — CourseProgressService only derives from curriculum lessons
AC-012: IMPLEMENTED — rendered pending and completed course-template coverage
AC-013: IMPLEMENTED — rendered course-template coverage
AC-014: IMPLEMENTED — rendered pending and completed lesson-template coverage
AC-015: IMPLEMENTED — rendered form and endpoint coverage
AC-016: IMPLEMENTED — automated duplicate-request coverage
AC-017: IMPLEMENTED — automated integration coverage
AC-018: IMPLEMENTED — automated integration coverage
AC-019: IMPLEMENTED — automated integration coverage
AC-020: IMPLEMENTED — automated integration coverage

---

## Progress Policy

Completion:

`Manual`

Progress formula:

```text
completed valid lessons / total valid lessons × 100
```

Quiz included in percentage:

`No`

Stored percentage:

`No`

Undo completion:

`Not supported in MVP`

Course automatically completed at 100% lessons:

`No`

---

## Persistence Policy

Expected uniqueness:

```text
UNIQUE(user_id, lesson_id)
```

Course ID stored in lesson progress:

`No, unless approved through Change Request`

---

## Security Requirements

Expected:

- authenticated user
- server-side access verification
- CSRF protection
- ownership validation
- curriculum relationship validation
- prepared queries
- idempotent write

---

## Known Issues

None.

---

## Open Findings

Round 1 remediation is complete in `0a666dd`:

- CODE-001: endpoint test now sends a forged `user_id` and proves that only the authenticated user receives progress.
- CODE-002: intermediate percentages are capped at 99%; 100% is emitted only when all current lessons are complete.
- CODE-003: the TOCTOU decision is documented below.
- CODE-004 and CODE-005: endpoint and completed-state UI coverage were extended.
- CODE-006 and CODE-008: implementation decisions are documented below.
- CODE-007: endpoint errors are handled with a user-facing response; the unused redirect query parameter was removed.

No round-2 audit has been executed. This is the candidate for that review.

---

## Change Requests

None.

---

## Human Review

Ready for code audit, round 2.

---

## Notes

Implementation was approved and completed. The stable code candidate for audit
round 2 is `0a666ddc6f9dc9fc5c5bba6bf7bea3132abbff48`; this status document
records its verification evidence.

### Concurrency and TOCTOU

The completion flow validates, in order, the published lesson, its published
Lesson → Module → Course hierarchy, and the active enrollment immediately before
writing. The database enforces `UNIQUE(user_id, lesson_id)` and the repository
uses one `INSERT IGNORE`, so concurrent requests cannot duplicate progress. A
revocation occurring after the access check and before that statement is the
accepted residual window for this MVP.

### Repository API decision

`LessonProgressRepository` intentionally exposes batch lookup instead of
`isCompleted()` or `countCompleted()`. `CourseProgress` answers completion from
the already-loaded batch, avoiding one query per lesson and preserving RNF-006.

### Lifecycle fixture decision

The lifecycle integration no longer requires WooCommerce to be inactive. The
lifecycle contract is independent of optional integrations, while the separate
WooCommerce fixture verifies the active integration path.

A progress value of 100% only means that all current lessons have been completed.

It does not mean that the course itself is academically completed because course completion will also depend on the final quiz in a later SPEC.
