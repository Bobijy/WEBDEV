# Rubric readiness implementation plan

**Goal:** Complete the mandatory Admin and Staff functions in the supplied final-project rubric while retaining the customer shop.

**Current clarified policy:** Staff can manage all customer orders and view all shop products. Staff may edit/delete only products they created; other staff/admin products and unowned products are view-only. Admins retain full product management. Current verification and behavior are documented in `docs/RUBRIC_DEMO.md`.

**Architecture:** Extend Symfony security and the existing product controller/templates. Store each product's creator, use a voter for ownership, and keep account-edit validation separate from the authenticated User entity. Existing unowned products remain admin-only. Account disabling must block fresh login and invalidate existing sessions.

**Constraints:** Preserve existing data and customer behavior. No default passwords or public role assignment. Keep the current visual design and installed dependencies. Work in the current workspace, preserving pre-existing Docker changes; no commits or deployment requested.

## Tasks

- [x] Repair obsolete category test fixtures and establish the baseline suite.
- [x] Write failing functional tests for staff permissions, ownership, account administration, disabling, role changes, and required dashboard totals.
- [x] Add staff roles, active status, product ownership, login/session checks, and an additive MySQL migration.
- [x] Complete admin account create/edit/reset/status functions, preserving at least one active admin and order history.
- [x] Reuse product management for a separate staff route; enforce ownership on reads/writes and preserve product order history.
- [x] Add role-based menus, profile/password links, confirmation behavior, search/filtering, dashboard totals, and mobile layouts.
- [x] Run the whole test suite, schema/template/container checks, and desktop/mobile browser verification. Document setup and a rubric demonstration checklist.

## Review focus

- Staff manually changing a URL or forging an owner/role field must never gain privileges.
- Disabled accounts, including existing and remembered sessions, must lose access.
- Invalid/duplicate account edits must not modify stored data or session identity.
- Administrators must not remove their own access or delete users with order history.
- Legacy products and records belonging to deleted users must remain accessible to admins without exposing them to staff.

## Progress

Baseline: tests initially could not run under restricted Windows file access. Normal PHP file access works; the first failure is an obsolete string category fixture after Product moved to a Category entity. Docker Desktop is currently stopped.

Completed: baseline 51 tests passed; new tests failed for missing features before implementation; final suite 59 tests / 451 assertions passed. SQLite test/QA now enables FK enforcement on every connection via Doctrine's installed middleware. New MySQL migration applied after a full local backup; all existing counts preserved and schema validation passed. Independent reviewer approved. Browser checks passed for admin/staff and 375px form. The visual detector only flagged the existing Inter font; preserved the established design. Local image package download stalled; cancelled the build and synchronized changes to the existing running container instead. See `docs/RUBRIC_DEMO.md` for operation and demonstration.
