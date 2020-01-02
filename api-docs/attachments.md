# Attachments

This endpoint returns the attached files as URLs of a given FAQ ID and the given language provided by "Accept-Language".

**URL** : `/api/v2.0/attachments/:faqId`

**HTTP Header** : `Accept-Language: en-US,en`

**URL parameters** : `faqId=[int]` where `faqId` is the unique ID of a FAQ

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the FAQ has at least one attached file.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "filename": "attachment-1.pdf",
    "url": "https://www.example.org/index.php?action=attachment&amp;id=1"
  },
  {
    "filename": "attachment-2.pdf",
    "url": "https://www.example.org/index.php?action=attachment&amp;id=2"
  }
]
```

## Error Responses

**Condition** : If the FAQ has no attachments.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
