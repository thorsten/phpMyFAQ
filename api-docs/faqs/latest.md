# Latest FAQs

This endpoint returns the latest FAQs for the given language provided by "Accept-Language".

**URL** : `/api/v2.0/faqs/latest`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one latest FAQ.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "date": "2019-07-13T11:28:00+0200",
    "question": "How can I survive without phpMyFAQ?",
    "answer": "A good question!",
    "visits": 10,
    "url": "https://www.example.org/index.php?action=faq&cat=1&id=36&artlang=de"
  },
  {
    "date": "2019-06-19T21:48:00+0200",
    "question": "Is there life after death?",
    "answer": "Maybe!",
    "visits": 3,
    "url": "https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en"
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
