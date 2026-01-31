# 7. Manage phpMyFAQ

The administration of phpMyFAQ is completely browser-based. The admin area can be found under this URL:

`http://www.example.com/faq/admin/index.php`

You can also log in the public frontend, and after the successful login you'll see a link to administration backend,
too.

If you've lost your password, you can reset it. A new random password will be generated and sent to you via email.
Please change it after your successful login with the generated password.

After entering your username and password, you can log into the system. On the dashboard page you can see the following
cards:

- some statistics about visits, entries, news, and comments
- the latest current number fetched from phpmyfaq.de
- a nice diagram with the number of visitors of the last 30 days
- a list on inactive FAQs
- a button to verify the integrity of the phpMyFAQ installation. Then clicking on the button phpMyFAQ calculates a
  SHA-1 hash for all files and checks it against a web service provided on phpmyfaq.de. With this service, it's possible
  to see if someone changed files.

You can switch the current language in the administration backend, and you have an info box about the session timeout.

## 5.1 Users and Groups

### 5.1.1 User Administration

phpMyFAQ offers flexible management of privileges (or rights) for different users in the admin area. To search for a
certain user, start typing the username in the input form, and you'll get a list of hits for usernames. It is
possible to assign different privileges to real people (represented by the term users). Those privileges are very
detailed and specific, so that you could allow a certain user to edit but not to delete an entry. It is crucial
to contemplate which user shall get which privileges. You could edit an entry by completely deleting all content, which
would be equivalent to deleting the whole entry. The number of possible users is not limited by phpMyFAQ.

Keep in mind that new users have no privileges at all; you will have to assign them by editing the user's profile.

A super admin can set users as super admins as well, then the given permissions aren't evaluated, so please be careful
with setting this option.

A user without any permission in the admin section can still get read access to categories and records. You can set
the permissions on categories and records in the category and record administration frontend.

If you enable LDAP or Microsoft Entra ID authentication, you can't edit the user's profile in the admin area.
The users will be created automatically when they log in the first time.

### 5.1.2 Group Administration

phpMyFAQ also offers flexible management of privileges (or rights) for different groups in the admin area. You can set
permissions for groups in the same way as for users described in the topic above.

Please note that the permissions for a group are higher rated than the permissions on a user. To enable the group
permissions, please set the permission level from _basic_ to _medium_ in the main configuration.

## 5.2 Content

### 5.2.1 Category Administration

phpMyFAQ lets you create different categories and nested subcategories for your FAQs.
You can re-arrange your categories in a different order and create nested ones with drag'n'drop.
It is possible to use various languages per category, too; there's also a frontend to view all translated categories.
For accessibility and SEO reasons, you should add a small description for every category.
You can add an image for every category, which will be shown even on the start page if you flag this category with this
configuration.
If you add a new category, you can set the permissions for users and groups, and you can bind a moderator to the new
category.
The moderator, e.g., can be responsible for the content of the category and can support the normal administrator.
This is quite nice if you want to share the work in your FAQ between various admin users.

If you display category images, they are saved as separate files in /content/user/images/ as follows:

- category-1-de.ext
- category-2-de.ext
- category-3-de.ext
- etc.

For SEO reasons (reduced file size),
you may want to **display the exact same file** as a background image **multiple times**.
To do this, enter the same file name for each entry in your **database, "faqcategories" table, image field**.

### 5.2.2 Add a new FAQ

You can create a completely new FAQ by using the 'Add new FAQ' option. When doing so, it is essential to select the desired category within the 'FAQ metadata' tab to ensure the entry is correctly indexed and visible to users. For a detailed explanation of all available settings and metadata options, please refer to section 5.2.3.

### 5.2.3 FAQ Administration

You can create entries directly in the admin area. Created entries aren't published by default. All available FAQs are
listed on the page "Edit FAQs". By clicking on them, the same interface that lets you create records will open up, this
time with all the relevant data of the specific entry. The meaning of the fields is as follows:

- **Category**
  The place in the FAQ hierarchy where this entry will be published depends on these settings. You can choose one or
  more categories where to store the entry. If you want to add a FAQ record to more than one category, you have to
  select the categories with your mouse and press the CTRL key.

- **Question**
  This is the question or headline of your entry.

- **Answer**
  The content is an answer to the question or a solution for a problem. The content can be edited with the included
  WYSIWYG (**W**hat **Y**ou **S**ee **I**s **W**hat **Y**ou **G**et) editor when JavaScript is enabled. You can place
  images where you want with the integrated image manager. The Editor can be disabled in the configuration if you want.
  One important thing to remember is that you need to allow external hosts for images or videos in the configuration if 
  you want to use images from external sources.

- **Language**
  You can select the language of your FAQ. By default, the selected language saved in the configuration will be chosen.
  You can create entries in multiple languages like this: Write an article in English (or any other language) and save
  it. Now choose _Edit FAQs_ and edit your English FAQ record. Change the question, answer, and keywords and change
  language to, let's say Brazilian Portuguese. _Save_ the FAQ record. Now you can, when you click _edit records_, see
  both FAQs in your list, having the same id, yet different languages.

- **Attachments**
  You can add attachments like PDFs or any other binary data using the **Add attachment** button. If you click on the
  button, a popup opens, and you can upload an attachment. Please keep in mind that the PHP configuration about upload
  size will be checked.

- **Keywords**
  Keywords are relevant for searching through the database. In case you didn't include a specific word in the FAQ
  itself, but it is closely related to the content, you may wish to include it as a keyword, so the FAQ will come up as
  a search result. It is also possible to use non-related keywords so that a wrongly entered search will also lead to
  the right results.

- **Tags**
  You can add some tags about the current FAQ here. An auto-completion method helps you while typing your tags.

- **Author**
  It is possible to specify an author for your FAQ.

- **Email**
  It is possible to specify the author's email for your FAQ, but the email address won't be shown in the frontend.

- **Solution ID**
  Every FAQ automatically generates a so-called solution ID. All records can be accessed directly by putting this ID
  into the search box.

- **Active?**
  If a FAQ is "active," it is visible in the public area and will be included in searches. Is it "deactivated" it will
  be invisible. Suggested FAQs are deactivated by default to prevent any abuse.

- **Sticky?**
  If a FAQ is "sticky," it is a crucial FAQ record and will always be shown on all pages on the right column.
  You should mark records as sticky if they're crucial for your whole FAQ. Sticky records also appear at the
  top positions of the lists of FAQ entries.

- **Comments?**
  If you don't want to allow public comments for this FAQ you can disable the feature here.

- **Revision**
  Like a wiki, phpMyFAQ supports revisions of every entry. New revisions won't be created automatically, but you can
  create a new one if you click on "yes". The old revision will be stored in the database, and the new current revision
  will be displayed in the public frontend. You can also bring back old revisions into the frontend if you select an
  old revision and save them as a new one.

- **Date**
  You have three options for the FAQ creation date. You can choose to refresh the date of the FAQ entry for every
  update, or you can keep the date, or you can set an individual date for the FAQ entry.

- **Permissions**
  If you add or edit a new entry, you can set the permissions for users and groups. Please note that the permissions
  of the chosen category override the permissions of the FAQ itself.

- **Date**
  Date of the last change.

- **Changed?**
  This field is reserved for comments that can reflect what changes have been applied to a certain entry. This helps
  multiple admins to keep track of what happened to the entry over time. Any information entered here will remain
  invisible in the public area.

- **Changelog**
  The changelog lists all previous changes, including user and date of change.

You can edit and delete all records as well. Please note that old revisions won't be deleted until the whole FAQ is
deleted.

phpMyFAQ lets visitors contribute to the FAQ by asking questions. Every visitor is able to view these open questions in
the public area and may give an answer. If you wish to get rid of open questions, you can do so using this section.
Alternatively, you can take over a question and answer it yourself and hereby add it to the FAQ.

### 5.2.4 Sticky FAQs

You can arrange the order of the sticky FAQs by drag'n'drop.
The order of the sticky FAQs will be the same in the public frontend.
To remove the "Important / Sticky" status from an FAQ and remove it from the overview, simply use the pin icon on the right.
Only the status will be changed - the FAQ will not be deleted from the database.

### 5.2.5 Orphaned FAQs

Orphaned FAQs are records that are no longer assigned to any category. This typically happens when categories are deleted without moving the associated FAQs, or through incomplete data imports. As they lack a category assignment, these entries are hidden from users in the public frontend.

Cross-Language Management
Administrators can manage orphaned FAQs across all installed languages, without needing to switch their backend interface language. The list displays all orphaned entries in the system, including the full language name, and allows direct editing within the correct linguistic context.

How to Resolve Orphaned FAQs:
To correct an orphaned FAQ entry, you must assign it to a valid category in the corresponding language:

1. Open the FAQ: The orphaned FAQ list displays entries from all languages. Click on the desired entry to open the FAQ editor, which will automatically be set to the FAQ's language.

2. Assign Category: Navigate to the "FAQ metadata" tab within the editor. The available categories in the dropdown list will automatically match the language of the FAQ you are editing, ensuring you can only select a category appropriate for that language.

3. Save Changes: Select a valid category and save the FAQ.

Once saved, the entry will be visible in its new category and will automatically be removed from the Orphaned FAQ list.

### 5.2.6 Open Questions

On the "Open Questions" page, you can see all open questions that visitors have posted in the currently selected administration language. 
You can answer open questions directly or, if they are not visible in the public area due to visibility settings, you can activate them. 
Additionally, you can delete them.

Please note:
Questions awaiting moderation in other languages are not automatically shown here. If questions exist in other active languages, a warning alert will be displayed at the bottom of the page indicating which languages have pending items and the respective counts.
To process these other questions, you must change your current administration language using the language selector in the top menu.

### 5.2.7 Comment Administration

In this frontend, you can see all comments that have been posted in the FAQs and the news. You can't edit comments,
but you can delete them with one easy click.

### 5.2.8 Attachment Administration

In the attachment administration, you can see an overview of all attachments with their filename, file size,
language, and MIME type.
You can delete them, too.

### 5.2.9 Tags Administration

You can edit existing tags, and if you need to, you can delete the tag.

### 5.2.10 Glossary

A glossary is a list of terms in a particular domain of knowledge with the definitions for those terms. You can add,
edit, and delete glossary items here. The items will be automatically displayed in <abbr> tags in the frontend.

### 5.2.11 News Administration

phpMyFAQ offers the ability to post news on the starting page of your FAQ.
In the administration area, you can create new news, edit existing news, or delete them.

### 5.2.12 Custom Pages Administration

Custom Pages allow you to create database-backed, SEO-friendly pages for legal information, about pages, and other 
static content using a WYSIWYG editor. Custom pages support multi-language content, are automatically included in 
sitemaps, and are searchable alongside FAQs.

#### 5.2.12.1 Creating a Custom Page

To create a new custom page:

1. Navigate to **Content → Custom Pages** in the admin menu
2. Click the **Add new page** button
3. Fill in the required information across three tabs:

**Content Tab:**
- **Page Title**: The main title of your page (e.g., "Privacy Policy")
- **URL Slug**: SEO-friendly URL identifier (e.g., "privacy-policy")
  - Automatically generated from the title
  - Must be unique per language
  - Real-time validation shows availability
  - Results in URL: `https://example.com/page/privacy-policy.html`
- **Content**: Rich text editor (TinyMCE) for page content
  - Full WYSIWYG editing with formatting, images, links
  - No HTML knowledge required
  - Images can be uploaded and managed

**SEO Tab:**
- **SEO Title**: Custom title for search engines (max 60 characters)
  - Character counter helps optimize length
  - Falls back to the page title if empty
- **Meta-Description**: Description for search results (max 160 characters)
  - Character counter helps optimize length
- **Robots Directive**: Controls search engine indexing
  - `index, follow` (default): Allow indexing and following links
  - `noindex, follow`: Don't index but follow links
  - `index, nofollow`: Index but don't follow links
  - `noindex, nofollow`: Don't index and don't follow links

**Settings Tab:**
- **Language**: Select the page language (supports multi-language content)
- **Author Name**: Name of the content author
- **Author Email**: Email of the content author
- **Active**: Toggle to publish/unpublish the page
  - Only active pages appear in search results and sitemaps
  - Inactive pages return 404 errors when accessed

4. Click **Save** to create the page

#### 5.2.12.2 Managing Custom Pages

The Custom Pages list view provides:

- **Pagination**: Navigate through pages with configurable items per page
- **Sorting**: Click column headers to sort by title, slug, language, or date
- **Filtering**: Filter by language or active status
- **Actions**:
  - **Edit**: Modify existing pages
  - **Delete**: Remove pages (with confirmation)
  - **Active Toggle**: Quickly publish/unpublish pages

#### 5.2.12.3 Multi-Language Support

Custom pages support multiple languages:

- Create the same page in different languages using the same ID
- Each language version has its own slug
- Language is selected from the Settings tab
- Example: "Privacy Policy" can exist as:
  - English: `privacy-policy`
  - German: `datenschutz`
  - French: `politique-de-confidentialite`

#### 5.2.12.4 Legal Pages Integration

Custom pages can be used for legal pages with automatic footer link integration:

**Configuration Setup:**

Navigate to **Configuration → Main Settings** and configure:

- **Privacy URL** (`main.privacyURL`)
- **Terms of Service URL** (`main.termsURL`)
- **Imprint URL** (`main.imprintURL`)
- **Cookie Policy URL** (`main.cookiePolicyURL`)

**Two Configuration Options:**

1. **Custom Page Reference**: Use format `page:slug`
   - Example: `page:privacy-policy`
   - Redirects `/privacy.html` → `/page/privacy-policy.html`
   - Recommended for database-backed legal pages

2. **External URL**: Use full URL
   - Example: `https://example.com/legal/privacy`
   - Redirects to external website
   - Useful if legal pages are hosted elsewhere

**Footer Links:**

When configured, links automatically appear in the footer:
- Privacy Statement (if `main.privacyURL` is set)
- Terms of Service (if `main.termsURL` is set)
- Imprint (if `main.imprintURL` is set)
- Cookie Policy (if `main.cookiePolicyURL` is set)

**Example Configuration:**

```
main.privacyURL = page:privacy-policy
main.termsURL = page:terms-of-service
main.imprintURL = page:imprint
main.cookiePolicyURL = page:cookie-policy
```

#### 5.2.12.5 Search Integration

Custom pages are automatically integrated with all search engines:

**Database Search:**
- Uses LIKE queries on title and content
- Works without additional configuration
- Searches alongside FAQs

**Elasticsearch Integration:**
- Automatically indexed with `content_type='page'`
- Updated in real-time on create/update/delete
- Priority 0.80 in search results
- Bulk import via **Elasticsearch → Import Data**

**OpenSearch Integration:**
- Same functionality as Elasticsearch
- Automatic real-time indexing
- Bulk import via **OpenSearch → Import Data**

**Search Results:**
- Custom pages appear with a file-text icon (📄)
- FAQs appear with a question-circle icon (❓)
- Results link directly to `/page/{slug}.html`

#### 5.2.12.6 Sitemap Integration

Active custom pages are automatically included in XML sitemaps:

- Only active pages (`active='y'`) are included
- Priority: 0.80 (FAQs have 1.00)
- Last modified date from `updated` or `created` field
- URL format: `https://example.com/page/{slug}.html`
- No manual configuration needed

Access sitemap at:
- `https://example.com/sitemap.xml`
- `https://example.com/sitemap.xml.gz` (gzipped)

#### 5.2.12.7 SEO Best Practices

**Slug Guidelines:**
- Use lowercase letters, numbers, and hyphens only
- Keep short and descriptive (e.g., `about-us`, `privacy-policy`)
- Avoid special characters or spaces
- Make it meaningful for users and search engines

**Content Guidelines:**
- Write clear, concise content focused on user needs
- Use headings (H2, H3) to structure content
- Keep paragraphs short for readability
- Include relevant keywords naturally
- Update regularly to keep content current

**SEO Optimization:**
- Fill in SEO title (under 60 characters)
- Write compelling meta description (under 160 characters)
- Use appropriate robots directive
- Set pages as active when ready to publish
- Use descriptive page titles

#### 5.2.12.8 Permissions

Custom pages require the following permissions:

- **PAGE_ADD**: Create new custom pages
- **PAGE_EDIT**: Modify existing pages
- **PAGE_DELETE**: Delete pages

Permissions are granted via **User Administration** → **Edit User** → **Permissions**.

Super admins have all permissions by default.

#### 5.2.12.9 Troubleshooting

**Slug validation fails:**
- Ensure slug is unique per language
- Check for special characters (only lowercase, numbers, hyphens allowed)
- Try a different slug

**Page not appearing in search:**
- Verify page is set to active
- Re-index search engine (Elasticsearch/OpenSearch → Drop Index → Create Index → Import Data)
- Check search configuration is enabled

**404 error when accessing the page:**
- Verify the page is active
- Check slug matches URL exactly
- Ensure language matches current site language

**Footer links do not appear:**
- Verify configuration values are set correctly
- Use format `page:slug` or full URL
- Check page exists and is active
- Clear cache if using caching

### 5.2.13 AI-Assisted Translation

phpMyFAQ includes an AI-assisted translation feature that helps you translate FAQ content, custom pages, categories, and
news articles into multiple languages using professional translation APIs. The feature preserves HTML formatting and
provides high-quality automated translations.

#### 5.2.13.1 Overview

The AI translation feature integrates with leading translation services:

- **Google Cloud Translation** - Neural machine translation with 100+ languages
- **DeepL** - Premium quality translations, especially for European languages
- **Azure Translator** - Microsoft's translation service with generous free tier
- **Amazon Translate** - AWS translation service with 75+ languages
- **LibreTranslate** - Open-source, self-hosted option for privacy

#### 5.2.13.2 Configuration

Navigate to **Configuration → Translation** tab to configure your translation provider:

1. Select your preferred **Translation Provider** from the dropdown
2. Enter the required API credentials for your chosen provider
3. Click **Save Configuration** to activate

**Provider-Specific Credentials:**

- **Google**: API key from Google Cloud Console
- **DeepL**: API key from DeepL dashboard (Free or Pro)
- **Azure**: API key and region from Azure Portal
- **Amazon**: AWS Access Key ID, Secret Access Key, and region
- **LibreTranslate**: Server URL and optional API key

For detailed setup instructions for each provider, see the [AI Translation Guide](ai-translation.md) and
[Quick Start Guide](ai-translation-quickstart.md).

#### 5.2.13.3 Translating Content

**Translating FAQs:**

1. Navigate to **FAQs** and select an FAQ to translate
2. Click on the **Translation** tab or **Translate FAQ**
3. Select the target language from the dropdown
4. Click the **"Translate with AI"** button
5. Review the translated question, answer, and keywords
6. Make any necessary edits
7. Click **Save** to store the translation

The AI will translate:
- Question text
- Answer (HTML formatting preserved)
- Keywords/tags

**Translating Custom Pages:**

1. Navigate to **Content → Custom Pages**
2. Select a page and click **Translate**
3. Select the target language
4. Click **"Translate with AI"**
5. Review translated content (title, content, SEO fields)
6. Adjust settings in the **Settings** tab
7. Click **Save**

The AI will translate:
- Page title
- Page content (HTML formatting preserved)
- SEO title
- SEO description

**Translating Categories:**

1. Navigate to **Categories**
2. Select a category and click **Translate Category**
3. Select the target language
4. Click **"Translate with AI"**
5. Review the translated name and description
6. Click **Save**

**Translating News:**

1. Create or edit a news article
2. Use the translation interface to create language versions
3. The AI assists with translating headline and content

#### 5.2.13.4 Best Practices

**Review All Translations:**
- AI translation is very accurate but not perfect
- Always review technical terms, brand names, and legal content
- Edit translations before publishing to ensure quality

**Maintain Consistency:**
- Use the same translation provider across your site
- Keep a glossary of key terms and their preferred translations
- Use consistent terminology in source content

**HTML Formatting:**
- Simple HTML (bold, italic, links, lists) translates best
- Complex nested structures may need manual adjustment
- Always preview translated content before publishing

**Cost Management:**
- Start with free tiers (DeepL Free: 500k chars/month, Azure: 2M chars/month)
- Monitor usage in your provider's dashboard
- Don't re-translate unnecessarily - review and edit instead

#### 5.2.13.5 Troubleshooting

**Translation button is disabled:**
- Verify translation provider is configured in settings
- Ensure source and target languages are different
- Check that both languages are supported by your provider

**Translation fails with error:**
- Verify API credentials are correct
- Check you haven't exceeded free tier limits
- For Azure: ensure region format is correct (e.g., "eastus" not "East US")

**Poor translation quality:**
- Try DeepL for better quality (European languages)
- Simplify source text (shorter sentences, clear language)
- Review and edit translations manually
- Verify language is well-supported by your provider

**HTML formatting issues:**
- Ensure source content has valid, clean HTML
- Simplify complex HTML structures
- Preview before saving
- Re-translate if formatting is broken

For comprehensive documentation, see:
- [Complete AI Translation Guide](ai-translation.md) - Full documentation
- [Quick Start Guide](ai-translation-quickstart.md) - Get started in 5 minutes

## 5.3 Statistics

### 5.3.1 Ratings

Below every FAQ, a visitor has the chance to rate the overall quality of a FAQ by giving ratings from one to five
(whereas 1 is the worst, 5 the best rating).
In the statistics, the average rating and number of votes become visible for every rated FAQ.
To give you a quick overview, FAQs with an average rating of two or worse are displayed in red; an average
above 4 results in a green number.

### 5.3.2 View sessions

This function lets you keep track of your visitors. Every visitor is assigned an ID when coming to your starting page,
that identifies a user during his whole visit. Using the information gathered here, you could reconstruct the way
visitors use your FAQ and make the necessary adjustments to your categories, content, or keywords.

### 5.3.3 View Admin log

The admin log allows you to track any actions taken by users in the admin area of phpMyFAQ. If you feel you have an
intruder in the system, you can find out for sure by checking the admin log.

### 5.3.4 Search statistics

On the search statistics page, you'll get a report about which keywords and how often your users are searching. This
information is split into keywords, the number of searches for this term, the language, and the overall percentage.

### 5.3.5 Reports

On the report page, you can select various data columns to generate a report about content and usage of your FAQ
installation. You can export the report then as a CSV file.

## 5.4 Imports & Exports

### 5.4.1 Imports

You can import faqs from a csv file. Further, you find an example of such a csv file:

> [!IMPORTANT]
> It is not allowed to have the first line of the following example file containing the headers in the uploaded file as well. Otherwise, the import will fail.

| category-Id | question                     | answer                                | keywords                 | language code | author   | email address of author | active | sticky |
| ----------- | ---------------------------- | ------------------------------------- | ------------------------ | ------------- | -------- | ----------------------- | ------ | ------ |
| 1           | What's the answer?           | This one is the answer                | question,answer          | en            | Thorsten | thorsten@phpmyfaq.de    | true   | false  |
| 1           | Can you buy me an ice cream? | Strawberry or chocolate is available. | ice,strawberry,chocolate | en            | Thorsten | thorsten@phpmyfaq.de    | true   | true   |

> [!NOTE]
> All cells are required except for the keywords. Additionally, you are able to use several keywords that are seperated with commas.

### 5.4.2 Exports

You can export your contents of your whole FAQ or just some selected categories into two formats:

- a JSON file
- a PDF file with a table of contents

### 5.4.3 Batch upload via phpMyAdmin

> [!NOTE]
> You should have a table ready with the data you want to import. The titles of your new FAQ's in one column, the HTML-formatted content in the next, and the keywords in a third column.
>
> You should have access to phpMyAdmin via your web host.
>
> You should have already created a few entries manually in your phpMyFAQ.
>
> This guide is intentionally extensive, but the individual steps can be completed quite quickly once you understand them. It is important that you don't make any mistakes when uploading to your DB, so take your time the first time you try it.

#### 5.4.3.1 Export the necessary tables

Open phpMyAdmin, first check whether the correct database is displayed.
You can switch to a different database at the top left.
At the top left you can see the name of your database and below that the names of the individual tables.
From this list, select the following five tables (one after the other) and click on them once to select them:

- faqdata
- faqdata_tags
- faqcategoryrelations
- faqdata_user
- faqdata_groups

![Screenshot of choosing DB and tables in phpmyadmin.](https://github.com/c1972/phpMyFAQ/assets/112912128/71807422-f84e-4259-b6d4-bb3555f7d5c5)

As soon as you have selected one of the five tables, the contents will be displayed on the right.
Now click on the **Export** menu item at the top.

![Screenshot of nav bar in phpmyadmin](https://github.com/c1972/phpMyFAQ/assets/112912128/beaa724a-f083-44c7-a5c1-514ba29e30db)

Select **CSV** as the format.
Make sure that **Dump all rows** is selected.
Then click **Export** at the bottom.

![Screenshot of export dialog in phpmyadmin](https://github.com/c1972/phpMyFAQ/assets/112912128/0f8fa9f1-dd66-4f6d-b73a-065f54320a50)

Save all 5 CSV-Files in a separate folder on your computer. Then you can close phpMyAdmin for the moment.

#### 5.4.3.2 Edit the tables

Open the first CSV file _faqdata.csv_ with the spreadsheet program of your choice.
(You can use LibreOffice, OpenOffice, or Excel, for example.)
Look at the table BEFORE you make any changes.
Pay attention to the column headings and the contents of the individual fields.
DO NOT change the column headings.
Because the database does not like errors, you should handle it carefully.

The _id_ column contains an ascending number that is assigned once and identifies each post.
So for a new post you only need to count one further.
This also applies to the _solution_id_, which can be used later to access individual FAQs.
The keywords for the respective post should be entered in the _keywords_ column, separated by commas.
In the _topic_ column you enter the question or the title of your post.
The HTML-formatted content of the post should be entered in the _content_ column.
For the remaining columns, use the existing entries as a guide.
Check everything you have entered again.
It's better to look at it once too often at the beginning until it becomes easier for you later.
Save the table in exactly the same format as a CSV file, then you can close it.

In order for the data from this first table to be displayed,
the exact right values in the other three tables are also required.
Only then will it work and your data will also be displayed.

You've probably already assigned some important _tags_ to your existing posts,
otherwise please do that now from within phpMyFAQ before we continue.
It's best to just use one tag per post.

So now let's take the second table _faqdata_tags._
On the left is the record_id, which matches the unique id from the previous table, but just is named differently.
So for a new post, you have to increment one here too.
REMEMBER that this number refers to the exact post whose data you have already entered the faqdata table!
In the right-hand column, there is a number that identifies the assigned tag.

Within the Tags section of phpMyFAQ you can hover your mose over the red trash can,
and your browser will now show you a link at the bottom left that ends with our tag number.
You should note down the tags and their numbers in a text file to have them at Hand later.
With this knowledge, we can now enter the desired tags in the table for our new posts.
Check again and you can close the table.

We continue with the third table, called _faqcategoryrelations_, and this is now about the categories.
The number in the first column category_id identifies the category in which the new post should be sorted.
You can assign the number to the category in exactly the same way as you did with the tags.
So make a quick note in your text file!
You can now enter the numbers so that the posts end up in the right category.
On the right at record_id we have the unique ID that identifies the post.
Once you have entered everything, save and close.

The fourth file called _faqdata_user.csv_ is straightforward.
Put in the _unique id_ of your new entries at the left and _-1_ at the right.
Save and close.
Same for the fifth file _faqdata_groups_.
Add the unique id and -1.

#### 5.4.3.3 Upload the finished tables

Now we have all the data ready to import.
So we open phpMyAdmin again.
Select the correct table on the left.
Click on Import at the top.
Select the correct one of our 4 CSV tables out of your folder.
To Skip the number of queries, select 1 so that our column headings are not created as a post.
The format must be CSV.
Check also **update data when duplicate keys are found on import**.
Otherwise, changes to existing entries won't be saved.
Else everything should be fine.
Now click on Import.

![Screenshot of import dialog in phpmyadmin](https://github.com/c1972/phpMyFAQ/assets/112912128/323be9c2-36d1-4129-b536-90656c2a2aa2)

We'll do that four more times with our other files.

Now go to your phpMyFAQ and check your new entries.

## 5.5 Backup

Using the backup function, it is possible to create a copy of the database to a single file. This makes it possible to
restore the FAQ after a possible "crash" or to move the FAQ from one server to another. It is recommended to create
regular backups of your FAQ.

- **backup data**
  A backup of all **data** will include all entries, users, comments, etc.
- **backup logs**
  The sessions of visits and the admin log will be saved (i.e., all **log** files). This information is not necessary
  for running phpMyFAQ, they serve only statistical purposes.

During the backup process, phpMyFAQ generates a hash on the whole backup file and stores this information. The hashes
of backups will be verified during the process of restoring. If a backup can't be verified, the admin can't use the
backup file for restore.

To back up the whole data located on your web server, you can run our simple backup script located in the folder /scripts.

## 5.6 Configuration

### 5.6.1 Main configuration

Here you can edit the general, FAQ specific, search, spam protection, spam control center, SEO related, layout
settings, Mail setup for SMTP, API settings, online update settings, and if enabled, LDAP configuration of phpMyFAQ.

### 5.6.2 FAQ Multi-sites

You can see a list of all multisite installations, and you're able to add new ones.

To host several distinct installations (with different configs, different templates, and most importantly,
different database credentials), but only want to update once, you need to follow these steps:

- Make sure you have the _multisite/_ directory in your document root and _multisite.php_ in it
- For every installation, there needs to be a subdirectory of _multisite/_ named exactly like the hostname of the
  separate installation.

For example, if you want to use _faq.example.org_ and _beta.faq.example.org_, it needs to look like this:

    .
    |-- [...]
    |-- content
    |   |-- core
    |   |   |-- config
    |   |   |   |-- constants.php
    |   |   |   `-- database.php
    `-- multisite
        |-- multisite.php
        `-- beta.faq.example.org
            |-- constants.php
            `-- database.php

### 5.6.3 Stop Words configuration

We need stop words for the smart answering feature and the related answers. If a user is adding a new question to your
FAQ, the words will be checked against all FAQs in your database but without the stop words. Stop words are words with a
very low relevance like the English word _the_.

### 5.6.4 phpMyFAQ Update (Experimental feature)

If you're running phpMyFAQ 4.0.0 or later, you can use the built-in automatic upgrade feature.
You can click through the update wizard:

1. Check for System Health: this checks if your system is ready for the upgrade
2. Check for Updates: this checks if there is a new version of phpMyFAQ available
3. Download of phpMyFAQ: this downloads the latest version of phpMyFAQ in the background, this can take some seconds
4. Extracting phpMyFAQ: this extracts the downloaded archive, this can take a while
5. Install downloaded package: first, it creates a backup of your current installation, then it copies the downloaded
   files into your installation, and in the end, the database is updated

### 5.6.5 Elasticsearch configuration

Here you can create and drop the Elasticsearch index, and you can run a full import of all data from your database
into the Elasticsearch index. 
You can also see some Elasticsearch relevant usage data. 
This page is only available if Elasticsearch is enabled.

### 5.6.6 OpenSearch configuration

Here you can create and drop the OpenSearch index, and you can run a full import of all data from your database
into the OpenSearch index.
You can also see some OpenSearch relevant usage data.
This page is only available if OpenSearch is enabled.

### 5.6.7 System information

On this page, phpMyFAQ displays some relevant system information like PHP version, database version, or session path.
Please use this information when reporting bugs.

## 5.7 Using Microsoft Entra ID

You can use our experimental Microsoft Entra ID support for user authentication as well.
App Registrations in Azure are used to integrate applications with Microsoft Azure services,
allowing them to authenticate and access resources securely.
Follow these steps to create an App Registration in Microsoft Azure:

**Prerequisites:**

- Azure account with appropriate permissions.

**Step 1: Sign in to Azure Portal**

1. Open your web browser and navigate to the [Azure Portal](https://portal.azure.com/).
2. Sign in with your Azure account credentials.

**Step 2: Create a New App Registration**

1. In the Azure Portal, click on "Entra ID" in the left-hand navigation pane.
2. Under "Entra ID," click on "App registrations."

**Step 3: Register a New App**

1. Click the "+ New registration" button.

**Step 4: Configure the App Registration**

1. In the "Name" field, provide a name for your App Registration, e.g. "phpMyFAQ".
2. Choose the supported account types that your application will authenticate: "Accounts in this organizational directory only"
3. In the "Redirect URI" section, specify the redirect URI where Entra ID will send authentication responses: `http://www.example.com/faq/services/azure/callback.php`
4. Click the "Register" button to create the App Registration.

**Step 5: Configure Authentication**

1. After the registration is created, go to the "Authentication" tab in the App Registration settings.
2. Under "Platform configurations," select the appropriate redirect URI type: Web
3. Configure the Redirect URIs as needed for your application.
4. Save the changes.

**Step 6: Note Application Details**

1. Make note of the "Application (client) ID." This is your application's unique identifier.
2. If your application requires client secrets, go to the "Certificates & secrets" tab to create and manage client secrets.

**Step 7: Create Azure config file**

1. Copy the file `./config/azure.php.original` and name it `./config/azure.php`
2. Add the Tenant ID, the client ID, and the secret from Step 7 and save the file
3. Then, activate Microsoft Entra ID support in the administration under "Security"

## 5.8 Command Line Interface (CLI)

phpMyFAQ offers a command line interface (CLI) for administrators.

### 5.8.1 Usage

The CLI is located in the `bin/console` file. You can run it with the PHP CLI binary.

### 5.8.2 Available commands

```bash
# Show available commands
php bin/console list
```


## 5.9 Troubleshooting

### 5.9.1 Hard password reset

If you can't log into your phpMyFAQ installation,
and your password reset doesn't work, you can reset your password on this page:
[Password Hash Generator Tool for phpMyFAQ]{https://password.phpmyfaq.de)
You need your phpMyFAQ salt (from the table `faqconfig` in the database) and the username you used to log in,
usually `admin`.
You can choose a new password, and the tool will generate a new password hash for you.
Copy the hash and paste it into the `pass` field in the `faquserlogin` table in the database for the user "admin".
Then you can log in with the new password.

For transparency reasons, the whole code base is available on GitHub.
You can find the code here: https://github.com/phpMyFAQ/password.phpmyfaq.de
