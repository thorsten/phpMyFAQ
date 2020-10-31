# Add a FAQ

Used to add a new FAQ.

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
    'language' => "[language code]",
    'category_id' => "[category id as integer value]",
    'question' => "[question in plain text]",
    'answer' => "[question in plain text]",
    'keywords' => "[keywords in comma separated plain text]",
    'author' => "[author name in plain text]",
    'email' => "[author email in plain text]",
    'date' => "[date in ISO 8601 format]",
    'active' => "true/false",
    'sticky' => "true/false"
}
```

**Data example**

```json
{
    'language' => "de",
    'category_id' => "1",
    'question' => "Is this the world we created?",
    'answer' => "What did we do it for, is this the world we invaded, against the law, so it seems in the end, is this what we're all living for today",
    'keywords' => "phpMyFAQ, FAQ, Foo, Bar",
    'author' => "Freddie Mercury",
    'email' => "freddie.mercury@example.org",
    'date' => "2020-10-20",
    'active' => "true",
    'sticky' => "false"
}
```
