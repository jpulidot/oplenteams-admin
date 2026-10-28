# Billing · Admin 0.9.0

Read-only view restricted to `billing.read`, `admin.full`, or super administrators. It reads the same central customer registry as Organizations, plus the existing `oplen_billing_sandbox` and `oplen_stripe_sandbox_events` tables maintained by App. It never creates billing tables, contacts Stripe, changes access, starts checkout or charges a payment method.

The company table distinguishes access kind and current access from Sandbox subscription and latest test invoice state. Seats are the Sandbox checkout/subscription selection, not active users. Plan value uses the current existing Sandbox schedule (US$39 including five seats, US$5 per extra seat).

Live MRR remains unavailable because live billing is not connected. Test monthly value includes only completed active company setups with an active Sandbox subscription and latest test invoice marked paid. It excludes orphan, deleted and deleting companies, unpaid/inactive subscriptions, and manual Paid/courtesy classifications without eligible test records. Invalid contributing seat counts make the aggregate unavailable rather than understated.

History lists the latest 30 globally processed Sandbox events, not payment receipts. Stored events do not contain company mappings, invoice amounts, renewal dates or original provider payloads; the UI does not infer them. Refresh reloads saved database records, not provider state. A missing Sandbox table is explicitly uninitialized; other database errors fail visibly rather than rendering fake zeros.

The original Admin 0.9.0 release targeted `admin-oplen/main`. This repository is the separate Teams migration target: deploy only after the explicit private Teams registry configuration and acceptance in README.md are complete. The migration branch passing CI does not mean the new Admin is installed. Existing App checkout/webhook processes maintain these tables. Preserve private platform configuration and the admin environment; never share the old production registry accidentally.

Current product status and billing blockers: https://github.com/jpulidot/oplen-master/blob/main/docs/PROJECT-STATUS.md. Sandbox renewal/cancellation and seat synchronization must be verified separately from live revenue; no live revenue is enabled by this view.
