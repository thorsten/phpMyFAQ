<?php

/**
 * German language file
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    A. Neufang <B_A_F_F@gmx.de>
 * @author    René-Roger Ziesack <rr-phpmyfaq.de@inf99.de>
 * @copyright 2004-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-19
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

$PMF_LANG['metaCharset'] = "UTF-8";
$PMF_LANG['metaLanguage'] = "de";
$PMF_LANG['language'] = "deutsch";
$PMF_LANG['dir'] = "ltr";
$PMF_LANG['nplurals'] = "2";

// Navigation
$PMF_LANG['msgCategory'] = "Kategorien";
$PMF_LANG['msgShowAllCategories'] = "Alle Kategorien";
$PMF_LANG['msgSearch'] = "Suche";
$PMF_LANG['msgAddContent'] = "FAQ vorschlagen";
$PMF_LANG['msgQuestion'] = "Frage stellen";
$PMF_LANG['msgOpenQuestions'] = "Offene Fragen";
$PMF_LANG['msgHelp'] = "Hilfe";
$PMF_LANG['msgContact'] = "Kontakt";
$PMF_LANG['msgHome'] = "Startseite";
$PMF_LANG['msgNews'] = "News";
$PMF_LANG['msgUserOnline'] = " Besucher online";
$PMF_LANG['msgBack2Home'] = "Zurück zur Startseite";

// Contentpages
$PMF_LANG['msgFullCategories'] = "Kategorien";
$PMF_LANG['msgFullCategoriesIn'] = "Weitere Kategorien in ";
$PMF_LANG['msgSubCategories'] = "Unterkategorien";
$PMF_LANG['msgEntries'] = "Einträge";
$PMF_LANG['msgEntriesIn'] = "Einträge in ";
$PMF_LANG['msgViews'] = "Aufrufe";
$PMF_LANG['msgPage'] = "Seite ";
$PMF_LANG['msgPages'] = " Seiten";
$PMF_LANG['msgPrevious'] = "Vorherige";
$PMF_LANG['msgNext'] = "Weitere";
$PMF_LANG['msgCategoryUp'] = "zur nächst höheren Kategorie zurück";
$PMF_LANG['msgLastUpdateArticle'] = "Letzte Änderung: ";
$PMF_LANG['msgAuthor'] = "Verfasser der FAQ: ";
$PMF_LANG['msgPrinterFriendly'] = "Druckerfreundliche Version";
$PMF_LANG['msgPrintArticle'] = "FAQ ausdrucken";
$PMF_LANG['msgMakeXMLExport'] = "als XML-Datei exportieren";
$PMF_LANG['msgAverageVote'] = "Durchschnittliche Bewertung";
$PMF_LANG['msgVoteUsability'] = "Bewertung der FAQ";
$PMF_LANG['msgVoteFrom'] = "von";
$PMF_LANG['msgVoteBad'] = "vollkommen überflüssig";
$PMF_LANG['msgVoteGood'] = "sehr wertvoll";
$PMF_LANG['msgVotings'] = "Bewertungen";
$PMF_LANG['msgVoteSubmit'] = "FAQ bewerten";
$PMF_LANG['msgVoteThanks'] = "Vielen Dank für die Bewertung!";
$PMF_LANG['msgYouCan'] = "Es ist möglich, diese ";
$PMF_LANG['msgWriteComment'] = "FAQ zu kommentieren.";
$PMF_LANG['msgShowCategory'] = "Inhaltsübersicht: ";
$PMF_LANG['msgCommentBy'] = "Kommentar von ";
$PMF_LANG['msgCommentHeader'] = "Kommentar zur FAQ";
$PMF_LANG['msgYourComment'] = "Kommentar";
$PMF_LANG['msgCommentThanks'] = "Vielen Dank für den Kommentar!";
$PMF_LANG['msgSeeXMLFile'] = "XML-Datei öffnen";
$PMF_LANG['msgSend2Friend'] = "FAQ weiterempfehlen";
$PMF_LANG['msgS2FName'] = "Absender Name";
$PMF_LANG['msgS2FEMail'] = "Absender E-Mail";
$PMF_LANG['msgS2FFriends'] = "Freundinnen und Freunde";
$PMF_LANG['msgS2FEMails'] = ". E-Mailadresse";
$PMF_LANG['msgS2FText'] = "Folgender Text wird gesendet";
$PMF_LANG['msgS2FText2'] = "Unter folgender Adresse ist der Beitrag zu finden";
$PMF_LANG['msgS2FMessage'] = "Eine zusätzliche Nachricht an den/die Empfänger";
$PMF_LANG['msgS2FButton'] = "E-Mails versenden";
$PMF_LANG['msgS2FThx'] = "Vielen Dank für die Empfehlung!";
$PMF_LANG['msgS2FMailSubject'] = "Empfehlung von ";

// Search
$PMF_LANG['msgSearchWord'] = "Suchbegriff";
$PMF_LANG['msgSearchFind'] = "Suchergebnis für ";
$PMF_LANG['msgSearchAmount'] = " Suchergebnis";
$PMF_LANG['msgSearchAmounts'] = " Suchergebnisse";
$PMF_LANG['msgSearchCategory'] = "Kategorie: ";
$PMF_LANG['msgSearchContent'] = "Antwort: ";

// new Content
$PMF_LANG['msgNewContentHeader'] = "Vorschlag für neuen FAQ-Eintrag";
$PMF_LANG['msgNewContentAddon'] = "Ihr Vorschlag erscheint nicht sofort, sondern wird vor der Veröffentlichung von uns überprüft. Pflichtfelder sind Name, E-Mail-Adresse, Kategorie, Frage und Antwort. Die Suchbegriffe bitte nur Kommas trennen.";
$PMF_LANG['msgNewContentName'] = "Name";
$PMF_LANG['msgNewContentMail'] = "E-Mail";
$PMF_LANG['msgNewContentCategory'] = "Kategorie";
$PMF_LANG['msgNewContentTheme'] = "Frage";
$PMF_LANG['msgNewContentArticle'] = "Antwort";
$PMF_LANG['msgNewContentKeywords'] = "Suchbegriffe";
$PMF_LANG['msgNewContentLink'] = "Link zu dieser FAQ";
$PMF_LANG['msgNewContentSubmit'] = "Absenden";
$PMF_LANG['msgInfo'] = "Mehr Informationen unter: ";
$PMF_LANG['msgNewContentThanks'] = "Vielen Dank für diesen Vorschlag!";

// ask Question
$PMF_LANG['msgNewQuestion'] = "Auf dieser Seite können Fragen an die FAQ-Leser gestellt werden und so neue FAQ-Einträge fördern. Nur durch Fragen können wir erfahren, zu welchen Themen Antworten gewünscht werden! Die gestellten Fragen erscheinen in der Kategorie der offenen Fragen.";
$PMF_LANG['msgAskCategory'] = "Kategorie";
$PMF_LANG['msgAskYourQuestion'] = "Frage";
$PMF_LANG['msgAskThx4Mail'] = "Vielen Dank für diese Anfrage.";
$PMF_LANG['msgDate_User'] = "Datum / Verfasser";
$PMF_LANG['msgQuestion2'] = "Frage";
$PMF_LANG['msg2answer'] = "beantworten";
$PMF_LANG['msgQuestionText'] = "Hier sind die Fragen anderer Nutzer zu sehen. Diese können hier beantwortet werden. Der Eintrag wird dadurch auch den FAQ-Beiträgen hinzugefügt.";
$PMF_LANG['msgNoQuestionsAvailable'] = "Derzeit gibt es keine offenen Fragen.";

// Contact
$PMF_LANG['msgContactEMail'] = "E-Mail an den Betreiber";
$PMF_LANG['msgMessage'] = "Anfrage";

// Startseite
$PMF_LANG['msgTopTen'] = "Beliebte FAQ-Beiträge";
$PMF_LANG['msgHomeThereAre'] = "Es sind ";
$PMF_LANG['msgHomeArticlesOnline'] = " FAQ-Beiträge verfügbar.";
$PMF_LANG['msgNoNews'] = "Es gibt derzeit keine News.";
$PMF_LANG['msgLatestArticles'] = "Neueste FAQ-Beiträge";

// E-Mailbenachrichtigung
$PMF_LANG['msgMailThanks'] = "Vielen Dank für den Vorschlag";
$PMF_LANG['msgMailCheck'] = "Es ist ein neuer FAQ-Beitrag vorhanden. Sie können diesen hier oder im Adminbereich überprüfen.";
$PMF_LANG['msgMailContact'] = "Die Anfrage wurde an den Administrator versendet!";

// Fehlermeldungen
$PMF_LANG['err_noDatabase'] = "Keine Datenbankverbindung möglich!";
$PMF_LANG['err_noHeaders'] = "Keine Kategorie gefunden";
$PMF_LANG['err_noArticles'] = "Es gibt noch keine Einträge.";
$PMF_LANG['err_badID'] = "Fehlerhafte ID!";
$PMF_LANG['err_noTopTen'] = "Derzeit sind keine beliebten FAQs verfügbar.";
$PMF_LANG['err_nothingFound'] = "Es wurde kein Eintrag gefunden.";
$PMF_LANG['err_SaveEntries'] = "Pflichtfelder sind Name, E-Mail-Adresse, Kategorie, Frage und Antwort!";
$PMF_LANG['err_SaveComment'] = "Pflichtfelder sind Name, E-Mail-Adresse und Kommentar!";
$PMF_LANG['err_VoteTooMuch'] = "Leider konnte die Bewertung nicht gespeichert werden, da mit der IP bereits bewertet wurde.";
$PMF_LANG['err_noVote'] = "Es wurde keine Bewertung abgegeben!";
$PMF_LANG['err_noMailAdress'] = "Die angegebene E-Mail-Adresse ist nicht korrekt.";
$PMF_LANG['err_sendMail'] = "Pflichtfelder sind u.a. Name und E-Mail-Adresse!";

// Hilfe zur Suche
$PMF_LANG['help_search'] = 'Antwort finden:<br>Mit der Eingabe "Begriff1 Begriff2" können zwei oder mehrere Suchbegriffe nach der Relevanz absteigend suchen lassen.Hinweis: Suchbegriff muss mindestens 4 Zeichen lang sein, kürzere Anfragen werden automatisch abgewiesen.';

// Menü
$PMF_LANG['ad'] = "ADMIN-BEREICH";
$PMF_LANG['ad_menu_user_administration'] = "Benutzerverwaltung";
$PMF_LANG['ad_menu_entry_aprove'] = "FAQs freischalten";
$PMF_LANG['ad_menu_entry_edit'] = "FAQs bearbeiten";
$PMF_LANG["ad_menu_categ_add"] = "Kategorie hinzufügen";
$PMF_LANG['ad_menu_categ_edit'] = "Kategorieverwaltung";
$PMF_LANG['ad_menu_news_add'] = "News hinzufügen";
$PMF_LANG['ad_menu_news_edit'] = "News";
$PMF_LANG['ad_menu_open'] = "Offene Fragen";
$PMF_LANG['ad_menu_stat'] = "Bewertungen";
$PMF_LANG['ad_menu_cookie'] = "Cookies";
$PMF_LANG['ad_menu_session'] = "Benutzer-Protokoll";
$PMF_LANG['ad_menu_adminlog'] = "Admin-Protokoll";
$PMF_LANG['ad_menu_passwd'] = "Passwort ändern";
$PMF_LANG['ad_menu_logout'] = "Ausloggen";
$PMF_LANG['ad_menu_startpage'] = "Startseite";

// Nachrichten
$PMF_LANG['ad_msg_identify'] = "Bitte identifizieren.";
$PMF_LANG['ad_msg_passmatch'] = "Beide Passwörter müssen übereinstimmen!";
$PMF_LANG['ad_msg_savedsuc_1'] = "Das Profil von";
$PMF_LANG['ad_msg_savedsuc_2'] = "wurde erfolgreich gespeichert.";
$PMF_LANG['ad_msg_mysqlerr'] = "Aufgrund eines Datenbankfehlers konnte das Profil nicht gespeichert werden.";
$PMF_LANG['ad_msg_noauth'] = "Hierfür nicht authorisiert.";

// Allgemein
$PMF_LANG['ad_gen_page'] = "Seite";
$PMF_LANG['ad_gen_of'] = "von";
$PMF_LANG['ad_gen_lastpage'] = "vorherige Seite";
$PMF_LANG['ad_gen_nextpage'] = "nächste Seite";
$PMF_LANG['ad_gen_save'] = "Speichern";
$PMF_LANG['ad_gen_reset'] = "Reset";
$PMF_LANG['ad_gen_yes'] = "Ja";
$PMF_LANG['ad_gen_no'] = "Nein";
$PMF_LANG['ad_gen_top'] = "Seitenbeginn";
$PMF_LANG['ad_gen_ncf'] = "Keine Kategorien gefunden";
$PMF_LANG['ad_gen_delete'] = "Löschen";
$PMF_LANG['ad_gen_or'] = "oder";

// Benutzerverwaltung
$PMF_LANG['ad_user'] = "Benutzerverwaltung";
$PMF_LANG['ad_user_username'] = "Loginname";
$PMF_LANG['ad_user_rights'] = "Rechte des Benutzer";
$PMF_LANG['ad_user_edit'] = "Bearbeiten";
$PMF_LANG['ad_user_delete'] = "Löschen";
$PMF_LANG['ad_user_add'] = "Benutzer hinzufügen";
$PMF_LANG['ad_user_profou'] = "Profil des Benutzers";
$PMF_LANG['ad_user_name'] = "Name";
$PMF_LANG['ad_user_password'] = "Passwort";
$PMF_LANG['ad_user_confirm'] = "Bestätigung";
$PMF_LANG['ad_user_del_1'] = "Soll der Benutzer ";
$PMF_LANG['ad_user_del_2'] = " gelöscht werden?";
$PMF_LANG['ad_user_del_3'] = "Sind Sie sicher?";
$PMF_LANG['ad_user_deleted'] = "Der Benutzer wurde erfolgreich gelöscht.";
$PMF_LANG['ad_user_checkall'] = "Alle auswählen";

// Beitragsverwaltung
$PMF_LANG['ad_entry_aor'] = "FAQ Übersicht";
$PMF_LANG['ad_entry_id'] = "ID";
$PMF_LANG['ad_entry_topic'] = "Frage";
$PMF_LANG['ad_entry_action'] = "Aktion";
$PMF_LANG['ad_entry_edit_1'] = "FAQ";
$PMF_LANG['ad_entry_edit_2'] = "bearbeiten";
$PMF_LANG['ad_entry_theme'] = "Frage";
$PMF_LANG['ad_entry_content'] = "Antwort";
$PMF_LANG['ad_entry_keywords'] = "Suchbegriffe";
$PMF_LANG['ad_entry_author'] = "Verfasser";
$PMF_LANG['ad_entry_category'] = "Kategorie";
$PMF_LANG['ad_entry_active'] = "Aktiviert";
$PMF_LANG['ad_entry_date'] = "Datum";
$PMF_LANG["ad_entry_status"] = "Status der FAQ";
$PMF_LANG['ad_entry_changed'] = "Was wurde geändert?";
$PMF_LANG['ad_entry_changelog'] = "Änderungen";
$PMF_LANG['ad_entry_commentby'] = "Kommentar von";
$PMF_LANG['ad_entry_comment'] = "Kommentare";
$PMF_LANG['ad_entry_save'] = "Speichern";
$PMF_LANG['ad_entry_delete'] = "Löschen";
$PMF_LANG['ad_entry_delcom_1'] = "Sicher, dass der Kommentar des Benutzers";
$PMF_LANG['ad_entry_delcom_2'] = "gelöscht werden soll?";
$PMF_LANG['ad_entry_commentdelsuc'] = "Der Kommentar wurde erfolgreich gelöscht.";
$PMF_LANG['ad_entry_back'] = "Zurück zur FAQ";
$PMF_LANG['ad_entry_commentdelfail'] = "Der Kommentar wurde nicht gelöscht.";
$PMF_LANG['ad_entry_savedsuc'] = "Die Änderungen wurden erfolgreich gespeichert.";
$PMF_LANG['ad_entry_savedfail'] = "Ein Datenbankfehler ist aufgetreten.";
$PMF_LANG['ad_entry_del_1'] = "Bist du sicher, dass die FAQ";
$PMF_LANG['ad_entry_del_2'] = "des Benutzers";
$PMF_LANG['ad_entry_del_3'] = "gelöscht werden soll?";
$PMF_LANG['ad_entry_delsuc'] = "Der FAQ-Eintrag erfolgreich gelöscht.";
$PMF_LANG['ad_entry_delfail'] = "Die FAQ-Eintrag wurde nicht gelöscht!";
$PMF_LANG['ad_entry_back'] = "Zurück";

// Newsverwaltung
$PMF_LANG['ad_news_header'] = "Überschrift";
$PMF_LANG['ad_news_text'] = "Text";
$PMF_LANG['ad_news_link_url'] = "Link";
$PMF_LANG['ad_news_link_title'] = "Titel des Links";
$PMF_LANG['ad_news_link_target'] = "Ziel des Links";
$PMF_LANG['ad_news_link_window'] = "Neues Fenster";
$PMF_LANG['ad_news_link_faq'] = "Innerhalb der FAQ";
$PMF_LANG['ad_news_add'] = "News hinzufügen";
$PMF_LANG['ad_news_id'] = "#";
$PMF_LANG['ad_news_headline'] = "Überschrift";
$PMF_LANG['ad_news_date'] = "Datum";
$PMF_LANG['ad_news_action'] = "Aktion";
$PMF_LANG['ad_news_update'] = "bearbeiten";
$PMF_LANG['ad_news_delete'] = "Löschen";
$PMF_LANG['ad_news_nodata'] = "Keine Daten in der Datenbank gefunden.";
$PMF_LANG['ad_news_updatesuc'] = "Der Eintrag wurde erfolgreich gespeichert.";
$PMF_LANG['ad_news_del'] = "Sicher, dass der Eintrag gelöscht werden sollen?";
$PMF_LANG['ad_news_yesdelete'] = "Ja, löschen!";
$PMF_LANG['ad_news_nodelete'] = "Nein!";
$PMF_LANG['ad_news_delsuc'] = "Der Eintrag wurde erfolgreich gelöscht.";
$PMF_LANG['ad_news_updatenews'] = "News-Eintrag bearbeiten";

// Kategorieverwaltung
$PMF_LANG['ad_categ_new'] = "Neue Kategorie hinzufügen";
$PMF_LANG['ad_categ_catnum'] = "Kategorienummer";
$PMF_LANG['ad_categ_subcatnum'] = "Unterkategorienummer";
$PMF_LANG['ad_categ_nya'] = "Noch nicht verfügbar!";
$PMF_LANG['ad_categ_titel'] = "Kategoriename";
$PMF_LANG['ad_categ_add'] = "Kategorie hinzufügen";
$PMF_LANG['ad_categ_existing'] = "Bestehende Kategorien";
$PMF_LANG['ad_categ_id'] = "#";
$PMF_LANG['ad_categ_categ'] = "Kategorie-ID";
$PMF_LANG['ad_categ_subcateg'] = "Unterkategorie-ID";
$PMF_LANG['ad_categ_titel'] = "Kategoriename";
$PMF_LANG['ad_categ_action'] = "Aktion";
$PMF_LANG['ad_categ_update'] = "Bearbeiten";
$PMF_LANG['ad_categ_delete'] = "Löschen";
$PMF_LANG['ad_categ_updatecateg'] = "Kategorie aktualisieren";
$PMF_LANG['ad_categ_nodata'] = "Keine Daten in der Datenbank gefunden.";
$PMF_LANG['ad_categ_remark'] = "Es gilt zu beachten, wenn eine Kategorie gelöscht wird, dass die FAQs der gelöschten Kategorie nicht mehr angezeigt werden. Der FAQ muss dann eine neue Kategorie zugewiesen oder gelöscht werden.";
$PMF_LANG['ad_categ_edit_1'] = "Editiere Kategorie";
$PMF_LANG['ad_categ_edit_2'] = " ";
$PMF_LANG['ad_categ_added'] = "Die Kategorie wurde hinzugefügt.";
$PMF_LANG['ad_categ_updated'] = "Die Kategorie wurde aktualisiert.";
$PMF_LANG['ad_categ_del_yes'] = "Ja, löschen!";
$PMF_LANG['ad_categ_del_no'] = "Nein!";
$PMF_LANG['ad_categ_deletesure'] = "Sicher, dass die Kategorie gelöscht werden soll?";
$PMF_LANG['ad_categ_deleted'] = "Kategorie gelöscht.";

// Cookies
$PMF_LANG['ad_cookie_cookiesuc'] = "Das Cookie wurde erfolgreich gesetzt.";
$PMF_LANG['ad_cookie_already'] = "Es ist bereits ein Cookie gesetzt. Es gibt nun folgende Möglichkeiten";
$PMF_LANG['ad_cookie_again'] = "Cookie erneut setzen";
$PMF_LANG['ad_cookie_delete'] = "Cookie löschen";
$PMF_LANG['ad_cookie_no'] = "Derzeit ist kein Cookie gesetzt. Ein Cookie speichert die Logininformationen, damit diese nicht immer erneut eingeben werden müssen. Es gibt folgende Möglichkeiten";
$PMF_LANG['ad_cookie_set'] = "Cookie setzen";
$PMF_LANG['ad_cookie_deleted'] = "Der Cookie wurde erfolgreich entfernt.";

// Adminlog
$PMF_LANG['ad_adminlog'] = "AdminLog";

// Passwd
$PMF_LANG['ad_passwd_cop'] = "Passwort ändern";
$PMF_LANG['ad_passwd_old'] = "Altes Passwort";
$PMF_LANG['ad_passwd_new'] = "Neues Passwort";
$PMF_LANG['ad_passwd_con'] = "Passwort wiederholen";
$PMF_LANG['ad_passwd_change'] = "Änderung speichern";
$PMF_LANG['ad_passwd_suc'] = "Passwort erfolgreich geändert.";
$PMF_LANG['ad_passwd_remark'] = "ACHTUNG:<br>Das Cookie muß neu gesetzt werden!";
$PMF_LANG['ad_passwd_fail'] = "Das alte Passwort muss korrekt eingegeben werden und beide neuen müssen übereinstimmen.";

// Adduser
$PMF_LANG['ad_adus_adduser'] = "Benutzer hinzufügen";
$PMF_LANG['ad_adus_name'] = "Loginname";
$PMF_LANG['ad_adus_password'] = "Passwort";
$PMF_LANG['ad_adus_add'] = "Hinzufügen";
$PMF_LANG['ad_adus_suc'] = "Der Benutzer wurde erfolgreich hinzugefügt.";
$PMF_LANG['ad_adus_edit'] = "Profil bearbeiten";
$PMF_LANG['ad_adus_dberr'] = "Datenbankfehler!";
$PMF_LANG['ad_adus_exerr'] = "Der Loginname existiert bereits.";

// Sessions
$PMF_LANG['ad_sess_id'] = "ID";
$PMF_LANG['ad_sess_sid'] = "Sitzungs-ID";
$PMF_LANG['ad_sess_ip'] = "IP-Adresse";
$PMF_LANG['ad_sess_time'] = "Zeit";
$PMF_LANG['ad_sess_pageviews'] = "Aktionen";
$PMF_LANG['ad_sess_search'] = "Suche";
$PMF_LANG['ad_sess_sfs'] = "Sitzungssuche";
$PMF_LANG['ad_sess_s_ip'] = "IP";
$PMF_LANG['ad_sess_s_minct'] = "min. Aktionen";
$PMF_LANG['ad_sess_s_date'] = "Datum";
$PMF_LANG['ad_sess_s_after'] = "nach";
$PMF_LANG['ad_sess_s_before'] = "vor";
$PMF_LANG['ad_sess_s_search'] = "Suchen";
$PMF_LANG['ad_sess_session'] = "Sitzung";
$PMF_LANG['ad_sess_r'] = "Suchergebnis für";
$PMF_LANG['ad_sess_referer'] = "Referer";
$PMF_LANG['ad_sess_browser'] = "Webbrowser";
$PMF_LANG['ad_sess_ai_rubrik'] = "Kategorie";
$PMF_LANG['ad_sess_ai_artikel'] = "Artikel";
$PMF_LANG['ad_sess_ai_sb'] = "Suchbegriffe";
$PMF_LANG['ad_sess_ai_sid'] = "Session-ID";
$PMF_LANG['ad_sess_back'] = "Zurück";
$PMF_LANG['ad_sess_noentry'] = "Kein Eintrag";

// Statistik
$PMF_LANG['ad_rs'] = "Bewertungsstatistik";
$PMF_LANG['ad_rs_rating_1'] = "Die Bewertung von";
$PMF_LANG['ad_rs_rating_2'] = "Benutzern sagt";
$PMF_LANG['ad_rs_red'] = "Rot";
$PMF_LANG['ad_rs_green'] = "Grün";
$PMF_LANG['ad_rs_altt'] = "mit einem Durchschnitt kleiner 20%";
$PMF_LANG['ad_rs_ahtf'] = "mit einem Durchschnitt größer 80%";
$PMF_LANG['ad_rs_no'] = "Keine Bewertungen verfügbar.";

// Auth
$PMF_LANG['ad_auth_insert'] = "Bitte den persönlichen Benutzernamen und das Passwort eingeben.";
$PMF_LANG['ad_auth_user'] = "Benutzername";
$PMF_LANG['ad_auth_passwd'] = "Passwort";
$PMF_LANG['ad_auth_ok'] = "OK";
$PMF_LANG['ad_auth_reset'] = "Reset";
$PMF_LANG['ad_auth_fail'] = "Falscher Loginname oder Passwort.";
$PMF_LANG['ad_auth_sess'] = "Diese Sitzungs-ID ist ungültig/ausgelaufen.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG['ad_config_edit'] = "Konfiguration bearbeiten";
$PMF_LANG['ad_config_save'] = "Konfiguration speichern";
$PMF_LANG['ad_config_reset'] = "Zurücksetzen";
$PMF_LANG['ad_config_saved'] = "Die Konfiguration wurde erfolgreich gespeichert.";
$PMF_LANG['ad_menu_editconfig'] = "FAQ-Konfiguration";
$PMF_LANG['ad_att_none'] = "Keine Anhänge vorhanden";
$PMF_LANG['ad_att_add'] = "Neuen Anhang hinzufügen";
$PMF_LANG['ad_entryins_suc'] = "Eintrag erfolgreich erstellt.";
$PMF_LANG['ad_entryins_fail'] = "Leider ist ein Fehler aufgetreten.";
$PMF_LANG['ad_att_del'] = "Löschen";
$PMF_LANG['ad_att_nope'] = "Anhänge sind erst beim Bearbeiten möglich.";
$PMF_LANG['ad_att_delsuc'] = "Der Anhang wurde erfolgreich gelöscht.";
$PMF_LANG['ad_att_delfail'] = "Leider ist ein Fehler beim Löschen des Anhangs aufgetreten.";
$PMF_LANG['ad_entry_add'] = "FAQ erstellen";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG['ad_csv_make'] = "Eine Datensicherung stellt im Grunde ein komplettes Abbild der SQL-Tabellen der FAQ dar. Diese Sicherung stellt immer eine Momentaufnahme dar. Das Format der Sicherung ist eine normale SQL-Datei, man kann eine Rücksicherung also notfalls auch mit Hilfe von Tools wie phpMyAdmin oder ähnlichen Tools vornehmen.";
$PMF_LANG["ad_csv_link"] = "Herunterladen der sicherung";
$PMF_LANG['ad_csv_head'] = "Datensicherung erstellen";
$PMF_LANG['ad_att_addto'] = "Anhang zur FAQ";
$PMF_LANG['ad_att_addto_2'] = "hinzufügen";
$PMF_LANG['ad_att_att'] = "Anhang auswählen";
$PMF_LANG['ad_att_butt'] = "Hochladen";
$PMF_LANG['ad_att_suc'] = "Der Anhang wurde erfolgreich hochgeladen.";
$PMF_LANG['ad_att_fail'] = "Leider ist ein Fehler beim Hochladen des Anhangs aufgetreten.";
$PMF_LANG['ad_att_close'] = "Dieses Fenster schließen";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG['ad_csv_restore'] = "Hier kann eine zuvor erstellte phpMyFAQ-Sicherungsdatei hochgeladen werden. Es gilt zu beachten, dass das Wiedereinspielen einer Sicherung die FAQ auf den Stand zurücksetzt, der beim Erstellen der Sicherung bestand, d.h. die Daten werden ersetzt.";
$PMF_LANG['ad_csv_file'] = "Datei auswählen";
$PMF_LANG['ad_csv_ok'] = "Datei hochladen und einspielen";
$PMF_LANG['ad_csv_linklog'] = "Sicherung der Loggingdaten herunterladen";
$PMF_LANG['ad_csv_linkdat'] = "Sicherung der Daten herunterladen";
$PMF_LANG['ad_csv_head2'] = "Datensicherung einspielen";
$PMF_LANG['ad_csv_no'] = "Dies scheint keine Sicherungsdatei von phpMyFAQ zu sein.";
$PMF_LANG['ad_csv_prepare'] = "Bereite die Datenbankanfragen vor...";
$PMF_LANG['ad_csv_process'] = "Führe die Datenbankabfragen aus...";
$PMF_LANG['ad_csv_of'] = "von";
$PMF_LANG['ad_csv_suc'] = "Anfragen waren erfolgreich.";
$PMF_LANG['ad_csv_backup'] = "Datensicherung";
$PMF_LANG['ad_csv_rest'] = "Wiederherstellung";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Sicherung";
$PMF_LANG["ad_logout"] = "Sitzung erfolgreich beendet.";
$PMF_LANG["ad_news_add"] = "News hinzufügen";
$PMF_LANG["ad_news_edit"] = "News bearbeiten";
$PMF_LANG["ad_cookie"] = "Cookies";
$PMF_LANG["ad_sess_head"] = "Sitzungen anzeigen";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG['ad_menu_stat'] = "Bewertungen";
$PMF_LANG['ad_kateg_add'] = "Hauptkategorie hinzufügen";
$PMF_LANG['ad_kateg_rename'] = "Bearbeiten";
$PMF_LANG['ad_adminlog_date'] = "Datum";
$PMF_LANG['ad_adminlog_user'] = "Benutzer";
$PMF_LANG['ad_adminlog_ip'] = "IP-Adresse";

$PMF_LANG['ad_stat_sess'] = "Sessions";
$PMF_LANG['ad_stat_days'] = "Statistiktage";
$PMF_LANG['ad_stat_vis'] = "Sitzungen (Besuche)";
$PMF_LANG['ad_stat_vpd'] = "Besuche pro Tag";
$PMF_LANG['ad_stat_fien'] = "Erster Eintrag";
$PMF_LANG['ad_stat_laen'] = "Letzter Eintrag";
$PMF_LANG['ad_stat_browse'] = "Besuchsstatistik";
$PMF_LANG['ad_stat_ok'] = "OK";

$PMF_LANG['ad_sess_time'] = "Zeit";
$PMF_LANG['ad_sess_sid'] = "Session-ID";
$PMF_LANG['ad_sess_ip'] = "IP-Adresse";

$PMF_LANG['ad_ques_take'] = "Frage beantworten";
$PMF_LANG['no_cats'] = "Keine Kategorien gefunden!";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG['ad_log_lger'] = "Es ist eine ungültige Loginkombination versucht worden.";
$PMF_LANG['ad_log_sess'] = "Es ist eine Session ausgelaufen.";
$PMF_LANG['ad_log_edit'] = "Es ist das <i>Benutzer bearbeiten</i>-Formular für den folgenden Benutzer aufgerufen worden: ";
$PMF_LANG['ad_log_crea'] = "Es ist das <i>neuer Beitrag</i> Formular aufgerufen worden.";
$PMF_LANG['ad_log_crsa'] = "Es ist ein neuer Beitrag erstellt worden.";
$PMF_LANG['ad_log_ussa'] = "Die Daten des folgenden Benutzers sind aktualisiert worden: ";
$PMF_LANG['ad_log_usde'] = "Der folgende Benutzer ist gelöscht worden: ";
$PMF_LANG['ad_log_beed'] = "Das Editierformular für den folgenden Beitrag ist aufgerufen worden: ";
$PMF_LANG['ad_log_bede'] = "Der folgende Beitrag wurde gelöscht: ";

$PMF_LANG['ad_start_visits'] = "Besuche";
$PMF_LANG['ad_start_articles'] = "FAQs";
$PMF_LANG['ad_start_comments'] = "Kommentare";

// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG['ad_categ_paste'] = "einfügen";
$PMF_LANG['ad_categ_cut'] = "ausschneiden";
$PMF_LANG['ad_categ_copy'] = "kopieren";
$PMF_LANG['ad_categ_process'] = "Bearbeite Kategorien...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG['err_NotAuth'] = "Sie haben keine ausreichende Berechtigung hierfür.";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG['msgPreviusPage'] = "vorherige Seite";
$PMF_LANG['msgNextPage'] = "nächste Seite";
$PMF_LANG['msgPageDoublePoint'] = "Seite: ";
$PMF_LANG['msgMainCategory'] = "Übergeordnete Kategorie";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG['ad_passwdsuc'] = "Das Passwort wurde erfolgreich geändert!";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG['ad_xml_gen'] = "Als XML-Datei exportieren";
$PMF_LANG['ad_entry_locale'] = "Sprache";
$PMF_LANG['msgLanguageSubmit'] = "Sprache ändern";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG['ad_attach_4'] = "Bitte eine Datei auswählen die innerhalb der Maximalgröße von %s Bytes für Attachments liegt.";
$PMF_LANG['ad_menu_export'] = "FAQ exportieren";

$PMF_LANG['rightsLanguage::add_user'] = "Benutzer hinzufügen";
$PMF_LANG['rightsLanguage::edit_user'] = "Benutzer bearbeiten";
$PMF_LANG['rightsLanguage::delete_user'] = "Benutzer löschen";
$PMF_LANG['rightsLanguage::add_faq'] = "FAQ hinzufügen";
$PMF_LANG['rightsLanguage::edit_faq'] = "FAQ bearbeiten";
$PMF_LANG['rightsLanguage::delete_faq'] = "FAQ löschen";
$PMF_LANG['rightsLanguage::viewlog'] = "Protokoll ansehen";
$PMF_LANG['rightsLanguage::adminlog'] = "Admin-Protokoll einsehen";
$PMF_LANG['rightsLanguage::delcomment'] = "Kommentar löschen";
$PMF_LANG['rightsLanguage::addnews'] = "News hinzufügen";
$PMF_LANG['rightsLanguage::editnews'] = "News bearbeiten";
$PMF_LANG['rightsLanguage::delnews'] = "News löschen";
$PMF_LANG['rightsLanguage::addcateg'] = "Kategorie hinzufügen";
$PMF_LANG['rightsLanguage::editcateg'] = "Kategorie editeren";
$PMF_LANG['rightsLanguage::delcateg'] = "Kategorie löschen";
$PMF_LANG['rightsLanguage::passwd'] = "Passwort ändern";
$PMF_LANG['rightsLanguage::editconfig'] = "Konfiguration bearbeiten";
$PMF_LANG['rightsLanguage::addatt'] = "Dateianhänge anfügen";
$PMF_LANG['rightsLanguage::delatt'] = "Dateianhänge löschen";
$PMF_LANG['rightsLanguage::backup'] = "Sicherung erstellen";
$PMF_LANG['rightsLanguage::restore'] = "Sicherung wiederherstellen";
$PMF_LANG['rightsLanguage::delquestion'] = "Offene Fragen löschen";
$PMF_LANG['rightsLanguage::changebtrevs'] = "Revisionen bearbeiten";

$PMF_LANG['msgAttachedFiles'] = "Angehängte Dateien";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG['ad_user_action'] = "Aktion";
$PMF_LANG['ad_entry_email'] = "E-Mail";
$PMF_LANG['ad_entry_allowComments'] = "Kommentare zulassen";
$PMF_LANG['msgWriteNoComment'] = "Kommentieren nicht möglich";
$PMF_LANG['ad_user_realname'] = "Echter Name";
$PMF_LANG['ad_export_generate_pdf'] = "Als PDF-Datei exportieren";
$PMF_LANG['ad_export_full_faq'] = "Die FAQ als PDF-Datei: ";
$PMF_LANG['err_bannedIP'] = "Diese IP ist gesperrt.";
$PMF_LANG['err_SaveQuestion'] = "Pflichtfelder sind Name, E-Mail-Adresse und Frage!";

// added v1.4.0 - 2003-12-04 by Thorsten
$LANG_CONF['main.language'] = ["select", "Sprache"];
$LANG_CONF['main.languageDetection'] = ["checkbox", "Automatische Spracherkennung"];
$LANG_CONF['main.titleFAQ'] = ["input", "Titel der FAQ"];
$LANG_CONF['main.currentVersion'] = ["print", "phpMyFAQ Version"];
$LANG_CONF['main.metaDescription'] = ["input", "Beschreibung der Seite"];
$LANG_CONF['main.metaKeywords'] = ["input", "Keywords für Suchmaschinen"];
$LANG_CONF['main.metaPublisher'] = ["input", "Name des Veröffentlichers"];
$LANG_CONF['main.administrationMail'] = ["input", "E-Mailadresse des Administrators"];
$LANG_CONF['main.contactInformation'] = ["area", "Kontaktdaten / Impressum"];
$LANG_CONF['main.send2friendText'] = ["area", "Text für die Empfehlungs-Seite"];
$LANG_CONF['records.maxAttachmentSize'] = ["input", "Maximalgröße von Anhängen in Bytes (max. %sByte)"];
$LANG_CONF['records.disableAttachments'] = ["checkbox", "Anhänge unter den Beiträgen anzeigen"];
$LANG_CONF['main.enableUserTracking'] = ["checkbox", "User-Tracking aktiviert?"];
$LANG_CONF['main.enableAdminLog'] = ["checkbox", "Admin-Logging aktiviert?"];
$LANG_CONF["main.enableCategoryRestrictions"] = ["checkbox", "Kategoriebeschränkungen aktivieren"];
$LANG_CONF['security.ipCheck'] = ["checkbox", "IP zur Überprüfung im Admin-Bereich nutzen"];
$LANG_CONF['records.numberOfRecordsPerPage'] = ["input", "Anzahl der FAQs pro Seite"];
$LANG_CONF['records.numberOfShownNewsEntries'] = ["input", "Anzahl der angezeigten News"];
$LANG_CONF['security.bannedIPs'] = ["area", "Gesperrte IPs (Bitte mit Leerzeichen trennen)"];
$LANG_CONF['main.enableRewriteRules'] = ["checkbox", "SEO-freundliche URLs aktivieren"];
$LANG_CONF['ldap.ldapSupport'] = ["checkbox", "LDAP-Unterstützung aktivieren"];
$LANG_CONF['main.referenceURL'] = ["input", "URL der FAQ (zB https://www.example.org/faq/)"];
$LANG_CONF['main.urlValidateInterval'] = ["input", "Zeit zwischen den Ajax-Linküberprüfungen (in Sekunden)"];
$LANG_CONF['records.enableVisibilityQuestions'] = ["checkbox", "Sichtbarkeit von neuen Fragen"];
$LANG_CONF['security.permLevel'] = ["select", "Berechtigungsebene"];

$PMF_LANG['ad_categ_new_main_cat'] = "Als neue Hauptkategorie";
$PMF_LANG['ad_categ_paste_error'] = "Diese Kategorie kann hier nicht eingefügt werden.";
$PMF_LANG['ad_categ_move'] = "Kategorie verschieben";
$PMF_LANG['ad_categ_lang'] = "Sprache";
$PMF_LANG['ad_categ_desc'] = "Beschreibung";
$PMF_LANG['ad_categ_change'] = "Austauschen mit";

$PMF_LANG['lostPassword'] = "Passwort vergessen?";
$PMF_LANG['lostpwd_err_1'] = "Fehler: Loginname und E-Mailadresse nicht gefunden.";
$PMF_LANG['lostpwd_err_2'] = "Fehler: Falsche Eingaben!";
$PMF_LANG['lostpwd_text_1'] = "Vielen Dank für die Abfrage deiner Account Informationen.";
$PMF_LANG['lostpwd_text_2'] = "Bitte ein neues Passwort im Adminbereich der FAQ setzen.";
$PMF_LANG['lostpwd_mail_okay'] = "E-Mail wurde gesendet.";

$PMF_LANG['ad_xmlrpc_button'] = "Aktuelle phpMyFAQ Version online abfragen";
$PMF_LANG['ad_xmlrpc_latest'] = "Aktuelle Version auf";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = "Sprache der Kategorie";

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = "Sitemap";

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = "Der Artikel wird zur Zeit überarbeitet und kann leider nicht angezeigt werden.";
$PMF_LANG['msgArticleCategories'] = "Kategorien zu diesem Artikel";

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = "Eindeutige ID";
$PMF_LANG['ad_entry_faq_record'] = "FAQ Eintrag";
$PMF_LANG['ad_entry_new_revision'] = "Neue Revision";
$PMF_LANG['ad_entry_record_administration'] = "FAQ Bearbeitung";
$PMF_LANG['ad_entry_revision'] = "Revision";
$PMF_LANG['ad_changerev'] = "Revisionsauswahl";
$PMF_LANG['msgCaptcha'] = "Bitte gebe den Captcha-Code ein";
$PMF_LANG['msgSelectCategories'] = "Suche in ...";
$PMF_LANG['msgAllCategories'] = "... allen Kategorien";
$PMF_LANG['ad_you_should_update'] = "Ihre phpMyFAQ Installation ist veraltet. Sie sollten auf die neueste Version aktualisieren.";
$PMF_LANG['msgAdvancedSearch'] = "Erweiterte Suche";

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = "Spamschutz";
$LANG_CONF['spam.enableSafeEmail'] = ["checkbox", "Sichere E-Mailadresse anzeigen"];
$LANG_CONF['spam.checkBannedWords'] = ["checkbox", "Bad-Word-Liste aktivieren"];
$LANG_CONF['spam.enableCaptchaCode'] = ["checkbox", "Captcha-Grafiken anzeigen"];
$PMF_LANG['ad_session_expiring'] = "Die Session wird in %d Minuten enden: Wollen Sie weiterarbeiten?";

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = "Session Management";
$PMF_LANG['ad_stat_choose'] = "Auswahl des Monats";
$PMF_LANG['ad_stat_delete'] = "Sofortiges Löschen der selektierten Sessions";

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru
$PMF_LANG['ad_menu_glossary'] = "Glossar";
$PMF_LANG['ad_glossary_add'] = "Glossar-Eintrag hinzufügen";
$PMF_LANG['ad_glossary_item'] = "Begriff";
$PMF_LANG['ad_glossary_definition'] = "Definition";
$PMF_LANG['ad_glossary_save'] = "Eintrag speichern";
$PMF_LANG['ad_glossary_save_success'] = "Der Glossar-Eintrag wurde erfolgreich gespeichert.";
$PMF_LANG['ad_glossary_save_error'] = "Der Glossar-Eintrag wurde nicht gespeichert, weil ein Fehler aufgetreten ist.";
$PMF_LANG['ad_glossary_edit'] = "Eintrag bearbeiten";
$PMF_LANG['ad_glossary_update_success'] = "Der Glossar-Eintrag wurde erfolgreich aktualisiert.";
$PMF_LANG['ad_glossary_update_error'] = "Der Glossar-Eintrag wurde nicht aktualisiert, weil ein Fehler aufgetreten ist.";
$PMF_LANG['ad_glossary_delete'] = "Eintrag löschen";
$PMF_LANG['ad_glossary_delete_success'] = "Der Glossar-Eintrag wurde erfolgreich gelöscht.";
$PMF_LANG['ad_glossary_delete_error'] = "Der Glossar-Eintrag wurde nicht gelöscht, weil ein Fehler aufgetreten ist.";
$PMF_LANG['msgNewQuestionVisible'] = "Dazu muss der Administrator allerdings diese erst freigeben.";
$PMF_LANG['msgQuestionsWaiting'] = "Wartend auf die Freigabe durch den Administrator";
$PMF_LANG['ad_entry_visibility'] = "veröffentlicht";
$PMF_LANG['ad_entry_not_visibility'] = "nicht veröffentlicht";

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = "Bitte geben Sie ein Passwort ein. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] = "Die Passwörter stimmen nicht überein. ";
$PMF_LANG['ad_user_error_loginInvalid'] = "Der ausgewählte Benutzer ist ungültig.";
$PMF_LANG['ad_user_error_noEmail'] = "Bitte geben Sie eine korrekte E-Mailadresse ein. ";
$PMF_LANG['ad_user_error_noRealName'] = "Bitte geben Sie ihren Namen ein. ";
$PMF_LANG['ad_user_error_delete'] = "Der Benutzeraccount kann nicht gelöscht werden. ";
$PMF_LANG['ad_user_error_noId'] = "Keine ID ausgewählt. ";
$PMF_LANG['ad_user_error_protectedAccount'] = "Der Benutzeraccount ist geschützt. ";
$PMF_LANG['ad_user_deleteUser'] = "Lösche Benutzer";
$PMF_LANG['ad_user_status'] = "Status";
$PMF_LANG['ad_user_lastModified'] = "Letzte Änderung";
$PMF_LANG['ad_gen_cancel'] = "Abbrechen";
$PMF_LANG['rightsLanguage::addglossary'] = "Wörterbucheinträge hinzufügen";
$PMF_LANG['rightsLanguage::editglossary'] = "Wörterbucheinträge bearbeiten";
$PMF_LANG['rightsLanguage::delglossary'] = "Wörterbucheinträge löschen";
$PMF_LANG['ad_menu_group_administration'] = "Gruppenverwaltung";
$PMF_LANG['ad_user_loggedin'] = "Sie sind eingeloggt als ";
$PMF_LANG['ad_group_details'] = "Details der Gruppe";
$PMF_LANG['ad_group_add'] = "Gruppe hinzufügen";
$PMF_LANG['ad_group_add_link'] = "Gruppe hinzufügen";
$PMF_LANG['ad_group_name'] = "Name";
$PMF_LANG['ad_group_description'] = "Beschreibung";
$PMF_LANG['ad_group_autoJoin'] = "Automatischer Eintritt";
$PMF_LANG['ad_group_suc'] = "Die Gruppe wurde erfolgreich hinzugefügt.";
$PMF_LANG['ad_group_error_noName'] = "Bitte geben Sie einen Namen für die Gruppe ein.";
$PMF_LANG['ad_group_error_delete'] = "Die Gruppe konnte nicht gelöscht werden.";
$PMF_LANG['ad_group_deleted'] = "Die Gruppe wurde erfolgreich gelöscht.";
$PMF_LANG['ad_group_deleteGroup'] = "Lösche Gruppe";
$PMF_LANG['ad_group_deleteQuestion'] = "Sind Sie sicher, dass Sie diese Gruppe löschen wollen?";
$PMF_LANG['ad_user_uncheckall'] = "Alle abwählen";
$PMF_LANG['ad_group_membership'] = "Gruppenmitgliedschaft";
$PMF_LANG['ad_group_members'] = "Mitglieder";
$PMF_LANG['ad_group_addMember'] = "+";
$PMF_LANG['ad_group_removeMember'] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = "Begrenzung der exportierten FAQ-Inhalte (optional)";
$PMF_LANG['ad_export_cat_downwards'] = "Inklusive Unterkategorien?";
$PMF_LANG['ad_export_type'] = "Exportformat";
$PMF_LANG['ad_export_type_choose'] = "Bitte wählen Sie eines der unterstützten Formate";
$PMF_LANG['ad_export_download_view'] = "Herunterladen oder Inline ansehen?";
$PMF_LANG['ad_export_download'] = "Herunterladen";
$PMF_LANG['ad_export_view'] = "Inline ansehen";
$PMF_LANG['ad_export_gen_xhtml'] = "Als XHTML-Datei exportieren";

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = "Nachricht";
$PMF_LANG['ad_news_author_name'] = "Verfasser";
$PMF_LANG['ad_news_author_email'] = "E-Mail des Verfassers";
$PMF_LANG['ad_news_set_active'] = "Aktivieren";
$PMF_LANG['ad_news_allowComments'] = "Erlaube Kommentare";
$PMF_LANG['ad_news_expiration_window'] = "Nachricht Abblaufdatum (optional)";
$PMF_LANG['ad_news_from'] = "von";
$PMF_LANG['ad_news_to'] = "bis";
$PMF_LANG['ad_news_insertfail'] = "Ein Fehler ist beim Speichern in die Datenbank aufgetreten.";
$PMF_LANG['ad_news_updatefail'] = "Ein Fehler ist beim Aktualisieren des Eintrags in die Datenbank aufgetreten.";
$PMF_LANG['newsShowCurrent'] = "Zeige aktuelle News.";
$PMF_LANG['newsShowArchive'] = "Zeige archivierte News.";
$PMF_LANG['newsArchive'] = " News Archiv";
$PMF_LANG['newsWriteComment'] = "Diesen Eintrag kommentieren";
$PMF_LANG['newsCommentDate'] = "Geschrieben am: ";

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = "Abblaufdatum des Eintrags (optional)";
$PMF_LANG['admin_mainmenu_home'] = "Dashboard";
$PMF_LANG['admin_mainmenu_users'] = "Benutzer";
$PMF_LANG['admin_mainmenu_content'] = "Inhalte";
$PMF_LANG['admin_mainmenu_statistics'] = "Statistiken";
$PMF_LANG['admin_mainmenu_exports'] = "Export";
$PMF_LANG['admin_mainmenu_backup'] = "Datensicherung";
$PMF_LANG['admin_mainmenu_configuration'] = "Konfiguration";
$PMF_LANG['admin_mainmenu_logout'] = "Ausloggen";

// added v2.0.0 - 2006-08-15 by Thorsten
$PMF_LANG['ad_categ_owner'] = "Kategorieverwalter";
$PMF_LANG['adminSection'] = "Administration";
$PMF_LANG['err_expiredArticle'] = "Diese FAQ ist abgelaufen und kann nicht angezeigt werden";
$PMF_LANG['err_expiredNews'] = "Diese Nachricht ist abgelaufen und kann nicht angezeigt werden";
$PMF_LANG['err_inactiveNews'] = "Diese Nachricht werden überarbeitet und kann nicht angezeigt werden";
$PMF_LANG['msgSearchOnAllLanguages'] = "alle Sprachen durchsuchen";
$PMF_LANG['ad_entry_tags'] = "Tags";
$PMF_LANG['msg_tags'] = "Tags";

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = "Verwandte Artikel";
$LANG_CONF['records.numberOfRelatedArticles'] = ["input", "Anzahl der verwandten FAQs"];

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = "Übersetze";
$PMF_LANG['ad_categ_trans_2'] = "Kategorie";
$PMF_LANG['ad_categ_translatecateg'] = "Übersetzung speichern";
$PMF_LANG['ad_categ_translate'] = "Übersetzen";
$PMF_LANG['ad_categ_transalready'] = "Bereits übersetzt in: ";
$PMF_LANG['ad_categ_deletealllang'] = "In alle Sprachen löschen?";
$PMF_LANG['ad_categ_deletethislang'] = "Nur in diese Sprache löschen?";
$PMF_LANG['ad_categ_translated'] = "Die Kategorie ist übersetzt.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG['ad_categ_show'] = "Übersicht";
$PMF_LANG['ad_menu_categ_structure'] = "Übersicht der Kategorien und der Übersetzungen";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = "Benutzerrechte";
$PMF_LANG['ad_entry_grouppermission'] = "Gruppenrechte";
$PMF_LANG['ad_entry_all_users'] = "Zugriff für alle Benutzer";
$PMF_LANG['ad_entry_restricted_users'] = "Zugriff nur für";
$PMF_LANG['ad_entry_all_groups'] = "Zugriff für alle Gruppen";
$PMF_LANG['ad_entry_restricted_groups'] = "Zugriff nur für";
$PMF_LANG['ad_session_expiration'] = "Ablauf der Session";
$PMF_LANG['ad_user_active'] = "aktiv";
$PMF_LANG['ad_user_blocked'] = "geblockt";
$PMF_LANG['ad_user_protected'] = "geschützt";

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'Wählen Sie einen FAQ-Datensatz aus, um ihn als Link einzufügen...';

// added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG['ad_categ_paste2'] = "Einfügen hinter";
$PMF_LANG['ad_categ_remark_move'] = "Das Verschieben zweier Kategorien ist nur innerhalb der gleichen Ebene möglich.";
$PMF_LANG['ad_categ_remark_overview'] = "Die richtige Reihenfolge der Kategorien zeigt sich wenn alle Kategorien in der aktuelle Sprache (erste Spalte) definiert sind.";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = "%d Gäste und %d Angemeldete";
$PMF_LANG['ad_adminlog_del_older_30d'] = "Automatisches Löschen von Logs älter als 30 Tage";
$PMF_LANG['ad_adminlog_delete_success'] = "Die alten Logdateien wurden erfolgreich gelöscht.";
$PMF_LANG['ad_adminlog_delete_failure'] = "Es wurden keine Logs gelöscht, da ein Fehler aufgetreten ist.";

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['ad_quicklinks'] = "Quicklinks";
$PMF_LANG['ad_quick_category'] = "Neue Kategorie hinzufügen";
$PMF_LANG['ad_quick_record'] = "Neue FAQ hinzufügen";
$PMF_LANG['ad_quick_user'] = "Neuen Benutzer anlegen";
$PMF_LANG['ad_quick_group'] = "Neue Benutzergruppe anlegen";

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = "Übersetzung vorschlagen";
$PMF_LANG['msgNewTranslationAddon'] = "Die Übersetzung erscheint nicht sofort, sondern wird vor der Veröffentlichung von uns überprüft. Pflichtfelder sind Name, E-Mail-Adresse, Kategorie, Frage und Antwort. Die Keywords bitte nur mit Leerzeichen trennen.";
$PMF_LANG['msgNewTransSourcePane'] = "Ursprungsbeitrag";
$PMF_LANG['msgNewTranslationPane'] = "Übersetzung";
$PMF_LANG['msgNewTranslationName'] = "Name";
$PMF_LANG['msgNewTranslationMail'] = "E-Mailadresse";
$PMF_LANG['msgNewTranslationKeywords'] = "Schlüsselwörter";
$PMF_LANG['msgNewTranslationSubmit'] = "Vorschlag absenden";
$PMF_LANG['msgTranslate'] = "Übersetzungsvorschlag";
$PMF_LANG['msgTranslateSubmit'] = "Beginne die Übersetzung ...";
$PMF_LANG['msgNewTranslationThanks'] = "Vielen Dank für den Übersetzungsvorschlag!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG['rightsLanguage::addgroup'] = "Gruppen hinzufügen";
$PMF_LANG['rightsLanguage::editgroup'] = "Gruppen bearbeiten";
$PMF_LANG['rightsLanguage::delgroup'] = "Gruppen löschen";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = "Link öffnet im gleichen Fenster";

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = "Kommentare";
$PMF_LANG['ad_comment_administration'] = "Kommentarverwaltung";
$PMF_LANG['ad_comment_faqs'] = "Kommentare in FAQs";
$PMF_LANG['ad_comment_news'] = "Kommentare in News";
$PMF_LANG['msgPDF'] = 'PDF-Version';
$PMF_LANG['ad_groups'] = "Gruppen";

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = ["select", "Sortierung (nach Eigenschaft)"];
$LANG_CONF['records.sortby'] = ["select", "Sortierung (absteigend/aufsteigend)"];
$PMF_LANG['ad_conf_order_id'] = "ID <br>(Standard)";
$PMF_LANG['ad_conf_order_thema'] = "Frage";
$PMF_LANG['ad_conf_order_visits'] = "Anzahl der Besucher";
$PMF_LANG['ad_conf_order_updated'] = "Datum";
$PMF_LANG['ad_conf_order_author'] = "Verfasser";
$PMF_LANG['ad_conf_desc'] = "absteigend";
$PMF_LANG['ad_conf_asc'] = "aufsteigend";
$PMF_LANG['mainControlCenter'] = "Allgemein";
$PMF_LANG['recordsControlCenter'] = "FAQs";

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = ["checkbox", "Neue FAQs sofort sichtbar?"];
$LANG_CONF['records.defaultAllowComments'] = ["checkbox", "Kommentare bei FAQs erlaubt?"];

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = "FAQs in dieser Kategorie";
$PMF_LANG['msgTagSearch'] = "FAQs mit gleichen Tags";
$PMF_LANG['ad_pmf_info'] = "phpMyFAQ Information";
$PMF_LANG['ad_online_info'] = "Online Versionsüberprüfung";
$PMF_LANG['ad_system_info'] = "System Information";

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = "Registrieren";
$PMF_LANG['ad_user_loginname'] = "Loginname";
$PMF_LANG['errorRegistration'] = "Dieses Feld muss ausgefüllt sein!";
$PMF_LANG['submitRegister'] = "Benutzer registrieren";
$PMF_LANG['msgUserData'] = "Notwendige Benutzerinformationen für die Anmeldung";
$PMF_LANG['captchaError'] = "Bitte geben Sie die korrekten CAPTCHA Daten ein!";
$PMF_LANG['msgRegError'] = "Bitte korrigieren Sie die folgenden Fehler";
$PMF_LANG['successMessage'] = "Die Anmeldung war erfolgreich. Sie erhalten in Kürze eine E-Mail mit ihren Daten!";
$PMF_LANG['msgRegThankYou'] = "Danke für die Anmeldung";
$PMF_LANG['emailRegSubject'] = "[%sitename%] Anmeldung: Neuer Benutzer";

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = "Beliebte Suchbegriffe";
$LANG_CONF['main.enableWysiwygEditor'] = ["checkbox", "Aktivierung des WYSIWYG Editors"];

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = "Suchstatistik";
$PMF_LANG['ad_searchstats_search_term'] = "Suchbegriff";
$PMF_LANG['ad_searchstats_search_term_count'] = "Anzahl";
$PMF_LANG['ad_searchstats_search_term_lang'] = "Sprache";
$PMF_LANG['ad_searchstats_search_term_percentage'] = "Anteil";

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = "Wichtig";
$PMF_LANG['ad_entry_sticky'] = "Wichtige FAQ";
$PMF_LANG['stickyRecordsHeader'] = "Wichtige FAQs";

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = "Stoppwörter";
$PMF_LANG['ad_config_stopword_input'] = "Neues Stoppwort hinzufügen";

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = "Nein, es wurde keine passende Antwort gefunden.";
$PMF_LANG['msgSendMailIfNothingIsFound'] = "Ist die gesuchte Antwort oben gelistet?";

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = "Bitte wählen Sie die zu übersetzende Sprache aus";
$PMF_LANG['msgLangDirIsntWritable'] = "Das Verzeichnis mit den Übersetzungsdateien ist nicht beschreibbar.";
$PMF_LANG['ad_menu_translations'] = "Übersetzung";
$PMF_LANG['ad_start_notactive'] = "Wartend auf Freischaltung";

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = "Neue Übersetzung hinzufügen";
$PMF_LANG['msgTransToolLanguage'] = "Sprache";
$PMF_LANG['msgTransToolActions'] = "Aktionen";
$PMF_LANG['msgTransToolWritable'] = "Beschreibbar";
$PMF_LANG['msgEdit'] = "Bearbeiten";
$PMF_LANG['msgDelete'] = "Löschen";
$PMF_LANG['msgYes'] = "ja";
$PMF_LANG['msgNo'] = "nein";
$PMF_LANG['msgTransToolSureDeleteFile'] = "Wollen Sie diese Sprachdatei wirklich löschen?";
$PMF_LANG['msgTransToolFileRemoved'] = "Sprachdatei erfolgreich gelöscht";
$PMF_LANG['msgTransToolErrorRemovingFile'] = "Fehler beim Löschen der Sprachdatei";
$PMF_LANG['msgVariable'] = "Variable";
$PMF_LANG['msgCancel'] = "Abbrechen";
$PMF_LANG['msgSave'] = "Speichern";
$PMF_LANG['msgSaving3Dots'] = "speichern ...";
$PMF_LANG['msgRemoving3Dots'] = "löschen ...";
$PMF_LANG['msgTransToolFileSaved'] = "Sprachdatei erfolgreich gespeichert";
$PMF_LANG['msgTransToolErrorSavingFile'] = "Fehler beim Speichern der Sprachdatei";
$PMF_LANG['msgLanguage'] = "Sprache";
$PMF_LANG['msgTransToolLanguageCharset'] = "Zeichensatz";
$PMF_LANG['msgTransToolLanguageDir'] = "Schriftrichtung";
$PMF_LANG['msgTransToolLanguageDesc'] = "Sprachbeschreibung";
$PMF_LANG['msgAuthor'] = "Verfasser";
$PMF_LANG['msgTransToolAddAuthor'] = "Verfasser hinzufügen";
$PMF_LANG['msgTransToolCreateTranslation'] = "Neue Übersetzung hinzufügen";
$PMF_LANG['msgTransToolTransCreated'] = "Neue Übersetzung erfolgreich erstellt.";
$PMF_LANG['msgTransToolCouldntCreateTrans'] = "Neue Übersetzung konnte nicht erstellt werden.";
$PMF_LANG['msgAdding3Dots'] = "hinzufügen ...";
$PMF_LANG['msgTransToolSendToTeam'] = "An das phpMyFAQ Team senden";
$PMF_LANG['msgSending3Dots'] = "versende ...";
$PMF_LANG['msgTransToolFileSent'] = "Die Sprachdatei wurde erfolgreich an das phpMyFAQ Team gesendet. Vielen Dank dafür!";
$PMF_LANG['msgTransToolErrorSendingFile'] = "Beim Versenden der Sprachdatei ist ein Fehler aufgetreten.";
$PMF_LANG['msgTransToolPercent'] = "Vollständigkeit";

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = ["input", "Pfad zum Speichern der Anhänge.<br><small>Relativer Pfad wird ab Webroot gesucht.</small>"];

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "Die Datei wurde auf dem Server nicht gefunden";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras (plural messages test)
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG['plmsgUserOnline'][0] = "%d Besucher online";
$PMF_LANG["plmsgUserOnline"][1] = "%d Besucher online";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = ["select", "Ausgewähltes Template"];

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = "Entfernen";
$PMF_LANG['msgTransToolLanguageNumberOfPlurals'] = "Anzahl der Pluralformen";
$PMF_LANG['msgTransToolLanguageOnePlural'] = "Diese Sprache hat nur eine Pluralform";
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = "Für %s ist die Unterstützung für Pluralformen deaktiviert (nplurals nicht gesetzt).";

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgHomeArticlesOnline'][0] = "Es ist %d FAQ-Eintrag online.";
$PMF_LANG['plmsgHomeArticlesOnline'][1] = "Es sind %d FAQ-Einträge online.";
$PMF_LANG['plmsgViews'][0] = "%dx gesehen";
$PMF_LANG['plmsgViews'][1] = "%dx gesehen";

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = "%d Gast";
$PMF_LANG['plmsgGuestOnline'][1] = "%d Gäste";
$PMF_LANG['plmsgRegisteredOnline'][0] = " und %d Registrierter";
$PMF_LANG['plmsgRegisteredOnline'][1] = " und %d Registrierte";
$PMF_LANG['plmsgSearchAmount'][0] = "%d Suchergebnis";
$PMF_LANG['plmsgSearchAmount'][1] = "%d Suchergebnisse";
$PMF_LANG['plmsgPagesTotal'][0] = " %d Seite";
$PMF_LANG['plmsgPagesTotal'][1] = " %d Seiten";
$PMF_LANG['plmsgVotes'][0] = "%d Abstimmung";
$PMF_LANG['plmsgVotes'][1] = "%d Abstimmungen";
$PMF_LANG['plmsgEntries'][0] = "%d FAQ";
$PMF_LANG['plmsgEntries'][1] = "%d FAQs";

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG['rightsLanguage::addtranslation'] = "Übersetzung hinzufügen";
$PMF_LANG['rightsLanguage::edittranslation'] = "Übersetzung bearbeiten";
$PMF_LANG['rightsLanguage::deltranslation'] = "Übersetzung löschen";
$PMF_LANG['rightsLanguage::approverec'] = "Eintrag freigeben";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF['records.enableAttachmentEncryption'] = ["checkbox", "Verschlüsselung der Anhänge"];
$LANG_CONF['records.defaultAttachmentEncKey'] = ["input", "Standardschlüssel für Verschlüsselung<br/><small style=\"color: red\">Warnung: Nach dem Aktivieren der Verschlüsselung nicht mehr ändern!</small>"];

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = "Aktualisieren";
$PMF_LANG['ad_you_shouldnt_update'] = "Sie nutzen die aktuelle Version von phpMyFAQ. Eine Aktualisierung ist nicht notwendig.";
$LANG_CONF['security.useSslForLogins'] = ["checkbox", "Logins nur über SSL/TLS erlauben? "];
$PMF_LANG['msgSecureSwitch'] = "Zum sicheren Login wechseln";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = 'Bitte beachten Sie, dass wir keine Dateien schreiben werden, bevor Sie nicht auf die Schaltfläche Speichern klicken.';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'Seite %d Puffer erfolgreich aufgezeichnet';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Fehleraufnahmeseite %d Puffer';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'Aufzeichnungsseite %d Puffer';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = "aktiviert";

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'Der Anhang ist ungültig, bitte informieren Sie den Admin';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms'] = ["input", "Anzahl der beliebtesten Suchbegriffe"];
$LANG_CONF['records.orderingPopularFaqs'] = ["select", "Sortierung der TOP-FAQ"];
$PMF_LANG['list_all_users'] = "Alle Benutzer anzeigen";

$PMF_LANG['records.orderingPopularFaqs.visits'] = "nach Anzahl der Besucher";
$PMF_LANG['records.orderingPopularFaqs.voting'] = "nach Bewertung der Besucher";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = "Bitte Begriffe mit Komma trennen.";

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = "aktualisieren";
$PMF_LANG['msgKeepFaqDate'] = "behalten";
$PMF_LANG['msgEditFaqDat'] = "ändern";
$LANG_CONF['main.optionalMailAddress'] = ["checkbox", "Angabe der E-Mailadresse als Pflichtfeld "];

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = ["select", "Sortierung nach Relevanz"];
$LANG_CONF['search.enableRelevance'] = ["checkbox", "Support für Relevanz? "];
$PMF_LANG['searchControlCenter'] = "Suche";
$PMF_LANG['search.relevance.thema-content-keywords'] = "Frage - Antwort - Schlüsselwörter";
$PMF_LANG['search.relevance.thema-keywords-content'] = "Frage - Schlüsselwörter - Antwort";
$PMF_LANG['search.relevance.content-thema-keywords'] = "Antwort - Frage - Schlüsselwörter";
$PMF_LANG['search.relevance.content-keywords-thema'] = "Antwort - Schlüsselwörter - Frage";
$PMF_LANG['search.relevance.keywords-content-thema'] = "Schlüsselwörter - Antwort - Frage";
$PMF_LANG['search.relevance.keywords-thema-content'] = "Schlüsselwörter - Frage - Antwort";

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = "Einloggen";
$PMF_LANG['socialNetworksControlCenter'] = "Social Networks";
$LANG_CONF['socialnetworks.enableTwitterSupport'] = ["checkbox", "Twitter Unterstützung "];
$LANG_CONF['socialnetworks.twitterConsumerKey'] = ["input", "Twitter Consumer Key"];
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = ["input", "Twitter Consumer Secret"];

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = ["input", "Twitter Access Token Key"];
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = ["input", "Twitter Access Token Secret"];

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG['ad_menu_attachments'] = "Anhänge";
$PMF_LANG['ad_menu_attachment_admin'] = "Anhang Administration";
$PMF_LANG['msgAttachmentsFilename'] = "Dateiname";
$PMF_LANG['msgAttachmentsFilesize'] = "Dateigröße";
$PMF_LANG['msgAttachmentsMimeType'] = "MIME Typ";
$PMF_LANG['msgAttachmentsWannaDelete'] = "Sind Sie sicher, dass Sie diesen Anhang löschen wollen?";
$PMF_LANG['msgAttachmentsDeleted'] = "Anhang erfolgreich gelöscht.";

// added v2.7.0-alpha2 - 2011-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = "Reports";
$PMF_LANG['ad_stat_report_fields'] = "Felder";
$PMF_LANG['ad_stat_report_category'] = "Kategorie";
$PMF_LANG['ad_stat_report_sub_category'] = "Unterkategorie";
$PMF_LANG['ad_stat_report_translations'] = "Übersetzungen";
$PMF_LANG['ad_stat_report_language'] = "Sprache";
$PMF_LANG['ad_stat_report_id'] = "ID";
$PMF_LANG['ad_stat_report_sticky'] = "Wichtige FAQ";
$PMF_LANG['ad_stat_report_title'] = "Frage";
$PMF_LANG['ad_stat_report_creation_date'] = "Datum";
$PMF_LANG['ad_stat_report_owner'] = "Autor";
$PMF_LANG['ad_stat_report_last_modified_person'] = "Letzter Autor";
$PMF_LANG['ad_stat_report_url'] = "URL";
$PMF_LANG['ad_stat_report_visits'] = "Anzahl Besuche";
$PMF_LANG['ad_stat_report_make_report'] = "Erstelle Report";
$PMF_LANG['ad_stat_report_make_csv'] = "CSV-Export";

// added v2.7.0-alpha2 - 2011-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = "Registrierung neuer Benutzer";
$PMF_LANG['msgRegistrationCredentials'] = "Um sich anzumelden, muss dein Name, dein Loginname und eine korrekte E-Mailadresse eingegeben werden.";
$PMF_LANG['msgRegistrationNote'] = "Nach der erfolgreichen Anmeldung erhälst du eine Antwort über Freischaltung deiner Anmeldung.";

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = "Änderungshistorie";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = ["checkbox", "Aktiviere Single Sign On Unterstützung "];
$LANG_CONF['security.ssoLogoutRedirect'] = ["input", "Single Sign On Weiterleitungs-Service URL beim Ausloggen"];
$LANG_CONF['main.dateFormat'] = ["input", "Datumsformat (Standard: Y-m-d H:i)"];
$LANG_CONF['security.enableLoginOnly'] = ["checkbox", "Komplett geschützte FAQ"];

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = "Sicherheit";
$PMF_LANG['ad_search_delsuc'] = "Der Suchbegriff wurde erfolgreich gelöscht";
$PMF_LANG['ad_search_delfail'] = "Der Suchbegriff konnte nicht gelöscht werden.";

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = 'Über diese FAQ';
$LANG_CONF['security.useSslOnly'] = ['checkbox', 'FAQ nur mit SSL/TLS nutzen '];
$PMF_LANG['msgTableOfContent'] = 'Inhaltsverzeichnis';

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG['msgExportAllFaqs'] = "FAQ als PDF speichern";
$PMF_LANG['ad_online_verification'] = "Online-Verifikation";
$PMF_LANG['ad_verification_button'] = "phpMyFAQ-Installation online überprüfen";
$PMF_LANG['ad_verification_notokay'] = "Diese phpMyFAQ-Installation hat lokale Änderungen";
$PMF_LANG['ad_verification_okay'] = "Diese phpMyFAQ-Installation wurde erfolgreich überprüft.";

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = 'FAQs suchen';

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF['records.enableCloseQuestion'] = ["checkbox", "Offene Frage nach Beantwortung schließen?"];
$LANG_CONF['records.enableDeleteQuestion'] = ["checkbox", "Offene Frage nach Beantwortung löschen?"];
$PMF_LANG['msg2answerFAQ'] = "Answered";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG['headerUserControlPanel'] = 'Persönlicher Bereich';

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG['rememberMe'] = 'Anmeldung merken';
$PMF_LANG['ad_menu_instances'] = "FAQ Multi-Sites";

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = 'FAQs inaktiv';
$LANG_CONF['main.maintenanceMode'] = ["checkbox", "FAQ in Wartungs-Modus"];
$PMF_LANG['msgMode'] = "Modus";
$PMF_LANG['msgMaintenanceMode'] = "FAQ ist im Wartungs-Modus";
$PMF_LANG['msgOnlineMode'] = "FAQ ist online";

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG['msgShowMore'] = "mehr zeigen";
$PMF_LANG['msgQuestionAnswered'] = "Frage beantwortet";
$PMF_LANG['msgMessageQuestionAnswered'] = "Deine Frage bei %s wurde beantwortet. Hier kommst du zur Antwort";

// added v2.8.0-alpha3 - 2012-11-03 by Thorsten
$PMF_LANG['rightsLanguage::addattachment'] = "Anhänge hinzufügen";
$PMF_LANG['rightsLanguage::editattachment'] = "Anhänge bearbeiten";
$PMF_LANG['rightsLanguage::delattachment'] = "Anhänge löschen";
$PMF_LANG['rightsLanguage::dlattachment'] = "Anhänge herunterladen";
$PMF_LANG['rightsLanguage::reports'] = "Reports erstellen";
$PMF_LANG['rightsLanguage::addfaq'] = "FAQs im Frontend hinzufügen";
$PMF_LANG['rightsLanguage::addquestion'] = "Fragen im Frontend hinzufügen";
$PMF_LANG['rightsLanguage::addcomment'] = "Kommentare im Frontend hinzufügen";
$PMF_LANG['rightsLanguage::editinstances'] = "Mulit-Sites bearbeiten";
$PMF_LANG['rightsLanguage::addinstances'] = "Multi-Sites hinzufügen";
$PMF_LANG['rightsLanguage::delinstances'] = "Multi-Sites löschen";
$PMF_LANG['rightsLanguage::export'] = "FAQs exportieren";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF['records.randomSort'] = ["checkbox", "Zufällige Sortierung der FAQs "];
$LANG_CONF['main.enableWysiwygEditorFrontend'] = ["checkbox", "Aktivierung des WYSIWYG Editors im Frontend "];

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF['main.enableGravatarSupport'] = ["checkbox", "Gravatar Unterstützung"];

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG['ad_stopwords_desc'] = "Bitte wählen Sie eine Sprache aus, um neue Stopwörter hinzuzufügen oder zu bearbeiten.";
$PMF_LANG['ad_visits_per_day'] = "Besucher pro Tag";

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG['ad_instance_add'] = "Neue phpMyFAQ Multisite Installation hinzufügen";
$PMF_LANG['ad_instance_error_notwritable'] = "Der Ordner /multisite ist nicht schreibbar.";
$PMF_LANG['ad_instance_url'] = "Instanz URL";
$PMF_LANG['ad_instance_path'] = "Instanz Pfad";
$PMF_LANG['ad_instance_name'] = "Instanz Name";
$PMF_LANG['ad_instance_email'] = "Admin E-Mailadresse";
$PMF_LANG['ad_instance_admin'] = "Admin Loginname";
$PMF_LANG['ad_instance_password'] = "Admin Passwort";
$PMF_LANG['ad_instance_hint'] = "Achtung: Die Erstellung einer neuen phpMyFAQ Instanz dauert einige Sekunden!";
$PMF_LANG['ad_instance_button'] = "Instanz speichern";
$PMF_LANG['ad_instance_error_cannotdelete'] = "Kann die Instanz nicht löschen ";
$PMF_LANG['ad_instance_config'] = "Instanz-Konfiguration";

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG['msgAboutThisNews'] = "Über diese Nachricht";

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG['msgAccessDenied'] = "Zugriff verweigert.";

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG['msgSeeFAQinFrontend'] = 'Zur FAQ im Frontend';

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG['msgRelatedTags'] = 'Suchwort hinzufügen';
$PMF_LANG['msgPopularTags'] = 'Beliebte Tags';
$LANG_CONF['search.enableHighlighting'] = ["checkbox", "Gefundene Wörter hervorheben"];
$LANG_CONF['records.allowCommentsForGuests'] = ["checkbox", "Erlaube Kommentare von Gästen "];
$LANG_CONF['records.allowQuestionsForGuests'] = ["checkbox", "Erlaube Fragen von Gästen "];
$LANG_CONF['records.allowNewFaqsForGuests'] = ["checkbox", "Erlaube neue FAQs von Gästen "];
$PMF_LANG['ad_searchterm_del'] = 'Alle gespeicherten Suchwörter löschen';
$PMF_LANG["ad_searchterm_del_suc"] = 'Erfolgreiche Löschung aller Suchbegriffe.';
$PMF_LANG["ad_searchterm_del_err"] = 'Konnte nicht alle Suchbegriffe löschen.';
$LANG_CONF['records.hideEmptyCategories'] = ["checkbox", "Leere Kategorien verbergen "];
$LANG_CONF['search.searchForSolutionId'] = ["checkbox", "Suche nach Solution ID "];
$LANG_CONF['socialnetworks.disableAll'] = ["checkbox", "Social Network Unterstützung deaktivieren "];
$LANG_CONF['main.enableGzipCompression'] = ["checkbox", "Aktiviere GZIP Kompression"];

// added v2.9.0-alpha2 - 2014-08-16 by Thorsten
$PMF_LANG['ad_tag_delete_success'] = "Der Tag wurde erfolgreich gelöscht.";
$PMF_LANG['ad_tag_delete_error'] = "Der Tag wurde nicht gelöscht, weil ein Fehler aufgetreten ist.";
$PMF_LANG['seoCenter'] = "SEO";
$LANG_CONF['seo.metaTagsHome'] = ["select", "HTML Meta Tags auf Startseite"];
$LANG_CONF['seo.metaTagsFaqs'] = ["select", "HTML Meta Tags auf FAQ-Seiten"];
$LANG_CONF['seo.metaTagsCategories'] = ["select", "HTML Meta Tags für Kategorien"];
$LANG_CONF['seo.metaTagsPages'] = ["select", "HTML Meta Tags für statische Seiten"];
$LANG_CONF['seo.metaTagsAdmin'] = ["select", "HTML Meta Tags für Admin-Seiten"];
$PMF_LANG['msgMatchingQuestions'] = "Die folgenden Ergebnisse könnten Ihre Frage beantworten";
$PMF_LANG['msgFinishSubmission'] = "Wenn keine der Vorschläge übereinstimmt, können Sie nun die Frage absenden.";
$LANG_CONF['spam.manualActivation'] = ['checkbox', 'Aktiviere Nutzer manuell'];

// added v2.9.0-alpha2 - 2014-10-13 by Christopher Andrews ( Chris--A )
$PMF_LANG['mailControlCenter'] = 'E-Mail';
$LANG_CONF['mail.remoteSMTP'] = ['checkbox', 'Verwendung eines externen SMTP Server'];
$LANG_CONF['mail.remoteSMTPServer'] = ['input', 'SMTP Server'];
$LANG_CONF['mail.remoteSMTPUsername'] = ['input', 'SMTP Username'];
$LANG_CONF['mail.remoteSMTPPassword'] = ['password', 'SMTP Passwort'];
$LANG_CONF['security.enableRegistration'] = ['checkbox', 'Erlaube Registrierung externer Besucher'];

// added v2.9.0-alpha3 - 2015-02-08 by Thorsten
$LANG_CONF['main.customPdfHeader'] = ['area', 'Eigener PDF Header (HTML erlaubt)'];
$LANG_CONF['main.customPdfFooter'] = ['area', 'Eigener PDF Footer (HTML erlaubt)'];
$LANG_CONF['records.allowDownloadsForGuests'] = ['checkbox', 'Erlaube Downloads von Gästen'];
$PMF_LANG['ad_msgNoteAboutPasswords'] = "Achtung! Beim Ausfüllen der Passwortfelder überschreiben Sie die Passwörter des Benutzers.";
$PMF_LANG['ad_delete_all_votings'] = "Alle Bewertungen löschen";
$PMF_LANG['ad_categ_moderator'] = "Moderatoren";
$PMF_LANG['ad_clear_all_visits'] = "Alle Besuche zurücksetzen";
$PMF_LANG['ad_reset_visits_success'] = 'Die Besuche wurden erfolgreich zurückgesetzt';
$LANG_CONF['main.enableMarkdownEditor'] = ['checkbox', 'Aktivierung des Markdown Editors'];

// added v2.9.0-beta - 2015-09-27 by Thorsten
$PMF_LANG['faqOverview'] = 'FAQ Übersicht';
$PMF_LANG['ad_dir_missing'] = 'Der Ordner %s fehlt.';
$LANG_CONF['main.enableSmartAnswering'] = ['checkbox', 'Aktivierung von Smart Answering bei Benutzerfragen'];

// added v2.9.0-beta2 - 2015-12-23 by Thorsten
$LANG_CONF['search.enableElasticsearch'] = ['checkbox', 'Aktiviere Elasticsearch Unterstützung'];
$PMF_LANG['ad_menu_elasticsearch'] = 'Elasticsearch Konfiguration';
$PMF_LANG['ad_es_create_index'] = 'Erstelle Suchindex';
$PMF_LANG['ad_es_drop_index'] = 'Lösche Suchindex';
$PMF_LANG['ad_es_bulk_index'] = 'Komplett-Import';
$PMF_LANG['ad_es_create_index_success'] = 'Der Elasticsearch Suchindex erfolgreich erstellt.';
$PMF_LANG['ad_es_create_import_success'] = 'Der Elasticsearch Import war erfolgreich.';
$PMF_LANG['ad_es_drop_index_success'] = 'Der Elasticsearch Suchindex erfolgreich gelöscht.';
$PMF_LANG['ad_export_generate_json'] = 'Als JSON-Datei exportieren';
$PMF_LANG['ad_media_name_search'] = 'Suche nach Mediennamen';

// added v2.9.0-RC - 2016-02-19 by Thorsten
$PMF_LANG['ad_admin_notes'] = 'Private Notizen';
$PMF_LANG['ad_admin_notes_hint'] = '%s (nur für Editoren sichtbar)';

// added v2.9.10 - 2018-02-17 by Thorsten
$PMF_LANG['ad_quick_entry'] = 'Neue FAQ in dieser Kategorie anlegen';

// added 2.10.0-alpha - 2016-08-08 by Thorsten
$LANG_CONF['ldap.ldap_mapping.name'] = ['input', 'LDAP Mapping für den Namen, "cn" bei Nutzung eines ADS'];
$LANG_CONF['ldap.ldap_mapping.username'] = ['input', 'LDAP Mapping für den Usernamen, "samAccountName" bei Nutzung eines ADS'];
$LANG_CONF['ldap.ldap_mapping.mail'] = ['input', 'LDAP Mapping für E-Mmail, "mail" bei Nutzung eines ADS'];
$LANG_CONF['ldap.ldap_mapping.memberOf'] = ['input', 'LDAP Mapping für "member of" bei Nutzung von LDAP Gruppen'];
$LANG_CONF['ldap.ldap_use_domain_prefix'] = ['checkbox', 'LDAP Domänenprefix, z.B. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'] = ['input', 'LDAP Protokoll Version (Standard: 3)'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_REFERRALS'] = ['input', 'LDAP Verweise (Standard: 0)'];
$LANG_CONF['ldap.ldap_use_memberOf'] = ['checkbox', 'Unterstützung für LDAP Gruppen, z.B. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_use_sasl'] = ['checkbox', 'Unterstützung für LDAP mit SASL'];
$LANG_CONF['ldap.ldap_use_multiple_servers'] = ['checkbox', 'Unterstützung für multiple LDAP Server'];
$LANG_CONF['ldap.ldap_use_anonymous_login'] = ['checkbox', 'Unterstützung für anonyme LDAP Verbindungen'];
$LANG_CONF['ldap.ldap_use_dynamic_login'] = ['checkbox', 'Unterstützung für dynamisches User Binding'];
$LANG_CONF['ldap.ldap_dynamic_login_attribute'] = ['input', 'LDAP Attribut bei dynamisches User Binding, "uid" bei Nutzung eines ADS'];
$LANG_CONF['seo.enableXMLSitemap'] = ['checkbox', 'Aktiviere XML Sitemap'];
$PMF_LANG['ad_category_image'] = 'Kategorie-Bild';
$PMF_LANG['ad_user_show_home'] = "Auf der Startseite anzeigen";

// added v.2.10.0-alpha - 2017-11-09 by Brian Potter (BrianPotter)
$PMF_LANG['ad_view_faq'] = 'FAQ ansehen';

// added 3.0.0-alpha - 2018-01-04 by Thorsten
$LANG_CONF['main.enableCategoryRestrictions'] = ['checkbox', 'Aktiviere Kategoriebeschränkungen'];
$LANG_CONF['main.enableSendToFriend'] = ['checkbox', 'Aktiviere Weiterempfehlung'];
$PMF_LANG['msgUserRemovalText'] = 'Sie können die Löschung Ihres Accounts und Ihrer persönlichen Daten beantragen. Eine E-Mail wird an das Admin-Team gesendet. Das Team wird Ihren Account, Ihre Kommentare und Fragen löschen. Da es sich um einen manuellen Prozess handelt, kann es bis zu 24 Stunden dauern. Danach erhalten Sie eine Löschbestätigung per E-Mail.';
$PMF_LANG['msgUserRemoval'] = "Antrag zur Löschung des Benutzers";
$PMF_LANG['ad_menu_RequestRemove'] = "Benutzer löschen";
$PMF_LANG['msgContactRemove'] = "Antrag auf Entfernung des Benutzers beim Admin Team";
$PMF_LANG['msgContactPrivacyNote'] = "Bitte beachten sie unsere";
$PMF_LANG['msgPrivacyNote'] = "Datenschutzerklärung";

// added 3.0.0-alpha2 - 2018-03-27 by Thorsten
$LANG_CONF['main.enableAutoUpdateHint'] = ['checkbox', 'Automatischer Check neuer Versionen'];
$PMF_LANG['ad_user_is_superadmin'] = 'Super-Admin';
$PMF_LANG['ad_user_overwrite_passwd'] = 'Überschreibe Passwort';
$LANG_CONF['records.enableAutoRevisions'] = ['checkbox', 'Versionierung für jede FAQ-Änderung'];
$PMF_LANG['rightsLanguage::view_faqs'] = 'FAQs lesen';
$PMF_LANG['rightsLanguage::view_categories'] = 'Kategorien lesen';
$PMF_LANG['rightsLanguage::view_sections'] = 'Bereiche lesen';
$PMF_LANG['rightsLanguage::view_news'] = 'Neuigkeiten lesen';
$PMF_LANG['rightsLanguage::add_section'] = 'Bereiche hinzufügen';
$PMF_LANG['rightsLanguage::edit_section'] = 'Bereiche bearbeiten';
$PMF_LANG['rightsLanguage::delete_section'] = 'Bereiche löschen';
$PMF_LANG['rightsLanguage::administrate_sections'] = 'Bereiche administrieren';
$PMF_LANG['rightsLanguage::administrate_groups'] = 'Gruppen administrieren';
$PMF_LANG['ad_group_rights'] = 'Rechte der Gruppe';
$PMF_LANG['ad_menu_meta'] = 'Template-Metadaten';
$PMF_LANG['ad_meta_add'] = 'Template-Metadaten hinzufügen';
$PMF_LANG['ad_meta_page_id'] = 'Seitentyp';
$PMF_LANG['ad_meta_type'] = 'Inhaltstyp';
$PMF_LANG['ad_meta_content'] = 'Inhalt';
$PMF_LANG['ad_meta_copy_snippet'] = 'Code-Snippet für Templates kopieren';
$PMF_LANG['rightsLanguage::viewadminlink'] = 'Link zur Administration sichtbar';

// added v3.0.0-beta.3 - 2019-09-22 by Thorsten
$LANG_CONF['mail.remoteSMTPPort'] = ['input', 'SMTP Server Port'];
$LANG_CONF['mail.remoteSMTPEncryption'] = ['input', 'SMTP Server Verschlüsselung'];
$PMF_LANG['ad_record_faq'] = 'Frage und Antwort';
$PMF_LANG['ad_record_permissions'] = 'Berechtigungen';
$PMF_LANG['loginPageMessage'] = 'Login für ';

// added v3.0.5 - 2020-10-03 by Thorsten
$PMF_LANG['ad_menu_faq_meta'] = 'FAQ-Metadaten';

// added v3.0.8 - 2021-01-22
$LANG_CONF['main.privacyURL'] = ['input', 'URL zum Datenschutzhinweis'];

// added v3.1.0-alpha - 2020-03-27 by Thorsten
$PMF_LANG['ad_user_data_is_visible'] = 'Benutzerdaten sollen öffentlich sichtbar sein';
$PMF_LANG['ad_user_is_visible'] = 'Sichtbar';
$PMF_LANG['ad_categ_save_order'] = 'Die neue Sortierung wurde erfolgreich gespeichert.';
$PMF_LANG['ad_add_user_change_password'] = 'Der Nutzer muss nach dem ersten Login sein Passwort ändern.';
$LANG_CONF['api.enableAccess'] = ['checkbox', 'REST API aktiviert'];
$LANG_CONF['api.apiClientToken'] = ['input', 'REST API Client Token'];
$LANG_CONF['security.domainWhiteListForRegistrations'] = ['area', 'Erlaubte Domains bei Registrierungen'];
$LANG_CONF['security.loginWithEmailAddress'] = ['checkbox', 'Login nur mit E-Mailadresse'];

// added v3.2.0-alpha - 2022-09-10 by Thorsten
$PMF_LANG['msgSignInWithMicrosoft'] = 'Mit Microsoft anmelden';
$LANG_CONF['security.enableSignInWithMicrosoft'] = ['checkbox', 'Aktiviere Anmeldung mit Microsoft (Azure AD)'];
$LANG_CONF['main.enableAskQuestions'] = ['checkbox', 'Aktiviere "Frage stellen"'];
$LANG_CONF['main.enableNotifications'] = ['checkbox', 'Aktiviere Benachrichtigungen'];
$LANG_CONF['mail.sendTestEmail'] = ['button', 'Sende eine E-Mail an den Administrator über SMTP'];
$PMF_LANG['mail.sendTestEmail'] = 'Sende eine E-Mail an den Administrator';
$PMF_LANG['msgGoToCategory'] = 'Zur Kategorie';
$LANG_CONF['security.enableGoogleReCaptchaV2'] = ['checkbox', 'Aktiviere unsichtbares Google ReCAPTCHA v2'];
$LANG_CONF['security.googleReCaptchaV2SiteKey'] = ['input', 'Google ReCAPTCHA v2 Website-Schlüssel'];
$LANG_CONF['security.googleReCaptchaV2SecretKey'] = ['input', 'Google ReCAPTCHA v2 Geheimer Schlüssel'];

// added v3.2.0-alpha - 2023-03-11 by Jan
$PMF_LANG['msgTwofactorEnabled'] = "2-Faktor-Authentifizierung aktivieren";
$PMF_LANG['msgTwofactorConfig'] = "2-Faktor-Authentifizierung konfigurieren";
$PMF_LANG['msgTwofactorConfigModelTitle'] = "Konfiguration der 2-Faktor-Authentifizierung";
$PMF_LANG['qr_code_secret_alt'] = "QR-Code Secret-Key";
$PMF_LANG['msgTwofactorNewSecret'] = "Aktuelles Secret löschen?";
$PMF_LANG['msgTwofactorTokenModelTitle'] = "2-Faktor-Authentifizierung: Token eingeben";
$PMF_LANG['msgEnterTwofactorToken'] = "Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein.";
$PMF_LANG['msgTwofactorCheck'] = "Prüfen";
$PMF_LANG['msgTwofactorErrorToken'] = "Der eingegebene Code war falsch!";
$PMF_LANG['ad_user_overwrite_twofactor'] = "2-Faktor-Authentifizierung überschreiben";

// added v3.2.0-alpha.2 - 2023-04-06 by Thorsten
$PMF_LANG['msgRedirect'] = 'Du wirst in 5 Sekunden automatisch weitergeleitet.';
$PMF_LANG['msgCategoryMissingButTranslationAvailable'] = 'Es wurde keine Kategorie in der gewählten Sprache gefunden, aber du kannst folgende Sprachen auswählen:';
$PMF_LANG['msgCategoryDescription'] = 'Hier findest du eine Übersicht aller Kategorien mit der Anzahl der FAQs.';
$PMF_LANG['msgSubCategoryContent'] = 'Wähle eine Hauptkategorie aus.';
$PMF_LANG['ad_open_question_deleted'] = 'Die Frage wurde erfolgreich gelöscht.';
$LANG_CONF['mail.remoteSMTPDisableTLSPeerVerification'] = ['checkbox', 'SMTP TLS Peer Verifizierung deaktivieren (nicht empfohlen)'];

// added v3.2.0-beta.2 - 2023-05-03 by Jan
$LANG_CONF['main.contactInformationHTML'] = ['checkbox', 'Kontaktinformationen/Impressum als HTML?'];

// added v3.2.0-RC - 2023-05-18 by Thorsten
$PMF_LANG['msgAuthenticationSource'] = 'Auth-Dienst';

// added v3.2.0-RC - 2023-05-27 by Jan
$LANG_CONF['spam.mailAddressInExport'] = ['checkbox', 'E-Mail-Adresse im Export anzeigen'];
$PMF_LANG['msgNewQuestionAdded'] = 'Es wurde eine neue Frage hinzugefügt. Sie können diese hier oder im Adminbereich überprüfen:';

return $PMF_LANG;
