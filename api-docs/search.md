# Search

This endpoint returns the results for the query string search term.

**URL** : `/api/v2.0/search`

**HTTP Header** :

```
Accept-Language: [language code]
```

**Query parameters** : `q=[string]`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the search returns at least one result.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": "1",
    "lang": "en",
    "category_id": "15",
    "question": "Why are you using phpMyFAQ?",
    "answer": "Because it's cool!",
    "link": "https://www.example.org/index.php?action=faq&cat=15&id=1&artlang=en"
  },
  {
    "id": "13",
    "lang": "en",
    "category_id": "5",
    "question": "Why do you like phpMyFAQ?",
    "answer": "Because it's cool!",
    "link": "https://www.example.org/index.php?action=faq&cat=5&id=13&artlang=en"
  }
]
```

## Error Responses

**Condition** : If the search returns no results.

**Code** : `404 NOT FOUND`

**Content** :

```json
[]
```
