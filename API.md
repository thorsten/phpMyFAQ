# REST API v2.1 for phpMyFAQ 3.1

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

## Open Endpoints

Open endpoints require no Authentication.

### General APIs

- [Version](api-docs/version.md): `GET /api/v2.1/version`
- [Language](api-docs/language.md): `GET /api/v2.1/language`
- [News](api-docs/news.md): `GET /api/v2.1/news`
- [Categories](api-docs/categories.md): `GET /api/v2.1/categories`
- [Tags](api-docs/tags.md): `GET /api/v2.1/tags`
- [Open Questions](api-docs/open-questions.md): `GET /api/v2.1/open-questions`

### Search related APIs

- [Search](api-docs/search.md): `GET /api/v2.1/search?q=<search string>`
- [Popular Searches](api-docs/searches/popular.md): `GET /api/v2.1/searches/popular`

### FAQ related APIs

- [Attachments](api-docs/attachments.md): `GET /api/v2.1/attachments`
- [Comments](api-docs/comments.md): `GET /api/v2.1/comments`
- [All FAQs](api-docs/faqs.md): `GET /api/v2.1/faqs`
- [All FAQs per Category](api-docs/faqs/categoryId.md): `GET /api/v2.1/faqs/:categoryId`
- [All FAQs per Tags](api-docs/faqs/tags.md): `GET /api/v2.1/faqs/tags/:tagId`
- [FAQ](api-docs/faq.md): `GET /api/v2.1/faq/:faqId`
- [FAQ as PDF](api-docs/faq/pdf.md): `GET /api/v2.1/faq/:faqId?filter=pdf`
- [Latest FAQs](api-docs/faqs/latest.md): `GET /api/v2.1/faqs/latest`
- [Popular FAQs](api-docs/faqs/popular.md): `GET /api/v2.1/faqs/popular`
- [Sticky FAQs](api-docs/faqs/sticky.md): `GET /api/v2.1/faqs/sticky`

### Login/Registration related APIs

- [Login](api-docs/login.md): `POST /api/v2.1/login`

## Endpoints that require Authentication

Closed endpoints require a valid API client token to be included in the header of the request. An API client token can
be acquired from the admin configuration.

### FAQ related APIs

- [Add FAQ](api-docs/faq/post.md): `GET /api/v2.1/faq`

### Login/Registration related APIs

- [Register](api-docs/register.md): `POST /api/v2.1/register`

© 2001-2022 phpMyFAQ Team

Copyright © 2001-2022 Thorsten Rinne and the phpMyFAQ Team
