# Comments

This endpoint returns the comments of a given FAQ ID and the given language provided by "Accept-Language".

**URL** : `/api/v2.0/comments/:faqId`

**HTTP Header** : `Accept-Language: en-US,en`

**URL parameters** : `faqId=[int]` where `faqId` is the unique ID of a FAQ

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the FAQ has at least one comment.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": 2,
    "recordId": 142,
    "categoryId": null,
    "type": "faq",
    "username": "phpMyFAQ User",
    "email": "user@example.org",
    "comment": "Foo! Bar?",
    "date": "2019-12-24T12:24:57+0100",
    "helped": null
  },
  {
    "id": 5,
    "recordId": 142,
    "categoryId": null,
    "type": "faq",
    "username": "phpMyFAQ User",
    "email": "user@example.org",
    "comment": "Foo? Bar!",
    "date": "2019-12-24T15:51:32+0100",
    "helped": null
  }
]
```

## Error Responses

**Condition** : If the FAQ has no comments.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
