# Delete companies

In Organizations, administrators with `organizations.delete` permission (or `admin.full` / `super_admin`) can open Delete company. The POST requires CSRF, the exact company identifier, a reason and a current row version. Deletion is available for active companies and pending registrations; provisioning and failed setups require review.

An active company is immediately marked `deletion_requested`, and its tenant and activation/handoff access are revoked in the same transaction as the audit entry. The existing hourly app lifecycle worker performs the physical deletion. It cancels Stripe Sandbox subscriptions and expires open Checkouts before deleting the database and private tenant directory. It retries failures using a private checkpoint. Until completion, Organizations shows Deletion in progress. The completed company is removed from the active list; an anonymized registry entry and administrator audit remain. No undo is offered.

Pending registrations without tenant resources are anonymized immediately and their activation tokens revoked. Their email can be used for a new registration.

Deploy the companion changes to `oplen-master` before enabling this admin update. The deletion audit table is created automatically on the first confirmed request; the existing hourly cron needs no replacement. No production company is deleted by deployment or by the tests.

Tests: `php tests/company-deletion.php` in each repository. Tests use isolated SQLite databases and, for the app worker, temporary fixture files and mocked provider operations.
