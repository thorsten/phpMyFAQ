# REST API v2.0 for phpMyFAQ v3.0.x

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
RESTful API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

## Open Endpoints

Open endpoints require no Authentication.

- [Version](api-docs/version.md): `GET /api/2.0/version`
- [Language](api-docs/language.md): `GET /api/2.0/language`
- [Search](api-docs/search.md): `GET /api/2.0/search?q=<search string>`
- [Categories](api-docs/categories.md): `GET /api/2.0/categories`
