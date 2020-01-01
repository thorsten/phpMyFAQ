# Categories

This endpoint returns the categories for the given language provided by "Accept-Language".

**URL** : `/api/2.0/categories`

**HTTP Header** : `Accept-Language: en-US`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one category.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": 1,
    "lang": "en",
    "parent_id": 0,
    "name": "Test",
    "description": "Hello, World! Hello, Tests!",
    "user_id": 1,
    "group_id": 1,
    "active": 1,
    "show_home": 1,
    "image": "category-1-en.png",
    "level": 1
  },
  {
    "id": 2,
    "lang": "en",
    "parent_id": 0,
    "name": "Network",
    "description": "",
    "user_id": 1,
    "group_id": -1,
    "active": 1,
    "show_home": 0,
    "image": null,
    "level": 1
  }
]
```

## Error Responses

**Condition** : If the search returns no results.

**Code** : 404 NOT FOUND

**Content** :

```json
[]
```
