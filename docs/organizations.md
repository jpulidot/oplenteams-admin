# Central customer registry

Organizations reads oplen_customers from the app platform database. It requires an active Admin login and organizations.read, admin.full or super_admin. No customer data is modified. Deleted customer records are excluded.

Teams Admin requires an explicit OPLEN_PLATFORM_CONFIG pointing to the new Teams app private configuration, through the server environment or the private administrative environment file. The app.oplen.io registry is rejected. Do not copy credentials into the repository. A failed connection shows an explicit error instead of a zero count. The admin database remains separate.

Trial access follows the app lifecycle: full until trial_ends_at, read only for 30 days, blocked for 30 days, then pending deletion. Dates display in America/Mexico_City.
