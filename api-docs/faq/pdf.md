# PDF export of FAQ

This endpoint returns the URL to the PDF of FAQ for the given FAQ ID and the language provided by "Accept-Language".

**URL** : `/api/v2.0/faq/:faqId?filter=pdf`

**HTTP Header** : `Accept-Language: en-US,en`

**URL parameters** : `faqId=[int]` where `faqId` is the ID of a FAQ

**Method** : `GET`

**Auth required** : NO

## Success Response

**Condition** : If the PDF for the FAQ exists.

**Code** : `200 OK`

**Content example**

```json
"https://www.example.org/pdf.php?cat=3&id=142&artlang=de"
```

## Error Responses

**Condition** : If there's no FAQ and PDF for the given FAQ ID.

**Code** : `404 NOT FOUND`

**Content** :

```json
{}
```
