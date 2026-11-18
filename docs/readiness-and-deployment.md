# Release boundaries and deployment

Prepared November 18, 2026. Names are confirmed as Jonny Page and Tyler.

## Working interfaces and backend source

The source includes the branded customer website; service/intake and booking-preference interfaces; MapLibre/public GIS layers; dispatch and on-site mapping tools; engineering record forms; job stages, notes and timestamps; proposal calculators; and separate professional/business discussion pages. The PHP backend includes durable intake, private PDF uploads, staff authentication and an SMTP outbox/worker.

Public field demonstrations use isolated PHP visitor sessions. Browser evidence files use local IndexedDB. These preview records are not a central, shared engineering archive. The role switcher demonstrates roles; it is not authentication. A preferred appointment time is not a confirmed reservation. Notification hooks do not mean messages were sent.

## Connections still to complete

- Connect the field workflow and retained evidence to durable authenticated multiuser records and enforce closeout against files actually stored on the server.
- Activate and verify a vehicle tracker feed, routing/ETA service and opted-in customer notifications.
- Connect the voice receptionist, real calendar availability and reservation confirmation.
- Connect payments and reconcile provider results rather than relying on preview controls.
- Exercise Docker/PostGIS deployment, backup restoration and production monitoring.

No live GPS tracker, voice agent, paid mapping subscription or new VPS is included merely by importing this source. The existing shared-hosting route uses PHP/MySQL; Supabase Pro and MapTiler are not requirements.

## Existing sites

- Original review: https://white-sandpiper-603604.hostingersite.com/
- Shareable demonstration: https://royalblue-salamander-202742.hostingersite.com/

The original review contains separate `proposal.html` and `platform.html` documents. The shareable feedback deployment must continue returning 404 for those files and `api.php`. Public preview pages carry noindex/robots controls; these are not access restrictions.

## Shared-hosting deployment layout

Deploy only `hosting/public_html/` contents to the Natural State site's public document root. Deploy `hosting/natural-state-private/` as a sibling directory outside it. Its relative position is required by `api.php`. Preserve the live private `config/config.php`, storage, database and mail configuration; these are deliberately absent from Git.

Configure from `config/config.example.php` privately. Do not point a test configuration at live customer records. Preserve the existing staff authentication and mail-worker configuration when updating public copy. The real API must continue denying unauthenticated record access. Do not copy this source into any unrelated existing website.

For a separate feedback site deploy only `feedback/public_html/`. It is a standalone public demonstration with no private database connection.

No automatic deployment is configured by this repository import. `app/START HERE.md` contains the original local/shared-hosting setup guide, including earlier implementation notes; this document and `current-proposed-terms.md` describe the current source handoff.
