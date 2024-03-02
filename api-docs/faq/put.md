# Update a FAQ

Used to update a FAQ in one existing category.

**URL** : `/api/v2.1/faq`

**HTTP Header** :

```
Accept-Language: [language code]
X-PMF-Token: [phpMyFAQ client API Token, generated in admin backend]
Content-Type: application/json
```

**Method** : `POST`

**Auth required** : NO

**Data constraints**

```json
{
  "faq-id": "[faq id as integer value, required value]",
  "language": "[language code, required value]",
  "category-id": "[category id as integer value, required value]",
  "question": "[question in plain text, required value]",
  "answer": "[question in plain text, required value]",
  "keywords": "[keywords in comma separated plain text or empty string, required value]",
  "author": "[author name in plain text, required value]",
  "email": "[author email in plain text, required value]",
  "is-active": "true/false, required value",
  "is-sticky": "true/false, required value"
}
```

**Data example**

```json
{
  "faq-id": 1,
  "language": "de",
  "category-id": 1,
  "question": "Is this the world we updated?",
  "answer": "What did we do it for, is this the world we invaded, against the law, so it seems in the end, is this what we're all living for today",
  "keywords": "phpMyFAQ, FAQ, Foo, Bar",
  "author": "Freddie Mercury",
  "email": "freddie.mercury@example.org",
  "is-active": "true",
  "is-sticky": "false"
}
```

## Success Response

**Condition** : If all putted data is correct.

**Code** : `200 OK`

**Content example**

```json
{
  "stored": true
}
```

## Error Responses

**Condition** : If FAQ ID or category id cannot be mapped to valid IDs

**Code** : `404 Not Found`

**Content** :

```json
{
  "stored": false,
  "error": "error message"
}
```
