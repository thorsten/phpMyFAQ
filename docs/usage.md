# 4. Use phpMyFAQ

The public phpMyFAQ frontend has a simple, HTML5/CCS3 based default layout based on
[Bootstrap v5.3](https://getbootstrap.com/docs/5.3/).
The header has the main links for all categories, propose new FAQs, add questions, open questions, and a login.
On the left side you only see the main categories.
You can also change the current language at the bottom of the FAQ,
use the global search in the center of the FAQ or use the login box in the upper right if you have a valid user account.
On the right side you see a list of the most popular FAQ records, the latest records, and the sticky FAQ records.
On the pages with the FAQ records you'll see the other records of the current category and the tag cloud
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

The Find As You Type feature is directly built into the main search bar in the top of the FAQ with direct access to the
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

## 4.5.1 Batch upload FAQs to Database with CSV files

**Three things in advance:**
1. You should have a table ready with the data you want to import. The titles of the FAQ's in one column, the HTML-formatted content in the next and the keywords in a third column.
2. You should have access to phpMyAdmin via your web host.
3. You should have already created a few entries by hand in your phpMyFAQ.

This guide is intentionally extensive, but the individual steps can be completed quite quickly once you understand them. It is important that you don't make any mistakes when uploading to your DB, so take your time the first time you try it.

**Exporting the tables**

Open phpMyAdmin. First check whether the correct database is displayed. You can switch to a different database at the top left. At the top left you can see the name of your database and below that the names of the individual tables. From this list, select the following 4 tables (one after the other) and click on them once to select them:
*faqdata, faqdata_tags, faqcategoryrelations, faqdata_user.csv*

As soon as you have selected one of the 4 tables, the contents will be displayed on the right. Now click on the **Export** menu item at the top. Select **CSV** as the format. Make sure that **Export ALL records** is selected. Then click **Export** at the bottom. Save all 4 CSV-Files in a separate folder on your computer. Then you can close phpMyAdmin for the moment.

**Make new entries and changes**

Open the first CSV file *faqdata.csv* with the spreadsheet program of your choice. (You can use LibreOffice, OpenOffice or Excel, for example.) Look at the table BEFORE you make any changes. Pay attention to the column headings and the contents of the individual fields. DO NOT change the column headings. Because the database does not like errors, you should handle it carefully.

The *id* column contains an ascending number that is assigned once and identifies each post. So for a new post you only need to count one further. This also applies to the *solution_id*, which can be used later to access individual FAQs. The keywords for the respective post should be entered in the *keywords* column, separated by commas. In the *topic* column you enter the question or the title of your post. The HTML-formatted content of the post should be entered in the *content* column. For the remaining columns, just use the existing entries as a guide. Check everything you have entered again. It's better to look at it once too often at the beginning until it becomes easier for you later. Save the table in exactly the same format as a CSV file, then you can close it.

In order for the data from this first table to be displayed, the exact right values ​​in the other three tables are also required. Only then will it work and your data will also be displayed.

You've probably already assigned some important *tags* to your existing posts, otherwise please do that now from within phpMyFAQ before we continue. It's best to just use one tag per post.

So now let's take the second table *faqdata_tags.* On the left is the record_id, which matches the unique id from the previous table, but just is named differently. So for a new post you have to increment one here too. REMEMBER that this number refers to the exact post whose data you have already entered in the faqdata table! In the right-hand column there is a number that identifies the assigned tag.

Within the Tags section of phpMyFAQ you can hover your mose over the red trash can and your browser will now show you a link at the bottom left that ends with our tag number. You should note down the tags and their numbers in a text file to have them at Hand later. With this knowledge we can now enter the desired tags in the table for our new posts. Check again and you can close the table.

We continue with the third table, called *faqcategoryrelations*, and this is now about the categories. The number in the first column category_id identifies the category in which the new post should be sorted. You can assign the number to the category in exactly the same way as you did with the tags. So make a quick note in your text file! You can now enter the numbers so that the posts end up in the right category. On the right at record_id we have the unique ID that identifies the post. Once you have entered everything, save and close.

The fourth file called *faqdata_user.csv* is easy. Just put in the *unique id* of your new entries at the left and *-1* at the right. Save and close.

**Import CSV-Files to the DB**

Now we have all the data ready to import. So we open phpMyAdmin again. Select the correct table on the left. Click on Import at the top. Select the correct one of our 4 CSV tables out of your folder. For Skip number of queries, select 1 so that our column headings are not created as a post. The format must be CSV. Check also to update on duplicate key. Otherwise changes to existing entries won't be saved. Else everything should be fine. Now click on Import. We'll do that three more times with our other files.

Now go to your phpMyFAQ and check your new entries.

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
The users can also enable or disable the permission to show their names in the public frontend, e.g. if they added an
own question.
Additionally, every user can activate the builtin two-factor authentication to enhance security.
You can use authentication apps like Google Authenticator for [iOS](https://apps.apple.com/app/google-authenticator/id388497605)
or [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2).

## 4.10 Related FAQs

For better usability, there are some helpful links below every FAQ entry.
If the administrator added tags to the records, they will be displayed next to five (or more) related articles.
The related articles are based on the content of the current FAQ entry.
On the right side, you'll see links to all entries of the current category and the complete tag cloud of the whole FAQ.
