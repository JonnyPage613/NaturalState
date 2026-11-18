# Natural State

Natural State Engineering website and GIS field-coordination platform, prepared by Jonny Page for Tyler. The separate Siteworks and digital-service proposals are discussion documents; they do not record an executed agreement.

Latest update: [GIS overview, responsive layout and Tyler spelling cleanup](docs/gis-overview-release.md).

## Source layout

| Folder | Purpose |
| --- | --- |
| `app/` | Portable PHP application, backend, configuration example, SQL and local Docker/PostGIS setup. |
| `hosting/public_html/` | Current original Hostinger website, customer preview, field portal and separate proposal pages. |
| `hosting/natural-state-private/` | Backend source intended outside the public web root. Includes no live credentials or uploads. |
| `feedback/public_html/` | Separate shareable, demonstration-only website. No partnership proposal or real customer API. |
| `docs/` | Current proposed commercial terms and deployment/readiness notes. |
| `tests/` | Workflow, engineering, geometry, pricing and isolated HTTP checks. |

The two hosting variants are intentional: the original review site includes separate business discussion pages; the feedback variant omits them. Never deploy the entire repository into a public document root.

## Run locally

PHP 8.4 is the Docker target. The lightweight local preview needs PHP with PDO SQLite; Node.js is used only to run JavaScript tests, with no frontend build step.

```sh
cd app
php bin/local.php
```

Open http://localhost:8080. Public demonstrations need no sign-in. To test protected staff records, follow the local instructions printed by the launcher; local mail is retained in the test outbox rather than sent to customers.

For the optional PostGIS/Mailpit environment:

```sh
cd app
docker compose up --build
```

App: http://localhost:8080. Local mail viewer: http://localhost:8025. Compose uses an intentionally non-production local database password and localhost-bound ports. Docker execution on this Mac remains unverified. See `app/START HERE.md` for setup details; use `docs/readiness-and-deployment.md` for current release boundaries.

## Checks

```sh
node tests/business-model-test.js
node tests/site-geometry-test.js
php tests/field-model-test.php
php tests/engineering-model-test.php
python3 app/tests/integration.py
```

The Python integration test launches its own isolated PHP server, temporary SQLite database and fictional records. It does not use the live account or send customer email. Set `PHP_BIN` if PHP is installed somewhere other than `/opt/homebrew/bin/php`.

## Branding and dependencies

The supplied Engineering and Siteworks brand artwork is retained. Photorealistic service illustrations are generated concept images, not photographs of completed Natural State projects. Bundled third-party components retain their notices; see `app/THIRD-PARTY-NOTICES.md` and vendor license files. This repository does not grant an open-source license to Jonny's custom work or the supplied brand artwork.
