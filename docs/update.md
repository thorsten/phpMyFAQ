# 5. Updating phpMyFAQ

First, please download the latest package of phpMyFAQ. Upgrading to phpMyFAQ 4.2 is possible from the following
versions:

- phpMyFAQ 3.1.x (3.1.1 and later)
- phpMyFAQ 3.2.x
- phpMyFAQ 4.0.x
- phpMyFAQ 4.1.x
- phpMyFAQ 4.2.x

If you're running an older version of phpMyFAQ than listed above, we recommend a new and fresh installation. If you need
support for updating an old FAQ from the 1.x, 2.x, or 3.0 series, [please email us](mailto:thorsten_AT_phpmyfaq_DOT_de).

If you run phpMyFAQ with the official Docker images, follow the update steps in [Production Deployment](deployment.md)
instead.

## Before you upgrade

- Please make sure that you're running at least PHP 8.4 and that all required PHP extensions are loaded (see the
  installation requirements), otherwise the upgrade won't work.
- Make a full backup of your database and your phpMyFAQ directory.
- Enable the maintenance mode: log in as admin into the admin section and enable it under
  _Configuration >> Edit Configuration >> Set FAQ in maintenance mode_. The update wizard refuses to update the
  database while the maintenance mode is disabled.

## The update token

The update wizard changes your database and your configuration, so it only runs for people who are allowed to do that.
If you are still logged in as an administrator when you open the update wizard, nothing changes for you: the wizard
starts right away.

If you are not logged in — which happens on major upgrades, where the old database doesn't match what the new code
expects, so a login is impossible — the wizard asks for an update token instead. phpMyFAQ writes that token into the
file `content/core/config/update-token.php` on your server. Open that file with your FTP client or on the console and
copy the value of `"token"` from it: the file starts with a line `<?php exit; ?>`, followed by a JSON object like
`{"token":"…","created":…}`. Paste the token into the update wizard.

The token is valid for one hour. If it has expired, reload the update page to get a new one. phpMyFAQ deletes the token
file once the database has been updated. The file cannot be read over HTTP.

If phpMyFAQ can't create the token file, make the directory `content/core/config/` writable for your web server.

## Upgrading from phpMyFAQ 3.1.x or 3.2.x

Upgrading from 3.1.x or 3.2.x is a major upgrade.
Your existing templates will not work with phpMyFAQ 4.2.
Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- in the directory **config/**
    - keep the file **database.php**
    - only if using LDAP/ActiveDirectory support also keep the file **ldap.php**
    - only if using EntraID support (phpMyFAQ 3.2.x) also keep the file **azure.php**
- the directory **attachments/**
- the directory **data/**
- the directory **images/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

## Upgrading from phpMyFAQ 4.0.x, 4.1.x, or 4.2.x

### Manual upgrade

Please make a full backup before you run the upgrade!
First, log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Second, you have to delete all files **except**:

- all files in the directory **content/**

Download the latest phpMyFAQ package and copy the contents into your existing FAQ directory, then open the following
URL in your browser:

`http://www.example.com/faq/update`

Click the button of the update script, your version will automatically be updated.

### Online update

If you're running phpMyFAQ 4.0 or later, you can use the built-in online update feature. It is disabled by default on
new installations: enable it first under _Configuration >> Edit Configuration >> Online Update >> Online Update
enabled_.

Log in as admin into the admin section and enable the maintenance mode.
(Configuration >> Edit Configuration >> Set FAQ in maintenance mode)
Then go to the "phpMyFAQ Upgrade" page in the configuration section and click through the update wizard:

1. Check for System Health: this checks if your system is ready for the upgrade
2. Check for Updates: this checks if there is a new version of phpMyFAQ available
3. Download of phpMyFAQ: this downloads the latest version of phpMyFAQ in the background, this can take some seconds
4. Extracting phpMyFAQ: this extracts the downloaded archive, this can take a while
5. Install downloaded package: first, it creates a backup of your current installation, then it copies the downloaded
   files into your installation, and in the end, the database is updated

Note:
Don't use the online update feature with the Docker images: the files it replaces are part of the image and would
be reset when the container is recreated.

### Permission changes in phpMyFAQ 4.2

The update separates reading, writing and publishing FAQs into independent rights, without changing what anybody can
currently do:

- The existing `View FAQs` (`view_faqs`) right starts being enforced for logged-in users. The update grants it to every
  existing user and group, and new accounts receive it automatically, so nothing becomes invisible. Anonymous visitors
  are not affected by the right at all.
- A new `Publish FAQs` (`faq_publish`) right decides whether an FAQ may be set live. The update grants it to every user
  and group that currently holds `Approve FAQs` (`approverec`), including their category and language restrictions, so
  everyone who can publish today can still publish afterwards.

Afterwards you can revoke `faq_publish` from editors who should be able to write but not publish. See section 7.1.5 of
the administration documentation for the details.

### Editorial workflow in phpMyFAQ 4.2

phpMyFAQ 4.2 replaces the boolean active flag on FAQs with a per-language editorial status: **Draft**, **Review**, or
**Published**. The update migrates every existing FAQ automatically — active FAQs become published, inactive ones become
drafts — so public visibility does not change. Transitions into or out of the published state require the `Publish FAQs`
right described above; moving between draft and review only needs the edit rights. See section 7.2.3 of the
administration documentation for how the workflow behaves in the FAQ editor and overview.

### Changes to review when updating to phpMyFAQ 4.2

**Publication window of FAQs (security).** Since phpMyFAQ 4.0, saving an FAQ in the administration discarded its start
and end date: the record was stored as "valid from now, never expires", so a scheduled or an expired FAQ became publicly
visible as soon as anyone saved it. phpMyFAQ 4.0 and 4.1 are affected as well. The FAQ editor has the publication window
fields again, an empty field means no limit, and saves that do not carry a window leave the stored one untouched.
Setting the window requires the `Publish FAQs` right. Windows that were overwritten before the update cannot be restored
automatically: review the FAQs that were meant to be scheduled or to expire and set their window again. Scheduled and
expired FAQs are also hidden from the search, the sitemap, tags, tag search and the FAQ counters of the categories.

**REST API.**

- `GET /api/v4.0/backup/{type}` requires the `x-pmf-token` header.
- With `api.onlyActiveFaqs` disabled, `GET /api/v4.0/faqs` lists FAQs that are not published or outside their
  publication window only for requests with a valid API credential. Anonymous requests get the visible FAQs.
  A list that a credential widened this way is sent with `Cache-Control: private, no-store`.
- Several administration API routes changed their HTTP method to the one the route declares (`PUT`, `DELETE`). Custom
  scripts that call the administration API with `POST` have to be adjusted.

**Passwords.** Passwords set through the administration ("overwrite password"), a password reset link or
`phpmyfaq:install` in an earlier 4.2 pre-release that contain `'`, `"` or `<...>` have to be reset once. The user control
panel no longer trims passwords: leading and trailing blanks are part of the password, as they always were at login.

**Permissions.** Administrators whose own rights are limited to certain languages or categories can no longer reduce a
wider grant. Rights that stay granted keep their language and category restrictions, and removing a right, language or
category outside the administrator's own scope is rejected. Saving a group no longer rewrites the category restrictions
of its rights.

**Login-only mode.** With `security.enableLoginOnly`, guests can reach the OAuth2 endpoints, the captcha image and the
contact form submission. If `security.enableRegistration` is enabled, the registration page and its endpoints are
reachable too; disable registration if a login-only installation must not accept sign-ups.

**LDAP.** A group restriction is enforced with an exact match of the group, and a restriction with an empty group list
refuses every LDAP login.

**Passkeys.** phpMyFAQ tracks the signature counter of an authenticator and refuses a login when the counter does not
increase. The browser origin has to match `main.referenceURL` including the port.

**Visitor session cookie.** With `main.enableUserTracking` enabled, phpMyFAQ sets the `pmf-sid` cookie again (HttpOnly,
SameSite=Lax, Secure on HTTPS, one hour, renewed on each tracked page view). Since 4.0 the cookie was missing and every
tracked page view was counted as a new session, so the session statistics of that time are inflated. Add `pmf-sid` to
your cookie policy or disable user tracking. The cookie is scoped to the host and path of `main.referenceURL`.

**E-mail.**

- Every transport sends a plain-text mail as `text/plain` and an HTML mail as `multipart/alternative` with a plain-text
  alternative. Custom code that puts HTML into `Mail::$message` has to set `$mail->contentType = 'text/html'`.
- Custom mail providers implementing `phpMyFAQ\Mail\MailProviderInterface` have to add the fourth parameter:
  `send(string $recipients, array $headers, string $body, string $htmlBody = '')`.
- Empty the mail queue before updating if you use SMTP or an API provider.
- Installations created with 4.2.0-alpha to 4.2.0-beta.3 have `mail.useQueue` enabled and send no e-mail without a
  running queue worker. Start the worker or disable `mail.useQueue`.

**Search engines.** Web requests no longer wait for Elasticsearch or OpenSearch during start-up; `SEARCH_WAIT_TIMEOUT`
applies to command-line processes only. Web requests use a 2-second connect timeout and wait 5 seconds for an answer
(`SEARCH_REQUEST_TIMEOUT`); the search falls back to the database when the engine does not answer. Creating, importing
and dropping the index in the administration are not limited by `SEARCH_REQUEST_TIMEOUT`.

**Uploads.** SVG uploads have to be well-formed XML and are stored re-serialised without comments and processing
instructions. The PHP DOM extension is required.

**Attachments and backups.** Attachments stored before 4.2 are read from their former location. Backups contain the
tables added in 4.2, except the job queue and the OAuth2 token tables.

**Microsoft Entra ID.** Accounts created before 4.2 are linked to their Entra ID object on the first login.

**Databases.**

- PostgreSQL and SQLite: installations with a table prefix name their indexes `<index>_<prefix>`. The update creates them
  and removes the indexes with the former shared name. If several installations share one schema or file, update the
  prefixed ones first.
- Where a unique index cannot be created because of duplicate values, the update logs a warning and keeps the existing
  index.
- PostgreSQL: table prefixes with uppercase letters are recognised by the table, column and index checks of the update.
- SQL Server: phpMyFAQ sets `SET DATEFORMAT ymd` on every connection.
- Installations that already ran a 4.2.0-RC.3 nightly run the RC.3 update step once more.

**Other changes.**

- Old-style links (`index.php?action=faq&cat=...&id=...`) in answers are only converted when they point to this
  installation. After a move to another domain, links with the former domain are no longer rewritten.
- The tenant quota for users no longer counts the anonymous user.
- Admin log: deleting entries older than 30 days stores the hash of the newest deleted entry, and the integrity check
  reports a log whose oldest entry does not continue from it. A log that was last pruned before the update is checked
  from its first entry on until it is pruned again.
- API keys, OAuth2 clients and OAuth2 scopes have their own administration pages ("Users → API keys" and
  "Configuration → OAuth 2.0 clients"); they no longer have to be created through the administration API or in the
  database.
- FrankenPHP worker mode is not supported with multisite; the worker answers HTTP 503 while `phpmyfaq/multisite/` contains
  instance directories.
- The nginx example configuration has a new location that sandboxes SVG files; compare it with your configuration.

## Modifying templates for phpMyFAQ 4.2

The templates are written in [Twig](https://twig.symfony.com/) and are located in the directory
**assets/templates/default/**. If you use your own template set, compare it with the default template set of the new
version after the update, and copy the changes into your template set. The development documentation describes how to
create a custom template set.

The layout is based on [Bootstrap](https://getbootstrap.com/), we recommend you take a look at its documentation.
Please remember that the style sheets are written with [SCSS](https://sass-lang.com/) and are located in the directory
**assets/scss/**. You have to compile them into CSS with Node.js and PNPM: run `pnpm install` and `pnpm build` (or
`pnpm build:prod` for a minified build) in a Git checkout of phpMyFAQ.

If you need help with theming phpMyFAQ, please don't hesitate to ask in our [forum](https://forum.phpmyfaq.de/).

Note: The character set for all languages and templates is UTF-8. If you notice problems with e.g., German umlauts, you
have to convert your templates to UTF-8 encoding. Please use UNIX file endings \n instead of Windows file endings with
\r\n.
