# Add a FAQ

Used to add a new FAQ in one existing category.

**URL** : `/api/v2.1/faq`

**HTTP Header** :

```
Accept-Language: [language code]
X-PMF-Token: [phpMyFAQ client API Token, generated in admin backend]
```

**Method** : `POST`

**Auth required** : NO

**Data constraints**

```json
{
  "language": "[language code, required value]",
  "category-id": "[category id as integer value, required value]",
  "category-name": "[category name in plain text, optional value]",
  "question": "[question in plain text, required value]",
  "answer": "[question in plain text, required value]",
  "keywords": "[keywords in comma separated plain text or empty string, required value]",
  "author": "[author name in plain text, required value]",
  "email": "[author email in plain text, required value]",
  "is-active": "true/false, required value",
  "is-sticky": "true/false, required value"
}
```

The category ID is a required value, the category name is optional. If the category name is present and the ID can be
mapped, the category ID from the name will be used. If the category name cannot be mapped, a 409 error is thrown

**Data example**

```json
{
  "language": "de",
  "category-id": "1",
  "category-name": "Queen Songs",
  "question": "Is this the world we created?",
  "answer": "What did we do it for, is this the world we invaded, against the law, so it seems in the end, is this what we're all living for today",
  "keywords": "phpMyFAQ, FAQ, Foo, Bar",
  "author": "Freddie Mercury",
  "email": "freddie.mercury@example.org",
  "is-active": "true",
  "is-sticky": "false"
}
```

## Success Response

**Condition** : If all posted data is correct.

**Code** : `200 OK`

**Content example**

```json
{
  "stored": true
}
```

## Error Responses

**Condition** : If category name cannot be mapped to a valid ID

**Code** : `409 Conflict`

**Content** :

```json
{
  "stored": false,
  "error": "error message"
}
```
