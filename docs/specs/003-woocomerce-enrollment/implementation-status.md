# Implementation Status

SPEC: SPEC-003 — WooCommerce Enrollment

Branch: feature/spec-003-woocommerce-enrollment
Current implementation commit: 8e3b9a31db080f8397126c78f9f610daee937c38

Status: Ready for audit
Ready for audit: Yes
Current audit round: 1

## Human Approval

SPEC approved for implementation: Yes

---

## Dependencies

SPEC-001:
Completed

SPEC-002:
Approved by human review — 2026-09-28

WooCommerce:
Installed version 11.1.1; integration fixture executed against WordPress 7.1.2 + WooCommerce 11.1.1.

---

## Tasks

Total: 37
Done: 37
In Progress: 0
Pending: 0
Blocked: 0
Not Applicable: 0

---

## Quality Gate

PHP Syntax:

`PASS — composer lint`

Unit Tests:

`PASS — 16 tests, 21 assertions`

Integration Tests:

`PASS — wordpress-lifecycle (WooCommerce inactive), courses-curriculum and woocommerce-enrollment, executed 2026-09-28 against a disposable WordPress 7.1.2 + WooCommerce 11.1.1 + MariaDB copy of the local site`

WooCommerce Integration Tests:

`PASS — processing, completed, cancelled, total/partial refund, alternative source, reactivation, guest order, admin authorization, bundle (1 product → 3 courses), overlapping products in one order, product without courses, trashed/deleted course and source traceability (order/product/order item)`

Composer Validation:

`PASS — composer validate --strict`

PHPCS:

`PASS — composer cs`

PHPStan:

`PASS — composer analyse (outside sandbox; local PHPStan socket required)`

Manual Verification:

`PENDING`

---

## Acceptance Criteria

AC-001: IMPLEMENTED AND INTEGRATION-TESTED  
AC-002: IMPLEMENTED AND INTEGRATION-TESTED  
AC-003: IMPLEMENTED AND INTEGRATION-TESTED  
AC-004: IMPLEMENTED AND INTEGRATION-TESTED  
AC-005: IMPLEMENTED AND INTEGRATION-TESTED  
AC-006: IMPLEMENTED AND INTEGRATION-TESTED  
AC-007: IMPLEMENTED AND INTEGRATION-TESTED  
AC-008: IMPLEMENTED AND INTEGRATION-TESTED  
AC-009: IMPLEMENTED AND INTEGRATION-TESTED  
AC-010: IMPLEMENTED AND INTEGRATION-TESTED  
AC-011: IMPLEMENTED AND INTEGRATION-TESTED  
AC-012: IMPLEMENTED AND INTEGRATION-TESTED  
AC-013: IMPLEMENTED AND INTEGRATION-TESTED  
AC-014: IMPLEMENTED AND INTEGRATION-TESTED  
AC-015: IMPLEMENTED AND INTEGRATION-TESTED  
AC-016: IMPLEMENTED AND INTEGRATION-TESTED  
AC-017: IMPLEMENTED AND INTEGRATION-TESTED  
AC-018: IMPLEMENTED, UNIT-TESTED AND INTEGRATION-TESTED (lifecycle fixture with WooCommerce inactive)  
AC-019: IMPLEMENTED AND INTEGRATION-TESTED  
AC-020: IMPLEMENTED AND INTEGRATION-TESTED  
AC-021: IMPLEMENTED; scope reviewed

---

## WooCommerce Policy

Grant access:

- processing
- completed

Revoke commercial sources:

- cancelled
- refunded

Does not grant access:

- pending
- on-hold
- failed

Guest orders:

Do not create enrollment.

---

## Known Issues

Manual authenticated verification of the product metabox is pending.

---

## Open Findings

Audit round 1 — Claude Code: FAIL (`audits/round-1/code-audit.md`, audited against the uncommitted working tree on top of `001496c`).

- CODE-001 — Blocking — Fixed, pending auditor verification: implementation committed as `3adde8c` on `feature/spec-003-woocommerce-enrollment`; SPEC-002 audit change committed separately (`6e6b9fb`).
- CODE-002 — Blocking — Fixed, pending auditor verification: `d2e4a77` adds bundle (AC-008), overlapping products in one order (AC-009), product without courses (AC-004), trashed/deleted course (EC-003) and source traceability (AC-007) cases. Each new assertion was checked to fail against a deliberately broken implementation (grant only the first course, skip course validation, process only the first item, drop order_item_id); production code is unchanged.
- CODE-003 — Non-blocking — Fixed, pending auditor verification: `8e3b9a3` lists every assignable course plus existing associations in the product metabox (with status labels) and only detaches the courses the submitted form displayed; trashed courses can stay associated but cannot be newly attached. Covered by integration tests that submit the rendered form.
- CODE-004 to CODE-008 — Non-blocking — OPEN.

Additional fix in `d2e4a77`: `tests/Integration/wordpress-lifecycle.php` (SPEC-001) still expected schema version 1 after the SPEC-003 bump to 2 and failed; it now uses `Version::DATABASE_SCHEMA`.

Next pass: reopen audit round 1 against `8e3b9a3`.

Qwen and Mimo round-1 audits: not yet executed.

---

## Change Requests

None.

---

## Human Review

Pending.

---

## Notes

The WooCommerce fixture cleaned all temporary users after execution. The fixture now also removes the product-course rows it creates. Manual authenticated verification of the product metabox remains pending.
