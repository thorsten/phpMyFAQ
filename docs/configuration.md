# 8. Configuration reference

This document lists all configuration keys used by phpMyFAQ, their default values, and a description of what
each setting controls. These values are set during installation and can be changed in the admin panel under
**Configuration**.

## 8.1 General

### 8.1.1 Main settings

| Key                                | Label | Default | Description |
|------------------------------------|-------|---------|-------------|
| `main.currentVersion`              | phpMyFAQ Version | *(auto-detected)* | The currently installed phpMyFAQ version. Set automatically during installation and upgrades. |
| `main.currentApiVersion`           | API Version | *(auto-detected)* | The current REST API version. Set automatically. |
| `main.language`                    | Standard language | *(set during install)* | The default language for the FAQ frontend and admin panel. |
| `main.languageDetection`           | Enable automatic language detection via web browser | `true` | When enabled, phpMyFAQ tries to detect the user's preferred language from their browser settings. |
| `main.phpMyFAQToken`               | phpMyFAQ Token | *(auto-generated)* | A unique token generated during installation, used internally for security purposes. |
| `main.referenceURL`                | URL of your FAQ | *(set during install)* | The base URL of your phpMyFAQ installation (e.g. `https://www.example.org/faq/`). |
| `main.administrationMail`          | Email address of the Admin | `webmaster@example.org` | The administrator's email address, used as the sender/recipient for system notifications. |
| `main.contactInformation`          | Contact information | *(empty)* | Free-text contact information displayed on the contact page. |
| `main.enableAdminLog`              | Use admin log? | `true` | Enables logging of administrative actions for auditing purposes. |
| `main.enableUserTracking`          | Enable user tracking | `true` | Tracks user visits and page views for statistics. |
| `main.metaDescription`             | Description | `phpMyFAQ should be the answer for all questions in life` | The HTML meta description used on the FAQ pages. |
| `main.metaPublisher`               | Name of the Publisher | *(set during install)* | The publisher name used in HTML meta tags. |
| `main.titleFAQ`                    | Title of your FAQ | `phpMyFAQ Codename Palaimon` | The main title displayed in the FAQ header and browser title bar. |
| `main.enableWysiwygEditor`         | Enable bundled WYSIWYG editor | `true` | Enables the rich-text WYSIWYG editor in the admin panel for creating and editing FAQ entries. |
| `main.enableWysiwygEditorFrontend` | Enable bundled WYSIWYG editor in frontend | `false` | Enables the WYSIWYG editor for frontend users when submitting new FAQs. |
| `main.enableMarkdownEditor`        | Enable bundled Markdown editor | `false` | Enables a Markdown editor as an alternative to the WYSIWYG editor. |
| `main.enableCommentEditor`         | Enable WYSIWYG editor for comments (logged-in users only) | `false` | When enabled, logged-in users can use the WYSIWYG editor to format their comments. |
| `main.dateFormat`                  | Date format | `Y-m-d H:i` | The PHP date format string used to display dates throughout the FAQ. |
| `main.maintenanceMode`             | Set FAQ in maintenance mode | `false` | When enabled, the FAQ is taken offline and only administrators can access it. Useful during updates or maintenance. |
| `main.enableGravatarSupport`       | Gravatar Support | `false` | Enables Gravatar profile images for users based on their email address. |
| `main.customPdfHeader`             | Custom PDF Header (HTML allowed) | *(empty)* | Custom HTML content to include in the header of exported PDF files. |
| `main.customPdfFooter`             | Custom PDF Footer (HTML allowed) | *(empty)* | Custom HTML content to include in the footer of exported PDF files. |
| `main.enableSmartAnswering`        | Enable smart answering for user questions | `true` | When a user submits a question, the system suggests existing FAQ entries that might already answer it. |
| `main.enableCategoryRestrictions`  | Enable category restrictions | `true` | Enables permission-based access restrictions on categories, allowing you to limit category visibility to specific users or groups. |
| `main.enableSendToFriend`          | Enable send to friends | `true` | Allows users to share FAQ entries via email with a "Send to friend" feature. |
| `main.privacyURL`                  | URL for Privacy note | *(empty)* | URL to your privacy policy page. Use `page:slug` for a custom phpMyFAQ page or a full URL for an external page. |
| `main.termsURL`                    | URL for Terms of Service | *(empty)* | URL to your terms of service page. Use `page:slug` for a custom phpMyFAQ page or a full URL for an external page. |
| `main.imprintURL`                  | URL for Imprint | *(empty)* | URL to your imprint/legal notice page. Use `page:slug` for a custom phpMyFAQ page or a full URL for an external page. |
| `main.cookiePolicyURL`             | URL for Cookie Policy | *(empty)* | URL to your cookie policy page. Use `page:slug` for a custom phpMyFAQ page or a full URL for an external page. |
| `main.accessibilityStatementURL`   | URL for Accessibility Statement | *(empty)* | URL to your accessibility statement page. Use `page:slug` for a custom phpMyFAQ page or a full URL for an external page. |
| `main.enableAutoUpdateHint`        | Automatic check for new versions | `true` | When enabled, the admin panel checks for new phpMyFAQ versions and displays an update notification. |
| `main.enableAskQuestions`          | Enable "Ask question" | `false` | Enables the "Ask a question" feature that allows users to submit questions from the frontend. |
| `main.enableNotifications`         | Enable notifications | `false` | Enables email notifications for events such as new questions, new FAQs, or new comments. |
| `main.botIgnoreList`               | Bot-ignore-list (Separate with commas) | *(long list of known bots)* | A comma-separated list of user-agent strings to ignore in user tracking. Common search engine crawlers and bots are included by default. |

### 8.1.2 FAQ Records settings

| Key                                  | Label | Default | Description |
|--------------------------------------|-------|---------|-------------|
| `records.numberOfRecordsPerPage`     | Number of displayed topics per page | `10` | How many FAQ entries are shown per page in category listings. |
| `records.numberOfShownNewsEntries`   | Number of news articles | `3` | The number of news entries displayed on the start page. |
| `records.defaultActivation`          | Automatically activate new records | `false` | When enabled, newly created FAQ entries are immediately visible. When disabled, an admin must activate them first. |
| `records.defaultAllowComments`       | Allow comments for new records | `false` | When enabled, comments are allowed by default on newly created FAQ entries. |
| `records.enableVisibilityQuestions`  | Disable visibility of new questions? | `false` | Controls whether newly submitted user questions are visible to other users on the open questions page. |
| `records.numberOfRelatedArticles`    | Number of related entries | `5` | The number of related FAQ entries displayed alongside each FAQ. |
| `records.orderby`                    | Record sorting (according to property) | `id` | The property used to sort FAQ entries. Options include `id`, `title`, `date`, and `author`. |
| `records.sortby`                     | Record sorting (descending or ascending) | `DESC` | The sort direction for FAQ entries: `DESC` (newest first) or `ASC` (oldest first). |
| `records.orderingPopularFaqs`        | Sorting of the top FAQs | `visits` | How the most popular FAQs are determined. Options: `visits` (by page views) or `voting` (by user ratings). |
| `records.disableAttachments`         | Enable visibility of attachments | `true` | Controls whether file attachments are visible and downloadable on FAQ entries. Despite the key name, `true` means attachments are shown. |
| `records.maxAttachmentSize`          | Maximum size for attachments in bytes | `100000` | The maximum allowed file size for attachments in bytes (default ~100 KB). |
| `records.attachmentsPath`            | Path where attachments will be saved | `content/user/attachments` | The directory path for storing uploaded attachments, relative to the web root. |
| `records.attachmentsStorageType`     | Attachment storage type | `0` | The storage backend for attachments. `0` = filesystem. |
| `records.enableAttachmentEncryption` | Enable attachment encryption | `false` | When enabled, uploaded attachments are encrypted on disk. Only takes effect when attachments are enabled. |
| `records.defaultAttachmentEncKey`    | Default attachment encryption key | *(empty)* | The encryption key used for attachment encryption. **Warning:** Do not change this after it has been set and files have been encrypted. |
| `records.enableCloseQuestion`        | Close open question after answer? | `false` | When enabled, open user questions are automatically closed once a matching FAQ is created. |
| `records.enableDeleteQuestion`       | Delete open question after answer? | `false` | When enabled, open user questions are automatically deleted once a matching FAQ is created. |
| `records.randomSort`                 | Sort FAQs randomly | `false` | When enabled, FAQ entries within a category are displayed in random order. |
| `records.allowCommentsForGuests`     | Allow comments for guests | `true` | Allows non-logged-in visitors to post comments on FAQ entries. |
| `records.allowQuestionsForGuests`    | Allow adding questions for guests | `true` | Allows non-logged-in visitors to submit questions. |
| `records.allowNewFaqsForGuests`      | Allow adding new FAQs for guests | `true` | Allows non-logged-in visitors to submit new FAQ entries for review. |
| `records.hideEmptyCategories`        | Hide empty categories | `false` | When enabled, categories that contain no FAQ entries are hidden from the frontend. |
| `records.allowDownloadsForGuests`    | Allow downloads for guests | `false` | When enabled, non-logged-in visitors can download attachments. |
| `records.numberMaxStoredRevisions`   | Maximum stored revisions | `10` | The maximum number of revisions to keep per FAQ entry. Older revisions are discarded. |
| `records.enableAutoRevisions`        | Allow versioning of FAQ changes | `false` | When enabled, a new revision is automatically created every time a FAQ entry is saved. |
| `records.orderStickyFaqsCustom`      | Custom ordering of sticky records | `false` | When enabled, sticky (pinned) FAQ entries can be manually reordered in the admin panel. |
| `records.allowedMediaHosts`          | Allowed external hosts for media content | `www.youtube.com` | A comma-separated list of external hostnames allowed for embedded media content (e.g. videos). |

### 8.1.3 Search settings

| Key                              | Label | Default | Description |
|----------------------------------|-------|---------|-------------|
| `search.numberSearchTerms`       | Number of listed search terms | `10` | The number of popular search terms displayed to users. |
| `search.relevance`               | Sort by relevance | `thema,content,keywords` | Defines the order of fields used for relevance sorting in search results. |
| `search.enableRelevance`         | Activate relevance support? | `false` | When enabled, search results are sorted by relevance rather than by default ordering. |
| `search.enableHighlighting`      | Highlight search terms | `true` | Highlights the matching search terms in search results for easier scanning. |
| `search.searchForSolutionId`     | Search for solution ID | `true` | When enabled, users can search by the unique solution ID of a FAQ entry. |
| `search.popularSearchTimeWindow` | Time window for popular searches (days) | `180` | The number of days to look back when calculating popular search terms. |
| `search.enableElasticsearch`     | Enable Elasticsearch support | `false` | Enables Elasticsearch as the search backend instead of the built-in database search. Requires a running Elasticsearch instance. |
| `search.enableOpenSearch`        | Enable OpenSearch support | `false` | Enables OpenSearch as the search backend. Requires a running OpenSearch instance. |

### 8.1.4 Translation settings

| Key                                 | Label | Default | Description |
|-------------------------------------|-------|---------|-------------|
| `translation.provider`              | Translation service provider | `none` | The translation service to use for automatic FAQ translation. Options: `none`, `google`, `deepl`, `azure`, `amazon`, `libretranslate`. |
| `translation.googleApiKey`          | Google Cloud Translation API key | *(empty)* | The API key for Google Cloud Translation. |
| `translation.deeplApiKey`           | DeepL API key | *(empty)* | The API key for the DeepL translation service. |
| `translation.deeplUseFreeApi`       | Use DeepL Free API (instead of Pro) | `true` | When enabled, uses the free tier of the DeepL API instead of the paid Pro version. |
| `translation.azureKey`              | Azure Translator API key | *(empty)* | The API key for Microsoft Azure Translator. |
| `translation.azureRegion`           | Azure region | *(empty)* | The Azure region for the Translator service (e.g. `eastus`, `westeurope`). |
| `translation.amazonAccessKeyId`     | Amazon Translate AWS Access Key ID | *(empty)* | The AWS Access Key ID for Amazon Translate. |
| `translation.amazonSecretAccessKey` | Amazon Translate AWS Secret Access Key | *(empty)* | The AWS Secret Access Key for Amazon Translate. |
| `translation.amazonRegion`          | Amazon Translate AWS region | `us-east-1` | The AWS region for Amazon Translate (e.g. `us-east-1`, `eu-west-1`). |
| `translation.libreTranslateUrl`     | LibreTranslate server URL | `https://libretranslate.com` | The URL of the LibreTranslate server instance. |
| `translation.libreTranslateApiKey`  | LibreTranslate API key (optional) | *(empty)* | The API key for the LibreTranslate service, if required by the server. |

## 8.2 Security

### 8.2.1 Security settings

| Key                                         | Label | Default | Description |
|---------------------------------------------|-------|---------|-------------|
| `security.permLevel`                        | Permission level | `basic` | The permission model: `basic` (user-level permissions only) or `medium` (user and group permissions). |
| `security.ipCheck`                          | Check the IP in administration | `false` | When enabled, the admin session is bound to the user's IP address for additional security. |
| `security.enableLoginOnly`                  | Complete secured FAQ | `false` | When enabled, the entire FAQ is accessible only to logged-in users. |
| `security.bannedIPs`                        | Ban these IPs | *(empty)* | A list of IP addresses that are blocked from accessing the FAQ. |
| `security.ssoSupport`                       | Enable Single Sign On Support | `false` | Enables Single Sign-On (SSO) authentication using external identity providers. |
| `security.ssoLogoutRedirect`                | Single Sign On logout redirect service URL | *(empty)* | The URL to redirect users to after logging out when SSO is enabled. |
| `security.useSslForLogins`                  | Only allow logins over SSL connection? | `false` | When enabled, login forms are only served over HTTPS connections. |
| `security.useSslOnly`                       | FAQ with SSL only | `false` | When enabled, the entire FAQ is forced to use HTTPS. |
| `security.forcePasswordUpdate`              | Force password update | `false` | When enabled, users are required to change their password on next login. |
| `security.enableRegistration`               | Enable registration for visitors | `true` | Allows new users to register an account on the FAQ. |
| `security.domainWhiteListForRegistrations`  | Allowed hosts for registrations | *(empty)* | A list of allowed email domains for new registrations. Leave empty to allow all domains. |
| `security.enableSignInWithMicrosoft`        | Enable Sign in with Microsoft Entra ID | `false` | Enables authentication via Microsoft Entra ID (formerly Azure AD). |
| `keycloak.enable`                           | Enable Keycloak sign-in | `false` | Enables OpenID Connect authentication via Keycloak for the frontend and admin login forms. |
| `keycloak.baseUrl`                          | Keycloak base URL | *(empty)* | Base URL of the Keycloak server, for example `https://sso.example.com`. |
| `keycloak.realm`                            | Realm | *(empty)* | Keycloak realm used for phpMyFAQ authentication. |
| `keycloak.clientId`                         | Client ID | *(empty)* | OIDC client identifier configured in Keycloak. |
| `keycloak.clientSecret`                     | Client secret | *(empty)* | Client secret configured for the Keycloak OIDC client. |
| `keycloak.redirectUri`                      | Redirect URI | *(empty)* | Callback URL registered in the Keycloak client, usually `https://faq.example.com/auth/keycloak/callback`. |
| `keycloak.scopes`                           | Scopes | `openid profile email` | Space-separated scopes requested during login. |
| `keycloak.autoProvision`                    | Automatically create phpMyFAQ users on first Keycloak login | `false` | When enabled, phpMyFAQ creates a local user automatically if no matching account exists yet. |
| `keycloak.groupAutoAssign`                  | Automatically assign phpMyFAQ groups from Keycloak roles | `false` | When enabled and permission level `medium` is active, phpMyFAQ assigns users to groups derived from Keycloak roles on login. |
| `keycloak.groupSyncOnLogin`                 | Synchronize mapped phpMyFAQ groups on login | `false` | When enabled, phpMyFAQ also removes stale memberships for groups managed by the Keycloak role mapping during login. |
| `keycloak.groupMapping`                     | Role to group mapping | *(empty)* | JSON object mapping Keycloak role names to phpMyFAQ group names, for example `{"admin":"Administrators","faq-editors":"Editors"}`. Only mapped roles are managed for assignment and synchronization. |
| `keycloak.logoutRedirectUrl`                | Logout redirect URL | *(empty)* | URL users should be redirected to after logging out from Keycloak, for example `https://faq.example.com/`. |
| `security.enableGoogleReCaptchaV2`          | Enable Invisible Google ReCAPTCHA v2 | `false` | Enables Google reCAPTCHA v2 to protect forms from spam and abuse. |
| `security.googleReCaptchaV2SiteKey`         | Google ReCAPTCHA v2 site key | *(empty)* | The site key from your Google reCAPTCHA v2 registration. |
| `security.googleReCaptchaV2SecretKey`       | Google ReCAPTCHA v2 secret key | *(empty)* | The secret key from your Google reCAPTCHA v2 registration. |
| `security.loginWithEmailAddress`            | Login only with email address | `false` | When enabled, users must log in using their email address instead of a username. |
| `security.enableWebAuthnSupport`            | Activate WebAuthn support (Experimental) | `false` | Enables passwordless authentication via WebAuthn (hardware security keys, biometrics). This feature is experimental. |
| `security.enableAdminSessionTimeoutCounter` | Activate admin session timeout counter | `true` | Displays a countdown timer in the admin panel showing the remaining session time before automatic logout. |

### 8.2.2 Spam protection settings

| Key                        | Label | Default | Description |
|----------------------------|-------|---------|-------------|
| `spam.checkBannedWords`    | Check public form content against banned words | `true` | Checks user-submitted content against a list of banned words to prevent spam. |
| `spam.enableCaptchaCode`   | Use a captcha code to allow public form submission | *(auto-detected)* | Enables a captcha on public forms. Automatically enabled if the PHP GD extension is available. |
| `spam.enableSafeEmail`     | Print user email in a safe way | `true` | Obfuscates email addresses on the frontend to prevent harvesting by spam bots. |
| `spam.manualActivation`    | Manually activate new users | `true` | When enabled, new user registrations require manual approval by an administrator. |
| `spam.mailAddressInExport` | Show email address in exports | `true` | When enabled, user email addresses are included in FAQ exports (PDF, HTML, etc.). |

## 8.3 Layout

### 8.2.1 Layout settings

| Key                             | Label | Default | Description |
|---------------------------------|-------|---------|-------------|
| `layout.templateSet`            | Template set to be used | `default` | The active frontend template/theme. Templates are located in the `assets/themes/` directory. |
| `layout.enablePrivacyLink`      | Activate link to privacy policy | `true` | Displays a link to the privacy policy in the footer. Requires `main.privacyURL` to be set. |
| `layout.enableCookieConsent`    | Activate Cookie Consent | `true` | Enables a cookie consent banner for GDPR/privacy compliance. |
| `layout.contactInformationHTML` | Contact information as HTML? | `false` | When enabled, the contact information field is rendered as HTML instead of plain text. |
| `layout.customCss`              | Custom CSS | *(empty)* | Custom CSS rules applied to the frontend. Use CSS only, no HTML or JavaScript. |

### 8.3.2 SEO settings

| Key                      | Label | Default | Description |
|--------------------------|-------|---------|-------------|
| `seo.title`              | SERP title | `phpMyFAQ Codename Porus` | The title used in search engine result pages (SERPs) and the browser title bar. |
| `seo.description`        | SERP description | `phpMyFAQ should be the answer for all questions in life` | The meta description used in search engine result pages. |
| `seo.enableXMLSitemap`   | Enable XML sitemap | `true` | Generates an XML sitemap at `/sitemap.xml` for search engine indexing. |
| `seo.enableRichSnippets` | Enable Rich Snippets | `false` | Adds structured data (JSON-LD) to FAQ pages for enhanced search engine result display. |
| `seo.metaTagsHome`       | Robots Meta Tags start page | `index, follow` | The robots meta tag directive for the start page. |
| `seo.metaTagsFaqs`       | Robots Meta Tags FAQs | `index, follow` | The robots meta tag directive for FAQ entry pages. |
| `seo.metaTagsCategories` | Meta Tags category pages | `index, follow` | The robots meta tag directive for category pages. |
| `seo.metaTagsPages`      | Robots Meta Tags static pages | `index, follow` | The robots meta tag directive for static/custom pages. |
| `seo.metaTagsAdmin`      | Robots Meta Tags Admin | `noindex, nofollow` | The robots meta tag directive for admin pages. Should remain `noindex, nofollow`. |
| `seo.contentRobotsText`  | Content for robots.txt | *(see below)* | Custom content for the `robots.txt` file. Default disallows `/admin/` and includes the sitemap URL. |
| `seo.contentLlmsText`    | Content for llms.txt | *(see below)* | Custom content for the `llms.txt` file, providing information about AI/LLM training data availability. |

## 8.4 Communication

### 8.4.1 Mail settings

| Key                                         | Label | Default | Description |
|---------------------------------------------|-------|---------|-------------|
| `mail.noReplySenderAddress`                 | No reply address for emails | *(empty)* | The "no-reply" sender address for outgoing emails. If empty, the administration email is used. |
| `mail.remoteSMTP`                           | Use remote SMTP server | `false` | When enabled, phpMyFAQ sends emails through an external SMTP server instead of the local `mail()` function. |
| `mail.remoteSMTPServer`                     | Server address | *(empty)* | The hostname or IP address of the SMTP server. |
| `mail.remoteSMTPUsername`                   | Username | *(empty)* | The username for SMTP authentication. |
| `mail.remoteSMTPPassword`                   | Password | *(empty)* | The password for SMTP authentication. |
| `mail.remoteSMTPPort`                       | SMTP server port | `25` | The port number of the SMTP server (common values: 25, 465, 587). |
| `mail.remoteSMTPDisableTLSPeerVerification` | Disable SMTP TLS peer verification (not recommended) | `false` | Disables TLS certificate verification for the SMTP connection. Only use this for testing or with self-signed certificates. |
| `mail.useQueue`                             | Use background worker queue for email delivery | `true` | When enabled, emails are queued and sent in the background instead of being sent immediately during the request. |
| `mail.provider`                             | Mail provider | `smtp` | The mail delivery provider. Options: `smtp`, `sendgrid`, `ses`, `mailgun`. |
| `mail.sendgridApiKey`                       | SendGrid API key | *(empty)* | API key for the SendGrid email delivery service. |
| `mail.sesAccessKeyId`                       | Amazon SES Access Key ID | *(empty)* | AWS Access Key ID for Amazon Simple Email Service (SES). |
| `mail.sesSecretAccessKey`                   | Amazon SES Secret Access Key | *(empty)* | AWS Secret Access Key for Amazon SES. |
| `mail.sesRegion`                            | Amazon SES region | `us-east-1` | The AWS region for Amazon SES (e.g. `us-east-1`, `eu-west-1`). |
| `mail.mailgunApiKey`                        | Mailgun API key | *(empty)* | API key for the Mailgun email delivery service. |
| `mail.mailgunDomain`                        | Mailgun domain | *(empty)* | The sending domain configured in your Mailgun account. |
| `mail.mailgunRegion`                        | Mailgun region | `eu` | The Mailgun region: `us` or `eu`. |

### 8.4.2 Push notification settings

| Key                    | Label | Default | Description |
|------------------------|-------|---------|-------------|
| `push.enableWebPush`   | Enable Web Push notifications | `false` | Enables browser-based Web Push notifications for users. |
| `push.vapidPublicKey`  | VAPID Public Key | *(empty)* | The VAPID public key for Web Push. Generated automatically when Web Push is configured. |
| `push.vapidPrivateKey` | VAPID Private Key | *(empty)* | The VAPID private key for Web Push. Keep this secret. |
| `push.vapidSubject`    | VAPID Subject | *(empty)* | The VAPID subject identifier, typically a `mailto:` address or URL for the push service operator. |

## 8.5 Integrations

### 8.5.1 API settings

| Key                        | Label | Default | Description |
|----------------------------|-------|---------|-------------|
| `api.enableAccess`         | REST API enabled | `true` | Enables the REST API for external integrations and third-party applications. |
| `api.apiClientToken`       | API Client Token | *(empty)* | An optional token that clients must provide to authenticate API requests. Leave empty for public API access. |
| `api.onlyActiveFaqs`       | API returns only active FAQs | `true` | When enabled, the API only returns FAQ entries that have been activated. |
| `api.onlyActiveCategories` | API returns only active categories | `true` | When enabled, the API only returns categories that are active. |
| `api.onlyPublicQuestions`  | API returns only public questions | `true` | When enabled, the API only returns questions that are marked as public. |
| `api.ignoreOrphanedFaqs`   | API ignores orphaned FAQs | `true` | When enabled, the API excludes FAQ entries that are not assigned to any category. |
| `api.rateLimit.requests`   | API rate limit | `100` | The maximum number of API requests allowed within the rate limit interval. |
| `api.rateLimit.interval`   | API rate limit interval in seconds | `3600` | The time window (in seconds) for the API rate limit. Default is 1 hour (3600 seconds). |

### 8.5.2 OAuth 2.0 settings

| Key                      | Label | Default | Description |
|--------------------------|-------|---------|-------------|
| `oauth2.enable`          | Enable OAuth 2.0 authentication | `false` | Enables OAuth 2.0 authentication for the REST API, allowing clients to obtain access tokens for API access. |
| `oauth2.accessTokenTTL`  | Access token time to live (seconds) | `3600` | The lifespan of issued access tokens in seconds. Default is 1 hour (3600 seconds). |
| `oauth2.refreshTokenTTL` | Refresh token time to live (seconds) | `86400` | The lifespan of issued refresh tokens in seconds. Default is 24 hours (86400 seconds). |
| `oauth2.authCodeTTL`     | Authorization code time to live (seconds) | `300` | The lifespan of authorization codes in seconds. Default is 5 minutes (300 seconds). |
| `oauth2.encryptionKey`   | OAuth 2.0 encryption key | *(empty)* | A random encryption key used for securing OAuth tokens. | 
| `oauth2.privateKeyPath`  | Path to private key for OAuth 2.0 | *(empty)* | The file path to the private key used for signing OAuth tokens. |
| `oauth2.publicKeyPath`   | Path to public key for OAuth 2.0 | *(empty)* | The file path to the public key used for verifying OAuth tokens. |

### 8.5.3 LDAP settings

| Key                                           | Label | Default | Description |
|-----------------------------------------------|-------|---------|-------------|
| `ldap.ldapSupport`                            | Enable LDAP support? | `false` | Enables LDAP/Active Directory authentication. |
| `ldap.ldap_mapping.name`                      | LDAP mapping for name | `cn` | The LDAP attribute mapped to the user's display name. Use `cn` for Active Directory. |
| `ldap.ldap_mapping.username`                  | LDAP mapping for username | `samAccountName` | The LDAP attribute mapped to the username. Use `samAccountName` for Active Directory. |
| `ldap.ldap_mapping.mail`                      | LDAP mapping for email | `mail` | The LDAP attribute mapped to the user's email address. |
| `ldap.ldap_mapping.memberOf`                  | LDAP mapping for "member of" | *(empty)* | The LDAP attribute used for group membership when using LDAP groups. |
| `ldap.ldap_use_domain_prefix`                 | LDAP domain prefix | `true` | When enabled, the domain prefix (e.g. `DOMAIN\username`) is used for authentication. |
| `ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION` | LDAP protocol version | `3` | The LDAP protocol version to use. Version 3 is recommended. |
| `ldap.ldap_options.LDAP_OPT_REFERRALS`        | LDAP referrals | `0` | Controls LDAP referral chasing. Set to `0` to disable (recommended for Active Directory). |
| `ldap.ldap_use_memberOf`                      | Enable LDAP group support | `false` | Enables group-based access control using the LDAP `memberOf` attribute. |
| `ldap.ldap_use_sasl`                          | Enable LDAP SASL support | `false` | Enables SASL (Simple Authentication and Security Layer) for LDAP connections. |
| `ldap.ldap_use_multiple_servers`              | Enable multiple LDAP servers support | `false` | Enables failover to multiple LDAP servers. |
| `ldap.ldap_use_anonymous_login`               | Enable anonymous LDAP connections | `false` | Allows anonymous (unauthenticated) LDAP connections for user lookups. |
| `ldap.ldap_use_dynamic_login`                 | Enable LDAP dynamic user binding | `false` | When enabled, the user's own credentials are used to bind to LDAP instead of a service account. |
| `ldap.ldap_dynamic_login_attribute`           | LDAP attribute for dynamic user binding | `uid` | The LDAP attribute used to construct the bind DN for dynamic login. Use `uid` for Active Directory. |
| `ldap.ldap_use_group_restriction`             | Restrict login to specific Active Directory groups | `false` | When enabled, only users belonging to specific AD groups can log in. |
| `ldap.ldap_group_allowed_groups`              | Comma-separated list of allowed AD groups | *(empty)* | The AD groups allowed to log in. Partial matches are supported. |
| `ldap.ldap_group_auto_assign`                 | Automatically assign users to phpMyFAQ groups based on AD membership | `false` | When enabled, users are automatically added to phpMyFAQ groups that match their AD group membership. |
| `ldap.ldap_group_mapping`                     | JSON mapping of AD groups to phpMyFAQ groups | *(empty)* | A JSON object mapping AD group names to phpMyFAQ group names, e.g. `{"Domain Admins": "Administrators"}`. |

### 8.5.4 Storage settings

| Key                                | Label | Default | Description |
|------------------------------------|-------|---------|-------------|
| `storage.useRedisForConfiguration` | Enable Redis for configuration storage | `false` | When enabled, phpMyFAQ stores configuration in Redis for faster access. Falls back to the database when disabled. |
| `storage.redisDsn`                 | Redis DSN for configuration storage | `tcp://redis:6379?database=1` | The Redis connection string for configuration storage (e.g. `tcp://127.0.0.1:6379?database=1`). |
| `storage.redisPrefix`              | Redis key prefix for configuration storage | `pmf:config:` | The key prefix used in Redis. Use different prefixes when sharing one Redis server across multiple phpMyFAQ instances. |
| `storage.redisConnectTimeout`      | Redis connection timeout in seconds | `1.0` | The timeout (in seconds) for establishing a connection to Redis. |

### 8.5.5 Session settings (not yet available in the admin panel)

| Key                | Label | Default | Description |
|--------------------|-------|---------|-------------|
| `session.handler`  | Session handler | `files` | The session storage backend. Options: `files` (default filesystem sessions) or `redis`. |
| `session.redisDsn` | Redis DSN for sessions | `tcp://redis:6379?database=0` | The Redis connection string for session storage when the session handler is set to `redis`. |

### 8.5.6 Queue settings (not yet available in the admin panel)

| Key               | Label | Default | Description |
|-------------------|-------|---------|-------------|
| `queue.transport` | Queue transport | `database` | The message queue transport backend used for background tasks such as email delivery. |

## 8.6 Online update

### 8.6.1 Online update settings

| Key                             | Label | Default | Description |
|---------------------------------|-------|---------|-------------|
| `upgrade.dateLastChecked`       | Last check for updates | *(empty)* | The date when phpMyFAQ last checked for available updates. Set automatically. |
| `upgrade.lastDownloadedPackage` | Last downloaded package | *(empty)* | The filename of the last downloaded update package. Set automatically. |
| `upgrade.onlineUpdateEnabled`   | Online Update enabled | `false` | When enabled, phpMyFAQ can download and install updates directly from the admin panel. |
| `upgrade.releaseEnvironment`    | Release Environment | *(auto-detected)* | The release channel: `stable` for production releases or `development` for nightly builds. Set automatically based on the installed version. |
