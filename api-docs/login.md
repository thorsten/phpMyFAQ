# Login

Used to login a registered user.

**URL** : `/api/v2.0/login`

**HTTP Header** : `Accept-Language: en-US,en`

**Method** : `POST`

**Auth required** : NO

**Data constraints**

```json
{
  "username": "[username in plain text]",
  "password": "[password in plain text]"
}
```

**Data example**

```json
{
  "username": "admin",
  "password": "foobarbaz"
}
```

## Success Response

**Condition** : If 'username' and 'password' combination is correct.

**Code** : `200 OK`

**Content example**

```json
{
  "loggedin": true
}
```

## Error Responses

**Condition** : If 'username' and 'password' combination is wrong.

**Code** : `400 BAD REQUEST`

**Content** :

```json
{
  "loggedin": false,
  "error": "Wrong username or password."
}
```
