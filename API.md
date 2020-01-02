# REST API v2.0 for phpMyFAQ v3.0.x

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
RESTful API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

## Open Endpoints

Open endpoints require no Authentication.

### General APIs

- [Version](api-docs/version.md): `GET /api/v2.0/version`
- [Language](api-docs/language.md): `GET /api/v2.0/language`
- [News](api-docs/news.md): `GET /api/v2.0/news`
- [Categories](api-docs/categories.md): `GET /api/v2.0/categories`
- [Tags](api-docs/tags.md): `GET /api/v2.0/tags`
- [Open Questions](api-docs/open-questions.md): `GET /api/v2.0/open-questions`

# Search related APIs

- [Search](api-docs/search.md): `GET /api/v2.0/search?q=<search string>`
- [Popular Searches](api-docs/searches/popular.md): `GET /api/v2.0/searches/popular`

# FAQ related APIs

- [Attachments](api-docs/attachments.md): `GET /api/v2.0/attachments`
- [Comments](api-docs/comments.md): `GET /api/v2.0/comments`
- [FAQs](api-docs/faqs.md): `GET /api/v2.0/faqs`
- [FAQs per Category](api-docs/faqs/categoryId.md): `GET /api/v2.0/faqs/:categoryId`
- [Latest FAQs](api-docs/faqs/latest.md): `GET /api/v2.0/faqs/latest`
- [Popular FAQs](api-docs/faqs/popular.md): `GET /api/v2.0/faqs/popular`
- [Sticky FAQs](api-docs/faqs/sticky.md): `GET /api/v2.0/faqs/sticky`

## Login APIs

- [Login](api-docs/login.md): `POST /api/v2.0/login`
