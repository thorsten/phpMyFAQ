# REST API v3.0 for phpMyFAQ 4.0

> This documentation will be migrated to the OpenAPI format during the 4.0 development cycle.

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like an iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

## Public Endpoints

Public endpoints require no Authentication.

### General APIs

- Version: `GET /api/v3.0/version`
- Title: `GET /api/v3.0/title`
- Language: `GET /api/v3.0/language`
- News: `GET /api/v3.0/news`
- Categories: `GET /api/v3.0/categories`
- Tags: `GET /api/v3.0/tags`
- Open Questions: `GET /api/v3.0/open-questions`

### Search related APIs

- Search: `GET /api/v3.0/search?q=<search string>`
- Popular Searches: `GET /api/v3.0/searches/popular`

### FAQ related APIs

- Attachments: `GET /api/v3.0/attachments`
- Comments: `GET /api/v3.0/comments`
- [All FAQs](api-docs/faqs.md): `GET /api/v3.0/faqs`
- All FAQs per Category: `GET /api/v3.0/faqs/:categoryId`
- All FAQs per Tags: `GET /api/v3.0/faqs/tags/:tagId`
- [FAQ](api-docs/faq.md): `GET /api/v3.0/faq/:categoryId/:faqId`
- [FAQ as PDF](api-docs/faq/pdf.md): `GET /api/v3.0/faq/:categoryId/:faqId?filter=pdf`
- Latest FAQs: `GET /api/v3.0/faqs/latest`
- Popular FAQs: `GET /api/v3.0/faqs/popular`
- Sticky FAQs: `GET /api/v3.0/faqs/sticky`

### Login/Registration related APIs

- Login: `POST /api/v3.0/login`

## Endpoints that require Authentication

Closed endpoints require a valid API client token to be included in the header of the request. An API client token can
be acquired from the admin configuration.

### Category related APIs

- Add category: `POST /api/v3.0/category`

### FAQ related APIs

- [Add FAQ](api-docs/faq/post.md): `POST /api/v3.0/faq`
- [Update FAQ](api-docs/faq/put.md): `PUT /api/v3.0/faq/:categoryId/:faqId`
- [Add question](api-docs/question/post.md): `POST /api/v3.0/question`

### Groups related APIs

- All groups: `GET /api/v3.0/groups`

### Login/Registration related APIs

- [Register](api-docs/register.md): `POST /api/v3.0/register`

Copyright © 2001-2024 Thorsten Rinne and the phpMyFAQ Team
