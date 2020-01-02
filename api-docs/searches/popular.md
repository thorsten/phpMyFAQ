# Popular Searches

This endpoint returns the popular search terms for the given language provided by "Accept-Language".

**URL** : `/api/2.0/searches/popular`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one popular search term.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": 3,
    "searchterm": "mac",
    "number": "18",
    "lang": "en"
  },
  {
    "id": 7,
    "searchterm": "test",
    "number": 9,
    "lang": "en"
  },
  {
    "id": 20,
    "searchterm": "xml",
    "number": 2,
    "lang": "en"
  }
]
```

## Error Responses

**Condition** : If search terms are stored.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
