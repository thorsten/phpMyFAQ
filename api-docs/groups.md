# Get all groups

Used to fetch all group IDs

**URL** : `/api/v2.2/groups`

**HTTP Header** :

```
Accept-Language: [language code]
X-PMF-Token: [phpMyFAQ client API Token, generated in admin backend]
```

**Method** : `GET`

**Auth required** : YES

**Data constraints**

```json
[
  {
    "group-id": "[group id as integer value]"
  }
]
```

**Data example**

```json
[
  {
    "group-id": 1
  },
  {
    "group-id": 2
  }
]
```
