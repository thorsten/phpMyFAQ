# Open Questions

This endpoint returns the open questions for the given language provided by "Accept-Language".

**URL** : `/api/2.0/open-questions`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one open question.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": 1,
    "lang": "en",
    "username": "phpMyFAQ User",
    "email": "user@example.org",
    "categoryId": 3,
    "question": "Foo? Bar? Baz?",
    "created": "20190106180429",
    "answerId": 0,
    "isVisible": "N"
  },
  {
    "id": 2,
    "lang": "en",
    "username": "phpMyFAQ User",
    "email": "user@example.org",
    "categoryId": 3,
    "question": "Foo?",
    "created": "20190922131431",
    "answerId": 0,
    "isVisible": "Y"
  }
]
```

## Error Responses

**Condition** : If no tags are stored.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
