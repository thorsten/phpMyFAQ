<?php
/**
 * $Id: language_he.php,v 1.28 2007-04-29 14:25:30 thorstenr Exp $
 *
 * Hebrew language file
 *
 * @author      Daniel Shkuri <dan-shk@bezeqint.net>
 * @author      Niran Shay <nirshay1@012.net.il>
 * @author      Roy Ronen <royroy15@gmail.com>
 * @since       2004-08-27
 * @copyright   (c) 2004-2007 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "he";
$PMF_LANG["language"] = "hebrew";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "rtl";

$PMF_LANG["nplurals"] = "2";
/**
 * This parameter is used with the function 'plural' from inc/PMF_Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 */

// Navigation
$PMF_LANG["msgCategory"] = "קטגוריות";
$PMF_LANG["msgShowAllCategories"] = "הראה את כל הקטגוריות";
$PMF_LANG["msgSearch"] = "חפש";
$PMF_LANG["msgSearch1"] = "חיפוש";
$PMF_LANG["msgAddContent"] = "הוסף תוכן";
$PMF_LANG["msgQuestion"] = "שאל שאלה";
$PMF_LANG["msgOpenQuestions"] = "שאלות פתוחות";
$PMF_LANG["msgHelp"] = "עזרה";
$PMF_LANG["msgContact"] = "צור קשר";
$PMF_LANG["msgHome"] = "בית";
$PMF_LANG["msgNews"] = "חדשות";
$PMF_LANG["msgUserOnline"] = "משתמשים מחוברים";
$PMF_LANG["msgXMLExport"] = "קובץ XML";
$PMF_LANG["msgBack2Home"] = "חזור לעמוד הבית";

// Contentpages
$PMF_LANG["msgFullCategories"] = "קטגוריות עם ערכים";
$PMF_LANG["msgFullCategoriesIn"] = "קטגוריות עם ערכים ב ";
$PMF_LANG["msgSubCategories"] = "קטגוריות משנה";
$PMF_LANG["msgEntries"] = "ערכים";
$PMF_LANG["msgEntriesIn"] = "ערכים ב ";
$PMF_LANG["msgViews"] = "צפיות";
$PMF_LANG["msgPage"] = "עמוד";
$PMF_LANG["msgPages"] = "עמודים";
$PMF_LANG["msgPrevious"] = "הקודם";
$PMF_LANG["msgNext"] = "הבא";
$PMF_LANG["msgCategoryUp"] = "קטגוריה אחת למעלה";
$PMF_LANG["msgLastUpdateArticle"] = "עדכון אחרון:";
$PMF_LANG["msgAuthor"] = "הכותב: ";
$PMF_LANG["msgPrinterFriendly"] = "גירסה להדפסה";
$PMF_LANG["msgPrintArticle"] = "הדפס ערך זה";
$PMF_LANG["msgMakeXMLExport"] = "יצא כקובץ XML";
$PMF_LANG["msgAverageVote"] = "דירוג ממוצע: ";
$PMF_LANG["msgVoteUseability"] = "דרג ערך זה: ";
$PMF_LANG["msgVoteFrom"] = "מ-";
$PMF_LANG["msgVoteBad"] = "לא שימושי";
$PMF_LANG["msgVoteGood"] = "מאוד שימושי";
$PMF_LANG["msgVotings"] = "הצבעות";
$PMF_LANG["msgVoteSubmit"] = "הצבע";
$PMF_LANG["msgVoteThanks"] = "תודה רבה על הצבעתך!";
$PMF_LANG["msgYouCan"] = "אתה יכול: ";
$PMF_LANG["msgWriteComment"] = "להגיב על ערך זה";
$PMF_LANG["msgShowCategory"] = "לראות מידע";
$PMF_LANG["msgCommentBy"] = "המגיב: ";
$PMF_LANG["msgCommentHeader"] = "תגובה";
$PMF_LANG["msgYourComment "] = "תגובתך:";
$PMF_LANG["msgCommentThanks"] = "תודה רבה על התגובה:";
$PMF_LANG["msgSeeXMLFile"] = "פתח קובץ XML";
$PMF_LANG["msgSend2Friend"] = "שלח לחבר";
$PMF_LANG["msgS2FName"] = "שמך:";
$PMF_LANG["msgS2FEMail"] = "כתובת הדואל שלך:";
$PMF_LANG["msgS2FFriends"] = "חברייך";
$PMF_LANG["msgS2FEMails"] = "כתובות דואל:";
$PMF_LANG["msgS2FText"] = "הטקסט הבא ישלח: ";
$PMF_LANG["msgS2FText2"] = "תמצא את הערך בכתובת הבאה: ";
$PMF_LANG["msgS2FMessage"] = "הוסף הודעה לחברך:";
$PMF_LANG["msgS2FButton"] = "שלח דואל:";
$PMF_LANG["msgS2FThx"] = "תודה על המלצתך!";
$PMF_LANG["msgS2FMailSubject"] = "המלצה מ";

// Search
$PMF_LANG["msgSearchWord"] = "מילה לחיפוש";
$PMF_LANG["msgSearchFind"] = "חפש";
$PMF_LANG["msgSearchAmount"] = "תוצאת חיפוש";
$PMF_LANG["msgSearchAmounts"] = "תוצאות חיפוש";
$PMF_LANG["msgSearchCategory"] = "קטגוריה: ";
$PMF_LANG["msgSearchContent"] = "תוכן: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "הצע לנו ערך: ";
$PMF_LANG["msgNewContentAddon"] = "הצעתך תתפרסם לאחר אישור עורך האתר. שדות נחוצים <strong>שמך</strong>, <strong>כתובת אמייל</strong>, <strong>קטגוריה</strong>, <strong>כותרת</strong> ו <strong>תוכן</strong>. הפרד בין המילים בעזרת מקש spacebar בלבד.";
$PMF_LANG["msgNewContentName"] = "שמך:";
$PMF_LANG["msgNewContentMail"] = "כתובת הדואל שלך:";
$PMF_LANG["msgNewContentCategory"] = "קטגוריה:";
$PMF_LANG["msgNewContentTheme"] = "כותרת:";
$PMF_LANG["msgNewContentArticle"] = "תוכן:";
$PMF_LANG["msgNewContentKeywords"] = "מילות מפתח:";
$PMF_LANG["msgNewContentLink"] = "קישור נילווה:";
$PMF_LANG["msgNewContentSubmit"] = "שלח";
$PMF_LANG["msgInfo"] = "נוסף מידע:";
$PMF_LANG["msgNewContentThanks"] = "תודה על הצעתך!";
$PMF_LANG["msgNoQuestionsAvailable"] = "אין כרגע שאלות פתוחות.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "שאל את שאלתך:";
$PMF_LANG["msgAskCategory"] = "השאלה בקשר לקטגוריה:";
$PMF_LANG["msgAskYourQuestion"] = "שאלתך:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>שאלתך התקבלה, תודה!</h2>";
$PMF_LANG["msgDate_User"] = "תאריך / משתמש";
$PMF_LANG["msgQuestion2"] = "שאלה";
$PMF_LANG["msg2answer"] = "תשובה";
$PMF_LANG["msgQuestionText"] = "כאן תוכל לראות שאלות שנשאלו על ידי משתמשים אחרים. אם הינך יודע את התשובה לשאלה, אנא שלח אותה והשאלה והתשובה יתווספו כערך לאתר.";

// Help
$PMF_LANG["msgHelpText"] = "<p>מבנה השאלה הנפוצה הינו די פשוט אתה יכול לחפש את ה<strong><a href=\"?action=show\">קטגוריה</a></strong>או לאפשר ל<strong><a href=\"?action=search\">מנוע חיפוש השאלות הנפוצות</a></strong> לחפש מילת מפתח.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "שלח דואל לעורך האתר:";
$PMF_LANG["msgMessage"] = "הודעתך:";

// Startseite
$PMF_LANG["msgNews"] = "חדשות ";
$PMF_LANG["msgTopTen"] = "הטיפים הנצפים ביותר";
$PMF_LANG["msgHomeThereAre"] = "ישנם ";
$PMF_LANG["msgHomeArticlesOnline"] = " טיפים במערכת";
$PMF_LANG["msgNoNews"] = "אין חדשות.";
$PMF_LANG["msgLatestArticles"] = "חמשת הטיפים האחרונים";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "תודה רבה על שאלתך.";
$PMF_LANG["msgMailCheck"] = "יש שאלה נפוצה חדשה! אנא בדוק את אזור הניהול!";
$PMF_LANG["msgMailContact"] = "שאלתך נשלחה לעורך האתר";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "אין בסיס נתונים זמין.";
$PMF_LANG["err_noHeaders"] = "לא נמצאו קטגוריות.";
$PMF_LANG["err_noArticles"] = "<p>לא נמצאו ערכים.</p>";
$PMF_LANG["err_badID"] = "<p>מספר שגוי.</p>";
$PMF_LANG["err_noTopTen"] = "<p>אין עשרה גדולים עדיין.</p>";
$PMF_LANG["err_nothingFound"] = "<p>לא נמצאו ערכים.</p>";
$PMF_LANG["err_SaveEntries"] = "שדות נחוצים הם <strong>שמך</strong>, <strong>הדואל שלך</strong>, <strong>קטגוריה</strong>, <strong>כותרת</strong> ו<strong>תוכן</strong>!<br /><br /><a href=\"javascript:history.back();\">הקודם</a><br /><br />";
$PMF_LANG["err_SaveComment"] = "השדות הנחוצים הם <strong>שמך</strong>, <strong>כתובת הדואל שלך</strong> ו<strong>תגובתך</strong>!<br /><br /><a href=\"javascript:history.back();\">הקודם</a><br /><br />";
$PMF_LANG["err_VoteTooMuch"] = "<p>איננו סופרים הצבעות כפולות. <a href=\"javascript:history.back();\">לחץ כאן</a>, כדי לחזור לעמוד הקודם.</p>";
$PMF_LANG["err_noVote"] = "<p><strong>לא דרגת את השאלה!</strong> <a href=\"javascript:history.back();\">אנא לחץ כאן</a>, להצבעה.</p>";
$PMF_LANG["err_noMailAdress"] = "כתובת האימייל אינה נכונה.<br /><a href=\"javascript:history.back();\">חזור</a>";
$PMF_LANG["err_sendMail"] = "שדות נחוצים <strong>שם פרטי</strong>, <strong>כתובת אמייל</strong>ו<strong>שאלתך</strong>!";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>חפש ערכים:</strong><br />עם ערך כמו <strong style=\"color: Red;\">מילה 1 מילה 2</strong> הנך יכול לבצע חיפוש לשניים או יותר קריטריונים.</p><p><strong>הערה:</strong> קריטריוני החיפוש חייבים להכיל 4 סימנים או יותר.</p>";

// Men
$PMF_LANG["ad"] = "אזור הניהול";
$PMF_LANG["ad_menu_user_administration"] = "ניהול משתמשים";
$PMF_LANG["ad_menu_entry_aprove"] = "אשר ערך";
$PMF_LANG["ad_menu_entry_edit"] = "ערוך ערך";
$PMF_LANG["ad_menu_categ_add"] = "הוסף קטגוריה";
$PMF_LANG["ad_menu_categ_edit"] = "ערוך קטגוריה";
$PMF_LANG["ad_menu_news_add"] = "הוסף חדשות";
$PMF_LANG["ad_menu_news_edit"] = "ערוך חדשות";
$PMF_LANG["ad_menu_open"] = "ערוך שאלה פתוחה";
$PMF_LANG["ad_menu_stat"] = "סטטיסטיקה";
$PMF_LANG["ad_menu_cookie"] = "עוגיות";
$PMF_LANG["ad_menu_session"] = "ראה ביקורים (session)";
$PMF_LANG["ad_menu_adminlog"] = "ראה רישום מנהל";
$PMF_LANG["ad_menu_passwd"] = "שנה סיסמה";
$PMF_LANG["ad_menu_logout"] = "התנתק";
$PMF_LANG["ad_menu_startpage"] = "עמוד הבית";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "אנא זהה את עצמך.";
$PMF_LANG["ad_msg_passmatch"] = "הסיסמאות שהזנת <strong>אינן זהות</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "הפרופיל של";
$PMF_LANG["ad_msg_savedsuc_2"] = "נשמר בהצלחה.";
$PMF_LANG["ad_msg_mysqlerr"] = "בעקבות <strong>תקלה בבסיס הנתונים</strong>, הפרופיל לא יכול להשמר.";
$PMF_LANG["ad_msg_noauth"] = "אינך מורשה.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "עמוד";
$PMF_LANG["ad_gen_of"] = "של";
$PMF_LANG["ad_gen_lastpage"] = "הקודם";
$PMF_LANG["ad_gen_nextpage"] = "הבא";
$PMF_LANG["ad_gen_save"] = "שמור";
$PMF_LANG["ad_gen_reset"] = "אפס";
$PMF_LANG["ad_gen_yes"] = "כן";
$PMF_LANG["ad_gen_no"] = "לא";
$PMF_LANG["ad_gen_top"] = "ראש העמוד";
$PMF_LANG["ad_gen_ncf"] = "לא נמצאו קטגוריות!";
$PMF_LANG["ad_gen_delete"] = "מחק";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "ניהול משתמשים";
$PMF_LANG["ad_user_username"] = "משתמשים רשומים";
$PMF_LANG["ad_user_rights"] = "הרשאות משתמשים";
$PMF_LANG["ad_user_edit"] = "ערוך";
$PMF_LANG["ad_user_delete"] = "מחק";
$PMF_LANG["ad_user_add"] = "הוסף משתמש";
$PMF_LANG["ad_user_profou"] = "פרופיל המשתמש";
$PMF_LANG["ad_user_name"] = "שם";
$PMF_LANG["ad_user_password"] = "סיסמה";
$PMF_LANG["ad_user_confirm"] = "ווידוא סיסמה";
$PMF_LANG["ad_user_rights"] = "הרשאות";
$PMF_LANG["ad_user_del_1"] = "המשתמש";
$PMF_LANG["ad_user_del_2"] = "צריך להמחק?";
$PMF_LANG["ad_user_del_3"] = "האם אתה בטוח?";
$PMF_LANG["ad_user_deleted"] = "המשתמש נמחק בהצלחה.";
$PMF_LANG["ad_user_checkall"] = "בחר הכל";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "ניהול ערכים";
$PMF_LANG["ad_entry_id"] = "מספר הערך";
$PMF_LANG["ad_entry_topic"] = "כותרת";
$PMF_LANG["ad_entry_action"] = "פעולה";
$PMF_LANG["ad_entry_edit_1"] = "ערוך ערך";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "כותרת:";
$PMF_LANG["ad_entry_content"] = "תוכן:";
$PMF_LANG["ad_entry_keywords"] = "מילות מפתח:";
$PMF_LANG["ad_entry_author"] = "מחבר:";
$PMF_LANG["ad_entry_category"] = "קטגוריה: ";
$PMF_LANG["ad_entry_active"] = "פעיל?";
$PMF_LANG["ad_entry_date"] = "תאריך:";
$PMF_LANG["ad_entry_changed"] = "מה שונה?";
$PMF_LANG["ad_entry_changelog"] = "היסטוריית שינויים:";
$PMF_LANG["ad_entry_commentby"] = "תגובה מ";
$PMF_LANG["ad_entry_comment"] = "תגובות:";
$PMF_LANG["ad_entry_save"] = "שמור";
$PMF_LANG["ad_entry_delete"] = "מחק";
$PMF_LANG["ad_entry_delcom_1"] = "האם אתה בטוח שהתגובה של המשתמש ";
$PMF_LANG["ad_entry_delcom_2"] = "צריכה להמחק?";
$PMF_LANG["ad_entry_commentdelsuc"] = "התגובה נמחקה <strong>בהצלחה</strong>.";
$PMF_LANG["ad_entry_back"] = "חזור לערך";
$PMF_LANG["ad_entry_commentdelfail"] = "התגובה <strong>לא</strong> נמחקה.";
$PMF_LANG["ad_entry_savedsuc"] = "השינויים נשמרו <strong>בהצלחה</strong>.";
$PMF_LANG["ad_entry_savedfail 	"] = "לרוע המזל, <strong>התרחשה תקלה בבסיס הנתונים</strong>.";
$PMF_LANG["ad_entry_del_1"] = "האם אתה בטוח שהערך";
$PMF_LANG["ad_entry_del_2"] = "של";
$PMF_LANG["ad_entry_del_3"] = "צריך להמחק?";
$PMF_LANG["ad_entry_delsuc"] = "הערך נמחק <strong>בהצלחה</strong>.";
$PMF_LANG["ad_entry_delfail"] = "הערך <strong>לא נמחק</strong>!";
$PMF_LANG["ad_entry_back"] = "חזור";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "כותרת החדשה";
$PMF_LANG["ad_news_text"] = "תוכן";
$PMF_LANG["ad_news_link_url"] = "קישור: (<strong>בלי //:http</strong>)!";
$PMF_LANG["ad_news_link_title"] = "כותרת הקישור:";
$PMF_LANG["ad_news_link_target"] = "יעד";
$PMF_LANG["ad_news_link_window"] = "פתח יעד בחלון חדש";
$PMF_LANG["ad_news_link_faq"] = "פתח בחלון הנוכחי";
$PMF_LANG["ad_news_add"] = "הוסף חדשות";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "כותרת";
$PMF_LANG["ad_news_date"] = "תאריך";
$PMF_LANG["ad_news_action"] = "פעולה";
$PMF_LANG["ad_news_update"] = "עדכן";
$PMF_LANG["ad_news_delete"] = "מחק";
$PMF_LANG["ad_news_nodata"] = "לא נמצא מידע";
$PMF_LANG["ad_news_updatesuc"] = "החדשות עודכנו.";
$PMF_LANG["ad_news_del"] = "האם אתה בטוח שברצונך למחוק את החדשה הנל?";
$PMF_LANG["ad_news_yesdelete"] = "כן, מחק!";
$PMF_LANG["ad_news_nodelete"] = "לא!";
$PMF_LANG["ad_news_delsuc"] = "החדשה נמחקה.";
$PMF_LANG["ad_news_updatenews"] = "עדכן חדשות";


// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "הוסף קטגוריה";
$PMF_LANG["ad_categ_catnum"] = "מספר קטגוריה:";
$PMF_LANG["ad_categ_subcatnum"] = "מספר קטגורית משנה:";
$PMF_LANG["ad_categ_nya"] = "<em>לא זמין עדיין!</em>";
$PMF_LANG["ad_categ_titel"] = "כותרת הקטגוריה:";
$PMF_LANG["ad_categ_add"] = "הוסף קטגוריה";
$PMF_LANG["ad_categ_existing"] = "קטגוריות קיימות";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "קטגוריה";
$PMF_LANG["ad_categ_subcateg"] = "קטגורית משנה";
$PMF_LANG["ad_categ_titel"] = "כותרת קטגוריה";
$PMF_LANG["ad_categ_action"] = "פעולה";
$PMF_LANG["ad_categ_update"] = "עדכון";
$PMF_LANG["ad_categ_delete"] = "מחיקה";
$PMF_LANG["ad_categ_updatecateg"] = "עדכן קטגוריה";
$PMF_LANG["ad_categ_nodata"] = "לא נמצא מידע במסד הנתונים";
$PMF_LANG["ad_categ_remark"] = "שים לב ! ערכים שאינם משויכים לקטגוריה לא ניתנים לצפייה.";
$PMF_LANG["ad_categ_edit_1"] = "ערוך";
$PMF_LANG["ad_categ_edit_2"] = "קטגוריה";
$PMF_LANG["ad_categ_add"] = "הוסף קטגוריה";
$PMF_LANG["ad_categ_added"] = "הקטגוריה נוספה.";
$PMF_LANG["ad_categ_updated"] = "הקטגוריה עודכנה.";
$PMF_LANG["ad_categ_del_yes"] = "כן, מחק!";
$PMF_LANG["ad_categ_del_no"] = "לא!";
$PMF_LANG["ad_categ_deletesure"] = "האם להמשיך במחיקה?";
$PMF_LANG["ad_categ_deleted"] = "הקטגוריה נמחקה.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc 	"] = "העוגיה נוצרה <strong>בהצלחה</strong>.";
$PMF_LANG["ad_cookie_already"] = "העוגיה נוצרה כבר, כעת יש לך את האפשרויות הבאות: ";
$PMF_LANG["ad_cookie_again"] = "ליצור שוב עוגיה";
$PMF_LANG["ad_cookie_delete"] = "למחוק את העוגיה";
$PMF_LANG["ad_cookie_no"] = "עוגיה לא נשמרה עדיין. עוגיה מאפשרת לך להכנס למערכת ללא צורך בהקלדת שם משתמש וסיסמה. האפשרויות שלך הן:";
$PMF_LANG["ad_cookie_set"] = "צור עוגייה";
$PMF_LANG["ad_cookie_deleted"] = "העוגייה נמחקה בהצלחה.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "רישום פעילות מנהל";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "שנה את סיסמתך";
$PMF_LANG["ad_passwd_old"] = "סיסמה ישנה:";
$PMF_LANG["ad_passwd_new"] = "סיסמה חדשה:";
$PMF_LANG["ad_passwd_con"] = "ווידוא סיסמה חדשה:";
$PMF_LANG["ad_passwd_change"] = "שנה סיסמה";
$PMF_LANG["ad_passwd_suc"] = "הסיסמה שונתה בהצלחה.";
$PMF_LANG["ad_passwd_remark"] = "<strong>לתשומת לבך:</strong><br />עוגיה צריכה להווצר שוב!";
$PMF_LANG["ad_passwd_fail"] = "הסיסמה הישנה <strong>חייבת</strong> להיות נכונה ושתי החדשות חייבות להיות <strong>זהות</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "הוסף משתמש";
$PMF_LANG["ad_adus_name"] = "שם:";
$PMF_LANG["ad_adus_password"] = "סיסמה:";
$PMF_LANG["ad_adus_add"] = "הוסף משתמש";
$PMF_LANG["ad_adus_suc"] = "המשתמש <strong>נוסף</strong> בהצלחה.";
$PMF_LANG["ad_adus_edit"] = "ערוך פרופיל";
$PMF_LANG["ad_adus_dberr"] = "<strong>שגיאה במסד הנתונים!</strong>";
$PMF_LANG["ad_adus_exerr"] = "שם המשתמש <strong>קיים</strong> כבר.";

// Sessions
$PMF_LANG["ad_sess_id"] = "מספר";
$PMF_LANG["ad_sess_sid"] = "מספר סשן";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "משך הסשן";
$PMF_LANG["ad_sess_pageviews"] = "צפיות בעמודים";
$PMF_LANG["ad_sess_search"] = "חפש";
$PMF_LANG["ad_sess_sfs"] = "חפש סשן";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "דקות הפעולה:";
$PMF_LANG["ad_sess_s_date"] = "תאריך";
$PMF_LANG["ad_sess_s_after"] = "אחרי";
$PMF_LANG["ad_sess_s_before"] = "לפני";
$PMF_LANG["ad_sess_s_search"] = "חפש";
$PMF_LANG["ad_sess_session"] = "סשן";
$PMF_LANG["ad_sess_r"] = "תוצאות חיפוש ל";
$PMF_LANG["ad_sess_referer"] = "מתיחס ל:";
$PMF_LANG["ad_sess_browser"] = "דפדפן:";
$PMF_LANG["ad_sess_ai_rubrik"] = "קטגוריה:";
$PMF_LANG["ad_sess_ai_artikel"] = "ערך:";
$PMF_LANG["ad_sess_ai_sb"] = "מחרוזת חיפוש:";
$PMF_LANG["ad_sess_ai_sid"] = "סשן מספר:";
$PMF_LANG["ad_sess_back"] = "חזור";

// Statistik
$PMF_LANG["ad_rs"] = "סטטיסטיקת דירוג ערכים";
$PMF_LANG["ad_rs_rating_1"] = "הדירוג של";
$PMF_LANG["ad_rs_rating_2"] = "הצגות משתמשים:";
$PMF_LANG["ad_rs_red"] = "אדום";
$PMF_LANG["ad_rs_green"] = "ירוק";
$PMF_LANG["ad_rs_altt"] = "עם ממוצע נמוך מ2";
$PMF_LANG["ad_rs_ahtf"] = "עם ממוצע גבוהה מ4";
$PMF_LANG["ad_rs_no"] = "אין דרוג זמין";

// Auth
$PMF_LANG["ad_auth_insert"] = "הזן את שם המשתמש והסיסמה שלך.";
$PMF_LANG["ad_auth_user"] = "שם משתמש:";
$PMF_LANG["ad_auth_passwd"] = "סיסמה:";
$PMF_LANG["ad_auth_ok"] = "אישור";
$PMF_LANG["ad_auth_reset"] = "אפס";
$PMF_LANG["ad_auth_fail"] = "שם המשתמש ו/או הסיסמה לא תקינים.";
$PMF_LANG["ad_auth_sess"] = "הסיסמה נשלחה.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "ערוך הגדרות";
$PMF_LANG["ad_config_save"] = "שמור הגדרות";
$PMF_LANG["ad_config_reset"] = "אפס";
$PMF_LANG["ad_config_saved"] = "ההגדרות נשמרו בהצלחה.";
$PMF_LANG["ad_config_file"] = "config_english.dat";
$PMF_LANG["ad_menu_editconfig"] = "עריכת הגדרות";
$PMF_LANG["ad_att_none"] = "אין קבצים מצורפים זמינים";
$PMF_LANG["ad_att_att"] = "קבצים מצורפים:";
$PMF_LANG["ad_att_add"] = "צרף קובץ";
$PMF_LANG["ad_entryins_suc"] = "הערך נשמר בהצלחה.";
$PMF_LANG["ad_entryins_fail"] = "התרחשה שגיאה.";
$PMF_LANG["ad_att_del"] = "מחק";
$PMF_LANG["ad_att_nope"] = "קבצים מצורפים יכולים להשמר רק בזמן העריכה.";
$PMF_LANG["ad_att_delsuc"] = "הקובץ המצורף נמחק בהצלחה.";
$PMF_LANG["ad_att_delfail"] = "התרחשה שגיאה במחיקת הקובץ.";
$PMF_LANG["ad_entry_add"] = "צור ערך";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "גיבוי זה תמונה מלאה של כל המידע שבבסיס נתונים. גיבוי צריך להעשות לפחות אחת לחודש. פורמט הגיבוי הוא mysql transaction file, שאפשר ליצא על-ידי phpMyAdmin או על ידי שורת הפקודה של משתמש הmysql.";
$PMF_LANG["ad_csv_link"] = "הורד את הגיבוי";
$PMF_LANG["ad_csv_head"] = "צור גיבוי";
$PMF_LANG["ad_att_addto"] = "הוסף קובץ מצורף לנושא";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "קובץ:";
$PMF_LANG["ad_att_butt"] = "אישור";
$PMF_LANG["ad_att_suc"] = "הקובץ נוסף בהצלחה.";
$PMF_LANG["ad_att_fail"] = "התרחשה תקלה בצרוף הקובץ.";
$PMF_LANG["ad_att_close"] = "סגור חלון זה";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "עם טופס זה אתה יכול לשחזר את תוכן בסיס הנתונים, תוך שימוש בגיבוי שנעשה עם phpmyfaq. אנא שים לב שהמידע הקיים עלול להמחק.";
$PMF_LANG["ad_csv_file"] = "קובץ";
$PMF_LANG["ad_csv_ok"] = "אישור";
$PMF_LANG["ad_csv_linklog"] = "גבה היסטוריה";
$PMF_LANG["ad_csv_linkdat"] = "גבה נתונים";
$PMF_LANG["ad_csv_head2"] = "שחזר";
$PMF_LANG["ad_csv_no"] = "זה לא גיבוי של המערכת.";
$PMF_LANG["ad_csv_prepare"] = "מכין את הבסיסים הנחוצים...";
$PMF_LANG["ad_csv_process"] = "מבצע...";
$PMF_LANG["ad_csv_of"] = "של";
$PMF_LANG["ad_csv_suc"] = "הצליח.";
$PMF_LANG["ad_csv_backup"] = "גיבוי";
$PMF_LANG["ad_csv_rest"] = "שחזר גיבוי";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "גיבוי";
$PMF_LANG["ad_logout"] = "יצאת מהמערכת.";
$PMF_LANG["ad_news_add"] = "הוסף חדשות";
$PMF_LANG["ad_news_edit"] = "ערוך חדשות";
$PMF_LANG["ad_cookie"] = "עוגיות";
$PMF_LANG["ad_sess_head"] = "ראה סשן.";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "ניהול קטגוריות";
$PMF_LANG["ad_menu_stat"] = "סטטיסטיקת דירוגים";
$PMF_LANG["ad_kateg_add"] = "הוסף קטגוריה";
$PMF_LANG["ad_kateg_rename"] = "שנה שם";
$PMF_LANG["ad_adminlog_date"] = "תאריך";
$PMF_LANG["ad_adminlog_user"] = "משתמש";
$PMF_LANG["ad_adminlog_ip"] = "כתובת IP";

$PMF_LANG["ad_stat_sess"] = "Sessions";
$PMF_LANG["ad_stat_days"] = "ימים";
$PMF_LANG["ad_stat_vis"] = "Sessions (ביקורים)";
$PMF_LANG["ad_stat_vpd"] = "ביקורים בכל יום";
$PMF_LANG["ad_stat_fien"] = "ביקור ראשון";
$PMF_LANG["ad_stat_laen"] = "ביקור אחרון";
$PMF_LANG["ad_stat_browse"] = "עיון Sessions";
$PMF_LANG["ad_stat_ok"] = "אישור";

$PMF_LANG["ad_sess_time"] = "זמן";
$PMF_LANG["ad_sess_sid"] = "Session-ID";
$PMF_LANG["ad_sess_ip"] = "כתובת IP";

$PMF_LANG["ad_ques_take"] = "בחר שאלה וערוך";
$PMF_LANG["no_cats"] = "לא נמצאו קטגוריות.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "שם משתמש או סיסמה לא נכונים.";
$PMF_LANG["ad_log_sess"] = "הסשן פקע.";
$PMF_LANG["ad_log_edit"] = "טופס עריכת משתמשים למשתמש הבא: ";
$PMF_LANG["ad_log_crea"] = "טופס ערך חדש.";
$PMF_LANG["ad_log_crsa"] = "צור ערך חדש.";
$PMF_LANG["ad_log_ussa"] = "עדכן את המידע למשתמשים הבאים: ";
$PMF_LANG["ad_log_usde"] = "מחק את המשתמשים הבאים: ";
$PMF_LANG["ad_log_beed"] = "טופס עריכה למשתמשים הבאים: ";
$PMF_LANG["ad_log_bede"] = "מחק את הערכים הבאים: ";

$PMF_LANG["ad_start_visits"] = "ביקורים";
$PMF_LANG["ad_start_articles"] = "ערכים";
$PMF_LANG["ad_start_comments"] = "תגובות";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "הדבק";
$PMF_LANG["ad_categ_cut"] = "חתוך";
$PMF_LANG["ad_categ_copy"] = "העתק";
$PMF_LANG["ad_categ_process"] = "מעבד קטגוריות...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>אינך מורשה.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "עמוד קודם";
$PMF_LANG["msgNextPage"] = "עמוד הבא";
$PMF_LANG["msgPageDoublePoint"] = "עמוד: ";
$PMF_LANG["msgMainCategory"] = "קטגוריה ראשית";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "סיסמתך שונתה.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "הראה זאת כקובץ PDF";
$PMF_LANG["ad_xml_head"] = "XML-גיבוי";
$PMF_LANG["ad_xml_hint"] = "שמור היסטוריה של שאלות נפוצות כקובץ XML.";
$PMF_LANG["ad_xml_gen"] = "צור קובץ XML";
$PMF_LANG["ad_entry_locale"] = "שפה";
$PMF_LANG["msgLangaugeSubmit"] = "שנה שפה";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "תצוגה מקדימה";
$PMF_LANG["ad_attach_1"] = "אתה צריך לבחור תיקייה לקבצים המצורפים באזור ההגדרות.";
$PMF_LANG["ad_attach_2"] = "אתה צריך לבחור קישור לתיקיית הקבצים המצורפים באזור ההגדרות.";
$PMF_LANG["ad_attach_3"] = "הקובץ attachment.php בלי בדיקת אוטנטיות.";
$PMF_LANG["ad_attach_4"] = "הקובץ המצורף חייב להיות קטן מ %s Bytes.";
$PMF_LANG["ad_menu_export"] = "יצא את הערכים שלך";
$PMF_LANG["ad_export_1"] = "בנה RSS-Feed";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "שגיאה: לא ניתן לייצור את הקובץ.";
$PMF_LANG["ad_export_news"] = "חדשות RSS-Feed";
$PMF_LANG["ad_export_topten"] = "מצעד ה RSS-Feed";
$PMF_LANG["ad_export_latest"] = "5 הערכים האחרונים RSS-Feed";
$PMF_LANG["ad_export_pdf"] = "יצא ב PDF את כל הערכים";
$PMF_LANG["ad_export_generate"] = "בנה RSS-Feed";

$PMF_LANG["rightsLanguage"]['adduser'] = "הוסף משתמש";
$PMF_LANG["rightsLanguage"]['edituser'] = "ערוך משתמש";
$PMF_LANG["rightsLanguage"]['deluser'] = "מחק משתמש";
$PMF_LANG["rightsLanguage"]['addbt'] = "הוסף ערך";
$PMF_LANG["rightsLanguage"]['editbt'] = "ערוך ערך";
$PMF_LANG["rightsLanguage"]['delbt'] = "מחק ערך";
$PMF_LANG["rightsLanguage"]['viewlog'] = "ראה רישום";
$PMF_LANG["rightsLanguage"]['adminlog'] = "ראה רישום פעילות מנהל";
$PMF_LANG["rightsLanguage"]['delcomment'] = "מחק הערה";
$PMF_LANG["rightsLanguage"]['addnews'] = "הוסף חדשות";
$PMF_LANG["rightsLanguage"]['editnews'] = "ערוך חדשות";
$PMF_LANG["rightsLanguage"]['delnews'] = "מחק חדשות";
$PMF_LANG["rightsLanguage"]['addcateg'] = "הוסף קטגוריה";
$PMF_LANG["rightsLanguage"]['editcateg'] = "ערוך קטגוריה";
$PMF_LANG["rightsLanguage"]['delcateg'] = "מחק קטגוריה";
$PMF_LANG["rightsLanguage"]['passwd'] = "שנה סיסמא";
$PMF_LANG["rightsLanguage"]['editconfig'] = "ערוך הגדרות";
$PMF_LANG["rightsLanguage"]['addatt'] = "הוסף קובץ";
$PMF_LANG["rightsLanguage"]['delatt'] = "מחק קובץ";
$PMF_LANG["rightsLanguage"]['backup'] = "צור גיבוי";
$PMF_LANG["rightsLanguage"]['restore'] = "שחזר גיבוי";
$PMF_LANG["rightsLanguage"]['delquestion'] = "מחק שאלות פתוחות";

$PMF_LANG["msgAttachedFiles"] = "קבצים מצורפים:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "פעולה";
$PMF_LANG["ad_entry_email"] = "כתובת דואל:";
$PMF_LANG["ad_entry_allowComments"] = "אפשר תגובות";
$PMF_LANG["msgWriteNoComment"] = "אינך יכול להוסיף הערות לערך זה";
$PMF_LANG["ad_user_realname"] = "שם אמיתי:";
$PMF_LANG["ad_export_generate_pdf"] = "צור קובץ PDF";
$PMF_LANG["ad_export_full_faq"] = "שאלותייך בפורמט PDF";
$PMF_LANG["err_bannedIP"] = "כתובת הIP שלך נחסמה.";
$PMF_LANG["err_SaveQuestion"] = "שדות נחוצים <strong> שמך</strong>, <strong>כתובת אמייל</strong> ו<strong>השאלה שלך</strong>.<br /><br /><a href=\"javascript:history.back();\">הקודם</a><br /><br />";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "צבע פונט: ";
$PMF_LANG["ad_entry_fontsize"] = "גודל פונט: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => "checkbox", 1 => "קובץ שפה");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "אפשר העברת נתונים אוטומטית");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "כותרת הערך");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "גירסת הערך");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "תיאור האתר");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "מילות מפתח למנועי חיפוש");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "שם היוצר");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "כתובת הדואל של עורך האתר");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "מידע ליצירת קשר");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "טקסט לעמוד שלח לחבר");
$LANG_CONF['records.maxAttachmentSize'] = array(0 => "input", 1 => "גודל מקסימלי של קובץ מצורף ב- Bytes (max. %sByte)");
$LANG_CONF["records.disableAttachments"] = array(0 => "checkbox", 1 => "קשר לקובץ המצורף בתחתית הערך?");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "אפשר רישום של פעילות המשתמשים?");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "אפשר רישום של פעילות המנהל ?");
$LANG_CONF["security.ipCheck"] = array(0 => "checkbox", 1 => "האם הנך רוצה שהIP יבדק כאשר נבדקים פרטי המשתמשים בadmin.php?");
$LANG_CONF["records.numberOfRecordsPerPage"] = array(0 => "input", 1 => "מספר ערכים מוצגים בכל עמוד");
$LANG_CONF["records.numberOfShownNewsEntries"] = array(0 => "input", 1 => "מספר קטעי חדשות");
$LANG_CONF['security.bannedIPs'] = array(0 => "area", 1 => "חסום את IP זה");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "הפעל mod_rewrite? (ברירת מחדל: לא פעיל)");
$LANG_CONF["security.ldapSupport"] = array(0 => "checkbox", 1 => "הפעל LDAP? (ברירת מחדל: לא פעיל)");
$LANG_CONF["main.referenceURL"] = array(0 => "input", 1 => "URL בסיסי לאימות קישורים (דוגמה: http://www.example.org/faq)");
$LANG_CONF["main.urlValidateInterval"] = array(0 => "input", 1 => "מרווח בין אימות קישורים (בשניות)");
$LANG_CONF["records.enableVisibilityQuestions"] = array(0 => "checkbox", 1 => "לא להציג באתר שאלות חדשות ?");
$LANG_CONF['security.permLevel'] = array(0 => "select", 1 => "רמת הרשאות");

$PMF_LANG["ad_categ_new_main_cat"] = "כקטגוריה ראשית חדשה";
$PMF_LANG["ad_categ_paste_error"] = "הזזת הקטגוריה בלתי אפשרית.";
$PMF_LANG["ad_categ_move"] = "הזז קטגוריה";
$PMF_LANG["ad_categ_lang"] = "שפה";
$PMF_LANG["ad_categ_desc"] = "תיאור";
$PMF_LANG["ad_categ_change"] = "הוחלפה עם";

$PMF_LANG["lostPassword"] = "שכחת את סיסמתך? הקש כאן.";
$PMF_LANG["lostpwd_err_1"] = "שגיאה: שם המשתמש והדואל לא נמצאו.";
$PMF_LANG["lostpwd_err_2"] = "שגיאה: ערכים שגויים";
$PMF_LANG["lostpwd_text_1"] = "תודה על בקשת מידע המשתמש שלך.";
$PMF_LANG["lostpwd_text_2"] = "אנא ערוך סיסמה אישית חדשה באזור הניהול.";
$PMF_LANG["lostpwd_mail_okay"] = "הדואל נשלח.";

$PMF_LANG["ad_xmlrpc_button"] = "קבל את הגירסה האחרונה של phpMyFAQ על ידי האינטרנט";
$PMF_LANG["ad_xmlrpc_latest"] = "הגירסה החדשה ניתנת להורדה מ";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'בחר שפה לקטגוריה';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'מפת האתר';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'ערך זה נמצא בעריכה ולא ניתן לצפייה.';
$PMF_LANG['msgArticleCategories'] = 'קטגוריות עבור ערך זה';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'מספר ייחודי לערך';
$PMF_LANG['ad_entry_faq_record'] = 'ערך';
$PMF_LANG['ad_entry_new_revision'] = 'ליצור גירסה חדשה?';
$PMF_LANG['ad_entry_record_administration'] = 'ניהול ערכים';
$PMF_LANG['ad_entry_changelog'] = 'מעקב שינויים';
$PMF_LANG['ad_entry_revision'] = 'גירסה';
$PMF_LANG['ad_changerev'] = 'בחר גירסה';
$PMF_LANG['msgCaptcha'] = "אנא הקלד את הסימנים";
$PMF_LANG['msgSelectCategories'] = 'חפש ב ...';
$PMF_LANG['msgAllCategories'] = '... כל הקטגוריות';
$PMF_LANG['ad_you_should_update'] = 'Your phpMyFAQ installation is outdated. You should update to the latest available version.';
$PMF_LANG['msgAdvancedSearch'] = 'חיפוש מתקדם';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'בקרת ספאם';
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "הדפס כתובות אימייל בצורה בטוחה (ברירת מחדל: פעיל).");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "בדוק מילים חסומות בטפסים ציבוריים (ברירת מחדל: פעיל).");
$LANG_CONF["spam.enableCaptchaCode"] = array(0 => "checkbox", 1 => "השתמש בסימנים גרפיים לאישור שליחת טפסים ציבוריים (ברירת מחדל: פעיל).");
$PMF_LANG['ad_session_expiring'] = 'הסשן שלך יפקע בעוד %d דקות: האם אתה רוצה להמשיך לעבוד?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'ניהול סשנים';
$PMF_LANG['ad_stat_choose'] = 'בחר חודש';
$PMF_LANG['ad_stat_delete'] = 'מחק מייד את הסשנים שנבחרו';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = 'מונחים';
$PMF_LANG['ad_glossary_add'] = 'הוסף מונח';
$PMF_LANG['ad_glossary_edit'] = 'ערוך מונח';
$PMF_LANG['ad_glossary_item'] = 'פריט';
$PMF_LANG['ad_glossary_definition'] = 'הגדרה';
$PMF_LANG['ad_glossary_save'] = 'שמור מונח';
$PMF_LANG['ad_glossary_save_success'] = 'המונח נשמר בהצלחה!';
$PMF_LANG['ad_glossary_save_error'] = 'המונח לא נשמר בעקבות תקלה במערכת.';
$PMF_LANG['ad_glossary_update_success'] = 'המונח עודכן בהצלחה!';
$PMF_LANG['ad_glossary_update_error'] = 'המונח לא עודכן בעקבות תקלה במערכת.';
$PMF_LANG['ad_glossary_delete'] = 'מחק מונח';
$PMF_LANG['ad_glossary_delete_success'] = 'המונח נמחק בהצלחה!';
$PMF_LANG['ad_glossary_delete_error'] = 'המונח לא נמחק בעקבות תקלה במערכת.';
$PMF_LANG['ad_linkcheck_noReferenceURL'] = 'אימות קישור אוטומטי לא פעיל (URL לאימות לא נקבע)';
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = 'אימות קישור אוטומטי לא פעיל (PHP אופצית allow_url_fopen לא מופעלת)';
$PMF_LANG['ad_linkcheck_checkResult'] = 'תוצאת אימות קישור אוטומטי';
$PMF_LANG['ad_linkcheck_checkSuccess'] = 'OK';
$PMF_LANG['ad_linkcheck_checkFailed'] = 'נכשל';
$PMF_LANG['ad_linkcheck_failReason'] = 'סיבת הכשלון:';
$PMF_LANG['ad_linkcheck_noLinksFound'] = 'לא נמצאו כתובות URL מתאימות לאימות.';
$PMF_LANG['ad_linkcheck_searchbadonly'] = 'רק עם קישורים פגומים';
$PMF_LANG['ad_linkcheck_infoReason'] = 'מידע נוסף:';
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = 'בבדיקה נמצא <strong>%s</strong>: ';
$PMF_LANG['ad_linkcheck_openurl_notready'] = 'מאמת הקישורים לא מוכן.';
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = 'יותר הכוונות (redirect) מהמקסימום <strong>%d</strong> המותר.';
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = 'תורגם ל URL ריק.';
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = 'השרת המארח <strong>%s</strong> איטי או לא מגיב.';
$PMF_LANG['ad_linkcheck_openurl_nodns'] = 'תרגום DNS של השרת המארח <strong>%s</strong> איטי או נכשל בעקבות בעיות DNS, מקומיות או מרוחקות.';
$PMF_LANG['ad_linkcheck_openurl_redirected'] = 'URL הוגבל ל <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = 'סטטוס HTTP לא ברור <strong>%s</strong> הוחזר.';
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = 'שיטת ה <em>HEAD</em> לא נתמכת על ידי השרת המארח <strong>%s</strong>, השיטות הנתמכות: <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_openurl_not_found'] = 'משאב זה אינו נמצא על השרת המארח <strong>%s</strong>.';
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = 'פרוטוקול %s לא נתמך על ידי מאמת הקישורים האוטומטי.';
$PMF_LANG['ad_menu_linkconfig'] = 'מאמת URL';
$PMF_LANG['ad_linkcheck_config_title'] = 'קונפיגורצית מאמת ה URL';
$PMF_LANG['ad_linkcheck_config_disabled'] = 'לא פעילה URL פונקצית מאמת ה';
$PMF_LANG['ad_linkcheck_config_warnlist'] = 'להזהיר מפני הכתובות הבאות';
$PMF_LANG['ad_linkcheck_config_ignorelist'] = 'להתעלם מהכתובות הבאות';
$PMF_LANG['ad_linkcheck_config_warnlist_description'] = 'הזהרה תוצג עבור הכתובות המופיעות מטה גם אם הן תקינות.<br />השתמש בתכונה זו כדי להזהיר מפני כתובות העומדות להפוך ללא פעילות בקרוב.';
$PMF_LANG['ad_linkcheck_config_ignorelist_description'] = 'הכתובות המופיעות כאן יחשבו תקינות ללא בדיקה.<br />השתמש בתכונה זו עבור כתובות תקינות שהמאמת מוצא כלא תקינות.';
$PMF_LANG['ad_linkcheck_config_th_id'] = 'ID#';
$PMF_LANG['ad_linkcheck_config_th_url'] = 'URL להתאמה';
$PMF_LANG['ad_linkcheck_config_th_reason'] = 'סיבת התאמה';
$PMF_LANG['ad_linkcheck_config_th_owner'] = 'הבעלים של הערך';
$PMF_LANG['ad_linkcheck_config_th_enabled'] = 'סמן לאיפשור ערך';
$PMF_LANG['ad_linkcheck_config_th_locked'] = 'סמן לנעילת בעלות';
$PMF_LANG['ad_linkcheck_config_th_chown'] = 'סמן לקבלת בעלות';
$PMF_LANG['msgNewQuestionVisible'] = 'יש לערוך את השאלה לפני פרסומה.';
$PMF_LANG['msgQuestionsWaiting'] = 'ממתין לפרסום על ידי עורך האתר: ';
$PMF_LANG['ad_entry_visibility'] = 'פרסם?';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] =  "אנא הכנס סיסמה. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] =  "הסיסמאות אינן זהות. ";
$PMF_LANG['ad_user_error_loginInvalid'] =  "שם המשתמש שצוין לא תקין.";
$PMF_LANG['ad_user_error_noEmail'] =  "אנא הכנס כתובת דואל תקפה. ";
$PMF_LANG['ad_user_error_noRealName'] =  "אנא הכנס את שמך האמיתי. ";
$PMF_LANG['ad_user_error_delete'] =  "לא ניתן היה למחוק את חשבון המשתמש. ";
$PMF_LANG['ad_user_error_noId'] =  "לא צוין ID. ";
$PMF_LANG['ad_user_error_protectedAccount'] =  "חשבון המשתמש מוגן. ";
$PMF_LANG['ad_user_deleteUser'] = "מחק משתמש";
$PMF_LANG['ad_user_status'] = "סטטוס:";
$PMF_LANG['ad_user_lastModified'] = "עדכון אחרון:";
$PMF_LANG['ad_gen_cancel'] = "בטל";
$PMF_LANG["rightsLanguage"]['addglossary'] = "הוסף מונח";
$PMF_LANG["rightsLanguage"]['editglossary'] = "ערוך מונח";
$PMF_LANG["rightsLanguage"]['delglossary'] = "מחק מונח";
$PMF_LANG["ad_menu_group_administration"] = "קבוצות";
$PMF_LANG['ad_user_loggedin'] = 'נכנסת למערכת בתור ';

$PMF_LANG['ad_group_details'] = "פרטי קבוצה";
$PMF_LANG['ad_group_add'] = "הוסף קבוצה";
$PMF_LANG['ad_group_add_link'] = "הוסף קישור";
$PMF_LANG['ad_group_name'] = "שם:";
$PMF_LANG['ad_group_description'] = "תאור:";
$PMF_LANG['ad_group_autoJoin'] = "Auto-join:";
$PMF_LANG['ad_group_suc'] = "קבוצה נוספה <strong>בהצלחה</strong>.";
$PMF_LANG['ad_group_error_noName'] = "אנא הכנס שם קבוצה. ";
$PMF_LANG['ad_group_error_delete'] = "לא ניתן היה למחוק את הקבוצה. ";
$PMF_LANG['ad_group_deleted'] = "הקבוצה נמחקה בהצלחה.";
$PMF_LANG['ad_group_deleteGroup'] = "מחק קבוצה";
$PMF_LANG['ad_group_deleteQuestion'] = "האם אתה בטוח שצריך למחוק את הקבוצה הזו?";
$PMF_LANG['ad_user_uncheckall'] = "בטל את כל הבחירות";
$PMF_LANG['ad_group_membership'] = "חברות בקבוצה";
$PMF_LANG['ad_group_members'] = "חברים";
$PMF_LANG['ad_group_addMember'] = "+";
$PMF_LANG['ad_group_removeMember'] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'הגבל את המידע ליצוא (אופציונלי)';
$PMF_LANG['ad_export_cat_downwards'] = 'יורד?';
$PMF_LANG['ad_export_type'] = 'פורמט יצוא';
$PMF_LANG['ad_export_type_choose'] = 'בחר באחד הפורמטים הנתמכים:';
$PMF_LANG['ad_export_download_view'] = 'הורדה או צפייה על המסך?';
$PMF_LANG['ad_export_download'] = 'הורדה';
$PMF_LANG['ad_export_view'] = 'צפייה על המסך';
$PMF_LANG['ad_export_gen_xhtml'] = 'צור קובץ XHTML';
$PMF_LANG['ad_export_gen_docbook'] = 'צור קובץ Docbook';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'תוכן החדשה';
$PMF_LANG['ad_news_author_name'] = 'שם הכותב:';
$PMF_LANG['ad_news_author_email'] = 'דואל הכותב:';
$PMF_LANG['ad_news_set_active'] = 'פעיל:';
$PMF_LANG['ad_news_allowComments'] = 'אפשר תגובות:';
$PMF_LANG['ad_news_expiration_window'] = 'חלון פקיעת החדשה (אופציונלי)';
$PMF_LANG['ad_news_from'] = 'פעיל מ:';
$PMF_LANG['ad_news_to'] = 'פעיל עד:';
$PMF_LANG['ad_news_insertfail'] = 'ארעה תקלה בהכנסת החדשה לבסיס הנתונים.';
$PMF_LANG['ad_news_updatefail'] = 'ארעה תקלה בעדכון החדשה.';
$PMF_LANG['newsShowCurrent'] = 'הצג חדשות נוכחיות.';
$PMF_LANG['newsShowArchive'] = 'חדשות נוספות';
$PMF_LANG['newsArchive'] = ' ארכיון חדשות';
$PMF_LANG['newsWriteComment'] = 'הגב על ערך זה';
$PMF_LANG['newsCommentDate'] = 'הוסף ב: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'חלון פקיעת הערך (אופציונלי)';
$PMF_LANG['admin_mainmenu_home'] = 'עמוד הבית';
$PMF_LANG['admin_mainmenu_users'] = 'משתמשים';
$PMF_LANG['admin_mainmenu_content'] = 'תוכן';
$PMF_LANG['admin_mainmenu_statistics'] = 'סטטיסטיקות';
$PMF_LANG['admin_mainmenu_exports'] = 'יצוא';
$PMF_LANG['admin_mainmenu_backup'] = 'גיבוי';
$PMF_LANG['admin_mainmenu_configuration'] = 'תצורה';
$PMF_LANG['admin_mainmenu_logout'] = 'יציאה מהמערכת';

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = 'הקטגוריה בבעלות';
$PMF_LANG['adminSection'] = 'ניהול';
$PMF_LANG['err_expiredArticle'] = 'ערך זה פקע ולא ניתן לצפייה';
$PMF_LANG['err_expiredNews'] = 'חדשה זו פקעה ואינה ניתנת לצפייה';
$PMF_LANG['err_inactiveNews'] = 'חדשה זו נמצאת בעריכה ולא ניתנת לצפייה';
$PMF_LANG['msgSearchOnAllLanguages'] = 'חפש בכל השפות:';
$PMF_LANG['ad_entry_tags'] = 'תגים';
$PMF_LANG['msg_tags'] = 'תגים';

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = 'בודק...';
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = 'בודק...';
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = 'בודק...';
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = 'בודק...';
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = 'לא פעיל';
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = 'Links KO';
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = 'Links OK';
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = 'אין גישה';
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = 'No AJAX';
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = 'אין קישורים';
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = 'אין סקריפט';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'ערכים קשורים';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "מספר ערכים קשורים");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'תרגם';
$PMF_LANG['ad_categ_trans_2'] = 'קטגוריה';
$PMF_LANG['ad_categ_translatecateg'] = 'תרגם קטגוריה';
$PMF_LANG['ad_categ_translate'] = 'תרגם';
$PMF_LANG['ad_categ_transalready'] = 'מתורגם כבר ב: ';
$PMF_LANG["ad_categ_deletealllang"] = 'מחק בכל השפות?';
$PMF_LANG["ad_categ_deletethislang"] = 'מחק בשפה זו בלבד?';
$PMF_LANG["ad_categ_translated"] = "הקטגוריה תורגמה.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "מבט על";
$PMF_LANG['ad_menu_categ_structure'] = "מבט על על קטגוריה בשפות הבאות";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'הרשאות משתמש:';
$PMF_LANG['ad_entry_grouppermission'] = 'הרשאות קבוצה:';
$PMF_LANG['ad_entry_all_users'] = 'גישה לכל המשתמשים';
$PMF_LANG['ad_entry_restricted_users'] = 'גישה מוגבלת ל';
$PMF_LANG['ad_entry_all_groups'] = 'גישה לכל הקבוצות';
$PMF_LANG['ad_entry_restricted_groups'] = 'גישה מוגבלת ל';
$PMF_LANG['ad_session_expiration'] = 'זמן נותר עד לפקיעת הסשן שלך';
$PMF_LANG['ad_user_active'] = 'פעיל';
$PMF_LANG['ad_user_blocked'] = 'חסום';
$PMF_LANG['ad_user_protected'] = 'מוגן';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'בחר ערך להכנסה כקישור...';

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "הדבק אחרי";
$PMF_LANG["ad_categ_remark_move"] = "החלפה בין שתי קטגוריות אפשרי רק באותה רמה.";
$PMF_LANG["ad_categ_remark_overview"] = "הקטגוריות יוצגו בסדר נכון רק אם הוגדרו עבור אותה שפה (תור ראשון).";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d אורחים ו %d רשומים';
$PMF_LANG['ad_adminlog_del_older_30d'] = 'מחק רישומים בני יותר מ 30 יום';
$PMF_LANG['ad_adminlog_delete_success'] = 'רישומים ישנים נמחקו בהצלחה.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'לא נמחקו רישומים: ארעה תקלה בביצוע הבקשה.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = 'הוסף הרחבת חיפוש';
$PMF_LANG['ad_quicklinks'] = 'קישורים מהירים';
$PMF_LANG['ad_quick_category'] = 'הוסף קטגוריה חדשה';
$PMF_LANG['ad_quick_record'] = 'הוסף ערך חדש';
$PMF_LANG['ad_quick_user'] = 'הוסף משתמש חדש';
$PMF_LANG['ad_quick_group'] = 'הוסף קבוצה חדשה';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'הצעת תרגום';
$PMF_LANG['msgNewTranslationAddon'] = 'הצעת התרגום שלך תפורסם לאחר בדיקת עורך האתר. שדות נדרשים הם <strong>שימך</strong>, <strong>כתובת האימייל שלך</strong>, <strong>תרגום הכותרת שלך</strong> ו <strong>תרגום התוכן שלך</strong>. אנא הפרד את מילות המפתח עם רווח בלבד.';
$PMF_LANG['msgNewTransSourcePane'] = 'חלונית המקור';
$PMF_LANG['msgNewTranslationPane'] = 'חלונית התרגום';
$PMF_LANG['msgNewTranslationName'] = "Your Name:";
$PMF_LANG['msgNewTranslationMail'] = "Your email address:";
$PMF_LANG['msgNewTranslationKeywords'] = "Keywords:";
$PMF_LANG['msgNewTranslationSubmit'] = 'שלח הצעתך';
$PMF_LANG['msgTranslate'] = 'הצע תרגום עבור';
$PMF_LANG['msgTranslateSubmit'] = 'התחל תרגום...';
$PMF_LANG['msgNewTranslationThanks'] = "תודה על הצעת התרגום שלך!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG["rightsLanguage"]['addgroup'] = "הוסף חשבונות לקבוצה";
$PMF_LANG["rightsLanguage"]['editgroup'] = "ערוך חשבונות בקבוצה";
$PMF_LANG["rightsLanguage"]['delgroup'] = "מחק חשבונות בקבוצה";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = 'קישור נפתח בחלון האב (באתר עם פריימים)';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'תגובות';
$PMF_LANG['ad_comment_administration'] = 'ניהול תגובות';
$PMF_LANG['ad_comment_faqs'] = 'תגובות לערכים:';
$PMF_LANG['ad_comment_news'] = 'תגובות לחדשות:';
$PMF_LANG['ad_groups'] = 'קבוצות';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'מיון ערכים (לפי תכונה)');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'מיון ערכים (סדר עולה או יורד)');
$PMF_LANG['ad_conf_order_id'] = 'ID (ברירת מחדל)';
$PMF_LANG['ad_conf_order_thema'] = 'כותרת';
$PMF_LANG['ad_conf_order_visits'] = 'מספר צפיות';
$PMF_LANG['ad_conf_order_datum'] = 'תאריך';
$PMF_LANG['ad_conf_order_author'] = 'כותב';
$PMF_LANG['ad_conf_desc'] = 'סדר יורד';
$PMF_LANG['ad_conf_asc'] = 'סדר עולה';
$PMF_LANG['mainControlCenter'] = 'תצורה בסיסית';
$PMF_LANG['recordsControlCenter'] = 'תצורת ערכים';

// added v2.0.0 - 2007-03-17 by Thorsten
$PMF_LANG['msgInstantResponse'] = 'תגובה מיידית';
$PMF_LANG['msgInstantResponseMaxRecords'] = '. Find below the first %d records.';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "הפעלת ערך חדש (ברירת מחדל: לא מופעל)");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "הרשה תגובות לערכים חדשים (ברירת מחדל: לא מופעל)");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'ערכים בקטגוריה זו';
$PMF_LANG['msgDescriptionInstantResponse'] = 'התשובות יופיעו תוך כדי הקלדה...';
$PMF_LANG['msgTagSearch'] = 'ערכים מתויגים';
$PMF_LANG['ad_pmf_info'] = 'נתוני phpMyFAQ';
$PMF_LANG['ad_online_info'] = 'בדיקת גירסה מקוונת';
$PMF_LANG['ad_system_info'] = 'נתוני המערכת';
