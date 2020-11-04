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
  "language": "[language code]",
  "category-id": "[category id as integer value]",
  "question": "[question in plain text]",
  "answer": "[question in plain text]",
  "keywords": "[keywords in comma separated plain text]",
  "author": "[author name in plain text]",
  "email": "[author email in plain text]",
  "is-active": "true/false",
  "is-sticky": "true/false"
}
```

**Data example**

```json
{
  "language": "de",
  "category-id": "1",
  "question": "Is this the world we created?",
  "answer": "What did we do it for, is this the world we invaded, against the law, so it seems in the end, is this what we're all living for today",
  "keywords": "phpMyFAQ, FAQ, Foo, Bar",
  "author": "Freddie Mercury",
  "email": "freddie.mercury@example.org",
  "is-active": "true",
  "is-sticky": "false"
}
```
