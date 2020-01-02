# Tags

This endpoint returns the tags for the given language provided by "Accept-Language".

**URL** : `/api/v2.0/tags`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one tag.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "tagId": 4,
    "tagName": "phpMyFAQ",
    "tagFrequency": 3
  },
  {
    "tagId": 1,
    "tagName": "PHP 7",
    "tagFrequency": 2
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
