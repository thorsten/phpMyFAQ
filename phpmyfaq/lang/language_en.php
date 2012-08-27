<?php
/**
 * The English language file - try to be the best of British and American English
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Translation
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matthias Sommerfeld <mso@bluebirdy.de>
 * @author    Henning Schulzrinne <hgs@cs.columbia.edu>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-02-19
 */

/**
 *                !!! IMPORTANT NOTE !!!
 * Please consider following while defining new vars:
 * - one variable definition per line !!!
 * - the perfect case is to define a scalar string value
 * - if some dynamic content is needed, use sprintf syntax
 * - arrays are allowed but not recommended
 * - no comments at the end of line after the var definition
 * - do not use '=' char in the array keys
 *   (eq. $PMF_LANG["a=b"] is not allowed)
 *
 *  Please be consistent with this format as we need it for
 *  the translation tool to work propertly
 */

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "en";
$PMF_LANG["language"] = "english";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";

$PMF_LANG["nplurals"] = "2";
/**
 * This parameter is used with the function 'plural' from inc/Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 *
 * If you add a translation for a new language, correct plural form support will be missing
 * (English plural messages will be used) until you add a correct expression to the function
 * 'plural' mentioned above.
 * If you need any help, please contact phpMyFAQ team.
 */

// Navigation
$PMF_LANG["msgCategory"] = "Categories";
$PMF_LANG["msgShowAllCategories"] = "All categories";
$PMF_LANG["msgSearch"] = "Search";
$PMF_LANG["msgAddContent"] = "Add FAQ";
$PMF_LANG["msgQuestion"] = "Add question";
$PMF_LANG["msgOpenQuestions"] = "Open questions";
$PMF_LANG["msgHelp"] = "Help";
$PMF_LANG["msgContact"] = "Contact";
$PMF_LANG["msgHome"] = "FAQ Home";
$PMF_LANG["msgNews"] = "FAQ News";
$PMF_LANG["msgUserOnline"] = " Users online";
$PMF_LANG["msgBack2Home"] = "Back to main page";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Categories with FAQs";
$PMF_LANG["msgFullCategoriesIn"] = "Categories with FAQs in ";
$PMF_LANG["msgSubCategories"] = "Subcategories";
$PMF_LANG["msgEntries"] = "FAQs";
$PMF_LANG["msgEntriesIn"] = "Questions in ";
$PMF_LANG["msgViews"] = "views";
$PMF_LANG["msgPage"] = "Page ";
$PMF_LANG["msgPages"] = " Pages";
$PMF_LANG["msgPrevious"] = "previous";
$PMF_LANG["msgNext"] = "next";
$PMF_LANG["msgCategoryUp"] = "one category up";
$PMF_LANG["msgLastUpdateArticle"] = "Last update: ";
$PMF_LANG["msgAuthor"] = "Author: ";
$PMF_LANG["msgPrinterFriendly"] = "printer-friendly version";
$PMF_LANG["msgPrintArticle"] = "Print this record";
$PMF_LANG["msgMakeXMLExport"] = "Export as XML-File";
$PMF_LANG["msgAverageVote"] = "Average rating:";
$PMF_LANG["msgVoteUseability"] = "Rate this FAQ";
$PMF_LANG["msgVoteFrom"] = "out of";
$PMF_LANG["msgVoteBad"] = "completely useless";
$PMF_LANG["msgVoteGood"] = "most valuable";
$PMF_LANG["msgVotings"] = "Votes ";
$PMF_LANG["msgVoteSubmit"] = "Vote";
$PMF_LANG["msgVoteThanks"] = "Thanks a lot for your vote!";
$PMF_LANG["msgYouCan"] = "You can ";
$PMF_LANG["msgWriteComment"] = "comment this FAQ";
$PMF_LANG["msgShowCategory"] = "Content Overview: ";
$PMF_LANG["msgCommentBy"] = "Comment of ";
$PMF_LANG["msgCommentHeader"] = "Comment this FAQ";
$PMF_LANG["msgYourComment"] = "Your comment:";
$PMF_LANG["msgCommentThanks"] = "Thanks a lot for your comment!";
$PMF_LANG["msgSeeXMLFile"] = "open XML-File";
$PMF_LANG["msgSend2Friend"] = "Send FAQ to a friend";
$PMF_LANG["msgS2FName"] = "Your name:";
$PMF_LANG["msgS2FEMail"] = "Your e-mail address:";
$PMF_LANG["msgS2FFriends"] = "Your friends:";
$PMF_LANG["msgS2FEMails"] = ". e-mail address:";
$PMF_LANG["msgS2FText"] = "The following text will be sent:";
$PMF_LANG["msgS2FText2"] = "You'll find the FAQ at the following address:";
$PMF_LANG["msgS2FMessage"] = "Additional message for your friends:";
$PMF_LANG["msgS2FButton"] = "send e-mail";
$PMF_LANG["msgS2FThx"] = "Thanks for your recommendation!";
$PMF_LANG["msgS2FMailSubject"] = "Recommendation from ";

// Search
$PMF_LANG["msgSearchWord"] = "Keyword";
$PMF_LANG["msgSearchFind"] = "Search result for ";
$PMF_LANG["msgSearchAmount"] = " search result";
$PMF_LANG["msgSearchAmounts"] = " search results";
$PMF_LANG["msgSearchCategory"] = "Category: ";
$PMF_LANG["msgSearchContent"] = "Answer: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Proposal for a new FAQ";
$PMF_LANG["msgNewContentAddon"] = "Your proposal will not be published right away, but will be released by the administrator upon receipt. Required  fields are <strong>your Name</strong>, <strong>your email address</strong>, <strong>category</strong>, <strong>question</strong> and <strong>answer</strong>. Please separate the keywords with commas only.";
$PMF_LANG["msgNewContentName"] = "Your name:";
$PMF_LANG["msgNewContentMail"] = "Email";
$PMF_LANG["msgNewContentCategory"] = "Category:";
$PMF_LANG["msgNewContentTheme"] = "Your question:";
$PMF_LANG["msgNewContentArticle"] = "Your answer:";
$PMF_LANG["msgNewContentKeywords"] = "Keywords:";
$PMF_LANG["msgNewContentLink"] = "Link for this FAQ:";
$PMF_LANG["msgNewContentSubmit"] = "submit";
$PMF_LANG["msgInfo"] = "More information: ";
$PMF_LANG["msgNewContentThanks"] = "Thank you for your suggestion!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Currently there are no pending questions.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Ask your question below:";
$PMF_LANG["msgAskCategory"] = "Category:";
$PMF_LANG["msgAskYourQuestion"] = "Your question:";
$PMF_LANG["msgAskThx4Mail"] = "Thanks for your question!";
$PMF_LANG["msgDate_User"] = "Date / User";
$PMF_LANG["msgQuestion2"] = "Question";
$PMF_LANG["msg2answer"] = "Answer";
$PMF_LANG["msgQuestionText"] = "Here you can see questions asked by other users. If you answer these question, your answers may be inserted into the FAQ.";

// Help
$PMF_LANG["msgHelpText"] = "<p>The structure of the FAQ (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) is quite simple. You can either search the <strong><a href=\"?action=show\">categories</a></strong> or let the <strong><a href=\"?action=search\">FAQ search engine</a></strong> search for keywords.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "Email to the webmaster:";
$PMF_LANG["msgMessage"] = "Your message:";

// Startseite
$PMF_LANG["msgTopTen"] = "Most popular FAQs";
$PMF_LANG["msgHomeThereAre"] = "There are ";
$PMF_LANG["msgHomeArticlesOnline"] = " FAQs online";
$PMF_LANG["msgNoNews"] = "No news is good news.";
$PMF_LANG["msgLatestArticles"] = "Latest FAQs";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Many thanks for your proposal to the FAQ.";
$PMF_LANG["msgMailCheck"] = "There's a new entry in the FAQ! Please check the admin section!";
$PMF_LANG["msgMailContact"] = "Your message has been sent to the administrator.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "No database connection available.";
$PMF_LANG["err_noHeaders"] = "No category found.";
$PMF_LANG["err_noArticles"] = "<p>No FAQs available.</p>";
$PMF_LANG["err_badID"] = "<p>Wrong ID.</p>";
$PMF_LANG["err_noTopTen"] = "<p>No popular FAQs available yet.</p>";
$PMF_LANG["err_nothingFound"] = "<p>No entry found.</p>";
$PMF_LANG["err_SaveEntries"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>category</strong>, <strong>question</strong>, <strong>your Record</strong> and, when requested, the <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_SaveComment"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your comments</strong> and, when requested, the <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_VoteTooMuch"] = "We do not count double votings.";
$PMF_LANG["err_noVote"] = "You did not rate the question!";
$PMF_LANG["err_noMailAdress"] = "Your email address is not correct.";
$PMF_LANG["err_sendMail"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your question</strong> and, when requested, the <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>Search for records:</strong><br />With an entry like <strong style=\"color: Red;\">word1 word2</strong> you can do a relevance descending search for two or more search criterion.</p><p><strong>Notice:</strong> Your search criterion has to be at least 4 letters long otherwise your request will be rejected.</p>";

// Menu
$PMF_LANG["ad"] = "Administration";
$PMF_LANG["ad_menu_user_administration"] = "Users";
$PMF_LANG["ad_menu_entry_aprove"] = "Approve FAQs";
$PMF_LANG["ad_menu_entry_edit"] = "Edit FAQs";
$PMF_LANG["ad_menu_categ_add"] = "Add category";
$PMF_LANG["ad_menu_categ_edit"] = "Edit category";
$PMF_LANG["ad_menu_news_add"] = "Add news";
$PMF_LANG["ad_menu_news_edit"] = "Edit news";
$PMF_LANG["ad_menu_open"] = "Open questions";
$PMF_LANG["ad_menu_stat"] = "Statistics";
$PMF_LANG["ad_menu_cookie"] = "Set cookies";
$PMF_LANG["ad_menu_session"] = "View Sessions";
$PMF_LANG["ad_menu_adminlog"] = "View Adminlog";
$PMF_LANG["ad_menu_passwd"] = "Change Password";
$PMF_LANG["ad_menu_logout"] = "Logout";
$PMF_LANG["ad_menu_startpage"] = "Startpage";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Please identify yourself.";
$PMF_LANG["ad_msg_passmatch"] = "Both passwords must <strong>match</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "The profile of";
$PMF_LANG["ad_msg_savedsuc_2"] = "was saved successfully.";
$PMF_LANG["ad_msg_mysqlerr"] = "Due to a <strong>database error</strong>, the profile could not be saved.";
$PMF_LANG["ad_msg_noauth"] = "You are not authorized.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Page";
$PMF_LANG["ad_gen_of"] = "of";
$PMF_LANG["ad_gen_lastpage"] = "Previous page";
$PMF_LANG["ad_gen_nextpage"] = "Next page";
$PMF_LANG["ad_gen_save"] = "Save";
$PMF_LANG["ad_gen_reset"] = "Reset";
$PMF_LANG["ad_gen_yes"] = "Yes";
$PMF_LANG["ad_gen_no"] = "No";
$PMF_LANG["ad_gen_top"] = "Top of page";
$PMF_LANG["ad_gen_ncf"] = "No category found!";
$PMF_LANG["ad_gen_delete"] = "Delete";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "User administration";
$PMF_LANG["ad_user_username"] = "Registered users";
$PMF_LANG["ad_user_rights"] = "User rights";
$PMF_LANG["ad_user_edit"] = "edit";
$PMF_LANG["ad_user_delete"] = "delete";
$PMF_LANG["ad_user_add"] = "Add user";
$PMF_LANG["ad_user_profou"] = "Profile of the user";
$PMF_LANG["ad_user_name"] = "Name";
$PMF_LANG["ad_user_password"] = "Password";
$PMF_LANG["ad_user_confirm"] = "Confirm";
$PMF_LANG["ad_user_rights"] = "Rights";
$PMF_LANG["ad_user_del_1"] = "The User";
$PMF_LANG["ad_user_del_2"] = "shall be deleted?";
$PMF_LANG["ad_user_del_3"] = "Are you sure?";
$PMF_LANG["ad_user_deleted"] = "The user was successfully deleted.";
$PMF_LANG["ad_user_checkall"] = "Select all";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "FAQ administration";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Topic";
$PMF_LANG["ad_entry_action"] = "Action";
$PMF_LANG["ad_entry_edit_1"] = "Edit Record";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Question";
$PMF_LANG["ad_entry_content"] = "Answer:";
$PMF_LANG["ad_entry_keywords"] = "Keywords:";
$PMF_LANG["ad_entry_author"] = "Author:";
$PMF_LANG["ad_entry_category"] = "Category:";
$PMF_LANG["ad_entry_active"] = "Visible:";
$PMF_LANG["ad_entry_date"] = "Date:";
$PMF_LANG["ad_entry_changed"] = "Changed?";
$PMF_LANG["ad_entry_changelog"] = "Changelog:";
$PMF_LANG["ad_entry_commentby"] = "Comment by";
$PMF_LANG["ad_entry_comment"] = "Comments:";
$PMF_LANG["ad_entry_save"] = "Save";
$PMF_LANG["ad_entry_delete"] = "delete";
$PMF_LANG["ad_entry_delcom_1"] = "Are you sure that the comment of the user";
$PMF_LANG["ad_entry_delcom_2"] = "should be deleted?";
$PMF_LANG["ad_entry_commentdelsuc"] = "The comment was <strong>successfully</strong> deleted.";
$PMF_LANG["ad_entry_back"] = "Back to the article";
$PMF_LANG["ad_entry_commentdelfail"] = "The comment was <strong>not</strong> deleted.";
$PMF_LANG["ad_entry_savedsuc"] = "The changes were saved <strong>successfully</strong>.";
$PMF_LANG["ad_entry_savedfail"] = "Unfortunately, a <strong>database error</strong> occurred.";
$PMF_LANG["ad_entry_del_1"] = "Are you sure that the topic";
$PMF_LANG["ad_entry_del_2"] = "of";
$PMF_LANG["ad_entry_del_3"] = "should be deleted?";
$PMF_LANG["ad_entry_delsuc"] = "Issue <strong>successfully</strong> deleted.";
$PMF_LANG["ad_entry_delfail"] = "Issue was <strong>not deleted</strong>!";
$PMF_LANG["ad_entry_back"] = "Back";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "Article header:";
$PMF_LANG["ad_news_text"] = "Text of the Record:";
$PMF_LANG["ad_news_link_url"] = "Link:";
$PMF_LANG["ad_news_link_title"] = "Title of the link:";
$PMF_LANG["ad_news_link_target"] = "Target of the link:";
$PMF_LANG["ad_news_link_window"] = "Link opens new window";
$PMF_LANG["ad_news_link_faq"] = "Link within the FAQ";
$PMF_LANG["ad_news_add"] = "Add News entry";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Headline";
$PMF_LANG["ad_news_date"] = "Date";
$PMF_LANG["ad_news_action"] = "Action";
$PMF_LANG["ad_news_update"] = "update";
$PMF_LANG["ad_news_delete"] = "delete";
$PMF_LANG["ad_news_nodata"] = "No data found in database";
$PMF_LANG["ad_news_updatesuc"] = "The news has been successfully updated.";
$PMF_LANG["ad_news_del"] = "Are you sure that you want to delete this news item?";
$PMF_LANG["ad_news_yesdelete"] = "yes, delete!";
$PMF_LANG["ad_news_nodelete"] = "no";
$PMF_LANG["ad_news_delsuc"] = "The news has been successfully deleted.";
$PMF_LANG["ad_news_updatenews"] = "News item updated.";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Add new category";
$PMF_LANG["ad_categ_catnum"] = "Category number:";
$PMF_LANG["ad_categ_subcatnum"] = "Subcategory number:";
$PMF_LANG["ad_categ_nya"] = "<em>not yet available!</em>";
$PMF_LANG["ad_categ_titel"] = "Category title:";
$PMF_LANG["ad_categ_add"] = "Add category";
$PMF_LANG["ad_categ_existing"] = "Existing categories";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Category";
$PMF_LANG["ad_categ_subcateg"] = "Subcategory";
$PMF_LANG["ad_categ_titel"] = "Category title";
$PMF_LANG["ad_categ_action"] = "Action";
$PMF_LANG["ad_categ_update"] = "update";
$PMF_LANG["ad_categ_delete"] = "delete";
$PMF_LANG["ad_categ_updatecateg"] = "Update Category";
$PMF_LANG["ad_categ_nodata"] = "No data found in database";
$PMF_LANG["ad_categ_remark"] = "Please note that existing entries will not be visible anymore, if you delete the category. You must assign a new category for the article or delete the article.";
$PMF_LANG["ad_categ_edit_1"] = "Edit";
$PMF_LANG["ad_categ_edit_2"] = "Category";
$PMF_LANG["ad_categ_add"] = "add Category";
$PMF_LANG["ad_categ_added"] = "The category was added.";
$PMF_LANG["ad_categ_updated"] = "The category was updated.";
$PMF_LANG["ad_categ_del_yes"] = "yes, delete!";
$PMF_LANG["ad_categ_del_no"] = "no!";
$PMF_LANG["ad_categ_deletesure"] = "Are you sure to delete this category?";
$PMF_LANG["ad_categ_deleted"] = "Category deleted.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "The cookie was <strong>successfully</strong> set.";
$PMF_LANG["ad_cookie_already"] = "A cookie was set already. You now have following options:";
$PMF_LANG["ad_cookie_again"] = "Set cookie again";
$PMF_LANG["ad_cookie_delete"] = "Delete cookie";
$PMF_LANG["ad_cookie_no"] = "There is no cookie saved yet. With a cookie you could save your login script, thus no need to remember your login details again. You now have following options:";
$PMF_LANG["ad_cookie_set"] = "Set cookie";
$PMF_LANG["ad_cookie_deleted"] = "Cookie deleted successfully.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "AdminLog";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Change your Password";
$PMF_LANG["ad_passwd_old"] = "Old password:";
$PMF_LANG["ad_passwd_new"] = "New password:";
$PMF_LANG["ad_passwd_con"] = "Retype password:";
$PMF_LANG["ad_passwd_change"] = "Change password";
$PMF_LANG["ad_passwd_suc"] = "Password changed successfully.";
$PMF_LANG["ad_passwd_remark"] = "<strong>ATTENTION:</strong><br />Cookie have to be set again!";
$PMF_LANG["ad_passwd_fail"] = "The old password <strong>must</strong> be entered correctly and both new ones have to <strong>match</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Add new user account";
$PMF_LANG["ad_adus_name"] = "Username:";
$PMF_LANG["ad_adus_password"] = "Password:";
$PMF_LANG["ad_adus_add"] = "Add user";
$PMF_LANG["ad_adus_suc"] = "User <strong>successfully</strong> added.";
$PMF_LANG["ad_adus_edit"] = "Edit profile";
$PMF_LANG["ad_adus_dberr"] = "<strong>database error!</strong>";
$PMF_LANG["ad_adus_exerr"] = "Username <strong>exists</strong> already.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "Session ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Time";
$PMF_LANG["ad_sess_pageviews"] = "PageViews";
$PMF_LANG["ad_sess_search"] = "Search";
$PMF_LANG["ad_sess_sfs"] = "Search for sessions";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. actions:";
$PMF_LANG["ad_sess_s_date"] = "Date";
$PMF_LANG["ad_sess_s_after"] = "after";
$PMF_LANG["ad_sess_s_before"] = "before";
$PMF_LANG["ad_sess_s_search"] = "Search";
$PMF_LANG["ad_sess_session"] = "Session";
$PMF_LANG["ad_sess_r"] = "Search results for";
$PMF_LANG["ad_sess_referer"] = "Referer:";
$PMF_LANG["ad_sess_browser"] = "Browser:";
$PMF_LANG["ad_sess_ai_rubrik"] = "Category:";
$PMF_LANG["ad_sess_ai_artikel"] = "Record:";
$PMF_LANG["ad_sess_ai_sb"] = "Search-Strings:";
$PMF_LANG["ad_sess_ai_sid"] = "Session ID:";
$PMF_LANG["ad_sess_back"] = "Back";

// Statistik
$PMF_LANG["ad_rs"] = "Rating Statistics";
$PMF_LANG["ad_rs_rating_1"] = "The ranking of";
$PMF_LANG["ad_rs_rating_2"] = "users shows:";
$PMF_LANG["ad_rs_red"] = "Red";
$PMF_LANG["ad_rs_green"] = "Green";
$PMF_LANG["ad_rs_altt"] = "with an average lower than 20%";
$PMF_LANG["ad_rs_ahtf"] = "with an average higher than 80%";
$PMF_LANG["ad_rs_no"] = "No ranking available";

// Auth
$PMF_LANG["ad_auth_insert"] = "Please enter your login name and password.";
$PMF_LANG["ad_auth_user"] = "Login name";
$PMF_LANG["ad_auth_passwd"] = "Password";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Reset";
$PMF_LANG["ad_auth_fail"] = "Wrong login name or password.";
$PMF_LANG["ad_auth_sess"] = "The Sessions ID is passed.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Edit configuration";
$PMF_LANG["ad_config_save"] = "Save configuration";
$PMF_LANG["ad_config_reset"] = "Reset";
$PMF_LANG["ad_config_saved"] = "The configuration has been saved successfully.";
$PMF_LANG["ad_menu_editconfig"] = "Edit configuration";
$PMF_LANG["ad_att_none"] = "No attachments available";
$PMF_LANG["ad_att_att"] = "Attachments:";
$PMF_LANG["ad_att_add"] = "Add new attachment";
$PMF_LANG["ad_entryins_suc"] = "Record successfully saved.";
$PMF_LANG["ad_entryins_fail"] = "An error occurred.";
$PMF_LANG["ad_att_del"] = "Delete";
$PMF_LANG["ad_att_nope"] = "Attachments can be added only while editing.";
$PMF_LANG["ad_att_delsuc"] = "The attachment has been deleted successfully.";
$PMF_LANG["ad_att_delfail"] = "An error occurred while deleting the attachment.";
$PMF_LANG["ad_entry_add"] = "Add FAQ";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "A backup is a complete image of the database content. The format of the backup is a SQL transaction file, which can be imported using tools like phpMyAdmin or the commandline SQL client. A backup should be performed at least once a month.";
$PMF_LANG["ad_csv_link"] = "Download the backup";
$PMF_LANG["ad_csv_head"] = "Create a backup";
$PMF_LANG["ad_att_addto"] = "Add an attachment to the issue";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "File:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "The file has been attached successfully.";
$PMF_LANG["ad_att_fail"] = "An error occurred while attaching the file.";
$PMF_LANG["ad_att_close"] = "Close this window";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "With this form you can restore the content of the database, using a backup made with phpMyFAQ. Please note that the existing data will be overwritten.";
$PMF_LANG["ad_csv_file"] = "File";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "backup logfiles";
$PMF_LANG["ad_csv_linkdat"] = "backup data";
$PMF_LANG["ad_csv_head2"] = "Restore";
$PMF_LANG["ad_csv_no"] = "This does not seem to be a backup of phpMyFAQ.";
$PMF_LANG["ad_csv_prepare"] = "Preparing the database queries...";
$PMF_LANG["ad_csv_process"] = "Querying...";
$PMF_LANG["ad_csv_of"] = "of";
$PMF_LANG["ad_csv_suc"] = "were successful.";
$PMF_LANG["ad_csv_backup"] = "Backup";
$PMF_LANG["ad_csv_rest"] = "Restore a backup";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Backup";
$PMF_LANG["ad_logout"] = "Session successfully terminated.";
$PMF_LANG["ad_news_add"] = "Add news";
$PMF_LANG["ad_news_edit"] = "Edit news";
$PMF_LANG["ad_cookie"] = "Cookies";
$PMF_LANG["ad_sess_head"] = "View sessions";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Categories";
$PMF_LANG["ad_menu_stat"] = "Rating Statistics";
$PMF_LANG["ad_kateg_add"] = "add main Category";
$PMF_LANG["ad_kateg_rename"] = "Rename";
$PMF_LANG["ad_adminlog_date"] = "Date";
$PMF_LANG["ad_adminlog_user"] = "User";
$PMF_LANG["ad_adminlog_ip"] = "IP-Address";

$PMF_LANG["ad_stat_sess"] = "Sessions";
$PMF_LANG["ad_stat_days"] = "Days";
$PMF_LANG["ad_stat_vis"] = "Sessions (Visits)";
$PMF_LANG["ad_stat_vpd"] = "Visits per Day";
$PMF_LANG["ad_stat_fien"] = "First Log";
$PMF_LANG["ad_stat_laen"] = "Last Log";
$PMF_LANG["ad_stat_browse"] = "browse Sessions";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Time";
$PMF_LANG["ad_sess_sid"] = "Session-ID";
$PMF_LANG["ad_sess_ip"] = "IP-Address";

$PMF_LANG["ad_ques_take"] = "Answer the question";
$PMF_LANG["no_cats"] = "No Categories found.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Invalid user or password.";
$PMF_LANG["ad_log_sess"] = "Session expired.";
$PMF_LANG["ad_log_edit"] = "\"Edit User\"-Form for the following user: ";
$PMF_LANG["ad_log_crea"] = "\"New article\" form.";
$PMF_LANG["ad_log_crsa"] = "New entry created.";
$PMF_LANG["ad_log_ussa"] = "Update data for the following user: ";
$PMF_LANG["ad_log_usde"] = "Deleted the following user: ";
$PMF_LANG["ad_log_beed"] = "Edit form for the following user: ";
$PMF_LANG["ad_log_bede"] = "Deleted the following entry: ";

$PMF_LANG["ad_start_visits"] = "Visits";
$PMF_LANG["ad_start_articles"] = "Articles";
$PMF_LANG["ad_start_comments"] = "Comments";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "paste";
$PMF_LANG["ad_categ_cut"] = "cut";
$PMF_LANG["ad_categ_copy"] = "copy";
$PMF_LANG["ad_categ_process"] = "Processing categories...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>You are not authorized.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "previous page";
$PMF_LANG["msgNextPage"] = "next page";
$PMF_LANG["msgPageDoublePoint"] = "Page: ";
$PMF_LANG["msgMainCategory"] = "Main category";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Your password has been changed.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "Show this as PDF file";
$PMF_LANG["ad_xml_head"] = "XML-Backup";
$PMF_LANG["ad_xml_hint"] = "Save all records of your FAQ in one XML file.";
$PMF_LANG["ad_xml_gen"] = "create XML file";
$PMF_LANG["ad_entry_locale"] = "Language";
$PMF_LANG["msgLangaugeSubmit"] = "Change language";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "Preview";
$PMF_LANG["ad_attach_1"] = "Please choose a directory for attachments first in configuration.";
$PMF_LANG["ad_attach_2"] = "Please choose a link for attachments first in configuration.";
$PMF_LANG["ad_attach_3"] = "The file attachment.php cannot be opened without proper authentification.";
$PMF_LANG["ad_attach_4"] = "The attached file must be smaller than %s Bytes.";
$PMF_LANG["ad_menu_export"] = "Export your FAQ";
$PMF_LANG["ad_export_1"] = "Built RSS-Feed on";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "Error: Cannot write file.";
$PMF_LANG["ad_export_news"] = "News RSS-Feed";
$PMF_LANG["ad_export_topten"] = "Top 10 RSS-Feed";
$PMF_LANG["ad_export_latest"] = "5 latest records RSS-Feed";
$PMF_LANG["ad_export_pdf"] = "PDF-Export of all records";
$PMF_LANG["ad_export_generate"] = "build RSS-Feed";

$PMF_LANG["rightsLanguage"]['adduser'] = "add user";
$PMF_LANG["rightsLanguage"]['edituser'] = "edit user";
$PMF_LANG["rightsLanguage"]['deluser'] = "delete user";
$PMF_LANG["rightsLanguage"]['addbt'] = "add record";
$PMF_LANG["rightsLanguage"]['editbt'] = "edit record";
$PMF_LANG["rightsLanguage"]['delbt'] = "delete record";
$PMF_LANG["rightsLanguage"]['viewlog'] = "view log";
$PMF_LANG["rightsLanguage"]['adminlog'] = "view admin log";
$PMF_LANG["rightsLanguage"]['delcomment'] = "delete comment";
$PMF_LANG["rightsLanguage"]['addnews'] = "add news";
$PMF_LANG["rightsLanguage"]['editnews'] = "edit news";
$PMF_LANG["rightsLanguage"]['delnews'] = "delete news";
$PMF_LANG["rightsLanguage"]['addcateg'] = "add category";
$PMF_LANG["rightsLanguage"]['editcateg'] = "edit category";
$PMF_LANG["rightsLanguage"]['delcateg'] = "delete category";
$PMF_LANG["rightsLanguage"]['passwd'] = "change password";
$PMF_LANG["rightsLanguage"]['editconfig'] = "edit configuration";
$PMF_LANG["rightsLanguage"]['addatt'] = "add attachments";
$PMF_LANG["rightsLanguage"]['delatt'] = "delete attachments";
$PMF_LANG["rightsLanguage"]['backup'] = "create backup";
$PMF_LANG["rightsLanguage"]['restore'] = "restore backup";
$PMF_LANG["rightsLanguage"]['delquestion'] = "delete open questions";
$PMF_LANG["rightsLanguage"]['changebtrevs'] = "edit revisions";

$PMF_LANG["msgAttachedFiles"] = "attached files:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "action";
$PMF_LANG["ad_entry_email"] = "E-mail address:";
$PMF_LANG["ad_entry_allowComments"] = "Allow comments:";
$PMF_LANG["msgWriteNoComment"] = "You cannot comment on this entry";
$PMF_LANG["ad_user_realname"] = "Real name:";
$PMF_LANG["ad_export_generate_pdf"] = "create PDF file";
$PMF_LANG["ad_export_full_faq"] = "Your FAQ as a PDF file: ";
$PMF_LANG["err_bannedIP"] = "Your IP address has been banned.";
$PMF_LANG["err_SaveQuestion"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your question</strong> and, when requested, the <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>.";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "Font color: ";
$PMF_LANG["ad_entry_fontsize"] = "Font size: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => "select", 1 => "Language");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "Enable automatic language detection");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "Title of your FAQ");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "phpMyFAQ Version");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "Description");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "Keywords for Spiders");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "Name of the Publisher");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "Email address of the Admin");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "Contact information");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "Text for the send to friend page");
$LANG_CONF['records.maxAttachmentSize'] = array(0 => "input", 1 => "Maximum size for attachments in Bytes (max. %sByte)");
$LANG_CONF["records.disableAttachments"] = array(0 => "checkbox", 1 => "Enable visibilty of attachments");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "Enable user tracking");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "use Adminlog?");
$LANG_CONF["security.ipCheck"] = array(0 => "checkbox", 1 => "Check the IP in administration");
$LANG_CONF["records.numberOfRecordsPerPage"] = array(0 => "input", 1 => "Number of displayed topics per page");
$LANG_CONF["records.numberOfShownNewsEntries"] = array(0 => "input", 1 => "Number of news articles");
$LANG_CONF['security.bannedIPs'] = array(0 => "area", 1 => "Ban these IPs");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "Enable URL rewrite support? (default: disabled)");
$LANG_CONF["security.ldapSupport"] = array(0 => "checkbox", 1 => "Enable LDAP support? (default: disabled)");
$LANG_CONF["main.referenceURL"] = array(0 => "input", 1 => "URL for link verification (e.g.: http://www.example.org/faq)");
$LANG_CONF["main.urlValidateInterval"] = array(0 => "input", 1 => "Interval between AJAX link verification (in seconds)");
$LANG_CONF["records.enableVisibilityQuestions"] = array(0 => "checkbox", 1 => "Disable visibility of new questions?");
$LANG_CONF['security.permLevel'] = array(0 => "select", 1 => "Permission level");

$PMF_LANG["ad_categ_new_main_cat"] = "as new main category";
$PMF_LANG["ad_categ_paste_error"] = "Moving this category isn't possible.";
$PMF_LANG["ad_categ_move"] = "move category";
$PMF_LANG["ad_categ_lang"] = "Language";
$PMF_LANG["ad_categ_desc"] = "Description";
$PMF_LANG["ad_categ_change"] = "Change with";

$PMF_LANG["lostPassword"] = "Password forgotten?";
$PMF_LANG["lostpwd_err_1"] = "Error: Username and e-mail address not found.";
$PMF_LANG["lostpwd_err_2"] = "Error: Wrong entries!";
$PMF_LANG["lostpwd_text_1"] = "Thank you for requesting your account information.";
$PMF_LANG["lostpwd_text_2"] = "Please set a new personal password in the admin section of your FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "E-Mail was sent.";

$PMF_LANG["ad_xmlrpc_button"] = "Click to check version of your phpMyFAQ installation";
$PMF_LANG["ad_xmlrpc_latest"] = "Latest version available on";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Select category language';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Sitemap';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'This entry is in revision and can not be displayed.';
$PMF_LANG['msgArticleCategories'] = 'Categories for this entry';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'Unique solution ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ record';
$PMF_LANG['ad_entry_new_revision'] = 'Create new revision?';
$PMF_LANG['ad_entry_record_administration'] = 'Record administration';
$PMF_LANG['ad_entry_changelog'] = 'Changelog';
$PMF_LANG['ad_entry_revision'] = 'Revision';
$PMF_LANG['ad_changerev'] = 'Select Revision';
$PMF_LANG['msgCaptcha'] = "Please enter the captcha code";
$PMF_LANG['msgSelectCategories'] = 'Search in ...';
$PMF_LANG['msgAllCategories'] = '... all categories';
$PMF_LANG['ad_you_should_update'] = 'Your phpMyFAQ installation is outdated. You should update to the latest available version.';
$PMF_LANG['msgAdvancedSearch'] = 'Advanced search';

// added v1.6.1 - 2006-04-25 by Matteoï and Thorsten
$PMF_LANG['spamControlCenter'] = 'Spam control center';
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "Print user email in a safe way (default: enabled).");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "Check public form content against banned words (default: enabled).");
$LANG_CONF["spam.enableCaptchaCode"] = array(0 => "checkbox", 1 => "Use a captcha code to allow public form submission (default: enabled).");
$PMF_LANG['ad_session_expiring'] = 'Your session will expire in %d minutes: would you like to go on working?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Sessions management';
$PMF_LANG['ad_stat_choose'] = 'Choose the month';
$PMF_LANG['ad_stat_delete'] = 'Delete immediately the selected sessions';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = 'Glossary';
$PMF_LANG['ad_glossary_add'] = 'Add glossary entry';
$PMF_LANG['ad_glossary_edit'] = 'Edit glossary entry';
$PMF_LANG['ad_glossary_item'] = 'Item';
$PMF_LANG['ad_glossary_definition'] = 'Definition';
$PMF_LANG['ad_glossary_save'] = 'Save entry';
$PMF_LANG['ad_glossary_save_success'] = 'Glossary entry successfully saved!';
$PMF_LANG['ad_glossary_save_error'] = 'The glossary entry could not saved because an error occurred.';
$PMF_LANG['ad_glossary_update_success'] = 'Glossary entry successfully updated!';
$PMF_LANG['ad_glossary_update_error'] = 'The glossary entry could not updated because an error occurred.';
$PMF_LANG['ad_glossary_delete'] = 'Delete entry';
$PMF_LANG['ad_glossary_delete_success'] = 'Glossary entry successfully deleted!';
$PMF_LANG['ad_glossary_delete_error'] = 'The glossary entry could not deleted because an error occurred.';
$PMF_LANG['ad_linkcheck_noReferenceURL'] = 'Automatic link verification disabled (base URL for link verify not set)';
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = 'Automatic link verification disabled (PHP option allow_url_fopen not Enabled)';
$PMF_LANG['ad_linkcheck_checkResult'] = 'Automatic link verification result';
$PMF_LANG['ad_linkcheck_checkSuccess'] = 'OK';
$PMF_LANG['ad_linkcheck_checkFailed'] = 'Failed';
$PMF_LANG['ad_linkcheck_failReason'] = 'Reason(s) failed:';
$PMF_LANG['ad_linkcheck_noLinksFound'] = 'No URLs compatible with link verifier feature found.';
$PMF_LANG['ad_linkcheck_searchbadonly'] = 'Only with bad links';
$PMF_LANG['ad_linkcheck_infoReason'] = 'Additional Information:';
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = 'Found while testing <strong>%s</strong>: ';
$PMF_LANG['ad_linkcheck_openurl_notready'] = 'LinkVerifier not ready.';
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = 'Maximum redirect count <strong>%d</strong> exceeded.';
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = 'Resolved to blank URL.';
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = 'Host <strong>%s</strong> is slow or not responding.';
$PMF_LANG['ad_linkcheck_openurl_nodns'] = 'DNS resolution of host <strong>%s</strong> is slow or is failed due to DNS issues, local or remote.';
$PMF_LANG['ad_linkcheck_openurl_redirected'] = 'URL was redirected to <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = 'Ambiguous HTTP status <strong>%s</strong> returned.';
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = 'The <em>HEAD</em> method is not supported by the host <strong>%s</strong>, allowed methods: <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_not_found'] = 'This resource cannot be found at host <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = '%s protocol unsupported by Automatic link verification.';
$PMF_LANG['msgNewQuestionVisible'] = 'The question have to be reviewed first before getting public.';
$PMF_LANG['msgQuestionsWaiting'] = 'Waiting for publishing by the administrators: ';
$PMF_LANG['ad_entry_visibility'] = 'Publish?';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] =  "Please enter a password. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] =  "Passwords do not match. ";
$PMF_LANG['ad_user_error_loginInvalid'] =  "The specified user name is invalid.";
$PMF_LANG['ad_user_error_noEmail'] =  "Please enter a valid mail address. ";
$PMF_LANG['ad_user_error_noRealName'] =  "Please enter your real name. ";
$PMF_LANG['ad_user_error_delete'] =  "User account could not be deleted. ";
$PMF_LANG['ad_user_error_noId'] =  "No ID specified. ";
$PMF_LANG['ad_user_error_protectedAccount'] =  "User account is protected. ";
$PMF_LANG['ad_user_deleteUser'] = "Delete User";
$PMF_LANG['ad_user_status'] = "Status:";
$PMF_LANG['ad_user_lastModified'] = "last modified:";
$PMF_LANG['ad_gen_cancel'] = "Cancel";
$PMF_LANG["rightsLanguage"]['addglossary'] = "add glossary item";
$PMF_LANG["rightsLanguage"]['editglossary'] = "edit glossary item";
$PMF_LANG["rightsLanguage"]['delglossary'] = "delete glossary item";
$PMF_LANG["ad_menu_group_administration"] = "Groups";
$PMF_LANG['ad_user_loggedin'] = 'Logged in as ';

$PMF_LANG['ad_group_details'] = "Group Details";
$PMF_LANG['ad_group_add'] = "Add Group";
$PMF_LANG['ad_group_add_link'] = "Add Group";
$PMF_LANG['ad_group_name'] = "Name:";
$PMF_LANG['ad_group_description'] = "Description:";
$PMF_LANG['ad_group_autoJoin'] = "Auto-join:";
$PMF_LANG['ad_group_suc'] = "Group <strong>successfully</strong> added.";
$PMF_LANG['ad_group_error_noName'] = "Please enter a group name. ";
$PMF_LANG['ad_group_error_delete'] = "Group could not be deleted. ";
$PMF_LANG['ad_group_deleted'] = "The group was successfully deleted.";
$PMF_LANG['ad_group_deleteGroup'] = "Delete Group";
$PMF_LANG['ad_group_deleteQuestion'] = "Are you sure that this group shall be deleted?";
$PMF_LANG['ad_user_uncheckall'] = "Unselect All";
$PMF_LANG['ad_group_membership'] = "Group Membership";
$PMF_LANG['ad_group_members'] = "Members";
$PMF_LANG['ad_group_addMember'] = "+";
$PMF_LANG['ad_group_removeMember'] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'Limit the FAQ data to be exported (optional)';
$PMF_LANG['ad_export_cat_downwards'] = 'Downwards?';
$PMF_LANG['ad_export_type'] = 'Format of the export';
$PMF_LANG['ad_export_type_choose'] = 'Supported formats:';
$PMF_LANG['ad_export_download_view'] = 'Download or view inline?';
$PMF_LANG['ad_export_download'] = 'download';
$PMF_LANG['ad_export_view'] = 'view in-line';
$PMF_LANG['ad_export_gen_xhtml'] = 'create XHTML file';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'News data';
$PMF_LANG['ad_news_author_name'] = 'Author name:';
$PMF_LANG['ad_news_author_email'] = 'Author email:';
$PMF_LANG['ad_news_set_active'] = 'Activate:';
$PMF_LANG['ad_news_allowComments'] = 'Allow comments:';
$PMF_LANG['ad_news_expiration_window'] = 'News expiration time window (optional)';
$PMF_LANG['ad_news_from'] = 'From:';
$PMF_LANG['ad_news_to'] = 'To:';
$PMF_LANG['ad_news_insertfail'] = 'An error occurred inserting the news item into the database.';
$PMF_LANG['ad_news_updatefail'] = 'An error occurred updating the news item into the database.';
$PMF_LANG['newsShowCurrent'] = 'Show current news.';
$PMF_LANG['newsShowArchive'] = 'Show archived news.';
$PMF_LANG['newsArchive'] = ' News archive';
$PMF_LANG['newsWriteComment'] = 'comment on this entry';
$PMF_LANG['newsCommentDate'] = 'Added at: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'Record expiration time window (optional)';
$PMF_LANG['admin_mainmenu_home'] = 'Dashboard';
$PMF_LANG['admin_mainmenu_users'] = 'Users';
$PMF_LANG['admin_mainmenu_content'] = 'Content';
$PMF_LANG['admin_mainmenu_statistics'] = 'Statistics';
$PMF_LANG['admin_mainmenu_exports'] = 'Exports';
$PMF_LANG['admin_mainmenu_backup'] = 'Backup';
$PMF_LANG['admin_mainmenu_configuration'] = 'Configuration';
$PMF_LANG['admin_mainmenu_logout'] = 'Logout';

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = 'Category owner';
$PMF_LANG['adminSection'] = 'Administration';
$PMF_LANG['err_expiredArticle'] = 'This entry is expired and can not be displayed';
$PMF_LANG['err_expiredNews'] = 'This news is expired and can not be displayed';
$PMF_LANG['err_inactiveNews'] = 'This news is in revision and can not be displayed';
$PMF_LANG['msgSearchOnAllLanguages'] = 'search in all languages';
$PMF_LANG['ad_entry_tags'] = 'Tags';
$PMF_LANG['msg_tags'] = 'Tags';

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = 'Checking...';
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = 'Checking...';
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = 'Checking...';
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = 'Checking...';
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = 'Disabled';
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = 'Links KO';
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = 'Links OK';
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = 'No access';
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = 'No AJAX';
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = 'No Links';
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = 'No Script';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'Related entries';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "Number of related entries");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'Translate';
$PMF_LANG['ad_categ_trans_2'] = 'Category';
$PMF_LANG['ad_categ_translatecateg'] = 'Translate Category';
$PMF_LANG['ad_categ_translate'] = 'Translate';
$PMF_LANG['ad_categ_transalready'] = 'Already translated in: ';
$PMF_LANG["ad_categ_deletealllang"] = 'Delete in all languages?';
$PMF_LANG["ad_categ_deletethislang"] = 'Delete in this language only?';
$PMF_LANG["ad_categ_translated"] = "The category has been translated.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "Overview";
$PMF_LANG['ad_menu_categ_structure'] = "Category Overview including its languages";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'User permissions:';
$PMF_LANG['ad_entry_grouppermission'] = 'Group permissions:';
$PMF_LANG['ad_entry_all_users'] = 'Access for all users';
$PMF_LANG['ad_entry_restricted_users'] = 'Restricted access to';
$PMF_LANG['ad_entry_all_groups'] = 'Access for all groups';
$PMF_LANG['ad_entry_restricted_groups'] = 'Restricted access to';
$PMF_LANG['ad_session_expiration'] = 'Session expires in';
$PMF_LANG['ad_user_active'] = 'active';
$PMF_LANG['ad_user_blocked'] = 'blocked';
$PMF_LANG['ad_user_protected'] = 'protected';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'Select a FAQ record to insert it as a link...';

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "Paste after";
$PMF_LANG["ad_categ_remark_move"] = "The exchange of 2 categories is only possible at the same level.";
$PMF_LANG["ad_categ_remark_overview"] = "The correct order of categories will be shown, if all categories are defined for the actual language (first column).";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d Guests and %d Registered';
$PMF_LANG['ad_adminlog_del_older_30d'] = 'Delete immediately logs older than 30 days';
$PMF_LANG['ad_adminlog_delete_success'] = 'Older logs successfully deleted.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'No logs deleted: an error occurred performing the request.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = 'add search plugin';
$PMF_LANG['ad_quicklinks'] = 'Quicklinks';
$PMF_LANG['ad_quick_category'] = 'Add new category';
$PMF_LANG['ad_quick_record'] = 'Add new FAQ record';
$PMF_LANG['ad_quick_user'] = 'Add new user';
$PMF_LANG['ad_quick_group'] = 'Add new group';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'Translation proposal';
$PMF_LANG['msgNewTranslationAddon'] = 'Your proposal will not be published right away, but will be released by the administrator upon receipt. Required  fields are <strong>your Name</strong>, <strong>your email address</strong>, <strong>your question translation</strong> and <strong>your answer translation</strong>. Please separate the keywords with commas only.';
$PMF_LANG['msgNewTransSourcePane'] = 'Source pane';
$PMF_LANG['msgNewTranslationPane'] = 'Translation pane';
$PMF_LANG['msgNewTranslationName'] = "Your Name:";
$PMF_LANG['msgNewTranslationMail'] = "Your email address:";
$PMF_LANG['msgNewTranslationKeywords'] = "Keywords:";
$PMF_LANG['msgNewTranslationSubmit'] = 'Submit your proposal';
$PMF_LANG['msgTranslate'] = 'Translate this FAQ';
$PMF_LANG['msgTranslateSubmit'] = 'Start translation...';
$PMF_LANG['msgNewTranslationThanks'] = "Thank you for your translation proposal!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG["rightsLanguage"]['addgroup'] = "add group accounts";
$PMF_LANG["rightsLanguage"]['editgroup'] = "edit group accounts";
$PMF_LANG["rightsLanguage"]['delgroup'] = "delete group accounts";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = 'Link opens in parent window';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'Comments';
$PMF_LANG['ad_comment_administration'] = 'Comments administration';
$PMF_LANG['ad_comment_faqs'] = 'Comments in FAQ records:';
$PMF_LANG['ad_comment_news'] = 'Comments in News records:';
$PMF_LANG['ad_groups'] = 'Groups';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'Record sorting (according to property)');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'Record sorting (descending or ascending)');
$PMF_LANG['ad_conf_order_id'] = 'ID (default)';
$PMF_LANG['ad_conf_order_thema'] = 'Title';
$PMF_LANG['ad_conf_order_visits'] = 'Number of visitors';
$PMF_LANG['ad_conf_order_datum'] = 'Date';
$PMF_LANG['ad_conf_order_author'] = 'Author';
$PMF_LANG['ad_conf_desc'] = 'descending';
$PMF_LANG['ad_conf_asc'] = 'ascending';
$PMF_LANG['mainControlCenter'] = 'Main configuration';
$PMF_LANG['recordsControlCenter'] = 'FAQ records configuration';

// added v2.0.0 - 2007-03-17 by Thorsten
$PMF_LANG['msgInstantResponse'] = 'Instant Response';
$PMF_LANG['msgInstantResponseMaxRecords'] = '. Find below the first %d records.';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "Activate new records (default: deactivated)");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "Allow comments for new records (default: disallowed)");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'Records in this category';
$PMF_LANG['msgDescriptionInstantResponse'] = 'Just type and find the answers ...';
$PMF_LANG['msgTagSearch'] = 'Tagged entries';
$PMF_LANG['ad_pmf_info'] = 'phpMyFAQ Information';
$PMF_LANG['ad_online_info'] = 'Online version check';
$PMF_LANG['ad_system_info'] = 'System Information';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = 'Register';
$PMF_LANG["ad_user_loginname"] = 'Login name:';
$PMF_LANG['errorRegistration'] = 'This field is required!';
$PMF_LANG['submitRegister'] = 'Register';
$PMF_LANG['msgUserData'] = 'User information required for registration';
$PMF_LANG['captchaError'] = 'Please enter the right captcha code!';
$PMF_LANG['msgRegError'] = 'Following errors occured. Please correct them:';
$PMF_LANG['successMessage'] = 'Your registration was successful. You will soon receive a confirmation mail with your login data!';
$PMF_LANG['msgRegThankYou'] = 'Thank you for your registration!';
$PMF_LANG['emailRegSubject'] = '[%sitename%] Registration: new user';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'The most popular searches are:';
$LANG_CONF['main.enableWysiwygEditor'] = array(0 => "checkbox", 1 => "Enable bundled WYSIWYG editor (default: enabled)");

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = 'Search Statistics';
$PMF_LANG['ad_searchstats_search_term'] = 'Keyword';
$PMF_LANG['ad_searchstats_search_term_count'] = 'Count';
$PMF_LANG['ad_searchstats_search_term_lang'] = 'Language';
$PMF_LANG['ad_searchstats_search_term_percentage'] = 'Percentage';

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = 'Sticky';
$PMF_LANG['ad_entry_sticky'] = 'Sticky';
$PMF_LANG['stickyRecordsHeader'] = 'Sticky FAQs';

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = 'Stop Words';
$PMF_LANG['ad_config_stopword_input'] = 'Add new stop word';

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = 'No, there is still no adequate answer (will send the mail)';
$PMF_LANG['msgSendMailIfNothingIsFound'] = 'Is the wanted answer listed in the results above?';

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = 'Please choose the language for translation';
$PMF_LANG['msgLangDirIsntWritable'] = 'The folder <strong>/lang</strong> for the translation files isn\'t writable.';
$PMF_LANG['ad_menu_translations'] = 'Interface Translation';
$PMF_LANG['ad_start_notactive'] = 'Waiting for activation';

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = 'Add new translation';
$PMF_LANG['msgTransToolLanguage'] = 'Language';
$PMF_LANG['msgTransToolActions'] = 'Actions';
$PMF_LANG['msgTransToolWritable'] = 'Writable';
$PMF_LANG['msgEdit'] = 'Edit';
$PMF_LANG['msgDelete'] = 'Delete';
$PMF_LANG['msgYes'] = 'yes';
$PMF_LANG['msgNo'] = 'no';
$PMF_LANG['msgTransToolSureDeleteFile'] = 'Are you sure this language file should be deleted?';
$PMF_LANG['msgTransToolFileRemoved'] = 'Language file successfully deleted.';
$PMF_LANG['msgTransToolErrorRemovingFile'] = 'Error deleting the language file.';
$PMF_LANG['msgVariable'] = 'Variable';
$PMF_LANG['msgCancel'] = 'Cancel';
$PMF_LANG['msgSave'] = 'Save';
$PMF_LANG['msgSaving3Dots'] = 'saving ...';
$PMF_LANG['msgRemoving3Dots'] = 'removing ...';
$PMF_LANG['msgTransToolFileSaved'] = 'Language file saved successfully';
$PMF_LANG['msgTransToolErrorSavingFile'] = 'Error saving the language file';
$PMF_LANG['msgLanguage'] = 'Language';
$PMF_LANG['msgTransToolLanguageCharset'] = 'Language charset';
$PMF_LANG['msgTransToolLanguageDir'] = 'Language direction';
$PMF_LANG['msgTransToolLanguageDesc'] = 'Language description';
$PMF_LANG['msgAuthor'] = 'Author';
$PMF_LANG['msgTransToolAddAuthor'] = 'Add author';
$PMF_LANG['msgTransToolCreateTranslation'] = 'Create Translation';
$PMF_LANG['msgTransToolTransCreated'] = 'New translation successfully created';
$PMF_LANG['msgTransToolCouldntCreateTrans'] = 'Could not create the new translation';
$PMF_LANG['msgAdding3Dots'] = 'adding ...';
$PMF_LANG['msgTransToolSendToTeam'] = 'Send to phpMyFAQ team';
$PMF_LANG['msgSending3Dots'] = 'sending ...';
$PMF_LANG['msgTransToolFileSent'] = 'Language file was successfully sent to the phpMyFAQ team. Thank you very much for sharing it.';
$PMF_LANG['msgTransToolErrorSendingFile'] = 'There was an error while sending the language file';
$PMF_LANG['msgTransToolPercent'] = 'Percentage';

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = array(0 => "input", 1 => "Path where attachments will be saved.<br /><small>Relative path means a folder within web root</small>");

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "The file you're trying to download was not found on this server";
$PMF_LANG['ad_sess_noentry'] = "No entry";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"][0] = "%d user online";
$PMF_LANG["plmsgUserOnline"][1] = "%d users online";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = array(0 => "select", 1 => "Template set to be used");

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = 'Remove';
$PMF_LANG["msgTransToolLanguageNumberOfPlurals"] = "Number of plural forms";
$PMF_LANG['msgTransToolLanguageOnePlural'] = 'This language has only one plural form';
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = "Plural form support for language %s is disabled (nplurals not set)";

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG["plmsgHomeArticlesOnline"][0] = "There is %d FAQ online";
$PMF_LANG["plmsgHomeArticlesOnline"][1] = "There are %d FAQs online";
$PMF_LANG["plmsgViews"][0] = "%d view";
$PMF_LANG["plmsgViews"][1] = "%d views";

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = '%d Guest';
$PMF_LANG['plmsgGuestOnline'][1] = '%d Guests';
$PMF_LANG['plmsgRegisteredOnline'][0] = ' and %d Registered';
$PMF_LANG['plmsgRegisteredOnline'][1] = ' and %d Registered';
$PMF_LANG["plmsgSearchAmount"][0] = "%d search result";
$PMF_LANG["plmsgSearchAmount"][1] = "%d search results";
$PMF_LANG["plmsgPagesTotal"][0] = " %d Page";
$PMF_LANG["plmsgPagesTotal"][1] = " %d Pages";
$PMF_LANG["plmsgVotes"][0] = "%d Vote";
$PMF_LANG["plmsgVotes"][1] = "%d Votes";
$PMF_LANG["plmsgEntries"][0] = "%d FAQ";
$PMF_LANG["plmsgEntries"][1] = "%d FAQs";

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG["rightsLanguage"]['addtranslation'] = "add translation";
$PMF_LANG["rightsLanguage"]['edittranslation'] = "edit translation";
$PMF_LANG["rightsLanguage"]['deltranslation'] = "delete translation";
$PMF_LANG["rightsLanguage"]['approverec'] = "approve records";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["records.enableAttachmentEncryption"] = array(0 => "checkbox", 1 => "Enable attachment encryption <br><small>Ignored when attachments is disabled</small>");
$LANG_CONF["records.defaultAttachmentEncKey"] = array(0 => "input", 1 => 'Default attachment encryption key <br><small>Ignored if attachment encryption is disabled</small><br><small><font color="red">WARNING: Do not change this once set and enabled file encryption!!!</font></small>');
//$LANG_CONF["records.attachmentsStorageType"] = array(0 => "select", 1 => "Attachment storage type");
//$PMF_LANG['att_storage_type'][0] = 'Filesystem';
//$PMF_LANG['att_storage_type'][1] = 'Database';

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'Upgrade';
$PMF_LANG['ad_you_shouldnt_update'] = 'You have the latest version of phpMyFAQ. You do not need to upgrade.';
$LANG_CONF['security.useSslForLogins'] = array(0 => 'checkbox', 1 => "Only allow logins over SSL connection? (default: disabled)");
$PMF_LANG['msgSecureSwitch'] = "Switch to secure mode to login!";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving']  = 'Please note that no files will we written until you click save button';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'Page %d buffer recorded successfully';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Error recording page %d buffer';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'Recording page %d buffer';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = 'Active';

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'The attachment is invalid, please inform admin';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms']   = array(0 => 'input', 1 => 'Number of listed search terms');
$LANG_CONF['records.orderingPopularFaqs'] = array(0 => "select", 1 => "Sorting of the top FAQ's");
$PMF_LANG['list_all_users']            = 'List all users';

$PMF_LANG['records.orderingPopularFaqs.visits'] = "list most visited entries";
$PMF_LANG['records.orderingPopularFaqs.voting'] = "list most voted entries";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = 'Please seperate words by comma.';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = 'update';
$PMF_LANG['msgKeepFaqDate'] = 'keep'; 
$PMF_LANG['msgEditFaqDat'] = 'edit';
$LANG_CONF['main.optionalMailAddress'] = array(0 => 'checkbox', 1 => 'Mail address as mandatory field (default: deactivated)');
$LANG_CONF['search.useAjaxSearchOnStartpage'] = array(0 => 'checkbox', 1 => 'Instant Response on startpage (default: deactivated)');

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = array(0 => 'select', 1 => 'Sort by relevance');
$LANG_CONF["search.enableRelevance"] = array(0 => "checkbox", 1 => "Activate relevance support? (default: disabled)");
$PMF_LANG['searchControlCenter'] = 'Search';
$PMF_LANG['search.relevance.thema-content-keywords'] = 'Question - Answer - Keywords';
$PMF_LANG['search.relevance.thema-keywords-content'] = 'Question - Keywords - Answer';
$PMF_LANG['search.relevance.content-thema-keywords'] = 'Answer - Question - Keywords';
$PMF_LANG['search.relevance.content-keywords-thema'] = 'Answer - Keywords - Question';
$PMF_LANG['search.relevance.keywords-content-thema'] = 'Keywords - Answer - Question';
$PMF_LANG['search.relevance.keywords-thema-content'] = 'Keywords - Question - Answer';

// added v2.6.99 - 2010-11-30 by Gustavo Solt
$LANG_CONF["main.enableGoogleTranslation"] = array(0 => "checkbox", 1 => "Activate Google translations (default: deactivated)");
$LANG_CONF['main.googleTranslationKey'] = array(0 => 'input', 1 => 'Google API key');
$PMF_LANG["msgNoGoogleApiKeyFound"] = 'The Google API key is empty, please provide one in the configuration section';

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = 'Login';
$PMF_LANG['socialNetworksControlCenter'] = 'Social networks configuration';
$LANG_CONF['socialnetworks.enableTwitterSupport'] = array(0 => 'checkbox', 1 => 'Twitter support (default: deactivated)');
$LANG_CONF['socialnetworks.twitterConsumerKey'] = array(0 => 'input', 1 => 'Twitter Consumer Key');
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = array(0 => 'input', 1 => 'Twitter Consumer Secret');

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = array(0 => 'input', 1 => 'Twitter Access Token Key');
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = array(0 => 'input', 1 => 'Twitter Access Token Secret');
$LANG_CONF['socialnetworks.enableFacebookSupport'] = array(0 => 'checkbox', 1 => 'Facebook support (default: deactivated)');

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG["ad_menu_attachments"] = "Attachments";
$PMF_LANG["ad_menu_attachment_admin"] = "Attachment administration";
$PMF_LANG['msgAttachmentsFilename'] = 'Filename';
$PMF_LANG['msgAttachmentsFilesize'] = 'Filensize';
$PMF_LANG['msgAttachmentsMimeType'] = 'MIME Type';
$PMF_LANG['msgAttachmentsWannaDelete'] = 'Are you sure you want to delete this attachment?';
$PMF_LANG['msgAttachmentsDeleted'] = 'Attachment <strong>successfully</strong> deleted.';

// added v2.7.0-alpha2 - 2010-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = 'Reports';
$PMF_LANG["ad_stat_report_fields"] = "Fields";
$PMF_LANG["ad_stat_report_category"] = "Category";
$PMF_LANG["ad_stat_report_sub_category"] = "Subcategory";
$PMF_LANG["ad_stat_report_translations"] = "Translations";
$PMF_LANG["ad_stat_report_language"] = "Language";
$PMF_LANG["ad_stat_report_id"] = "FAQ ID";
$PMF_LANG["ad_stat_report_sticky"] = "Sticky FAQ";
$PMF_LANG["ad_stat_report_title"] = "Question";
$PMF_LANG["ad_stat_report_creation_date"] = "Date";
$PMF_LANG["ad_stat_report_owner"] = "Original author";
$PMF_LANG["ad_stat_report_last_modified_person"] = "Last author";
$PMF_LANG["ad_stat_report_url"] = "URL";
$PMF_LANG["ad_stat_report_visits"] = "Visits";
$PMF_LANG["ad_stat_report_make_report"] = "Generate Report";
$PMF_LANG["ad_stat_report_make_csv"] = "Export to CSV";

// added v2.7.0-alpha2 - 2010-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = 'Registration';
$PMF_LANG['msgRegistrationCredentials'] = 'To register please enter your name, your loginname and a valid email address!';
$PMF_LANG['msgRegistrationNote'] = 'After successful registration you will receive an answer soon after Administration has authorized your registration.';

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = "Changelog history";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = array(0 => 'checkbox', 1 => 'Single Sign On Support (default: deactivated)');
$LANG_CONF['security.ssoLogoutRedirect'] = array(0 => 'input', 1 => 'Single Sign On logout redirect service URL');
$LANG_CONF['main.dateFormat'] = array(0 => 'input', 1 => 'Date format (default: Y-m-d H:i)');
$LANG_CONF['security.enableLoginOnly'] = array(0 => 'checkbox', 1 => 'Complete secured FAQ (default: deactivated)');

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = 'Security configuration';
$PMF_LANG['ad_search_delsuc'] = 'The searchterm was successfully deleted.';
$PMF_LANG['ad_search_delfail'] = 'The seachterm was not deleted.';

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = 'About this FAQ';
$LANG_CONF['security.useSslOnly'] = array(0 => 'checkbox', 1 => 'FAQ with SSL only (default: deactivated)');
$PMF_LANG['msgTableOfContent'] = 'Table of Content';

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG["msgExportAllFaqs"] = "Print all as PDF";
$PMF_LANG["ad_online_verification"] = "Online verification check";
$PMF_LANG["ad_verification_button"] = "Click to verify your phpMyFAQ installation";
$PMF_LANG["ad_verification_notokay"] = "Your version of phpMyFAQ has local changes:";
$PMF_LANG["ad_verification_okay"] = "Your version of phpMyFAQ was successfully verified.";

// added v2.8.0-alpha - 2011-09-22 by Anatoliy
$PMF_LANG['cacheControlCenter'] = 'Cache configuration';
$LANG_CONF['cache.varnishEnable'] = array(0 => 'checkbox', 1 => 'Enable Varnish >=3.0 support<br><small>You will need varnish PECL extension</small>');
$LANG_CONF['cache.varnishHost'] = array(0 => 'input', 1 => 'Varnish host');
$LANG_CONF['cache.varnishPort'] = array(0 => 'input', 1 => 'Varnish port');
$LANG_CONF['cache.varnishSecret'] = array(0 => 'input', 1 => 'Varnish secret');
$LANG_CONF['cache.varnishTimeout'] = array(0 => 'input', 1 => 'Varnish timeout');

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = 'Search FAQs';

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF["records.enableCloseQuestion"] = array(0 => "checkbox", 1 => "Close open question after answer?");
$LANG_CONF["records.enableDeleteQuestion"] = array(0 => "checkbox", 1 => "Delete open question after answer?");
$PMF_LANG["msg2answerFAQ"] = "Answered";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG["headerUserControlPanel"] = 'User Control Panel';

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG["rememberMe"] = 'Remember me';
$PMF_LANG["ad_menu_instances"] = "FAQ Multi-sites";

// added v2.8.0-alpha2 - 2012-07-07 by Anatoliy
$LANG_CONF['records.autosaveActive'] = array(0 => 'checkbox', 1 => 'Activate FAQ autosaving');
$LANG_CONF['records.autosaveSecs'] = array(0 => 'input', 1 => 'Interval for autosaving in seconds, default 180');

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = 'FAQs inactive';
$LANG_CONF["main.maintenanceMode"] = array(0 => "checkbox", 1 => "Set FAQ in maintenance mode");
$PMF_LANG['msgMode'] = "Modus";
$PMF_LANG['msgMaintenanceMode'] = "FAQ is in maintenance";
$PMF_LANG['msgOnlineMode'] = "FAQ is online";