# Oplen Teams Admin migration

Target: https://admin.oplenteams.io

This branch imports the existing administrative application from `jpulidot/admin-oplen` source snapshot `3988bd3588fd2228cca9a79fe8e7e89e0dcfb594`. It retains the administrative roles, customer registry, billing overview, user directory and settings. It does not copy production data or credentials and does not create administrative users.

Before deployment:

1. Back up and restore the existing Admin database into an isolated validation database. Restore the Teams central registry and company databases using the App migration procedure.
2. Place the administrative environment file outside every web root. Set `OPLEN_ADMIN_ENV` to that absolute path, or use `private/admin.env` beside this site's `public_html`. Use `.env.example` only as a field reference; it contains no credentials.
3. Set `APP_URL=https://admin.oplenteams.io`, the copied Admin database credentials and an explicit `OPLEN_PLATFORM_CONFIG` pointing to the copied Teams App configuration. That platform config must declare `primary_host=app.oplenteams.io` and `routing=path`. The legacy app registry is rejected.
4. Deploy the migration branch and run `php scripts/migration-check.php` through SSH. The check is read-only and does not verify that two separately named configurations use isolated databases; compare the copied databases against the source inventory before functional testing.
5. Verify login with an existing copied administrative account, role permissions, company directory, user directory, settings and billing Sandbox classification. Keep mail, billing, deletion schedules and lifecycle automation inactive during validation.
6. Coordinate final data synchronization and App/Admin cutover so only one installation controls customer lifecycle operations. Keep the old installation and backup available for rollback.

The new site uses local logos, Teams domain health links, private-only configuration and an explicit Teams registry. The old web installer and static mock login are disabled/removed. Publication must not precede private configuration and database validation.

Status 2026-10-28: migration source and tests prepared. Hosting configuration, real database copy and live acceptance remain blocked by unavailable server access; no production cutover is claimed.

## Current rollout status

See the [project status and numbered backlog](https://github.com/jpulidot/oplen-master/blob/main/docs/PROJECT-STATUS.md). Admin migration code and CI are ready; server configuration, copied registry and production cutover remain unverified. Billing is a read-only Sandbox view, not evidence of live revenue or an active subscription platform.
