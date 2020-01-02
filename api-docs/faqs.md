# FAQs

This endpoint returns all the FAQs for the given language provided by "Accept-Language".

**URL** : `/api/v2.0/faqs`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the installation returns at least one FAQ.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": "1",
    "lang": "en",
    "solution_id": "1000",
    "revision_id": "0",
    "active": "yes",
    "sticky": "0",
    "keywords": "",
    "title": "Is there life after death?",
    "content": "Maybe!",
    "author": "phpMyFAQ User",
    "email": "user@example.org",
    "comment": "y",
    "date": "2009-10-10 17:54:00",
    "dateStart": "00000000000000",
    "dateEnd": "99991231235959",
    "created": "2008-09-03T21:30:17+02:00",
    "notes": ""
  },
  {
    "id": "1",
    "lang": "en",
    "solution_id": "1001",
    "revision_id": "0",
    "active": "yes",
    "sticky": "0",
    "keywords": "",
    "title": "Is there really life after death?",
    "content": "Maybe not!",
    "author": "phpMyFAQ User",
    "email": "user@example.org",
    "comment": "y",
    "date": "2009-10-10 17:54:00",
    "dateStart": "00000000000000",
    "dateEnd": "99991231235959",
    "created": "2008-09-03T21:30:17+02:00",
    "notes": ""
  }
]
```

## Error Responses

**Condition** : If there are no FAQs.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
