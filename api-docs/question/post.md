# Add a question

Used to add a new FAQ in one existing category.

**URL** : `/api/v2.2/question`

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
  "language": "[language code]",
  "category-id": "[category id as integer value]",
  "question": "[question in plain text]",
  "author": "[author name in plain text]",
  "email": "[author email in plain text]"
}
```

**Data example**

```json
{
  "language": "de",
  "category-id": "1",
  "question": "Is this the world we created?",
  "author": "Freddie Mercury",
  "email": "freddie.mercury@example.org"
}
```
