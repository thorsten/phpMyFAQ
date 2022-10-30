# FAQ

This endpoint returns the FAQ for the given FAQ ID and the language provided by "Accept-Language".

**URL** : `/api/v2.0/faq/:categoryId/:faqId`

**HTTP Header** :

```
Accept-Language: [language code]
```

**URL parameters** :

- `categorId=[int]` where `categorId` is the ID of a category
- `faqId=[int]` where `faqId` is the ID of a FAQ

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the FAQ exists.

**Code** : `200 OK`

**Content example**

```json
{
  "id": 1,
  "lang": "en",
  "solution_id": 1000,
  "revision_id": 0,
  "active": "yes",
  "sticky": 0,
  "keywords": "",
  "title": "Is there life after death?",
  "content": "Maybe!",
  "author": "phpMyFAQ User",
  "email": "user@example.org",
  "comment": "y",
  "date": "2019-10-10 17:54",
  "dateStart": "00000000000000",
  "dateEnd": "99991231235959",
  "linkState": "",
  "linkCheckDate": "0",
  "created": "2019-09-03T21:30:17+02:00"
}
```

## Error Responses

**Condition** : If there are no FAQs for the given FAQ ID.

**Code** : `404 NOT FOUND`

**Content** :

```json
{}
```
