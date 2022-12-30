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
  "language": "[language code]",
  "parent-id": "[category parent id as integer value, 0 is a root category]",
  "category-name": "[name in plain text]",
  "description": "[question in plain text]",
  "user-id": "[user id as integer value]",
  "group-id": "[group-id as integer value]",
  "is-active": "true/false",
  "show-on-homepage": "true/false"
}
```

**Data example**

```json
{
  "language": "de",
  "parent-id": "0",
  "category-name": "phpMyFAQ",
  "description": "Hello, World",
  "user-id": "1",
  "group-id": "-1",
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

**Code** : `400 BAD REQUEST`

**Content** :

```json
{
  "stored": false,
  "error": "Cannot add category"
}
```
