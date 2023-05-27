<?php

/**
 * The English language file - try to be the best of British and American English
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matthias Sommerfeld <mso@bluebirdy.de>
 * @author Henning Schulzrinne <hgs@cs.columbia.edu>
 * @copyright 2004-2023 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2004-02-19
 * @codingStandardsIgnoreFile
 */

/**
 *                !!! IMPORTANT NOTE !!!
 * Please consider following while defining new vars:
 * - one variable definition per line !!!
 * - the perfect case is to define a scalar string value
 * - if some dynamic content is needed, use sprintf() syntax
 * - arrays are allowed but not recommended
 * - no comments at the end of line after the var definition
 * - do not use '=' char in the array keys
 *   (eq. $PMF_LANG["a=b"] is not allowed)
 *
 *  Please be consistent with this format as we need it for
 *  the translation tool to work properly
 */

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "en";
$PMF_LANG["language"] = "english";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";
$PMF_LANG["nplurals"] = "2";

// Navigation
$PMF_LANG["msgCategory"] = "Categories";
$PMF_LANG["msgShowAllCategories"] = "All categories";
$PMF_LANG["msgSearch"] = "Search";
$PMF_LANG["msgAddContent"] = "Add new FAQ";
$PMF_LANG["msgQuestion"] = "Add question";
$PMF_LANG["msgOpenQuestions"] = "Open questions";
$PMF_LANG["msgHelp"] = "Help";
$PMF_LANG["msgContact"] = "Contact";
$PMF_LANG["msgHome"] = "FAQ Home";
$PMF_LANG["msgNews"] = "FAQ News";
$PMF_LANG["msgUserOnline"] = " Users online";
$PMF_LANG["msgBack2Home"] = "Back to main page";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Categories";
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
$PMF_LANG["msgMakeXMLExport"] = "Export as XML file";
$PMF_LANG["msgAverageVote"] = "Average rating";
$PMF_LANG["msgVoteUsability"] = "Rate this FAQ";
$PMF_LANG["msgVoteFrom"] = "out of";
$PMF_LANG["msgVoteBad"] = "not helpful at all";
$PMF_LANG["msgVoteGood"] = "extremely helpful";
$PMF_LANG["msgVotings"] = "Votes ";
$PMF_LANG["msgVoteSubmit"] = "Vote";
$PMF_LANG["msgVoteThanks"] = "Thank you for voting!";
$PMF_LANG["msgYouCan"] = "You can ";
$PMF_LANG["msgWriteComment"] = "comment on this FAQ";
$PMF_LANG["msgShowCategory"] = "Content Overview: ";
$PMF_LANG["msgCommentBy"] = "Comment of ";
$PMF_LANG["msgCommentHeader"] = "Comment on this FAQ";
$PMF_LANG["msgYourComment"] = "Your comment";
$PMF_LANG["msgCommentThanks"] = "Thank you for commenting!";
$PMF_LANG["msgSeeXMLFile"] = "open XML file";
$PMF_LANG["msgSend2Friend"] = "Send FAQ to a friend";
$PMF_LANG["msgS2FName"] = "Your name";
$PMF_LANG["msgS2FEMail"] = "Your email address";
$PMF_LANG["msgS2FFriends"] = "Your friends";
$PMF_LANG["msgS2FEMails"] = ". email address";
$PMF_LANG["msgS2FText"] = "The following text will be sent";
$PMF_LANG["msgS2FText2"] = "You'll find the FAQ at the following address";
$PMF_LANG["msgS2FMessage"] = "Additional message for your friends";
$PMF_LANG["msgS2FButton"] = "send email";
$PMF_LANG["msgS2FThx"] = "Thank you for your recommendation!";
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
$PMF_LANG["msgNewContentAddon"] = "Your proposal will not be published right away, but may be posted by an administrator upon review. Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>category</strong>, <strong>question</strong> and <strong>answer</strong>. Please separate any keywords with commas only.";
$PMF_LANG["msgNewContentName"] = "Your name";
$PMF_LANG["msgNewContentMail"] = "Your email address";
$PMF_LANG["msgNewContentCategory"] = "Category";
$PMF_LANG["msgNewContentTheme"] = "Your question";
$PMF_LANG["msgNewContentArticle"] = "Your answer";
$PMF_LANG["msgNewContentKeywords"] = "Keywords";
$PMF_LANG["msgNewContentLink"] = "Link for this FAQ";
$PMF_LANG["msgNewContentSubmit"] = "submit";
$PMF_LANG["msgInfo"] = "More information: ";
$PMF_LANG["msgNewContentThanks"] = "Thank you for your suggestion!";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Ask your question below";
$PMF_LANG["msgAskCategory"] = "Category";
$PMF_LANG["msgAskYourQuestion"] = "Your question";
$PMF_LANG["msgAskThx4Mail"] = "Thank you for your question!";
$PMF_LANG["msgDate_User"] = "Date / User";
$PMF_LANG["msgQuestion2"] = "Question";
$PMF_LANG["msg2answer"] = "Answer";
$PMF_LANG["msgQuestionText"] = "Here you can see questions asked by other users. If you answer these questions, your answers may be inserted into the FAQ.";
$PMF_LANG["msgNoQuestionsAvailable"] = "Currently there are no pending questions.";

// Contact
$PMF_LANG["msgContactEMail"] = "Email the FAQ owner";
$PMF_LANG["msgMessage"] = "Your message";

// Homepage
$PMF_LANG["msgTopTen"] = "Most popular FAQs";
$PMF_LANG["msgHomeThereAre"] = "There are ";
$PMF_LANG["msgHomeArticlesOnline"] = " FAQs online";
$PMF_LANG["msgNoNews"] = "No news is good news.";
$PMF_LANG["msgLatestArticles"] = "Latest FAQs";

// Email notification
$PMF_LANG["msgMailThanks"] = "Thank you for your proposal to the FAQ!";
$PMF_LANG["msgMailCheck"] = "There's a new entry in the FAQ! Please check it here or in the admin section.";
$PMF_LANG["msgMailContact"] = "Your message has been sent to the administrator.";

// Error messages
$PMF_LANG["err_noDatabase"] = "No database connection available.";
$PMF_LANG["err_noHeaders"] = "No category found.";
$PMF_LANG["err_noArticles"] = "No FAQs available.";
$PMF_LANG["err_badID"] = "Wrong ID.";
$PMF_LANG["err_noTopTen"] = "No popular FAQs available yet.";
$PMF_LANG["err_nothingFound"] = "No entry found.";
$PMF_LANG["err_SaveEntries"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>category</strong>, <strong>question</strong>, <strong>your Record</strong> and, when requested, the <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_SaveComment"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your comments</strong> and, when requested, the <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_VoteTooMuch"] = "We do not count multiple votes.";
$PMF_LANG["err_noVote"] = "You did not rate the question!";
$PMF_LANG["err_noMailAdress"] = "Your email address is not correct.";
$PMF_LANG["err_sendMail"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your question</strong> and, when requested, the <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>!";

// Search help
$PMF_LANG["help_search"] = "<strong>Search for records:</strong><br>With an entry like <strong style=\"color: Red;\">word1 word2</strong> you can do a relevance descending search for two or more search criterion.<strong>Notice:</strong> Your search criterion has to be at least 4 letters long otherwise your request will be rejected.";

// Menu
$PMF_LANG["ad"] = "Administration";
$PMF_LANG["ad_menu_user_administration"] = "Users";
$PMF_LANG["ad_menu_entry_aprove"] = "Approve FAQs";
$PMF_LANG["ad_menu_entry_edit"] = "Edit FAQs";
$PMF_LANG["ad_menu_categ_add"] = "Add new category";
$PMF_LANG["ad_menu_categ_edit"] = "Categories";
$PMF_LANG["ad_menu_news_add"] = "Add news";
$PMF_LANG["ad_menu_news_edit"] = "FAQ News";
$PMF_LANG["ad_menu_open"] = "Open questions";
$PMF_LANG["ad_menu_stat"] = "Statistics";
$PMF_LANG["ad_menu_cookie"] = "Set cookies";
$PMF_LANG["ad_menu_session"] = "View sessions";
$PMF_LANG["ad_menu_adminlog"] = "View admin log";
$PMF_LANG["ad_menu_passwd"] = "Change password";
$PMF_LANG["ad_menu_logout"] = "Logout";
$PMF_LANG["ad_menu_startpage"] = "Start page";

// Messages
$PMF_LANG["ad_msg_identify"] = "Please identify yourself.";
$PMF_LANG["ad_msg_passmatch"] = "Both passwords must <strong>match</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "The profile of";
$PMF_LANG["ad_msg_savedsuc_2"] = "was saved successfully.";
$PMF_LANG["ad_msg_mysqlerr"] = "Due to a <strong>database error</strong>, the profile could not be saved.";
$PMF_LANG["ad_msg_noauth"] = "You are not authorized.";

// General
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
$PMF_LANG['ad_gen_or'] = "or";

// User administration
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
$PMF_LANG["ad_user_del_1"] = "The user";
$PMF_LANG["ad_user_del_2"] = "shall be deleted?";
$PMF_LANG["ad_user_del_3"] = "Are you sure?";
$PMF_LANG["ad_user_deleted"] = "The user was successfully deleted.";
$PMF_LANG["ad_user_checkall"] = "Select all";

// Contribution management
$PMF_LANG["ad_entry_aor"] = "FAQ administration";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Topic";
$PMF_LANG["ad_entry_action"] = "Action";
$PMF_LANG["ad_entry_edit_1"] = "Edit Record";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Question";
$PMF_LANG["ad_entry_content"] = "Answer";
$PMF_LANG["ad_entry_keywords"] = "Keywords";
$PMF_LANG["ad_entry_author"] = "Author";
$PMF_LANG["ad_entry_category"] = "Category";
$PMF_LANG["ad_entry_active"] = "Visible";
$PMF_LANG["ad_entry_date"] = "Date";
$PMF_LANG["ad_entry_status"] = "FAQ status";
$PMF_LANG["ad_entry_changed"] = "Changed?";
$PMF_LANG["ad_entry_changelog"] = "Changelog";
$PMF_LANG["ad_entry_commentby"] = "Comment by";
$PMF_LANG["ad_entry_comment"] = "Comments";
$PMF_LANG["ad_entry_save"] = "Save";
$PMF_LANG["ad_entry_delete"] = "delete";
$PMF_LANG["ad_entry_delcom_1"] = "Are you sure ";
$PMF_LANG["ad_entry_delcom_2"] = "'s comment should be deleted?";
$PMF_LANG["ad_entry_commentdelsuc"] = "The comment was <strong>successfully</strong> deleted.";
$PMF_LANG["ad_entry_back"] = "Back to the article";
$PMF_LANG["ad_entry_commentdelfail"] = "The comment was <strong>not</strong> deleted.";
$PMF_LANG["ad_entry_savedsuc"] = "The changes were saved <strong>successfully</strong>.";
$PMF_LANG["ad_entry_savedfail"] = "Unfortunately, a <strong>database error</strong> occurred.";
$PMF_LANG["ad_entry_del_1"] = "Are you sure the topic";
$PMF_LANG["ad_entry_del_2"] = "of";
$PMF_LANG["ad_entry_del_3"] = "should be deleted?";
$PMF_LANG["ad_entry_delsuc"] = "Issue <strong>successfully</strong> deleted.";
$PMF_LANG["ad_entry_delfail"] = "Issue was <strong>not deleted</strong>!";
$PMF_LANG["ad_entry_back"] = "Back";

// News management
$PMF_LANG["ad_news_header"] = "Article header";
$PMF_LANG["ad_news_text"] = "Text of the Record";
$PMF_LANG["ad_news_link_url"] = "Link";
$PMF_LANG["ad_news_link_title"] = "Title of the link";
$PMF_LANG["ad_news_link_target"] = "Target of the link";
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
$PMF_LANG["ad_news_updatesuc"] = "The news entry has been successfully updated.";
$PMF_LANG["ad_news_del"] = "Are you sure you want to delete this news entry?";
$PMF_LANG["ad_news_yesdelete"] = "yes, delete!";
$PMF_LANG["ad_news_nodelete"] = "no";
$PMF_LANG["ad_news_delsuc"] = "The news entry has been successfully deleted.";
$PMF_LANG["ad_news_updatenews"] = "News entry updated.";

// Category management
$PMF_LANG["ad_categ_new"] = "Add new category";
$PMF_LANG["ad_categ_catnum"] = "Category number";
$PMF_LANG["ad_categ_subcatnum"] = "Subcategory number";
$PMF_LANG["ad_categ_nya"] = "<em>not yet available!</em>";
$PMF_LANG["ad_categ_titel"] = "Category title";
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
$PMF_LANG["ad_categ_remark"] = "Please note that existing entries will not be visible anymore if you delete the category. You must either assign a new category for the article or delete the article.";
$PMF_LANG["ad_categ_edit_1"] = "Edit";
$PMF_LANG["ad_categ_edit_2"] = "Category";
$PMF_LANG["ad_categ_added"] = "The category was added.";
$PMF_LANG["ad_categ_updated"] = "The category was updated.";
$PMF_LANG["ad_categ_del_yes"] = "yes, delete!";
$PMF_LANG["ad_categ_del_no"] = "no!";
$PMF_LANG["ad_categ_deletesure"] = "Are you sure you want to delete this category?";
$PMF_LANG["ad_categ_deleted"] = "Category deleted.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "The cookie was <strong>successfully</strong> set.";
$PMF_LANG["ad_cookie_already"] = "A cookie was set already. You now have following options";
$PMF_LANG["ad_cookie_again"] = "Set cookie again";
$PMF_LANG["ad_cookie_delete"] = "Delete cookie";
$PMF_LANG["ad_cookie_no"] = "Currently, there is no cookie saved. With a cookie you could save your login session, thus no need to remember your login details each visit. You now have following options";
$PMF_LANG["ad_cookie_set"] = "Set cookie";
$PMF_LANG["ad_cookie_deleted"] = "Cookie deleted successfully.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "Admin log";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Change your password";
$PMF_LANG["ad_passwd_old"] = "Old password";
$PMF_LANG["ad_passwd_new"] = "New password";
$PMF_LANG["ad_passwd_con"] = "Retype password";
$PMF_LANG["ad_passwd_change"] = "Change password";
$PMF_LANG["ad_passwd_suc"] = "Password changed successfully.";
$PMF_LANG["ad_passwd_remark"] = "<strong>ATTENTION:</strong><br>Cookie has to be set again!";
$PMF_LANG["ad_passwd_fail"] = "The old password <strong>must</strong> be entered correctly and both new ones have to <strong>match</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Add new user account";
$PMF_LANG["ad_adus_name"] = "Username";
$PMF_LANG["ad_adus_password"] = "Password";
$PMF_LANG["ad_adus_add"] = "Add user";
$PMF_LANG["ad_adus_suc"] = "User <strong>successfully</strong> added.";
$PMF_LANG["ad_adus_edit"] = "Edit profile";
$PMF_LANG["ad_adus_dberr"] = "Database error";
$PMF_LANG["ad_adus_exerr"] = "Username <strong>already exists</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "Session ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Time";
$PMF_LANG["ad_sess_pageviews"] = "Page views";
$PMF_LANG["ad_sess_search"] = "Search";
$PMF_LANG["ad_sess_sfs"] = "Search for sessions";
$PMF_LANG["ad_sess_s_ip"] = "IP";
$PMF_LANG["ad_sess_s_minct"] = "min. actions";
$PMF_LANG["ad_sess_s_date"] = "Date";
$PMF_LANG["ad_sess_s_after"] = "after";
$PMF_LANG["ad_sess_s_before"] = "before";
$PMF_LANG["ad_sess_s_search"] = "Search";
$PMF_LANG["ad_sess_session"] = "Session";
$PMF_LANG["ad_sess_r"] = "Search results for";
$PMF_LANG["ad_sess_referer"] = "Referer";
$PMF_LANG["ad_sess_browser"] = "Browser";
$PMF_LANG["ad_sess_ai_rubrik"] = "Category";
$PMF_LANG["ad_sess_ai_artikel"] = "Record";
$PMF_LANG["ad_sess_ai_sb"] = "Search strings";
$PMF_LANG["ad_sess_ai_sid"] = "Session ID";
$PMF_LANG["ad_sess_back"] = "Back";
$PMF_LANG['ad_sess_noentry'] = "No entry";

// Statistics
$PMF_LANG["ad_rs"] = "Rating statistics";
$PMF_LANG["ad_rs_rating_1"] = "The ranking of";
$PMF_LANG["ad_rs_rating_2"] = "users shows";
$PMF_LANG["ad_rs_red"] = "Red";
$PMF_LANG["ad_rs_green"] = "Green";
$PMF_LANG["ad_rs_altt"] = "with an average lower than 20%";
$PMF_LANG["ad_rs_ahtf"] = "with an average higher than 80%";
$PMF_LANG["ad_rs_no"] = "No ranking available";

// Auth
$PMF_LANG["ad_auth_insert"] = "Please enter your username and password.";
$PMF_LANG["ad_auth_user"] = "Username";
$PMF_LANG["ad_auth_passwd"] = "Password";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Reset";
$PMF_LANG["ad_auth_fail"] = "Wrong username or password.";
$PMF_LANG["ad_auth_sess"] = "The Session ID is passed.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Edit configuration";
$PMF_LANG["ad_config_save"] = "Save configuration";
$PMF_LANG["ad_config_reset"] = "Reset";
$PMF_LANG["ad_config_saved"] = "The configuration has been saved successfully.";
$PMF_LANG["ad_menu_editconfig"] = "Edit configuration";
$PMF_LANG["ad_att_none"] = "No attachments available";
$PMF_LANG["ad_att_add"] = "Add new attachment";
$PMF_LANG["ad_entryins_suc"] = "Record successfully saved.";
$PMF_LANG["ad_entryins_fail"] = "An error occurred.";
$PMF_LANG["ad_att_del"] = "Delete";
$PMF_LANG["ad_att_nope"] = "Attachments can be added only while editing.";
$PMF_LANG["ad_att_delsuc"] = "The attachment has been deleted successfully.";
$PMF_LANG["ad_att_delfail"] = "An error occurred while deleting the attachment.";
$PMF_LANG["ad_entry_add"] = "Add new FAQ";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "A backup is a complete image of the database content. The format of the backup is a SQL transaction file, which can be imported using tools like phpMyAdmin or the commandline SQL client. A backup should be performed at least once a month.";
$PMF_LANG["ad_csv_link"] = "Download the backup";
$PMF_LANG["ad_csv_head"] = "Create a backup";
$PMF_LANG["ad_att_addto"] = "Add an attachment to the issue";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Select attachment";
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
$PMF_LANG["ad_menu_stat"] = "Rating Statistics";
$PMF_LANG["ad_kateg_add"] = "Add new top-level category";
$PMF_LANG["ad_kateg_rename"] = "Edit";
$PMF_LANG["ad_adminlog_date"] = "Date";
$PMF_LANG["ad_adminlog_user"] = "User";
$PMF_LANG["ad_adminlog_ip"] = "IP address";

$PMF_LANG["ad_stat_sess"] = "Sessions";
$PMF_LANG["ad_stat_days"] = "Days";
$PMF_LANG["ad_stat_vis"] = "Sessions (Visits)";
$PMF_LANG["ad_stat_vpd"] = "Visits per Day";
$PMF_LANG["ad_stat_fien"] = "First Log";
$PMF_LANG["ad_stat_laen"] = "Last Log";
$PMF_LANG["ad_stat_browse"] = "browse Sessions";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Time";
$PMF_LANG["ad_sess_sid"] = "Session ID";
$PMF_LANG["ad_sess_ip"] = "IP address";

$PMF_LANG["ad_ques_take"] = "Answer the question";
$PMF_LANG["no_cats"] = "No Categories found.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Invalid user or password.";
$PMF_LANG["ad_log_sess"] = "Session expired.";
$PMF_LANG["ad_log_edit"] = "\"Edit User\" form for the following user: ";
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
$PMF_LANG["ad_xml_gen"] = "Create XML export";
$PMF_LANG["ad_entry_locale"] = "Language";
$PMF_LANG["msgLanguageSubmit"] = "Change language";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_attach_4"] = "The attached file must be smaller than %s bytes.";
$PMF_LANG["ad_menu_export"] = "Export your FAQ";

$PMF_LANG['rightsLanguage::add_user'] = "Add user";
$PMF_LANG['rightsLanguage::edit_user'] = "Edit user";
$PMF_LANG['rightsLanguage::delete_user'] = "Delete user";
$PMF_LANG['rightsLanguage::add_faq'] = "Add record";
$PMF_LANG['rightsLanguage::edit_faq'] = "Edit record";
$PMF_LANG['rightsLanguage::delete_faq'] = "Delete record";
$PMF_LANG['rightsLanguage::viewlog'] = "View logs";
$PMF_LANG['rightsLanguage::adminlog'] = "View admin log";
$PMF_LANG['rightsLanguage::delcomment'] = "Delete comment";
$PMF_LANG['rightsLanguage::addnews'] = "Add news";
$PMF_LANG['rightsLanguage::editnews'] = "Edit news";
$PMF_LANG['rightsLanguage::delnews'] = "Delete news";
$PMF_LANG['rightsLanguage::addcateg'] = "Add category";
$PMF_LANG['rightsLanguage::editcateg'] = "Edit category";
$PMF_LANG['rightsLanguage::delcateg'] = "Delete category";
$PMF_LANG['rightsLanguage::passwd'] = "Change passwords";
$PMF_LANG['rightsLanguage::editconfig'] = "Edit configuration";
$PMF_LANG['rightsLanguage::addatt'] = "Add attachments";
$PMF_LANG['rightsLanguage::delatt'] = "Delete attachments";
$PMF_LANG['rightsLanguage::backup'] = "Create backups";
$PMF_LANG['rightsLanguage::restore'] = "Restore backups";
$PMF_LANG['rightsLanguage::delquestion'] = "Delete open questions";
$PMF_LANG['rightsLanguage::changebtrevs'] = "Edit revisions";

$PMF_LANG["msgAttachedFiles"] = "Attached files";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "Action";
$PMF_LANG["ad_entry_email"] = "Email";
$PMF_LANG["ad_entry_allowComments"] = "Allow comments";
$PMF_LANG["msgWriteNoComment"] = "You cannot comment on this entry";
$PMF_LANG["ad_user_realname"] = "Real name";
$PMF_LANG["ad_export_generate_pdf"] = "Create PDF file";
$PMF_LANG["ad_export_full_faq"] = "Your FAQ as a PDF file: ";
$PMF_LANG["err_bannedIP"] = "Your IP address has been banned.";
$PMF_LANG["err_SaveQuestion"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your question</strong> and, when requested, the <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Read more on Captcha at Wikipedia\" target=\"_blank\">Captcha</a> code</strong>.";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = ["select", "Language"];
$LANG_CONF["main.languageDetection"] = ["checkbox", "Enable automatic language detection"];
$LANG_CONF['main.titleFAQ'] = ["input", "Title of your FAQ"];
$LANG_CONF['main.currentVersion'] = ["print", "phpMyFAQ Version"];
$LANG_CONF["main.metaDescription"] = ["input", "Description"];
$LANG_CONF["main.metaKeywords"] = ["input", "Keywords for Spiders"];
$LANG_CONF["main.metaPublisher"] = ["input", "Name of the Publisher"];
$LANG_CONF['main.administrationMail'] = ["input", "Email address of the Admin"];
$LANG_CONF["main.contactInformation"] = ["area", "Contact information"];
$LANG_CONF["main.send2friendText"] = ["area", "Text for the send to friend page"];
$LANG_CONF['records.maxAttachmentSize'] = ["input", "Maximum size for attachments in bytes (max. %s bytes)"];
$LANG_CONF["records.disableAttachments"] = ["checkbox", "Enable visibility of attachments"];
$LANG_CONF["main.enableUserTracking"] = ["checkbox", "Enable user tracking"];
$LANG_CONF["main.enableAdminLog"] = ["checkbox", "Use admin log?"];
$LANG_CONF["main.enableCategoryRestrictions"] = ["checkbox", "Enable category restrictions"];
$LANG_CONF["security.ipCheck"] = ["checkbox", "Check the IP in administration"];
$LANG_CONF["records.numberOfRecordsPerPage"] = ["input", "Number of displayed topics per page"];
$LANG_CONF["records.numberOfShownNewsEntries"] = ["input", "Number of news articles"];
$LANG_CONF['security.bannedIPs'] = ["area", "Ban these IPs"];
$LANG_CONF["main.enableRewriteRules"] = ["checkbox", "Enable URL rewrite support? (default: disabled)"];
$LANG_CONF["ldap.ldapSupport"] = ["checkbox", "Enable LDAP support? (default: disabled)"];
$LANG_CONF["main.referenceURL"] = ["input", "URL of your FAQ (e.g.: https://www.example.org/faq/)"];
$LANG_CONF["main.urlValidateInterval"] = ["input", "Interval between AJAX link verification (in seconds)"];
$LANG_CONF["records.enableVisibilityQuestions"] = ["checkbox", "Disable visibility of new questions?"];
$LANG_CONF['security.permLevel'] = ["select", "Permission level"];

$PMF_LANG["ad_categ_new_main_cat"] = "as new main category";
$PMF_LANG["ad_categ_paste_error"] = "Moving this category isn't possible.";
$PMF_LANG["ad_categ_move"] = "move category";
$PMF_LANG["ad_categ_lang"] = "Language";
$PMF_LANG["ad_categ_desc"] = "Description";
$PMF_LANG["ad_categ_change"] = "Change with";

$PMF_LANG["lostPassword"] = "Forgot your password?";
$PMF_LANG["lostpwd_err_1"] = "Error: Username and email address not found.";
$PMF_LANG["lostpwd_err_2"] = "Error: Wrong entries!";
$PMF_LANG["lostpwd_text_1"] = "Thank you for requesting your account information.";
$PMF_LANG["lostpwd_text_2"] = "Please set a new personal password in the admin section of your FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "Email has been sent.";

$PMF_LANG["ad_xmlrpc_button"] = "Click to check version of your phpMyFAQ installation";
$PMF_LANG["ad_xmlrpc_latest"] = "Latest version available on";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Select category language';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Sitemap';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'Access denied';
$PMF_LANG['msgArticleCategories'] = 'Categories for this entry';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'Unique solution ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ record';
$PMF_LANG['ad_entry_new_revision'] = 'Create new revision?';
$PMF_LANG['ad_entry_record_administration'] = 'Record administration';
$PMF_LANG['ad_entry_revision'] = 'Revision';
$PMF_LANG['ad_changerev'] = 'Select revision';
$PMF_LANG['msgCaptcha'] = "Please enter the captcha code";
$PMF_LANG['msgSelectCategories'] = 'Search in ...';
$PMF_LANG['msgAllCategories'] = '... all categories';
$PMF_LANG['ad_you_should_update'] = 'Your phpMyFAQ installation is outdated. You should update to the latest available version.';
$PMF_LANG['msgAdvancedSearch'] = 'Advanced search';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'Spam control center';
$LANG_CONF["spam.enableSafeEmail"] = ["checkbox", "Print user email in a safe way."];
$LANG_CONF["spam.checkBannedWords"] = ["checkbox", "Check public form content against banned words."];
$LANG_CONF["spam.enableCaptchaCode"] = ["checkbox", "Use a captcha code to allow public form submission."];
$PMF_LANG['ad_session_expiring'] = 'Your session will expire in %d minutes: would you like to go on working?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Sessions management';
$PMF_LANG['ad_stat_choose'] = 'Choose the month';
$PMF_LANG['ad_stat_delete'] = 'Immediately delete the selected sessions?';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = 'FAQ Glossary';
$PMF_LANG['ad_glossary_add'] = 'Add glossary entry';
$PMF_LANG['ad_glossary_edit'] = 'Edit glossary entry';
$PMF_LANG['ad_glossary_item'] = 'Title';
$PMF_LANG['ad_glossary_definition'] = 'Definition';
$PMF_LANG['ad_glossary_save'] = 'Save glossary';
$PMF_LANG['ad_glossary_save_success'] = 'Glossary entry successfully saved!';
$PMF_LANG['ad_glossary_save_error'] = 'The glossary entry could not saved because an error occurred.';
$PMF_LANG['ad_glossary_update_success'] = 'Glossary entry successfully updated!';
$PMF_LANG['ad_glossary_update_error'] = 'The glossary entry could not updated because an error occurred.';
$PMF_LANG['ad_glossary_delete'] = 'Delete entry';
$PMF_LANG['ad_glossary_delete_success'] = 'Glossary entry successfully deleted!';
$PMF_LANG['ad_glossary_delete_error'] = 'The glossary entry could not deleted because an error occurred.';
$PMF_LANG['msgNewQuestionVisible'] = 'The question have to be reviewed first before getting public.';
$PMF_LANG['msgQuestionsWaiting'] = 'Waiting for publishing by the administrators: ';
$PMF_LANG['ad_entry_visibility'] = 'published';
$PMF_LANG['ad_entry_not_visibility'] = "not published";

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = "Please enter a password. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] = "Passwords do not match. ";
$PMF_LANG['ad_user_error_loginInvalid'] = "The specified username is invalid.";
$PMF_LANG['ad_user_error_noEmail'] = "Please enter a valid email address. ";
$PMF_LANG['ad_user_error_noRealName'] = "Please enter your real name. ";
$PMF_LANG['ad_user_error_delete'] = "User account could not be deleted. ";
$PMF_LANG['ad_user_error_noId'] = "No ID specified. ";
$PMF_LANG['ad_user_error_protectedAccount'] = "User account is protected. ";
$PMF_LANG['ad_user_deleteUser'] = "Delete user";
$PMF_LANG['ad_user_status'] = "Status";
$PMF_LANG['ad_user_lastModified'] = "last modified";
$PMF_LANG['ad_gen_cancel'] = "Cancel";
$PMF_LANG['rightsLanguage::addglossary'] = "add glossary item";
$PMF_LANG['rightsLanguage::editglossary'] = "edit glossary item";
$PMF_LANG['rightsLanguage::delglossary'] = "delete glossary item";
$PMF_LANG["ad_menu_group_administration"] = "Groups";
$PMF_LANG['ad_user_loggedin'] = 'Logged in as ';
$PMF_LANG['ad_group_details'] = "Group details";
$PMF_LANG['ad_group_add'] = "Add group";
$PMF_LANG['ad_group_add_link'] = "Add group";
$PMF_LANG['ad_group_name'] = "Name";
$PMF_LANG['ad_group_description'] = "Description";
$PMF_LANG['ad_group_autoJoin'] = "Auto-join";
$PMF_LANG['ad_group_suc'] = "Group <strong>successfully</strong> added.";
$PMF_LANG['ad_group_error_noName'] = "Please enter a group name. ";
$PMF_LANG['ad_group_error_delete'] = "Group could not be deleted. ";
$PMF_LANG['ad_group_deleted'] = "The group was successfully deleted.";
$PMF_LANG['ad_group_deleteGroup'] = "Delete group";
$PMF_LANG['ad_group_deleteQuestion'] = "Are you sure you want to delete this group?";
$PMF_LANG['ad_user_uncheckall'] = "Unselect all";
$PMF_LANG['ad_group_membership'] = "Group Membership";
$PMF_LANG['ad_group_members'] = "Members";
$PMF_LANG['ad_group_addMember'] = "+";
$PMF_LANG['ad_group_removeMember'] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'Limit the FAQ data to be exported (optional)';
$PMF_LANG['ad_export_cat_downwards'] = 'Include child categories?';
$PMF_LANG['ad_export_type'] = 'Format for the export';
$PMF_LANG['ad_export_type_choose'] = 'Supported formats:';
$PMF_LANG['ad_export_download_view'] = 'Download or view inline?';
$PMF_LANG['ad_export_download'] = 'download';
$PMF_LANG['ad_export_view'] = 'view in-line';
$PMF_LANG['ad_export_gen_xhtml'] = 'Create XHTML file';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'FAQ News';
$PMF_LANG['ad_news_author_name'] = 'Author name:';
$PMF_LANG['ad_news_author_email'] = 'Author email:';
$PMF_LANG['ad_news_set_active'] = 'Activate';
$PMF_LANG['ad_news_allowComments'] = 'Allow comments:';
$PMF_LANG['ad_news_expiration_window'] = 'News expiration time window (optional)';
$PMF_LANG['ad_news_from'] = 'From:';
$PMF_LANG['ad_news_to'] = 'To:';
$PMF_LANG['ad_news_insertfail'] = 'An error occurred inserting the news entry into the database.';
$PMF_LANG['ad_news_updatefail'] = 'An error occurred updating the news entry in the database.';
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
$PMF_LANG['err_expiredNews'] = 'This news entry is expired and can not be displayed';
$PMF_LANG['err_inactiveNews'] = 'This news entry is in revision and can not be displayed';
$PMF_LANG['msgSearchOnAllLanguages'] = 'search in all languages';
$PMF_LANG['ad_entry_tags'] = 'Tags';
$PMF_LANG['msg_tags'] = 'Tags';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'Related entries';
$LANG_CONF['records.numberOfRelatedArticles'] = ["input", "Number of related entries"];

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
$PMF_LANG["ad_categ_show"] = "Category overview";
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
$PMF_LANG['ad_adminlog_del_older_30d'] = 'Immediately delete logs older than 30 days';
$PMF_LANG['ad_adminlog_delete_success'] = 'Older logs successfully deleted.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'No logs deleted: an error occurred performing the request.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['ad_quicklinks'] = 'Quicklinks';
$PMF_LANG['ad_quick_category'] = 'Add new category';
$PMF_LANG['ad_quick_record'] = 'Add new FAQ record';
$PMF_LANG['ad_quick_user'] = 'Add new user';
$PMF_LANG['ad_quick_group'] = 'Add new group';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'Translation proposal';
$PMF_LANG['msgNewTranslationAddon'] = 'Your proposal will not be published right away, but may be posted by an administrator upon review. Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>your question translation</strong> and <strong>your answer translation</strong>. Please separate any keywords with commas only.';
$PMF_LANG['msgNewTransSourcePane'] = 'Source pane';
$PMF_LANG['msgNewTranslationPane'] = 'Translation pane';
$PMF_LANG['msgNewTranslationName'] = "Your name";
$PMF_LANG['msgNewTranslationMail'] = "Your email address";
$PMF_LANG['msgNewTranslationKeywords'] = "Keywords";
$PMF_LANG['msgNewTranslationSubmit'] = 'Submit your proposal';
$PMF_LANG['msgTranslate'] = 'Translate this FAQ';
$PMF_LANG['msgTranslateSubmit'] = 'Start translation...';
$PMF_LANG['msgNewTranslationThanks'] = "Thank you for your translation proposal!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG['rightsLanguage::addgroup'] = "add group accounts";
$PMF_LANG['rightsLanguage::editgroup'] = "edit group accounts";
$PMF_LANG['rightsLanguage::delgroup'] = "delete group accounts";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = 'Link opens in parent window';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'Comments';
$PMF_LANG['ad_comment_administration'] = 'Comments administration';
$PMF_LANG['ad_comment_faqs'] = 'Comments in FAQ entries:';
$PMF_LANG['ad_comment_news'] = 'Comments in News entries:';
$PMF_LANG['msgPDF'] = 'PDF version';
$PMF_LANG['ad_groups'] = 'Groups';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = ['select', 'Record sorting (according to property)'];
$LANG_CONF['records.sortby'] = ['select', 'Record sorting (descending or ascending)'];
$PMF_LANG['ad_conf_order_id'] = 'ID<br>(default)';
$PMF_LANG['ad_conf_order_thema'] = 'Title';
$PMF_LANG['ad_conf_order_visits'] = 'Number of visitors';
$PMF_LANG['ad_conf_order_updated'] = 'Date';
$PMF_LANG['ad_conf_order_author'] = 'Author';
$PMF_LANG['ad_conf_desc'] = 'descending';
$PMF_LANG['ad_conf_asc'] = 'ascending';
$PMF_LANG['mainControlCenter'] = 'Main';
$PMF_LANG['recordsControlCenter'] = 'FAQs';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = ["checkbox", "Activate new records"];
$LANG_CONF['records.defaultAllowComments'] = ["checkbox", "Allow comments for new records<br>(default: disallowed)"];

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'Records in this category';
$PMF_LANG['msgTagSearch'] = 'Tagged entries';
$PMF_LANG['ad_pmf_info'] = 'phpMyFAQ Information';
$PMF_LANG['ad_online_info'] = 'Online version check';
$PMF_LANG['ad_system_info'] = 'System Information';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = 'Sign up';
$PMF_LANG["ad_user_loginname"] = 'Username:';
$PMF_LANG['errorRegistration'] = 'This field is required!';
$PMF_LANG['submitRegister'] = 'Create';
$PMF_LANG['msgUserData'] = 'User information required for registration';
$PMF_LANG['captchaError'] = 'Please enter the right captcha code!';
$PMF_LANG['msgRegError'] = 'Following errors occurred. Please correct them:';
$PMF_LANG['successMessage'] = 'Your registration was successful. You will soon receive a confirmation mail with your login data!';
$PMF_LANG['msgRegThankYou'] = 'Thank you for your registration!';
$PMF_LANG['emailRegSubject'] = '[%sitename%] Registration: new user';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'Most popular searches';
$LANG_CONF['main.enableWysiwygEditor'] = ["checkbox", "Enable bundled WYSIWYG editor"];

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
$PMF_LANG['msgTransToolSureDeleteFile'] = 'Are you sure you want to delete this language file?';
$PMF_LANG['msgTransToolFileRemoved'] = 'Language file successfully removed';
$PMF_LANG['msgTransToolErrorRemovingFile'] = 'Error removing the language file';
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
$LANG_CONF['records.attachmentsPath'] = ["input", "Path where attachments will be saved.<br><small>Relative path means a folder within web root</small>"];

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "The file you are trying to download was not found on this server";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"][0] = "%d user online";
$PMF_LANG["plmsgUserOnline"][1] = "%d users online";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = ["select", "Template set to be used"];

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
$PMF_LANG['rightsLanguage::addtranslation'] = "add translation";
$PMF_LANG['rightsLanguage::edittranslation'] = "edit translation";
$PMF_LANG['rightsLanguage::deltranslation'] = "delete translation";
$PMF_LANG['rightsLanguage::approverec'] = "approve records";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["records.enableAttachmentEncryption"] = ["checkbox", "Enable attachment encryption <br><small>Ignored when attachments is disabled</small>"];
$LANG_CONF["records.defaultAttachmentEncKey"] = ["input", 'Default attachment encryption key <br><small>Ignored if attachment encryption is disabled</small><br><small><span class="text-danger">WARNING: Do not change this once set and enabled file encryption!!!</span></small>'];

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'Upgrade';
$PMF_LANG['ad_you_shouldnt_update'] = 'You have the latest version of phpMyFAQ. You do not need to upgrade.';
$LANG_CONF['security.useSslForLogins'] = ['checkbox', "Only allow logins over SSL connection?"];
$PMF_LANG['msgSecureSwitch'] = "Switch to secure mode to login!";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = 'Please note that no files will be written until you click the save button';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'Page %d buffer recorded successfully';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Error recording page %d buffer';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'Recording page %d buffer';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = 'Active';

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'The attachment is invalid, please inform an administrator';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms'] = ['input', 'Number of listed search terms'];
$LANG_CONF['records.orderingPopularFaqs'] = ["select", "Sorting of the top FAQs"];
$PMF_LANG['list_all_users'] = 'List all users';

$PMF_LANG['records.orderingPopularFaqs.visits'] = "list most visited entries";
$PMF_LANG['records.orderingPopularFaqs.voting'] = "list most voted entries";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = 'Please separate words by comma.';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = 'update';
$PMF_LANG['msgKeepFaqDate'] = 'keep';
$PMF_LANG['msgEditFaqDat'] = 'edit';
$LANG_CONF['main.optionalMailAddress'] = ['checkbox', 'Mail address as mandatory field'];

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = ['select', 'Sort by relevance'];
$LANG_CONF["search.enableRelevance"] = ["checkbox", "Activate relevance support?"];
$PMF_LANG['searchControlCenter'] = 'Search';
$PMF_LANG['search.relevance.thema-content-keywords'] = 'Question - Answer - Keywords';
$PMF_LANG['search.relevance.thema-keywords-content'] = 'Question - Keywords - Answer';
$PMF_LANG['search.relevance.content-thema-keywords'] = 'Answer - Question - Keywords';
$PMF_LANG['search.relevance.content-keywords-thema'] = 'Answer - Keywords - Question';
$PMF_LANG['search.relevance.keywords-content-thema'] = 'Keywords - Answer - Question';
$PMF_LANG['search.relevance.keywords-thema-content'] = 'Keywords - Question - Answer';

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = 'Login';
$PMF_LANG['socialNetworksControlCenter'] = 'Social networks';
$LANG_CONF['socialnetworks.enableTwitterSupport'] = ['checkbox', 'Twitter support'];
$LANG_CONF['socialnetworks.twitterConsumerKey'] = ['input', 'Twitter Consumer Key'];
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = ['input', 'Twitter Consumer Secret'];

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = ['input', 'Twitter Access Token Key'];
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = ['input', 'Twitter Access Token Secret'];

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG["ad_menu_attachments"] = "FAQ Attachments";
$PMF_LANG["ad_menu_attachment_admin"] = "Attachment administration";
$PMF_LANG['msgAttachmentsFilename'] = 'Filename';
$PMF_LANG['msgAttachmentsFilesize'] = 'File size';
$PMF_LANG['msgAttachmentsMimeType'] = 'MIME type';
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
$PMF_LANG['msgRegistrationCredentials'] = 'To register please enter your name, your username and a valid email address!';
$PMF_LANG['msgRegistrationNote'] = 'After successfully submitting this form, you will receive an email once an administrator has authorized your registration.';

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = "Changelog history";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = ['checkbox', 'Enable Single Sign On Support'];
$LANG_CONF['security.ssoLogoutRedirect'] = ['input', 'Single Sign On logout redirect service URL'];
$LANG_CONF['main.dateFormat'] = ['input', 'Date format (default: Y-m-d H:i)'];
$LANG_CONF['security.enableLoginOnly'] = ['checkbox', 'Complete secured FAQ'];

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = 'Security';
$PMF_LANG['ad_search_delsuc'] = 'The search term was successfully deleted.';
$PMF_LANG['ad_search_delfail'] = 'The search term was not deleted.';

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = 'About this FAQ';
$LANG_CONF['security.useSslOnly'] = ['checkbox', 'FAQ with SSL only'];
$PMF_LANG['msgTableOfContent'] = 'Table of Content';

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG["msgExportAllFaqs"] = "Print all as PDF";
$PMF_LANG["ad_online_verification"] = "Online verification check";
$PMF_LANG["ad_verification_button"] = "Click to verify your phpMyFAQ installation";
$PMF_LANG["ad_verification_notokay"] = "Your version of phpMyFAQ has local changes";
$PMF_LANG["ad_verification_okay"] = "Your version of phpMyFAQ was successfully verified.";

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = 'Search for FAQs';

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF["records.enableCloseQuestion"] = ["checkbox", "Close open question after answer?"];
$LANG_CONF["records.enableDeleteQuestion"] = ["checkbox", "Delete open question after answer?"];
$PMF_LANG["msg2answerFAQ"] = "Answered";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG["headerUserControlPanel"] = 'User Control Panel';

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG["rememberMe"] = 'Log me on automatically next time';
$PMF_LANG["ad_menu_instances"] = "FAQ Multisites";

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = 'FAQs inactive';
$LANG_CONF["main.maintenanceMode"] = ["checkbox", "Set FAQ in maintenance mode"];
$PMF_LANG['msgMode'] = "Modus";
$PMF_LANG['msgMaintenanceMode'] = "FAQ is in maintenance";
$PMF_LANG['msgOnlineMode'] = "FAQ is online";

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG['msgShowMore'] = "show more";
$PMF_LANG['msgQuestionAnswered'] = "Question answered";
$PMF_LANG['msgMessageQuestionAnswered'] = "Your question at %s was answered. Please check it here";

// added v2.8.0-alpha3 - 2012-11-03 by Thorsten
$PMF_LANG['rightsLanguage::addattachment'] = "Add attachments";
$PMF_LANG['rightsLanguage::editattachment'] = "Edit attachments";
$PMF_LANG['rightsLanguage::delattachment'] = "Delete attachments";
$PMF_LANG['rightsLanguage::dlattachment'] = "Download attachments";
$PMF_LANG['rightsLanguage::reports'] = "Generate reports";
$PMF_LANG['rightsLanguage::addfaq'] = "Add FAQs in the frontend";
$PMF_LANG['rightsLanguage::addquestion'] = "Add questions in the frontend";
$PMF_LANG['rightsLanguage::addcomment'] = "Add comments in the frontend";
$PMF_LANG['rightsLanguage::editinstances'] = "Edit Multisites";
$PMF_LANG['rightsLanguage::addinstances'] = "Add Multisites";
$PMF_LANG['rightsLanguage::delinstances'] = "Delete Multisites";
$PMF_LANG['rightsLanguage::export'] = "Export FAQs";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF["records.randomSort"] = ["checkbox", "Sort FAQs randomly"];
$LANG_CONF['main.enableWysiwygEditorFrontend'] = ["checkbox", "Enable bundled WYSIWYG editor in frontend"];

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF["main.enableGravatarSupport"] = ["checkbox", "Gravatar Support"];

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG["ad_stopwords_desc"] = "Please select a language to add or edit stop words.";
$PMF_LANG["ad_visits_per_day"] = "Visits per day";

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG["ad_instance_add"] = "Add a new phpMyFAQ multisite instance";
$PMF_LANG["ad_instance_error_notwritable"] = "The folder /multisite isn\'t writable.";
$PMF_LANG["ad_instance_url"] = "Instance URL";
$PMF_LANG["ad_instance_path"] = "Instance path";
$PMF_LANG["ad_instance_name"] = "Instance name";
$PMF_LANG["ad_instance_email"] = "Your admin email";
$PMF_LANG["ad_instance_admin"] = "Admin username";
$PMF_LANG["ad_instance_password"] = "Admin password";
$PMF_LANG["ad_instance_hint"] = "Caution: It will take some seconds to create a new phpMyFAQ instance!";
$PMF_LANG["ad_instance_button"] = "Save instance";
$PMF_LANG["ad_instance_error_cannotdelete"] = "Could not delete instance ";
$PMF_LANG["ad_instance_config"] = "Instance configuration";

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG["msgAboutThisNews"] = "About this news";

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG["msgAccessDenied"] = "Access denied.";

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG['msgSeeFAQinFrontend'] = 'See FAQ in Frontend';

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG["msgRelatedTags"] = 'Add Search Word';
$PMF_LANG["msgPopularTags"] = 'Most popular searches';
$LANG_CONF["search.enableHighlighting"] = ["checkbox", "Highlight search terms"];
$LANG_CONF["records.allowCommentsForGuests"] = ["checkbox", "Allow comments for guests"];
$LANG_CONF["records.allowQuestionsForGuests"] = ["checkbox", "Allow adding questions for guests"];
$LANG_CONF["records.allowNewFaqsForGuests"] = ["checkbox", "Allow adding new FAQs"];
$PMF_LANG["ad_searchterm_del"] = 'Delete all logged search terms';
$PMF_LANG["ad_searchterm_del_suc"] = 'Successfully deleted all search terms.';
$PMF_LANG["ad_searchterm_del_err"] = 'Could not delete all search terms.';
$LANG_CONF["records.hideEmptyCategories"] = ["checkbox", "Hide empty categories"];
$LANG_CONF["search.searchForSolutionId"] = ["checkbox", "Search for solution ID"];
$LANG_CONF["socialnetworks.disableAll"] = ["checkbox", "Disable all social networks"];
$LANG_CONF["main.enableGzipCompression"] = ["checkbox", "Enable GZIP compression"];

// added v2.9.0-alpha2 - 2014-08-16 by Thorsten
$PMF_LANG["ad_tag_delete_success"] = "The tag was deleted successfully.";
$PMF_LANG["ad_tag_delete_error"] = "The tag was not deleted because an error occurred.";
$PMF_LANG["seoCenter"] = "SEO";
$LANG_CONF["seo.metaTagsHome"] = ["select", "Meta Tags start page"];
$LANG_CONF["seo.metaTagsFaqs"] = ["select", "Meta Tags FAQs"];
$LANG_CONF["seo.metaTagsCategories"] = ["select", "Meta Tags category pages"];
$LANG_CONF["seo.metaTagsPages"] = ["select", "Meta Tags static pages"];
$LANG_CONF["seo.metaTagsAdmin"] = ["select", "Meta Tags Admin"];
$PMF_LANG["msgMatchingQuestions"] = "The following results closely matched your question";
$PMF_LANG["msgFinishSubmission"] = "If none of the suggestions above match your question, click the button below to finish submitting your question.";
$LANG_CONF['spam.manualActivation'] = ['checkbox', 'Manually activate new users (default: activated)'];

// added v2.9.0-alpha2 - 2014-10-13 by Christopher Andrews ( Chris--A )
$PMF_LANG['mailControlCenter'] = 'Mail setup';
$LANG_CONF['mail.remoteSMTP'] = ['checkbox', 'Use remote SMTP server (default: deactivated)'];
$LANG_CONF['mail.remoteSMTPServer'] = ['input', 'Server address'];
$LANG_CONF['mail.remoteSMTPUsername'] = ['input', 'Username'];
$LANG_CONF['mail.remoteSMTPPassword'] = ['password', 'Password'];
$LANG_CONF['security.enableRegistration'] = ['checkbox', 'Enable registration for visitors'];

// added v2.9.0-alpha3 - 2015-02-08 by Thorsten
$LANG_CONF['main.customPdfHeader'] = ['area', 'Custom PDF Header (HTML allowed)'];
$LANG_CONF['main.customPdfFooter'] = ['area', 'Custom PDF Footer (HTML allowed)'];
$LANG_CONF['records.allowDownloadsForGuests'] = ['checkbox', 'Allow downloads for guests'];
$PMF_LANG["ad_msgNoteAboutPasswords"] = "Attention! If you enter a password, you overwrite the password of the user.";
$PMF_LANG["ad_delete_all_votings"] = "Clear all voting";
$PMF_LANG["ad_categ_moderator"] = "Moderators";
$PMF_LANG['ad_clear_all_visits'] = "Clear all visits";
$PMF_LANG['ad_reset_visits_success'] = 'The reset of visits was successfully.';
$LANG_CONF['main.enableMarkdownEditor'] = ['checkbox', 'Enable bundled Markdown editor'];

// added v2.9.0-beta - 2015-09-27 by Thorsten
$PMF_LANG['faqOverview'] = 'FAQ Overview';
$PMF_LANG['ad_dir_missing'] = 'The directory %s is missing.';
$LANG_CONF['main.enableSmartAnswering'] = ['checkbox', 'Enable smart answering for user questions'];

// added v2.9.0-beta2 - 2015-12-23 by Thorsten
$LANG_CONF['search.enableElasticsearch'] = ['checkbox', 'Enable Elasticsearch support'];
$PMF_LANG['ad_menu_elasticsearch'] = 'Elasticsearch configuration';
$PMF_LANG['ad_es_create_index'] = 'Create Index';
$PMF_LANG['ad_es_drop_index'] = 'Drop Index';
$PMF_LANG['ad_es_bulk_index'] = 'Full import';
$PMF_LANG['ad_es_create_index_success'] = 'Index successfully created.';
$PMF_LANG['ad_es_create_import_success'] = 'Import was successful.';
$PMF_LANG['ad_es_drop_index_success'] = 'Index successfully dropped.';
$PMF_LANG['ad_export_generate_json'] = 'Create JSON file';
$PMF_LANG['ad_media_name_search'] = 'Search for media name';

// added v2.9.0-RC - 2016-02-19 by Thorsten
$PMF_LANG['ad_admin_notes'] = 'Private Notes';
$PMF_LANG['ad_admin_notes_hint'] = '%s (only visible for editors)';

// added v2.9.10 - 2018-02-17 by Thorsten
$PMF_LANG['ad_quick_entry'] = 'Add new FAQ in this category';

// added 2.10.0-alpha - 2016-08-08 by Thorsten
$LANG_CONF['ldap.ldap_mapping.name'] = ['input', 'LDAP mapping for name, "cn" when using an ADS'];
$LANG_CONF['ldap.ldap_mapping.username'] = ['input', 'LDAP mapping for username, "samAccountName" when using an ADS'];
$LANG_CONF['ldap.ldap_mapping.mail'] = ['input', 'LDAP mapping for email, "mail" when using an ADS'];
$LANG_CONF['ldap.ldap_mapping.memberOf'] = ['input', 'LDAP mapping for "member of" when using LDAP groups'];
$LANG_CONF['ldap.ldap_use_domain_prefix'] = ['checkbox', 'LDAP domain prefix, e.g. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'] = ['input', 'LDAP protocol version (default: 3)'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_REFERRALS'] = ['input', 'LDAP referrals (default: 0)'];
$LANG_CONF['ldap.ldap_use_memberOf'] = ['checkbox', 'Enable LDAP group support, e.g. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_use_sasl'] = ['checkbox', 'Enable LDAP SASL support'];
$LANG_CONF['ldap.ldap_use_multiple_servers'] = ['checkbox', 'Enable multiple LDAP servers support'];
$LANG_CONF['ldap.ldap_use_anonymous_login'] = ['checkbox', 'Enable anonymous LDAP connections'];
$LANG_CONF['ldap.ldap_use_dynamic_login'] = ['checkbox', 'Enable LDAP dynamic user binding'];
$LANG_CONF['ldap.ldap_dynamic_login_attribute'] = ['input', 'LDAP attribute for dynamic user binding, "uid" when using an ADS'];
$LANG_CONF['seo.enableXMLSitemap'] = ['checkbox', 'Enable XML sitemap'];
$PMF_LANG['ad_category_image'] = 'Category image';
$PMF_LANG["ad_user_show_home"] = "Show on startpage";

// added v.2.10.0-alpha - 2017-11-09 by Brian Potter (BrianPotter)
$PMF_LANG['ad_view_faq'] = 'View FAQ';

// added 3.0.0-alpha - 2018-01-04 by Thorsten
$LANG_CONF['main.enableCategoryRestrictions'] = ['checkbox', 'Enable category restrictions'];
$LANG_CONF['main.enableSendToFriend'] = ['checkbox', 'Enable send to friends'];
$PMF_LANG['msgUserRemovalText'] = 'You can request the deletion of your account and personal data. An email will be sent to the admin team. The team will delete your account, comments and questions. Since this is a manual process, it can take up to 24 hours. After this you will receive a deletion confirmation by email. ';
$PMF_LANG["msgUserRemoval"] = "Request user removal";
$PMF_LANG["ad_menu_RequestRemove"] = "Request user removal";
$PMF_LANG["msgContactRemove"] = "Deletion request from admin team";
$PMF_LANG["msgContactPrivacyNote"] = "Please notice our";
$PMF_LANG["msgPrivacyNote"] = "Privacy Statement";

// added 3.0.0-alpha2 - 2018-03-27 by Thorsten
$LANG_CONF['main.enableAutoUpdateHint'] = ['checkbox', 'Automatic check for new versions'];
$PMF_LANG['ad_user_is_superadmin'] = 'Super-Admin';
$PMF_LANG['ad_user_overwrite_passwd'] = 'Overwrite password';
$LANG_CONF['records.enableAutoRevisions'] = ['checkbox', 'Versioning of all FAQ changes'];
$PMF_LANG['rightsLanguage::view_faqs'] = 'View FAQs';
$PMF_LANG['rightsLanguage::view_categories'] = 'View categories';
$PMF_LANG['rightsLanguage::view_sections'] = 'View sections';
$PMF_LANG['rightsLanguage::view_news'] = 'View news';
$PMF_LANG['rightsLanguage::add_section'] = 'Add sections';
$PMF_LANG['rightsLanguage::edit_section'] = 'Edit sections';
$PMF_LANG['rightsLanguage::delete_section'] = 'Delete sections';
$PMF_LANG['rightsLanguage::administrate_sections'] = 'Administrate sections';
$PMF_LANG['rightsLanguage::administrate_groups'] = 'Administrate groups';
$PMF_LANG['ad_group_rights'] = 'Group permissions';
$PMF_LANG['ad_menu_meta'] = 'Template meta data';
$PMF_LANG['ad_meta_add'] = 'Add template meta data';
$PMF_LANG['ad_meta_page_id'] = 'Page type';
$PMF_LANG['ad_meta_type'] = 'Content type';
$PMF_LANG['ad_meta_content'] = 'Content';
$PMF_LANG['ad_meta_copy_snippet'] = 'Copy code snippet for templates';

// added v3.0.0-alpha.3 - 2018-09-20 by Timo
$PMF_LANG['ad_menu_section_administration'] = "Sections";
$PMF_LANG['ad_section_add'] = "Add Section";
$PMF_LANG['ad_section_add_link'] = "Add Section";
$PMF_LANG['ad_sections'] = 'Sections';
$PMF_LANG['ad_section_details'] = "Section Details";
$PMF_LANG['ad_section_name'] = "Name";
$PMF_LANG['ad_section_description'] = "Description";
$PMF_LANG['ad_section_membership'] = "Section Assignment";
$PMF_LANG['ad_section_members'] = "Assignments";
$PMF_LANG['ad_section_addMember'] = "+";
$PMF_LANG['ad_section_removeMember'] = "-";
$PMF_LANG['ad_section_deleteSection'] = "Delete Section";
$PMF_LANG['ad_section_deleteQuestion'] = "Are you sure you want to delete this section?";
$PMF_LANG['ad_section_error_delete'] = "The section could not be deleted. ";
$PMF_LANG['ad_section_error_noName'] = "Please enter a section name. ";
$PMF_LANG['ad_section_suc'] = "The section was <strong>successfully</strong> added.";
$PMF_LANG['ad_section_deleted'] = "The section was successfully deleted.";
$PMF_LANG['rightsLanguage::viewadminlink'] = 'View admin link';

// added v3.0.0-beta.3 - 2019-09-22 by Thorsten
$LANG_CONF['mail.remoteSMTPPort'] = ['input', 'SMTP server port'];
$LANG_CONF['mail.remoteSMTPEncryption'] = ['input', 'SMTP server encryption'];
$PMF_LANG['ad_record_faq'] = 'Question and answer';
$PMF_LANG['ad_record_permissions'] = 'Permissions';
$PMF_LANG['loginPageMessage'] = 'Log in to ';

// added v3.0.5 - 2020-10-03 by Thorsten
$PMF_LANG['ad_menu_faq_meta'] = 'FAQ metadata';

// added v3.0.8 - 2021-01-22
$LANG_CONF['main.privacyURL'] = ['input', 'URL for Privacy note'];

// added v3.1.0-alpha - 2020-03-27 by Thorsten
$PMF_LANG['ad_user_data_is_visible'] = 'Username should be visible';
$PMF_LANG['ad_user_is_visible'] = 'Visible';
$PMF_LANG['ad_categ_save_order'] = 'The new sorting was successfully saved.';
$PMF_LANG['ad_add_user_change_password'] = 'User must change password after first login';
$LANG_CONF['api.enableAccess'] = ['checkbox', 'REST API enabled'];
$LANG_CONF['api.apiClientToken'] = ['input', 'API Client Token'];
$LANG_CONF['security.domainWhiteListForRegistrations'] = ['area', 'Allowed hosts for registrations'];
$LANG_CONF['security.loginWithEmailAddress'] = ['checkbox', 'Login only with email address'];

// added v3.2.0-alpha - 2022-09-10 by Thorsten
$PMF_LANG['msgSignInWithMicrosoft'] = 'Sign in with Microsoft';
$LANG_CONF['security.enableSignInWithMicrosoft'] = ['checkbox', 'Enable Sign in with Microsoft (Azure AD)'];
$LANG_CONF['main.enableAskQuestions'] = ['checkbox', 'Enable "Ask question"'];
$LANG_CONF['main.enableNotifications'] = ['checkbox', 'Enable notifications'];
$LANG_CONF['mail.sendTestEmail'] = ['button', 'Send test email to the administrator via SMTP'];
$PMF_LANG['mail.sendTestEmail'] = 'Send test email to the administrator';
$PMF_LANG['msgGoToCategory'] = 'Go to category';
$LANG_CONF['security.enableGoogleReCaptchaV2'] = ['checkbox', 'Enable Invisible Google ReCAPTCHA v2'];
$LANG_CONF['security.googleReCaptchaV2SiteKey'] = ['input', 'Google ReCAPTCHA v2 site key'];
$LANG_CONF['security.googleReCaptchaV2SecretKey'] = ['input', 'Google ReCAPTCHA v2 secret key'];

// added v3.2.0-alpha - 2023-03-11 by Jan
$PMF_LANG['msgTwofactorEnabled'] = "Two Factor Authentication enabled";
$PMF_LANG['msgTwofactorConfig'] = "Config Two Factor Authentication";
$PMF_LANG['msgTwofactorConfigModelTitle'] = "Configuration Two Factor Authentication";
$PMF_LANG['qr_code_secret_alt'] = "QR-Code Secret-Key";
$PMF_LANG['msgTwofactorNewSecret'] = "Delete current secret?";
$PMF_LANG['msgTwofactorTokenModelTitle'] = "Two Factor Authentication - Enter token:";
$PMF_LANG['msgEnterTwofactorToken'] = "Enter 6-digits code from your authenticator app.";
$PMF_LANG['msgTwofactorCheck'] = "Check";
$PMF_LANG['msgTwofactorErrorToken'] = "You entered a wrong code!";
$PMF_LANG['ad_user_overwrite_twofactor'] = "Reset two-factor-authentication";

// added v3.2.0-alpha.2 - 2023-04-06 by Thorsten
$PMF_LANG['msgRedirect'] = 'You will be automatically redirected in 5 seconds.';
$PMF_LANG['msgCategoryMissingButTranslationAvailable'] = 'No category was found in the selected language, but you can select the following languages:';
$PMF_LANG['msgCategoryDescription'] = 'Here you will find an overview of all categories with the number of FAQs.';
$PMF_LANG['msgSubCategoryContent'] = 'Select a main category.';
$PMF_LANG['ad_open_question_deleted'] = 'The question was successfully deleted.';
$LANG_CONF['mail.remoteSMTPDisableTLSPeerVerification'] = ['checkbox', 'Disable SMTP TLS peer verification (not recommended)'];

// added v3.2.0-beta.2 - 2023-05-03 by Jan
$LANG_CONF['main.contactInformationHTML'] = ['checkbox', 'Contact information as HTML?'];

// added v3.2.0-RC - 2023-05-18 by Thorsten
$PMF_LANG['msgAuthenticationSource'] = 'Authentication service';

// added v3.2.0-RC - 2023-05-27 by Jan
$LANG_CONF['spam.mailAddressInExport'] = ['checkbox', 'Show email address in exports'];
$PMF_LANG['msgNewQuestionAdded'] = 'A new question was added. You can check them here or in the admin section:';

return $PMF_LANG;
