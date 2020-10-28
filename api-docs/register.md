# Registration

Used to register a new user.

**URL** : `/api/v2.0/register`

**HTTP Header** :

```
Accept-Language: [language code]
X-PMF-Token: [Token, generated in admin backend]
```

**Method** : `POST`

**Auth required** : NO

**Data constraints**

```json
{
  "username": "[username in plain text]",
  "fullname": "[real name in plain text]",
  "email": "[real name in plain text]",
  "is-visible": "true/false"
}
```

**Data example**

```json
{
  "username": "ada",
  "fullname": "Ada Lovelace",
  "email": "ada.lovelace@example.org",
  "is-visible": false
}
```

## Success Response

**Condition** : If 'username', 'fullname', 'email', and 'is-visible' combination is correct.

**Code** : `200 OK`

**Content example**

```json
{
  "registered": true
}
```

## Error Responses

**Condition** : If 'username', 'fullname', 'email', and 'is-visible' combination is wrong.

**Code** : `400 BAD REQUEST`

**Content** :

```json
{
  "registered": false,
  "error": "[error message]"
}
```
