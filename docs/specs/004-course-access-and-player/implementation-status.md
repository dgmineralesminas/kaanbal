# Implementation Status

SPEC: SPEC-004 — Course Access and Player

Branch: feature/spec-004-course-access-and-player
Current commit: 7ab80cd — test: cubrir rutas protegidas de lecciones

Status: Ready for code audit
Ready for audit: Yes
Current audit round: 2 (pending)

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

---

## Tasks

Total: 33
Done: 33
In Progress: 0
Pending: 0
Blocked: 0
Not Applicable: 0

---

## Quality Gate

PHP Syntax:

`PASS — composer lint`

Unit Tests:

`PASS — 31 tests, 51 assertions`

Integration Tests:

`PASS — course-access-player, courses-curriculum and woocommerce-enrollment fixtures against local WordPress + WooCommerce`

Frontend/Access Tests:

`PASS — active/completed/revoked/unenrolled/anonymous access on Course and Lesson routes, Course/Lesson IDOR, 404 content handling, ordered curriculum, empty course, templates, YouTube and no enrollment writes`

Security Tests:

`PASS — anonymous, unenrolled and revoked users receive 403 without Course, Lesson or Curriculum context; an enrollment in Course A cannot open Course B or its Lesson; forged Course/Lesson requests receive 404; YouTube embeds are constructed only from normalized IDs`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (outside sandbox; local PHPStan socket required)`

Manual Verification:

`PASS — authenticated browser verification of the Course and Lesson views, curriculum links, current-lesson state, player loading and course start CTA.`

---

## Acceptance Criteria

AC-001: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-002: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-003: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-004: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-005: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-006: IMPLEMENTED AND INTEGRATION-TESTED
AC-007: IMPLEMENTED AND INTEGRATION-TESTED
AC-008: IMPLEMENTED AND INTEGRATION-TESTED
AC-009: IMPLEMENTED AND INTEGRATION-TESTED
AC-010: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-011: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-012: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-013: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-014: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-015: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED
AC-016: IMPLEMENTED AND INTEGRATION-TESTED
AC-017: IMPLEMENTED AND INTEGRATION-TESTED
AC-018: IMPLEMENTED AND INTEGRATION-TESTED
AC-019: IMPLEMENTED AND INTEGRATION-TESTED
AC-020: IMPLEMENTED AND INTEGRATION-TESTED
AC-021: IMPLEMENTED; PARTIALLY INTEGRATION-TESTED (no enrollment writes are asserted; no academic-progress storage exists in this SPEC)
AC-022: IMPLEMENTED AND INTEGRATION-TESTED

---

## Access Policy

Access granted:

- enrollment = active
- enrollment = completed

Access denied:

- enrollment = revoked
- no enrollment
- unauthenticated user

WooCommerce is not consulted during normal player authorization.

---

## Progress Writes

Expected:

`NONE`

Course and Lesson views must remain read-only regarding academic progress.

---

## Known Issues

The YouTube-hosted player can still expose native YouTube links and sharing controls. The embed uses the privacy-enhanced domain and reduced permissions, but preventing URL sharing requires a different video provider or hosting model. That decision is outside this SPEC's YouTube-only scope.

---

## Open Findings

CODE-001 from audit round 1 is remediated by the integration coverage in `7ab80cd`.

CODE-002 is remediated by this documentation commit. CODE-003 through CODE-008 remain recorded as non-blocking observations for audit round 2.

---

## Change Requests

None.

---

## Human Review

Ready for code audit round 2

---

## Notes

The routing decision is recorded as DEC-008 in `plan.md`. The candidate is the stable commit `7ab80cd`; audit must review that exact commit together with this documentation record.
