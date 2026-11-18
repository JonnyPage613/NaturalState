# Included software and data services

- MapLibre GL JS 5.6.2 browser distribution, BSD-3-Clause. Included license: `public/assets/vendor/MAPLIBRE-LICENSE.txt`. Source: https://github.com/maplibre/maplibre-gl-js . Vendored locally so a CDN is not required at runtime.
- PHPMailer 7.2.0, LGPL-2.1. Included license: `src/vendor/PHPMailer/LICENSE`. Source: https://github.com/PHPMailer/PHPMailer . No SMTP password is included.
- The Engineering icon is an exact raster crop from the user's supplied Engineering brand sheet, with no regenerated artwork. The original supplied brand boards remain in the separate HQ source package.
- Map background: https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer . Terrain: https://elevation.nationalmap.gov/arcgis/rest/services/3DEPElevation/ImageServer . Attribution is displayed by MapLibre.
- Arkansas CAMP parcels: https://gis.arkansas.gov/arcgis/rest/services/FEATURESERVICES/Planning_Cadastre/FeatureServer/6 . Coverage and publication dates vary. This source does not establish legal boundaries.
- USDA NRCS Soil Data Access services: https://sdmdataaccess.sc.egov.usda.gov/WebServiceHelp.aspx . Published soil-unit names are distinct from measured field observations.
- Optional Geocodio: https://www.geocod.io/pricing . The published allowance is 2,500 lookups per day; account terms and usage apply. API key configuration is optional and server-only.

No public GIS service is represented as having guaranteed uptime, unlimited capacity, complete parcel coverage or guaranteed engineering suitability. Public lookup calls share the queried location with the relevant provider. A customer privacy notice should describe this before production launch.

Docker images are downloaded only when you run Docker Compose: official PHP, PostGIS and Mailpit distributions. Review their current licenses and operational requirements. The supplied Compose setup is local-only; its test credentials must not be reused for a public VPS deployment.

- GIS overview typography: Cinzel and Montserrat, SIL Open Font License 1.1. Self-hosted Latin font assets and their OFL texts are in `public/assets/fonts/`. Source: https://github.com/google/fonts .
