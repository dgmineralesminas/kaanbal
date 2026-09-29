# Implementation Status

SPEC: SPEC-007 — Student Dashboard

Branch: feature/spec-007-student-dashboard
Current commit: 4fe0f1eab59115f60dd938132e32687092512dd8

Status: In implementation
Ready for audit: No
Current audit round: 0

## Human Approval

SPEC approved for implementation: Yes

---

## Dependencies

SPEC-001:
Completed

SPEC-002:
Completed

SPEC-003:
Completed

SPEC-004:
Completed

SPEC-005:
Completed

SPEC-006:
Completed

---

## Tasks

Total: 40
Done: 38
In Progress: 1
Pending: 1
Blocked: 0
Not Applicable: 0

---

## Quality Gate

PHP Syntax:

`PASS — composer lint`

Unit Tests:

`PASS — composer test (61 tests, 90 assertions)`

Integration Tests:

`PASS — dashboard integration behavior; rewrite persistence rerun pending environment access`

Dashboard Tests:

`PASS — dashboard integration: access, ownership, courses, progress, quiz states, certificate, empty state and read-only behavior`

Security Tests:

`PASS — anonymous access and forged user_id coverage`

Performance Review:

`PASS — batch course/progress loading reviewed; no per-course dashboard service calls`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (0 errors; rerun after final rewrite-only patch pending environment access)`

Manual Verification:

`NOT RUN — visual browser verification unavailable`

---

## Acceptance Criteria

AC-001: IMPLEMENTED — authenticated route and denial template
AC-002: IMPLEMENTED — anonymous request returns 403 without dashboard context
AC-003: IMPLEMENTED — active enrollments appear in the read model
AC-004: IMPLEMENTED — completed enrollments appear as approved
AC-005: IMPLEMENTED — revoked and orphaned courses are excluded
AC-006: IMPLEMENTED — progress counts and percentage from CourseProgressService
AC-007: IMPLEMENTED — no dashboard-owned progress storage
AC-008: IMPLEMENTED — active status label En curso
AC-009: IMPLEMENTED — completed status label Aprobado
AC-010: IMPLEMENTED — Continuar curso action
AC-011: IMPLEMENTED — Ver curso action preserves the player URL
AC-012: IMPLEMENTED — no quiz state for courses without a quiz
AC-013: IMPLEMENTED — locked quiz state
AC-014: IMPLEMENTED — available quiz state
AC-015: IMPLEMENTED — passed quiz state
AC-016: IMPLEMENTED — limited attempts remaining
AC-017: IMPLEMENTED — exhausted attempts state
AC-018: IMPLEMENTED — completed_at is included in the view model
AC-019: IMPLEMENTED — certificate follow-up message
AC-020: IMPLEMENTED — no certificate download or URL
AC-021: IMPLEMENTED — completed course without certificate
AC-022: IMPLEMENTED — empty dashboard state
AC-023: IMPLEMENTED — session identity only; forged user_id ignored
AC-024: IMPLEMENTED — dashboard query is read-only
AC-025: IMPLEMENTED — batched enrollment, course, progress and quiz-attempt reads

---

## Student Status Policy

Enrollment active:

`En curso`

Enrollment completed:

`Aprobado`

Enrollment revoked:

`Excluded from main dashboard`

---

## Progress Policy

Source:

`CourseProgressService`

Stored independently by Dashboard:

`No`

100% means Approved automatically:

`No`

Formal approval source:

`Enrollment.status = completed`

---

## Quiz Dashboard Policy

Possible states:

- Not Required
- Locked
- Available
- Failed / Retry Available
- Passed
- Attempts Exhausted

---

## Certificate Policy

Certificate generated:

`No`

Certificate downloadable:

`No`

Certificate delivered by Kaanbal:

`No`

If applicable, the dashboard only displays the informational follow-up message.

---

## Dashboard Write Policy

Normal dashboard request modifies academic state:

`No`

Expected behavior:

`Read only`

---

## Known Issues

La integración que verifica la regla persistida de `/mis-cursos/` debe repetirse
cuando el límite de ejecución del entorno permita conectar nuevamente con
WordPress/MySQL.

---

## Open Findings

No audit round has been executed. The implementation is not yet an audit
candidate until the pending integration rerun passes.

---

## Change Requests

None.

---

## Human Review

Implementation authorized by the user; human acceptance is pending the final
integration rerun.

---

## Notes

SPEC-007 is implemented on its dedicated branch. The current implementation
commit is `4fe0f1eab59115f60dd938132e32687092512dd8`.
