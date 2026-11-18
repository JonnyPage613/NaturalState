# Natural State — Phase 1 and Phase 2 pilot

Built November 15, 2026. No VPS, Supabase or MapTiler subscription is required. This application is separate from the existing private Project Headquarters. It does not replace the pitch site or establish a Siteworks partnership.

## Open the working local preview

Double-click `Start Local Preview.command`. PHP is now installed on this Mac. Open http://localhost:8080. Keep the terminal window running while reviewing. Only fictional test information belongs in this local preview.

A fresh local preview includes three clearly fictional sample lots. The default customer page is a no-login walkthrough using isolated sample records. To test durable local database intake, open http://localhost:8080/engineering.html?mode=live using fictional details. That real-intake mode saves requests, PDFs and individual lot records. For the private local database workspace, open http://localhost:8080/staff.html and request a sign-in link for `engineer@naturalstate.test` or `operations@naturalstate.test`. Then double-click `Open Local Test Inbox.command` and open the newest sign-in link it displays. This local inbox does not send email to anyone. Links expire after 20 minutes and work once; the browser then remembers staff access for 30 days.

This convenient preview uses local SQLite. The shared-hosting deployment uses MySQL. The Docker version below uses PostgreSQL/PostGIS. All three use the same PHP application and record structure. SQLite is a review/test convenience, not the proposed production database.

## What is implemented

- Durable customer inquiry storage; confirmation only after the database transaction succeeds.
- Name, email, optional phone, role, service, deadline, notes and contact consent.
- One to twenty explicit lot records per request. Each has its own county, parcel reference, bedrooms, water source and optional map coordinates. No fictional parcel IDs are generated.
- A MapLibre map with USGS topo tiles; clicking sets the chosen lot’s location.
- One private PDF per request, up to 10 MB. Server-side upload/error/MIME/header validation; randomized filenames; storage outside the web root; authenticated download as an attachment. A shared survey stays attached to the batch and is accessible from its individual lots.
- Staff-only dashboard and project APIs, engineer/operations roles, emailed single-use sign-in links, 30-day revocable remembered sessions, logout, CSRF checks and basic rate limits.
- Shared project search, stage filters, map pins, notes, history, PDF access and conflict detection when two people edit the same record.
- A staged project pipeline. Engineer permissions and evidence references are required for submission, authority-decision and document-release milestones. Skipping/reopening checkpoints requires an explanatory note. Financial stages are manually recorded; no payment processor is connected.
- Staff-triggered Arkansas CAMP point-to-parcel lookup, USDA soil-unit lookup, USDA WMS overlay, USGS 3DEP slope visualization. Public services may lack coverage or be unavailable. A missing parcel is shown honestly.
- Geocodio integration through a staff-only server endpoint. Add an optional free-tier API key to enable it; no key is exposed in the browser. Without a key, parcel references and pins still work. Government map/soil requests run directly in the browser.
- Transactional email outbox, SMTP delivery worker, retries, and pending/failed email counts in the dashboard. Saved requests remain available if SMTP is down. Email delivery itself is not guaranteed exactly once after ambiguous SMTP timeouts.
- Engineer-only pit log entry as a small optional workflow extension. This is not a full Phase 3 offline field application.

## Test PostGIS locally with Docker

Docker Desktop is not installed on this Mac; this configuration is supplied but has not been run here. Install Docker Desktop from its official source and review its applicable license before using it. The PostGIS image uses amd64 emulation for compatibility on Apple Silicon, which may run more slowly.

From this folder:

```sh
docker compose up --build -d
docker compose exec app php bin/staff.php engineer@naturalstate.test engineer
docker compose exec app php bin/staff.php operations@naturalstate.test operations
```

Open http://localhost:8080 and the test email inbox http://localhost:8025. The worker sends only to the local Mailpit inbox. The database is not exposed on a host port. The website and Mailpit are bound to localhost. Named volumes preserve records and uploads between restarts.

The startup step creates the database tables, PostGIS extension, a spatial-feature table and GiST index. Saving a project pin writes an EPSG:4326 Point geometry. A spatial smoke check:

```sh
docker compose exec db psql -U naturalstate -c 'SELECT PostGIS_Version();'
docker compose exec db psql -U naturalstate -c 'SELECT kind, ST_AsText(geom) FROM spatial_features;'
```

Save a pin in the dashboard before expecting a spatial row. Full parcel buffering, terrain calculations and GIS snapshots are later work; enabling PostGIS does not automatically build those features.

Stop the PHP SQLite preview first if it occupies port 8080. `docker compose down` stops the containers while retaining data. Do not add `-v` unless you intend to delete the local test database and uploaded files.

## Deploy to the existing Hostinger PHP/MySQL plan

Do not bulk-upload this folder over the current private review site. Use a separate application document root or a separate new site/subdomain on the existing plan, or deliberately integrate routes after backing up the current gateway. No paid plan is needed for the application itself within your existing hosting allowances.

Required: PHP 8.2+, PDO MySQL, fileinfo, sessions, HTTPS, writable private storage, one included MySQL database configured for utf8mb4, the actual business mailbox SMTP settings and a cron job. SMTP/mailbox availability and sending limits depend on the existing account. No real mailbox credentials have been added to this package.

1. Back up the target site's existing files, configuration and database. The current pitch site's live `review-config.php`, `review-gate.php` and `.htaccess` must be preserved. The old HQ `site/.htaccess` containing `Require all denied` must never replace that live file.
2. Create an application directory outside `public_html`, for example `/home/ACCOUNT/domains/DOMAIN/natural-state-app`. Upload `src/`, `bin/`, `sql/` and `config/` there. Create `storage/uploads` there too, readable/writable only by the PHP account. Do not put uploads, configuration, source dependencies, test scripts, Docker files or backups under the public website directory.
3. Put only the contents of this package's `public/` in the application's public document root. Its `.htaccess` is for this separate application root, not the existing private review site. Ensure the server enforces it. Set PHP upload_max_filesize to 10M and post_max_size to at least 12M.
4. Because the private application folder and public document root may be siblings, adjust the first require in public `api.php` to the actual private `src/bootstrap.php` path. The packaged default assumes `public/` is inside the application folder. Prefer setting the document root directly to that `public/` folder when available.
5. Copy `config/config.example.php` to private `config/config.php`. Set the MySQL DSN to `mysql:host=127.0.0.1;dbname=FULL_PREFIXED_NAME;charset=utf8mb4`, the full database username/password, the exact HTTPS base URL, private storage path, real SMTP host/port/security/username/password, verified sender and staff alert address. The app must use the local MySQL host, not Hostinger's external database hostname. Keep the config out of shared archives and source control.
6. Run `php bin/setup.php` once from the private application folder. It creates missing tables; it is not a schema-upgrade system for future releases. Run `php bin/staff.php actual-email operations` and/or `php bin/staff.php actual-email engineer` for the intended staff only. There is no public registration or web installer.
7. Add a Hostinger cron job running `php /ABSOLUTE/PRIVATE/PATH/bin/mail-worker.php` every minute. Use Hostinger's actual PHP binary/path. SMTP messages go through the included PHPMailer library; no paid email service is required. Verify SPF/DKIM with your mailbox provider and test delivery to your real inbox.
8. Test from two separate devices: submit a fictional request with three lots and a PDF; receive the acknowledgement; sign in as staff; open each lot and PDF; update a pin/status; verify another device sees it. Sign out and confirm private project/file URLs return 401. Confirm failed mail appears in the queue and is retried.
9. Test public GIS layers on actual representative properties and phones. Confirm CORS/security headers, source coverage and useful scale. Keep the county fallback for missing CAMP parcels. Enable Geocodio only if needed and set usage controls.
10. Schedule daily database and separate upload backups to an existing private destination, and test restoring both. Hosting backups may have plan-specific coverage. Do not call the app production-ready until MySQL, SMTP, permissions, backup restoration and HTTPS have been checked on the target host.
11. Keep the partner-review/noindex labels until Tylor approves actual business identity, services, contact details, scope and launch. For public launch, revise those labels and the privacy/retention notice; the private HQ and staff data remain restricted. The public staff HTML contains no private records: the authenticated PHP API enforces access.

Future routine updates: upload changed files from `public/assets/` and public HTML only, or reviewed API/src/bin changes as needed. Never overwrite private `config/config.php`, `storage/`, database contents or existing pitch gateway files. Preserve a release copy for rollback. Schema changes need an explicit migration; `setup.php` does not migrate old columns.

Revoke staff access, including remembered sessions:

```sh
php bin/revoke.php actual-email@business.com
```

## What remains deliberately unbuilt

No live AI/chat/telephone service, payments, accounting integration, self-service appointment confirmation, Google Calendar synchronization, customer/builder accounts, offline field synchronization, automated engineering design, legal boundary determination, validated setback tools, automatic ADH submission or permit approval. No Siteworks referral agreement is implied. Pit logs are text records; attachments, per-pit surveyed coordinates and released design versions require a later field/engineering phase.

A separate public-domain address is optional for the pitch: continue using an included Hostinger subdomain. A new domain has its own registration/renewal price if purchased later. Zero extra subscription cost does not remove existing hosting renewals, storage limits, implementation effort or maintenance duties.

## Validation completed and limits

25 automated HTTP integration checks passed using isolated SQLite, including multi-lot/PDF intake, replay protection, private file access, magic-link replay prevention, role guards, stale-edit conflicts and staff revocation. The SMTP worker also delivered all four queued test messages into a local SMTP sink and recorded success. PHP and JavaScript syntax checks passed. Browser checks verified actual inquiry persistence, staff sign-in/dashboard access, USGS basemap rendering and a real USDA unit lookup at a fictional test location. GIS service capabilities were checked for available WMS layers and slope rendering.

MySQL and PostGIS execution, Docker startup and real SMTP delivery have not been tested in their target environments. The current Hostinger site now uses the same integrated branding and a no-login sample walkthrough. Its real MySQL intake, private PDFs and SMTP worker have been verified separately; see the integrated release receipt. This package is a working locally tested application, with the explicit hosting activation steps above still required.
