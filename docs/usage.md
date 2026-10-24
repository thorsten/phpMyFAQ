# 6. Use phpMyFAQ

The public phpMyFAQ frontend has a simple, HTML5 / CSS3-based default layout based on
[Bootstrap v5.3](https://getbootstrap.com/docs/5.3/).
To ensure the best possible user experience for everyone, phpMyFAQ supports three distinct visual modes, which can be
toggled via the theme switcher in the header:

- **Light Mode:** The classic bright interface.
- **Dark Mode:** A reduced-glare interface for low-light environments.
- **High Contrast Mode:** Specifically designed for better accessibility (**A11y**). It features the
  ***Atkinson Hyperlegible Next*** font to maximize character recognition and strictly adheres to high contrast
  standards (WCAG AAA).

By default, the mode follows the system preference of the visitor. The administrator can set a different default
mode, hide the theme switcher, or force one mode for all visitors.

The header has the main links for all categories, adding new FAQs, asking questions, and open questions.
The user menu in the upper right offers the login and the registration, and for logged-in users the user control
panel, bookmarks, messages, and the request for user removal.
Below the header you find the global search with a link to the advanced search.
The start page shows the categories selected by the administrator for the start page, the sticky FAQs, the most
popular FAQs, the latest FAQs, the trending FAQs, the latest news, and the tag cloud if you're using tagging.
In the footer you can change the current language, and you find links to the FAQ overview, the sitemap, the glossary,
the contact form, and, if configured, the privacy policy, terms of service, imprint, cookie settings, and
accessibility statement.

## 6.1 Change languages

As written above, there's a select box in the footer for changing the current language.
If you're visiting a phpMyFAQ powered FAQ, the current language will be the one your browser is using or the language 
which was selected by the administrator of the FAQ.
If you change the language, you'll see the categories and records of your chosen language.
If there are no entries in this language, you'll see no entries.
If you're switching to languages with a right-to-left text direction (for example,
Arabic, Hebrew, or Farsi), the whole default layout will be switching, according to the text direction.

## 6.2 Find As You Type

The Find As You Type feature is directly built into the main search bar at the top of the FAQ with direct access to the
whole FAQ database.
The page will return results while you're typing into the input form.
For performance reasons, only the first results will be suggested; the number is the same as the number of displayed
FAQs per page (default: 10).

## 6.3 Advanced search

There's a link to the advanced search just below the main search, where you have more possibilities to find a FAQ.
You can search over all languages if you want to, and it's also possible to search only in one selected category.

## 6.4 All categories

All users will get an overview of all available categories according to their permissions.
The number of FAQs is displayed on this page as well.
If you click into a subcategory, you'll jump into this part of the category tree.
You also see — if available — a category image, a category description, and the list of the FAQs in the selected
category.

## 6.5 Add FAQ

On the _Add FAQ_ page it's possible to add a new FAQ record.
Guests can use this page if the administrator enabled "Allow adding new FAQs for guests", logged-in users need the
permission to add FAQs. Otherwise, guests are redirected to the login page.
The users have to add a FAQ question, select a category, add an answer, and they have to insert their name and e-mail
address.
If the spam protection is enabled, they have to enter the correct captcha code, too.
New FAQ entries won't be displayed by default and have to be activated by an administrator.

If a user is logged in, the name and e-mail address are filled automatically.

## 6.6 Ask questions

On the _Ask question_ page, it's possible for all users to add a new question without an answer.
Questions can only be submitted if the administrator has enabled the setting "Enable 'Ask question'" in the
configuration.

Note for Guests:
Guests can only submit questions if the setting "Allow adding questions for guests" is enabled in the configuration.
If this setting is disabled, guests must log in to access the form.

If the question is submitted, phpMyFAQ checks the words for the question and will do a full text search on the database
with the existing FAQs.
If phpMyFAQ finds some matches, the user will get some recommendations depending on the submitted question.
This smart answering can be disabled by the administrator.

The users have to add a question, select a category, and they have to insert their name and e-mail address.
If the spam protection is enabled, they have to enter the correct captcha code, too.
By default, new questions won't be displayed and have to be activated by an administrator.

If a user is logged in, the name and email address are filled automatically.

## 6.7 Open questions

This page displays all open questions. Depending on the configuration and user permissions, the page offers the following options:

*   **Answer question:** Users with the required permissions can add an answer. This is also available for guests if the setting **"Allow adding new FAQs for guests"** is enabled, as answering an open question technically creates a new FAQ entry. By clicking the button, the user is directed to the "Add FAQ" page, where the question and the category selected by the inquirer are automatically pre-filled into the form. If spam protection is enabled, the correct captcha code must also be entered.
*   **Read answer:** If the option **"Close open question after answer?"** is enabled and a question has already been answered, a direct link to the corresponding FAQ entry is displayed. This allows users to access the existing solution directly.

If both the "Allow adding new FAQs for guests" setting and the "Close open question after answer?" setting are disabled or a user does not have the corresponding **user permissions**, the page serves as a read-only overview. In this case, **guests and restricted users** can view the questions but are not authorized to provide answers or access existing answers through this list.

Furthermore, the **number of questions** that have not yet been approved or published by an administrator is displayed at the bottom of the list. This serves as a confirmation for inquirers that their submitted question has been successfully received by the system and is currently awaiting moderation.

## 6.8 User registration

Users of the FAQ also have the possibility to register themselves, if the administrator enabled the registration
for visitors.
The user-generated accounts are unactivated by default, and the administrator has to activate them.
The administrator can also restrict the registration to email addresses of specific domains.
If WebAuthn support is enabled by the administrator, the user can also register for login via passkey.

## 6.9 User control panel

Every registered user can edit their name, email address, and the password in the _User Control Panel_.
If the email address of the user is registered at Gravatar, phpMyFAQ uses the images fetched from Gravatar for this
email address.
The users can also enable or disable the permission to show their names in the public frontend, e.g., if they added 
their own question.
Additionally, every user can activate the builtin two-factor authentication to enhance security.
You can use authentication apps like Google Authenticator for [iOS](https://apps.apple.com/app/google-authenticator/id388497605)
or [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2).
If enabled by the administrator, the user can also register for login via passkey.
If Web Push notifications are enabled, users can subscribe to browser notifications about new FAQs and questions.
With _Download your data_, every user can download a ZIP file containing their stored user data.

On the separate page _Request user removal_, logged-in users can request the deletion of their account and personal
data. The request is sent to the administrators, who delete the account manually.

## 6.10 Related FAQs

For better usability, there's a sidebar next to every FAQ entry.
If the administrator added tags to the records, they will be displayed together with the related entries
(five by default, configurable by the administrator) and the categories of the current FAQ entry.
The related entries are based on the content of the current FAQ entry.

## 6.11 Login and password reset

Registered users can log in with their username (or email address, if configured) and password.
Depending on the configuration, the login page also offers _Sign in with Microsoft_ (Microsoft Entra ID),
_Sign in with Keycloak_, a sign-in button for a generic OpenID Connect provider, and _Login with Passkey_.
If two-factor authentication is enabled for the account, the user has to enter the code from the authentication app
after the password.

With _Forgot your password?_ users can request a password reset. After entering the username and the email address,
phpMyFAQ sends an email with a link to set a new password.

## 6.12 FAQ page features

In the sidebar of every FAQ entry, users find some more features:

- **Rating:** Users can rate every FAQ with one to five stars.
- **Bookmarks:** Logged-in users can bookmark FAQs. All bookmarks are listed on the _Bookmarks_ page in the user menu.
- **PDF version:** Every FAQ can be downloaded as a PDF file.
- **Share this FAQ:** If enabled by the administrator, users can share a link to the FAQ via LinkedIn, Reddit,
  WhatsApp, or Telegram, or copy the link.
- **Translations:** If the FAQ is available in other languages, users can switch to another language version.

Attached files and comments are listed below the answer. If comments are allowed for the FAQ, users can add
comments; guests can only comment if the administrator allowed comments for guests.
Terms from the glossary are highlighted in the answer and show their definition as a tooltip.

## 6.13 Tags, sitemap, glossary, and news

- **Tags:** Clicking on a tag shows all FAQs with this tag.
- **FAQ Overview:** The _FAQ Overview_ page lists all FAQs with their answers, grouped by category.
- **Sitemap:** The sitemap lists all FAQs by the first letter of their question.
- **Glossary:** The _FAQ Glossary_ page lists all glossary terms and their definitions.
- **News:** The latest news are shown on the start page. Every news entry has its own page, where users can add
  comments if allowed.
- **Contact:** The _Contact_ page offers a contact form to send a message to the administrator.

## 6.14 Messages

Logged-in users can send private messages to other registered users on the _Messages_ page in the user menu.
