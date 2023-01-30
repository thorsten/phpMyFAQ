# FAQs by Category ID

This endpoint returns all the FAQs with a preview of the answer for the given category ID and the language provided by
"Accept-Language". If a query parameter filter=all is set, the full answers are returned.

**URL** : `/api/v2.0/faqs/:categorId`

**HTTP Header** : `Accept-Language: en-US,en`

**URL parameters** :
_ `categorId=[int]` where `categorId` is the ID of a category
_ optional: `filter=all` to get all answers without a preview

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the category returns at least one FAQ.

**Code** : `200 OK`

**Content example**

Standard without query parameter:

```json
[
  {
    "record_id": 1,
    "record_lang": "en",
    "category_id": 1,
    "record_title": "Is there life after death?",
    "record_preview": "Maybe!",
    "record_link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=1&artlang=en",
    "record_updated": "20191010175452",
    "visits": 3,
    "record_created": "2018-09-03T21:30:17+02:00"
  },
  {
    "record_id": 2,
    "record_lang": "en",
    "category_id": 1,
    "record_title": "How can I survive without phpMyFAQ?",
    "record_preview": "It's easy!",
    "record_link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=2&artlang=en",
    "record_updated": "20191014181500",
    "visits": 10,
    "record_created": "2018-09-03T21:30:17+02:00"
  }
]
```

With query parameter `filter=all`:

```json
[
  {
    "faq_id": 1,
    "faq_lang": "en",
    "category_id": 1,
    "question": "Is there life after death?",
    "answer": "Maybe!",
    "link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=1&artlang=en",
    "updated": "20191010175452",
    "visits": 3,
    "created": "2018-09-03T21:30:17+02:00"
  },
  {
    "faq_id": 2,
    "faq_lang": "en",
    "category_id": 1,
    "question": "How can I survive without phpMyFAQ?",
    "answer": "It's easy!",
    "link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=2&artlang=en",
    "updated": "20191014181500",
    "visits": 10,
    "created": "2018-09-03T21:30:17+02:00"
  }
]
```

## Error Responses

**Condition** : If there are no FAQs for the given category ID.

**Code** : `404 NOT FOUND`

**Content** :

```json
[]
```
