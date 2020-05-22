# News

This endpoint returns the news for the given language provided by "Accept-Language".

**URL** : `/api/v2.0/news`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If there's at least one news entry.

**Code** : `200 OK`

**Content example**

```json
[
  {
    "id": 1,
    "lang": "en",
    "date": "2019-08-23T20:43:00+0200",
    "header": "Hallo, World!",
    "content": "Hello, phpMyFAQ!",
    "authorName": "phpMyFAQ User",
    "authorEmail": "user@example.org",
    "dateStart": "0",
    "dateEnd": "99991231235959",
    "active": true,
    "allowComments": true,
    "link": "",
    "linkTitle": "",
    "target": "",
    "url": "https://www.example.org/?action=news&newsid=1&newslang=de"
  },
  {
    "id": 2,
    "lang": "en",
    "date": "2019-09-23T20:43:00+0200",
    "header": "Hallo, Mars!",
    "content": "Hello, phpMyFAQ on Mars!",
    "authorName": "phpMyFAQ User",
    "authorEmail": "user@example.org",
    "dateStart": "0",
    "dateEnd": "99991231235959",
    "active": true,
    "allowComments": true,
    "link": "",
    "linkTitle": "",
    "target": "",
    "url": "https://www.example.org/?action=news&newsid=1&newslang=de"
  }
]
```

## Error Responses

**Condition** : If no news are stored.

**Code** : `404 NOT FOUND`

**Content** :

```json
[]
```
