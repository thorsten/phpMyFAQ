# Add a category

Used to add a new category in phpMyFAQ.

**URL** : `/api/v2.1/category`

**HTTP Header** :

```
Accept-Language: [language code]
X-PMF-Token: [phpMyFAQ client API Token, generated in admin backend]
```

**Method** : `POST`

**Auth required** : YES

**Data constraints**

```json
{
  "language": "[language code, required value]",
  "parent-id": "[category parent id as integer value, 0 is a root category, required value]",
  "parent-category-name": "[parent category name in plain text, optional value]",
  "category-name": "[name in plain text, required value]",
  "description": "[question in plain text or empty string, required value]",
  "is-active": "true/false, required value",
  "show-on-homepage": "true/false, required value"
}
```

The parent category ID is a required value, the parent category name is optional. If the parent category name is present
and the ID can be mapped, the parent category ID from the name will be used. If the parent category name cannot be
mapped, a 409 error is thrown

**Data example**

```json
{
  "language": "de",
  "parent-id": "0",
  "parent-category-name": "Open Source Software",
  "category-name": "phpMyFAQ",
  "description": "Hello, World",
  "is-active": "true",
  "show-on-homepage": "false"
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

**Condition** : If something didn't worked.

**Code** : `400 Bad Request`

**Content** :

```json
{
  "stored": false,
  "error": "Cannot add category"
}
```

**Condition** : If parent category name cannot be mapped to a valid ID

**Code** : `409 Conflict`

**Content** :

```json
{
  "stored": false,
  "error": "The given parent category name was not found."
}
```
