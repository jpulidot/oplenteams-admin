# Platform Settings · Admin 0.8.0

Settings is restricted to full administrators (`super_admin` or `admin.full`). Saving requires a session CSRF token and an unchanged version. Changes and their administrator IDs are written atomically to the audit history. No credentials are stored in Settings.

Deploy both admin-oplen and oplen-master. Admin creates `oplen_platform_settings` and `oplen_platform_settings_audit` in the same central registry used by Organizations when a full administrator opens Settings. The registry user needs CREATE, SELECT, INSERT and UPDATE for these tables. No customer tables are migrated or existing trial dates changed.

- Support email appears on App registration, including paused registration. It does not change the activation sender.
- Admin time zone affects Overview, System health and Settings history; per-company subscription date inputs remain explicitly Mexico City time.
- Registration pause blocks new public trial registrations in the backend. Existing sign-in, activation resend and pending setup continue. CLI courtesy provisioning remains available.
- Trial duration (1–90 days) applies at completion of future setups, including currently pending companies. Existing active trial end dates are unchanged. Expiration grace/retention periods remain fixed.
- Integration diagnostics show only credential/configuration presence. They do not confirm service health, deliverability, valid API credentials or enabled live billing. Stripe remains Sandbox-only.

Before the Settings tables exist, App retains legacy defaults (seven days and registration open). Other read failures or invalid saved configuration fail closed for registration and provisioning. Deploy App before relying on saved registration and trial controls. Admin's shared settings contract and App's platform/settings.php must be kept identical.

Prices, country restrictions, invitations, notification recipients and retention editing are not implemented in this release.
