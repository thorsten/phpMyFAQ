# Sticky FAQs

This endpoint returns the sticky FAQs for the given language provided by "Accept-Language".

**URL** : `/api/v2.0/faqs/sticky`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one sticky FAQ.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "question": "How can I survive without phpMyFAQ?",
    "url": "https://www.example.org/index.php?action=faq&cat=1&id=36&artlang=de"
  },
  {
    "question": "Is there life after death?",
    "url": "https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en"
  }
]
```

## Error Responses

**Condition** : If search terms are stored.

**Code** : `404 NOT FOUND`

**Content** :

```json
[]
```
