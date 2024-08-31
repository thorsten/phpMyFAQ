# 4. Use phpMyFAQ

The public phpMyFAQ frontend has a simple, HTML5/CCS3 based default layout based on
[Bootstrap v5.3](https://getbootstrap.com/docs/5.3/).
The header has the main links for all categories, propose new FAQs, add questions, open questions, and a login.
On the left side you only see the main categories.
You can also change the current language at the bottom of the FAQ,
use the global search in the center of the FAQ or use the login box in the upper right if you have a valid user account.
On the right side you see a list of the most popular FAQ records, the latest records, and the sticky FAQ records.
On the pages with the FAQ records, you'll see the other records of the current category and the tag cloud
if you're using
tagging.

## 4.1 Change languages

As written above, there's a select box in the footer for changing the current language.
If you're visiting a phpMyFAQ
powered FAQ, the current language will be the one your browser is using or the language which was selected by the
administrator of the FAQ.
If you change the language, you'll see the categories and records of your chosen language.
If there are no entries in this language, you'll see no entries.
If you're switching to languages with a right to left text direction (for example,
Arabic, Hebrew or Farsi), the whole default layout will be switching, according to the text direction.

**Note:** phpMyFAQ uses a WYSIWYG online editor that has support for multiple languages.
However, phpMyFAQ comes only with English language pack installed so changing the current language will not change the
language of WYSIWYG editor.
If you would like to have WYSIWYG editor in another language, download the latest
[language pack](https://www.tiny.cloud/get-tiny/language-packages/), extract it and upload the extracted files to
admin/editor directory under your phpMyFAQ's installation directory on your web server.

## 4.2 Find As You Type

The Find As You Type feature is directly built into the main search bar at the top of the FAQ with direct access to the
whole FAQ database.
The page will return results while you're typing into the input form.
For performance reasons, only the first 10 results will be suggested.

## 4.3 Advanced search

There's a link to the advanced search just below the main search, where you have more possibilities to find a FAQ.
You can search over all languages if you want to, and it's also possible to search only in one selected category.

## 4.4 All categories

All users will get an overview over all available categories according to their permissions.
The number of FAQs is displayed on this page as well.
If you click into a subcategory, you'll jump into this part of the category tree.
You also see—if available — a category image, a category description the list of the FAQs in the selected category.

## 4.5 Add FAQ

On the _Add FAQ_ page it's possible for all users to add a new FAQ record.
The users have to add a FAQ question, select a category, add an answer, and they have to insert their name and e-mail
address.
If the spam protection is enabled they have to enter the correct captcha code, too.
New FAQ entries won't be displayed by default and have to be activated by an administrator.

If a user is logged in, the name and e-mail address are filled automatically.

## 4.6 Ask questions

On the _Ask question_ page, it's possible for all users to add a new question without an answer.
If the question is submitted, phpMyFAQ checks the words for the question and will do a full text search on the database
with the existing FAQs.
If we found some matches the user will get some recommendations depending on the question he submitted.

The users have to add a question, select a category, and they have to insert their name and e-mail address.
If the spam protection is enabled, they have to enter the correct captcha code, too.
By default, new questions won't be displayed and have to be activated by an administrator.

If a user is logged in, the name and e-mail address are filled automatically.

## 4.7 Open questions

This page displays all open questions, and it's possible for all users to add an answer for this question.
The user will be directed to the [Add FAQ](#44-add-faq) page.
If the spam protection is enabled, they have to enter the correct captcha code, too.

## 4.8 User registration

Users of the FAQ also have the possibility to register themselves.
The user-generated accounts are unactivated by default, and the administrator has to activate them.

## 4.9 User control panel

Every registered user can edit their name, email address and the password.
If the email address of the user is registered at Gravatar, phpMyFAQ uses the images fetched from Gravatar for this
email address.
The users can also enable or disable the permission to show their names in the public frontend, e.g., if they added an
own question.
Additionally, every user can activate the builtin two-factor authentication to enhance security.
You can use authentication apps like Google Authenticator for [iOS](https://apps.apple.com/app/google-authenticator/id388497605)
or [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2).

## 4.10 Related FAQs

For better usability, there are some helpful links below every FAQ entry.
If the administrator added tags to the records, they will be displayed next to five (or more) related articles.
The related articles are based on the content of the current FAQ entry.
On the right side, you'll see links to all entries of the current category and the complete tag cloud of the whole FAQ.
