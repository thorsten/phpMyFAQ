<?php
/******************************************************************************
 * File:				language_hu.php
 * Description:		    Hungarian language file
 * Authors:				Bal�zs T�th <>
 * Date:				2004-06-24
 * Last Update:		    2004-07-07
 * Copyright:           (c) 2006 phpMyFAQ Team
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
 ******************************************************************************/

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "hu";
$PMF_LANG["language"] = "Hungarian";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";

$PMF_LANG["nplurals"] = "2";
/**
 * This parameter is used with the function 'plural' from inc/PMF_Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 */

// Navigation
$PMF_LANG["msgCategory"] = "Kategóriák";
$PMF_LANG["msgShowAllCategories"] = "Az összes kategória";
$PMF_LANG["msgSearch"] = "Keresés";
$PMF_LANG["msgAddContent"] = "Javaslat";
$PMF_LANG["msgQuestion"] = "Kérdés";
$PMF_LANG["msgOpenQuestions"] = "Nyitott kérdések";
$PMF_LANG["msgHelp"] = "Segítség";
$PMF_LANG["msgContact"] = "Kapcsolat";
$PMF_LANG["msgHome"] = "Kezdőlap";
$PMF_LANG["msgNews"] = "GYIK-Hírek";
$PMF_LANG["msgUserOnline"] = "Aktív felhasználó";
$PMF_LANG["msgXMLExport"] = "XML-Fájl";
$PMF_LANG["msgBack2Home"] = "vissza a kezdőlapra";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategóriák a bejegyzésekkel";
$PMF_LANG["msgFullCategoriesIn"] = "Kategóriák a bejegyzésekkel: ";
$PMF_LANG["msgSubCategories"] = "Alkategóriák";
$PMF_LANG["msgEntries"] = "bejegyzés";
$PMF_LANG["msgEntriesIn"] = "Kérdések: ";
$PMF_LANG["msgViews"] = "megjelenítés";
$PMF_LANG["msgPage"] = "Oldal ";
$PMF_LANG["msgPages"] = "Oldalak";
$PMF_LANG["msgPrevious"] = "előző";
$PMF_LANG["msgNext"] = "következő";
$PMF_LANG["msgCategoryUp"] = "egy kategóriával feljebb";
$PMF_LANG["msgLastUpdateArticle"] = "Utolsó módosítás: ";
$PMF_LANG["msgAuthor"] = "Szerző: ";
$PMF_LANG["msgPrinterFriendly"] = "nyomtatható verzió";
$PMF_LANG["msgPrintArticle"] = "Bejegyzés nyomtatása";
$PMF_LANG["msgMakeXMLExport"] = "exportálás XML fájlként";
$PMF_LANG["msgAverageVote"] = "�?tlagos osztályzat:";
$PMF_LANG["msgVoteUseability"] = "Kérlek osztályozd a bejegyzést:";
$PMF_LANG["msgVoteFrom"] = "tól";
$PMF_LANG["msgVoteBad"] = "használhatatlan";
$PMF_LANG["msgVoteGood"] = "remek";
$PMF_LANG["msgVotings"] = "Szavazatok ";
$PMF_LANG["msgVoteSubmit"] = "Szavazás";
$PMF_LANG["msgVoteThanks"] = "Köszönjük a szavazatot!";
$PMF_LANG["msgYouCan"] = "Tudsz ";
$PMF_LANG["msgWriteComment"] = "megjegyzést fűzni a bejegyzéshez";
$PMF_LANG["msgShowCategory"] = "Tartalomjegyzék: ";
$PMF_LANG["msgCommentBy"] = "Megjegyzés ";
$PMF_LANG["msgCommentHeader"] = "Megjegyzés a bejegyzéshez";
$PMF_LANG["msgYourComment"] = "Megjegyzéseid:";
$PMF_LANG["msgCommentThanks"] = "Köszönjük a hozzászólást!";
$PMF_LANG["msgSeeXMLFile"] = "XML fájl megnyitása";
$PMF_LANG["msgSend2Friend"] = "Küld el a barátaidnak";
$PMF_LANG["msgS2FName"] = "Neved:";
$PMF_LANG["msgS2FEMail"] = "Email címed:";
$PMF_LANG["msgS2FFriends"] = "A barátaid:";
$PMF_LANG["msgS2FEMails"] = ". email címe:";
$PMF_LANG["msgS2FText"] = "A következő szöveg lesz elküldve:";
$PMF_LANG["msgS2FText2"] = "A bejegyzést a következő címen érheted el:";
$PMF_LANG["msgS2FMessage"] = "További üzenet:";
$PMF_LANG["msgS2FButton"] = "email elküldése";
$PMF_LANG["msgS2FThx"] = "Köszönjük az ajánlást!";
$PMF_LANG["msgS2FMailSubject"] = "Recommendation from ";

// Search
$PMF_LANG["msgSearchWord"] = "Kulcsszó";
$PMF_LANG["msgSearchFind"] = "A keresés eredménye: ";
$PMF_LANG["msgSearchAmount"] = " találat";
$PMF_LANG["msgSearchAmounts"] = " találatok";
$PMF_LANG["msgSearchCategory"] = "Kategória: ";
$PMF_LANG["msgSearchContent"] = "Tartalom: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Javaslat a GYIK-hoz";
$PMF_LANG["msgNewContentAddon"] = "A javaslatod nem jelenik meg egyből a GYIK-ban, további sorsáról az adminisztrátor dönt. A kötelező mezők: <strong>neved</strong>, <strong>email címed</strong>, <strong>kategória</strong>, <strong>cím</strong> és <strong>bejegyzés</strong>. A kulcsszavakat csak szóközzel válaszd el.";
$PMF_LANG["msgNewContentUBB"] = "<p>Használhatsz UBB kódokat a kérdésedben. <a href=\"help/ubbcode.php\" target=\"_blank\">Segítség a UBB kódokhoz</a></p>";
$PMF_LANG["msgNewContentName"] = "Neved:";
$PMF_LANG["msgNewContentMail"] = "Email címed:";
$PMF_LANG["msgNewContentCategory"] = "Kategória:";
$PMF_LANG["msgNewContentTheme"] = "Cím:";
$PMF_LANG["msgNewContentArticle"] = "Szöveg:";
$PMF_LANG["msgNewContentKeywords"] = "Kulcsszavak:";
$PMF_LANG["msgNewContentLink"] = "Link ehhez a bejegyzéshez";
$PMF_LANG["msgNewContentSubmit"] = "elküld";
$PMF_LANG["msgInfo"] = "További információ: ";
$PMF_LANG["msgNewContentThanks"] = "Köszönjük a javaslatot!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Jelenleg nincs nyitott kérdés.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "";
$PMF_LANG["msgAskCategory"] = "Kategória:";
$PMF_LANG["msgAskYourQuestion"] = "A kérdés:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>Köszönjük a leveledet!</h2>";
$PMF_LANG["msgDate_User"] = "Dátum / Felhasználó";
$PMF_LANG["msgQuestion2"] = "Kérdés";
$PMF_LANG["msg2answer"] = "Javaslat";
$PMF_LANG["msgQuestionText"] = "Itt a többi látogató által feltett kérdéseket láthatod. Ha válaszolsz valamelyik kérdésre, akkor a válaszod bekerülhet a GYIK-ba.";

// Help
$PMF_LANG["msgHelpText"] = "<p>A GYIK (<strong>GY</strong>akran <strong>I</strong>smételt <strong>K</strong>érdések) használata meglehetősen egyszerű. Kereshetsz a <strong><a href=\"?action=show\">kategóriákban</a></strong> vagy <strong><a href=\"?action=search\">kulcsszavakra</a></strong>.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "email az adminisztrátornak:";
$PMF_LANG["msgMessage"] = "Üzenet:";

// Startseite
$PMF_LANG["msgNews"] = " Hír";
$PMF_LANG["msgTopTen"] = "TOP 10";
$PMF_LANG["msgHomeThereAre"] = "Jelenleg ";
$PMF_LANG["msgHomeArticlesOnline"] = " bejegyzés érhető el";
$PMF_LANG["msgNoNews"] = "Ha nincs hír az jó hír.";
$PMF_LANG["msgLatestArticles"] = "Az utolsó öt beküldött kérdés:";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Köszönjük a javaslatot.";
$PMF_LANG["msgMailCheck"] = "Új bejegyzés a GYIK-ban!Ellenőrizd az admin részen!";
$PMF_LANG["msgMailContact"] = "Az üzenet postázva az adminisztrátornak.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "Az adabázis elérhetetlen.";
$PMF_LANG["err_noHeaders"] = "Nincsenek kategóriák.";
$PMF_LANG["err_noArticles"] = "<p>Nincsenek bejegyzések.</p>";
$PMF_LANG["err_badID"] = "<p>Rossz ID.</p>";
$PMF_LANG["err_noTopTen"] = "<p>Még nincs TOP 10.</p>";
$PMF_LANG["err_nothingFound"] = "<p>Nincs bejegyzés.</p>";
$PMF_LANG["err_SaveEntries"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong>, <strong>category</strong>, <strong>headline</strong> and <strong>your Record</strong>!<br /><br /><a href=\"javascript:history.back();\">one page back</a><br /><br />";
$PMF_LANG["err_SaveComment"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong> and <strong>your comments</strong>!<br /><br /><a href=\"javascript:history.back();\">one page back</a><br /><br />";
$PMF_LANG["err_VoteTooMuch"] = "<p>We do not count double votings. <a href=\"javascript:history.back();\">Click here</a>, to go back.</p>";
$PMF_LANG["err_noVote"] = "<p><strong>You did not rate the question!</strong> <a href=\"javascript:history.back();\">Please click here</a>, to vote.</p>";
$PMF_LANG["err_noMailAdress"] = "Your email address is not correct.<br /><a href=\"javascript:history.back();\">back</a>";
$PMF_LANG["err_sendMail"] = "Required fields are <strong>your name</strong>, <strong>your email address</strong> and <strong>your question</strong>!<br /><br /><a href=\"javascript:history.back();\">one page back</a><br /><br />";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>Találatok:</strong><br /></p>";

// Menü
$PMF_LANG["ad"] = "ADMIN RÉSZ";
$PMF_LANG["ad_menu_user_administration "] = "Felhasználók beállításai";
$PMF_LANG["ad_menu_entry_aprove"] = "Javasolt bejegyzések";
$PMF_LANG["ad_menu_entry_edit"] = "Bejegyzések szerkesztése";
$PMF_LANG["ad_menu_categ_add"] = "Kategória hozzáadása";
$PMF_LANG["ad_menu_categ_edit"] = "Kategória szerkesztése";
$PMF_LANG["ad_menu_news_add"] = "Hír hozzáadása";
$PMF_LANG["ad_menu_news_edit"] = "Hír szerkesztése";
$PMF_LANG["ad_menu_open"] = "Nyitott kérdések szerkesztése";
$PMF_LANG["ad_menu_stat"] = "Statisztika";
$PMF_LANG["ad_menu_cookie"] = "Cookiek";
$PMF_LANG["ad_menu_session"] = "Sessionok listázása";
$PMF_LANG["ad_menu_adminlog"] = "Adminlog megjelenítése";
$PMF_LANG["ad_menu_passwd"] = "Jelszó változtatás";
$PMF_LANG["ad_menu_logout"] = "Kijelentkezés";
$PMF_LANG["ad_menu_startpage"] = "Kezdőlap";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Kérlek azonosítsd magad.";
$PMF_LANG["ad_msg_passmatch"] = "Mindkét jelszónak <strong>egyeznie</strong> kell!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profil: ";
$PMF_LANG["ad_msg_savedsuc_2"] = "sikeresen tárolva.";
$PMF_LANG["ad_msg_mysqlerr"] = "<strong>Adatbázis hiba</strong> miatt a profilt nem lehet tárolni.";
$PMF_LANG["ad_msg_noauth"] = "Nincs jogosultságod.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Oldal";
$PMF_LANG["ad_gen_of"] = "ból";
$PMF_LANG["ad_gen_lastpage"] = "Előző oldal";
$PMF_LANG["ad_gen_nextpage"] = "Következő oldal";
$PMF_LANG["ad_gen_save"] = "Tárolás";
$PMF_LANG["ad_gen_reset"] = "Reset";
$PMF_LANG["ad_gen_yes"] = "Igen";
$PMF_LANG["ad_gen_no"] = "Nem";
$PMF_LANG["ad_gen_top"] = "A lap teteje";
$PMF_LANG["ad_gen_ncf"] = "Nem találom a kategóriát!";
$PMF_LANG["ad_gen_delete"] = "Törlés";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "Felhasználók beállításai";
$PMF_LANG["ad_user_username"] = "Bejegyzett felhasználók";
$PMF_LANG["ad_user_rights"] = "Jogosultságok";
$PMF_LANG["ad_user_edit"] = "szerkesztés";
$PMF_LANG["ad_user_delete"] = "törlés";
$PMF_LANG["ad_user_add"] = "Felhasználó felvétele";
$PMF_LANG["ad_user_profou"] = "A felhasználó profilja";
$PMF_LANG["ad_user_name"] = "Név";
$PMF_LANG["ad_user_password"] = "Jelszó";
$PMF_LANG["ad_user_confirm"] = "Megerősítés";
$PMF_LANG["ad_user_rights"] = "Jogok";
$PMF_LANG["ad_user_del_1"] = "A felhasználót [";
$PMF_LANG["ad_user_del_2"] = "] valóban töröljem?";
$PMF_LANG["ad_user_del_3"] = "Biztos?";
$PMF_LANG["ad_user_deleted"] = "A felhasználó törölve.";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "Bejegyzések szerkesztése";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Tartalom";
$PMF_LANG["ad_entry_action"] = "Művelet";
$PMF_LANG["ad_entry_edit_1"] = "Bejegyzés szerkesztése";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Téma:";
$PMF_LANG["ad_entry_content"] = "Bejegyzés:";
$PMF_LANG["ad_entry_keywords"] = "Kulcsszavak:";
$PMF_LANG["ad_entry_author"] = "Szerző:";
$PMF_LANG["ad_entry_category"] = "Kategória:";
$PMF_LANG["ad_entry_active"] = "Aktív?";
$PMF_LANG["ad_entry_date"] = "Dátum:";
$PMF_LANG["ad_entry_changed"] = "Változás";
$PMF_LANG["ad_entry_changelog"] = "Változtatások:";
$PMF_LANG["ad_entry_commentby"] = "Megjegyzés ";
$PMF_LANG["ad_entry_comment"] = "Megjegyzése:";
$PMF_LANG["ad_entry_save"] = "Tárolás";
$PMF_LANG["ad_entry_delete"] = "Törlés";
$PMF_LANG["ad_entry_delcom_1"] = "Biztos hogy a felhasználó [";
$PMF_LANG["ad_entry_delcom_2"] = "] megjegyzése törölhető?";
$PMF_LANG["ad_entry_commentdelsuc "] = "A megjegyzés <strong>sikeresen</strong> törölve.";
$PMF_LANG["ad_entry_back"] = "Vissza a bejegyzéshez";
$PMF_LANG["ad_entry_commentdelfail "] = "A megjegyzés <strong>nem</strong> lett törölve.";
$PMF_LANG["ad_entry_savedsuc"] = "A változások <strong>sikeresen</strong> tárolva.";
$PMF_LANG["ad_entry_savedfail"] = "<strong>Hiba</strong> az adatbázis elérésében.";
$PMF_LANG["ad_entry_del_1"] = "Biztos hogy a téma [";
$PMF_LANG["ad_entry_del_2"] = "][";
$PMF_LANG["ad_entry_del_3"] = "] törölhető?";
$PMF_LANG["ad_entry_delsuc"] = "Bejegyzés <strong>sikeresen</strong> törölve.";
$PMF_LANG["ad_entry_delfail"] = "A bejegyzés <strong>nem</strong> lett törölve!";
$PMF_LANG["ad_entry_back"] = "Vissza";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "A hír címe";
$PMF_LANG["ad_news_text"] = "A hír szövege";
$PMF_LANG["ad_news_link_url"] = "Link: (<strong>http:// nélkül</strong>)!";
$PMF_LANG["ad_news_link_title"] = "A link címe:";
$PMF_LANG["ad_news_link_target"] = "A link célja";
$PMF_LANG["ad_news_link_window"] = "A link új ablakot nyit";
$PMF_LANG["ad_news_link_faq"] = "Link a GYIK-on belül";
$PMF_LANG["ad_news_add"] = "Hír hozzáadása";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Cím";
$PMF_LANG["ad_news_date"] = "Dátum";
$PMF_LANG["ad_news_action"] = "Művelet";
$PMF_LANG["ad_news_update"] = "frissítés";
$PMF_LANG["ad_news_delete"] = "törlés";
$PMF_LANG["ad_news_nodata"] = "Nem találom az adatbázisban";
$PMF_LANG["ad_news_updatesuc"] = "A hír frissítve.";
$PMF_LANG["ad_news_del"] = "Biztos hogy tötölni akarod a hírt?";
$PMF_LANG["ad_news_yesdelete"] = "Igen, törlöm!";
$PMF_LANG["ad_news_nodelete"] = "Nem!";
$PMF_LANG["ad_news_delsuc"] = "A hír törölve.";
$PMF_LANG["ad_news_updatenews"] = "Hír frissítése";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Kategória hozzáadása";
$PMF_LANG["ad_categ_catnum"] = "Kategória sorszáma:";
$PMF_LANG["ad_categ_subcatnum"] = "Alkategória sorszáma:";
$PMF_LANG["ad_categ_nya"] = "<em>még nem elérhető!</em>";
$PMF_LANG["ad_categ_titel"] = "Kategória címe:";
$PMF_LANG["ad_categ_add"] = "Kategória hozzáadása";
$PMF_LANG["ad_categ_existing"] = "Meglévő kategóriák";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategória";
$PMF_LANG["ad_categ_subcateg"] = "Alkategória";
$PMF_LANG["ad_categ_titel"] = "Kategória címe";
$PMF_LANG["ad_categ_action"] = "Művelet";
$PMF_LANG["ad_categ_update"] = "frissítés";
$PMF_LANG["ad_categ_delete"] = "törlés";
$PMF_LANG["ad_categ_updatecateg"] = "Kategória frissítése";
$PMF_LANG["ad_categ_nodata"] = "Nem találom az adatbázisban";
$PMF_LANG["ad_categ_remark"] = "A meglévő bejegyzések nem lesznek elérhetőek ha törlöd a kategóriát. Új kategóriát kell hozzárendelned a bejegyzéshez, vagy törölni azt.";
$PMF_LANG["ad_categ_edit_1"] = "Szerkeszés";
$PMF_LANG["ad_categ_edit_2"] = "Kategória";
$PMF_LANG["ad_categ_add"] = "kategória hozzáadása";
$PMF_LANG["ad_categ_added"] = "Kategória hozzáadva.";
$PMF_LANG["ad_categ_updated"] = "Kategória frissítve.";
$PMF_LANG["ad_categ_del_yes"] = "Igen, törlöm!";
$PMF_LANG["ad_categ_del_no"] = "Nem!";
$PMF_LANG["ad_categ_deletesure"] = "Biztos hogy törlöd a kategóriát?";
$PMF_LANG["ad_categ_deleted"] = "Kategória törölve.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "A cookie <strong>sikeresen</strong> beállítva.";
$PMF_LANG["ad_cookie_already"] = "A cookie már be van állítva. A következő lehetőségeid vannak:";
$PMF_LANG["ad_cookie_again"] = "A cookie újra beállítása";
$PMF_LANG["ad_cookie_delete"] = "A cookie törlése";
$PMF_LANG["ad_cookie_no"] = "Jelenleg nincs cookie beállítva. A cookie-val automatikusan beléphetsz. A következő lehetőségeid vannak:";
$PMF_LANG["ad_cookie_set"] = "A cookie beállítása";
$PMF_LANG["ad_cookie_deleted"] = "A cookie sikeresen törölve.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "AdminLog";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Jelszó változtatás";
$PMF_LANG["ad_passwd_old"] = "Régi jelszó:";
$PMF_LANG["ad_passwd_new"] = "Új jelszó:";
$PMF_LANG["ad_passwd_con"] = "Megerősítés:";
$PMF_LANG["ad_passwd_change"] = "Jelszó változtatás";
$PMF_LANG["ad_passwd_suc"] = "Jelszó megváltoztatva.";
$PMF_LANG["ad_passwd_remark"] = "<strong>FIGYELEM:</strong><br />A cookiet újra be kell állítani!";
$PMF_LANG["ad_passwd_fail"] = "A régi jelszónak helyesnek kell lennie és az újaknak egyezniekell.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Felhasználó hozzáadása";
$PMF_LANG["ad_adus_name"] = "Név:";
$PMF_LANG["ad_adus_password"] = "Jelszó:";
$PMF_LANG["ad_adus_add"] = "Felhasználó hozzáadása";
$PMF_LANG["ad_adus_suc"] = "Felhasználó hozzáadva.";
$PMF_LANG["ad_adus_edit"] = "Profil szerkesztése";
$PMF_LANG["ad_adus_dberr"] = "<strong>adatbázis hiba!</strong>";
$PMF_LANG["ad_adus_exerr"] = "A felhasználónév már <strong>létezik</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "Session ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Idő";
$PMF_LANG["ad_sess_pageviews"] = "Megnézett oldalak";
$PMF_LANG["ad_sess_search"] = "Keresés";
$PMF_LANG["ad_sess_sfs"] = "Keresés a sessionban";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. műveletek:";
$PMF_LANG["ad_sess_s_date"] = "Dátum";
$PMF_LANG["ad_sess_s_after"] = "után";
$PMF_LANG["ad_sess_s_before"] = "előtt";
$PMF_LANG["ad_sess_s_search"] = "Keresés";
$PMF_LANG["ad_sess_session"] = "Session";
$PMF_LANG["ad_sess_r"] = "Találatok: ";
$PMF_LANG["ad_sess_referer"] = "Referer:";
$PMF_LANG["ad_sess_browser"] = "Browser:";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategória:";
$PMF_LANG["ad_sess_ai_artikel"] = "Bejegyzés:";
$PMF_LANG["ad_sess_ai_sb"] = "Kereső kérdések:";
$PMF_LANG["ad_sess_ai_sid"] = "Session ID:";
$PMF_LANG["ad_sess_back"] = "Vissza";

// Statistik
$PMF_LANG["ad_rs "] = "Osztályozási statisztika";
$PMF_LANG["ad_rs_rating_1"] = "Az osztályzata: ";
$PMF_LANG["ad_rs_rating_2"] = "users shows:";
$PMF_LANG["ad_rs_red"] = "Vörös";
$PMF_LANG["ad_rs_green"] = "Zöld";
$PMF_LANG["ad_rs_altt"] = "az átlaga kisebb mint 2";
$PMF_LANG["ad_rs_ahtf"] = "az átlaga nagyobb mint 4";
$PMF_LANG["ad_rs_no"] = "Nincs osztályzat";

// Auth
$PMF_LANG["ad_auth_insert"] = "�?rd be a neved és a jelszavadat.";
$PMF_LANG["ad_auth_user"] = "Név:";
$PMF_LANG["ad_auth_passwd"] = "jelszó:";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Reset";
$PMF_LANG["ad_auth_fail"] = "A név vagy a jelszó nem megfelelő.";
$PMF_LANG["ad_auth_sess"] = "A Session ID elfogadva.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Beállítások szerkesztése";
$PMF_LANG["ad_config_save"] = "Beállítások mentése";
$PMF_LANG["ad_config_reset"] = "Reset";
$PMF_LANG["ad_config_saved"] = "Beállítások elmentve.";
$PMF_LANG["ad_menu_editconfig"] = "Beállítások szerkesztése";
$PMF_LANG["ad_att_none"] = "Nincsenek csatolt fájlok";
$PMF_LANG["ad_att_att"] = "Csatolt fájlok:";
$PMF_LANG["ad_att_add"] = "Fájl csatolása";
$PMF_LANG["ad_entryins_suc"] = "Bejegyzése tárolva.";
$PMF_LANG["ad_entryins_fail"] = "Hiba történt.";
$PMF_LANG["ad_att_del"] = "Törlés";
$PMF_LANG["ad_att_nope"] = "Csatolt fájlokat csak szerkesztés közben lehet hozzáadni.";
$PMF_LANG["ad_att_delsuc"] = "Csatolt fájl törölve.";
$PMF_LANG["ad_att_delfail"] = "Hiba történt a csatolt fájl törlése közben.";
$PMF_LANG["ad_entry_add"] = "Bejegyzés létrehozása";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "A backup a teljes adatbázis másolata. A backup formátuma MySQL tranzakciós fájl, melynek visszaállítása a mysql klienssel lehetséges.";
$PMF_LANG["ad_csv_link"] = "Backup letöltése";
$PMF_LANG["ad_csv_head"] = "Backup létrehozása";
$PMF_LANG["ad_att_addto"] = "Fájl csatolása";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Fájl:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "A fájl sikeresen csatolva.";
$PMF_LANG["ad_att_fail"] = "Hiba történt a fájl csatolása közben.";
$PMF_LANG["ad_att_close"] = "Ablak bezárása";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Itt vissza tudod állítani az adatbázist egy backup-ból. A jelenleg az adatbázisban tárolt adatok elvesznek.";
$PMF_LANG["ad_csv_file"] = "Fájl";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "backup LOG-ok";
$PMF_LANG["ad_csv_linkdat"] = "backup adat";
$PMF_LANG["ad_csv_head2"] = "Visszaállítás";
$PMF_LANG["ad_csv_no"] = "Ez nem megfelelő backup fájl.";
$PMF_LANG["ad_csv_prepare"] = "Előkészítés...";
$PMF_LANG["ad_csv_process"] = "Lekérdezés...";
$PMF_LANG["ad_csv_of"] = "";
$PMF_LANG["ad_csv_suc"] = "sikerült.";
$PMF_LANG["ad_csv_backup"] = "Backup";
$PMF_LANG["ad_csv_rest"] = "Visszaállítás backupból";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Backup";
$PMF_LANG["ad_logout"] = "A session megszakítva.";
$PMF_LANG["ad_news_add"] = "Hír hozzáadása";
$PMF_LANG["ad_news_edit"] = "Hír szerkesztése";
$PMF_LANG["ad_cookie"] = "Cookie-k";
$PMF_LANG["ad_sess_head"] = "Sessionok lekérdezése";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Kategória szerkesztése";
$PMF_LANG["ad_menu_stat"] = "Osztályozási statisztika";
$PMF_LANG["ad_kateg_add"] = "kategória hozzáadása";
$PMF_LANG["ad_kateg_rename"] = "átnevezés";
$PMF_LANG["ad_adminlog_date"] = "Dátum";
$PMF_LANG["ad_adminlog_user"] = "Felhasználó";
$PMF_LANG["ad_adminlog_ip"] = "IP cím";

$PMF_LANG["ad_stat_sess"] = "Session-ök";
$PMF_LANG["ad_stat_days"] = "Napok";
$PMF_LANG["ad_stat_vis"] = "Session-ok (Látogatások)";
$PMF_LANG["ad_stat_vpd"] = "Napi látogatások";
$PMF_LANG["ad_stat_fien"] = "Első Log";
$PMF_LANG["ad_stat_laen"] = "Utolsó Log";
$PMF_LANG["ad_stat_browse"] = "session-ök böngészése";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Idő";
$PMF_LANG["ad_sess_sid"] = "Session-ID";
$PMF_LANG["ad_sess_ip"] = "IP cím";

$PMF_LANG["ad_ques_take"] = "Kérdés szerkesztése";
$PMF_LANG["no_cats"] = "Nincs kategória.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "A név vagy a jelszó nem megfelelő.";
$PMF_LANG["ad_log_sess"] = "A session lejárt.";
$PMF_LANG["ad_log_edit"] = "\"Felhasználó szerkesztése\"-Felhasználó: ";
$PMF_LANG["ad_log_crea"] = "\"Új hír\"";
$PMF_LANG["ad_log_crsa"] = "Bejegyzés létrehozva.";
$PMF_LANG["ad_log_ussa"] = "Felhasználó adatainak frissítése: ";
$PMF_LANG["ad_log_usde"] = "Felhasználó törlése: ";
$PMF_LANG["ad_log_beed"] = "Felhasználó szerkesztése: ";
$PMF_LANG["ad_log_bede"] = "Bejegyzés törölve: ";

$PMF_LANG["ad_start_visits"] = "Látogatások";
$PMF_LANG["ad_start_articles"] = "Hírek";
$PMF_LANG["ad_start_comments"] = "Megjegyzések";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "beillesztés";
$PMF_LANG["ad_categ_cut"] = "kivágás";
$PMF_LANG["ad_categ_copy"] = "másolás";
$PMF_LANG["ad_categ_process"] = "Kategóriák feldolgozása...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Nem vagy bejelentkezve.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "előző oldal";
$PMF_LANG["msgNextPage"] = "következő oldal";
$PMF_LANG["msgPageDoublePoint"] = "Oldal: ";
$PMF_LANG["msgMainCategory"] = "Fő kategória";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Jelszavad megváltoztatva.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "Mutasd PDF fájlként";
$PMF_LANG["ad_xml_head"] = "XML-Backup";
$PMF_LANG["ad_xml_hint"] = "A GYIK mentése XML fájlba.";
$PMF_LANG["ad_xml_gen"] = "XML fájl készítése";
$PMF_LANG["ad_entry_locale"] = "Nyelv";
$PMF_LANG["msgLangaugeSubmit"] = "nyelv megváltoztatása";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "Előnézet";
$PMF_LANG["ad_attach_1"] = "Először add meg a csatolt fájlok helyét a beállításokban.";
$PMF_LANG["ad_attach_2"] = "Először állítsd be a linkeket a beállításokban.";
$PMF_LANG["ad_attach_3"] = "Az attachment.php nem nyitható meg.";
$PMF_LANG["ad_attach_4"] = "A csatolt fájlnak kisebbnek kell lennie mint %s byte.";
$PMF_LANG["ad_menu_export"] = "GYIK exportálása";
$PMF_LANG["ad_export_1"] = "RSS készítése";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "Hiba: Nem írható a fájl.";
$PMF_LANG["ad_export_news"] = "Hírek RSS";
$PMF_LANG["ad_export_topten"] = "Top 10 RSS";
$PMF_LANG["ad_export_latest"] = "Az 5 legújabb bejegyzés RSS";
$PMF_LANG["ad_export_pdf"] = "PDF fájlba exportálása a bejegyzéseknek";
$PMF_LANG["ad_export_generate"] = "RSS készítése";

$PMF_LANG["rightsLanguage"]['adduser'] = "felhasználó hozzáadása";
$PMF_LANG["rightsLanguage"]['edituser'] = "felhasználó szerkesztése";
$PMF_LANG["rightsLanguage"]['deluser'] = "felhasználó törlése";
$PMF_LANG["rightsLanguage"]['addbt'] = "bejegyzés hozzáadása";
$PMF_LANG["rightsLanguage"]['editbt'] = "bejegyzés szerkesztése";
$PMF_LANG["rightsLanguage"]['delbt'] = "bejegyzés törlése";
$PMF_LANG["rightsLanguage"]['viewlog'] = "log megnézése";
$PMF_LANG["rightsLanguage"]['adminlog'] = "admin log megnézése";
$PMF_LANG["rightsLanguage"]['delcomment'] = "megjegyzés törlése";
$PMF_LANG["rightsLanguage"]['addnews'] = "hír hozzáadása";
$PMF_LANG["rightsLanguage"]['editnews'] = "hír szerkesztése";
$PMF_LANG["rightsLanguage"]['delnews'] = "hír törlése";
$PMF_LANG["rightsLanguage"]['addcateg'] = "kategória hozzáadása";
$PMF_LANG["rightsLanguage"]['editcateg'] = "kategória szerkesztése";
$PMF_LANG["rightsLanguage"]['delcateg'] = "kategória törlése";
$PMF_LANG["rightsLanguage"]['passwd'] = "jelszó megváltoztatása";
$PMF_LANG["rightsLanguage"]['editconfig'] = "beállítások szerkesztése";
$PMF_LANG["rightsLanguage"]['addatt'] = "fájl csatolása";
$PMF_LANG["rightsLanguage"]['delatt'] = "csatolt fájl törlése";
$PMF_LANG["rightsLanguage"]['backup'] = "backup készítése";
$PMF_LANG["rightsLanguage"]['restore'] = "backup visszaállítása";
$PMF_LANG["rightsLanguage"]['delquestion'] = "nyitott kérdések törlése";

$PMF_LANG["msgAttachedFiles"] = "csatolt fájlok:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "művelet";
$PMF_LANG["ad_entry_email"] = "email cím:";
$PMF_LANG["ad_entry_allowComments"] = "megjegyzések engedélyezése";
$PMF_LANG["msgWriteNoComment"] = "Nem fűzhetsz megjegyzést ehhez a bejegyzéshez!";
$PMF_LANG["ad_user_realname"] = "teljes név:";
$PMF_LANG["ad_export_generate_pdf"] = "PDF fájl létrehozása";
$PMF_LANG["ad_export_full_faq"] = "A GYIK PFD fájlban: ";
$PMF_LANG["err_bannedIP"] = "Az IP-d ki lett tiltva.";
$PMF_LANG["err_SaveQuestion"] = "A kötelező mezők: <strong>neved</strong>, <strong>email címed</strong> és a <strong>kérdésed</strong>.<br /><br /><a href=\"javascript:history.back();\">egy oldalt visszak</a><br /><br />";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "Betű szín: ";
$PMF_LANG["ad_entry_fontsize"] = "Betű méret: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => "select", 1 => "Language-File");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "Enable automatic content negotiation");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "Title of the FAQ");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "FAQ Version");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "Describtion of the Page");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "Keywords for Spiders");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "Name of the Publisher");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "Emailadress of the Admin");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "Contactinformation");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "Text for the send2friend page");
$LANG_CONF['main.maxAttachmentSize'] = array(0 => "input", 1 => "maximum Size for attachments in Bytes (max. %sByte)");
$LANG_CONF["main.disableAttachments"] = array(0 => "checkbox", 1 => "Link the attachments below the entries?");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "use Tracking?");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "use Adminlog?");
$LANG_CONF["main.ipCheck"] = array(0 => "checkbox", 1 => "Do you want the IP to be checked when checking the UINs in admin.php?");
$LANG_CONF["main.numberOfRecordsPerPage"] = array(0 => "input", 1 => "Number of displayed topics per page");
$LANG_CONF["main.numberOfShownNewsEntries"] = array(0 => "input", 1 => "Number of news articles");
$LANG_CONF['main.bannedIPs'] = array(0 => "area", 1 => "Ban these IPs");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "Activate mod_rewrite support? (default: disabled)");
$LANG_CONF["main.ldapSupport"] = array(0 => "checkbox", 1 => "Do you want to enable LDAP support? (default: disabled)");

$PMF_LANG["ad_categ_new_main_cat"] = "as new main category";
$PMF_LANG["ad_categ_paste_error"] = "Moving this category isn't possible.";
$PMF_LANG["ad_categ_move"] = "move category";
$PMF_LANG["ad_categ_lang"] = "Language";
$PMF_LANG["ad_categ_desc"] = "Description";
$PMF_LANG["ad_categ_change"] = "Change with";

$PMF_LANG["lostPassword"] = "Password forgotten? Click here.";
$PMF_LANG["lostpwd_err_1"] = "Error: Username and e-mail adress not found.";
$PMF_LANG["lostpwd_err_2"] = "Error: Wrong entries!";
$PMF_LANG["lostpwd_text_1"] = "Thank you for requesting your account information.";
$PMF_LANG["lostpwd_text_2"] = "Please set a new personal password in the admin section of your FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "E-Mail was sent.";

$PMF_LANG["ad_xmlrpc_button"] = "Get latest phpMyFAQ version number by web service";
$PMF_LANG["ad_xmlrpc_latest"] = "Latest version available on";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Select category language';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Sitemap';
