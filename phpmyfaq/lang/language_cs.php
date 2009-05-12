<?php
/**
 * $Id: language_cs.php,v 1.31 2008-04-02 18:33:58 thorstenr Exp $
 *
 * Czech language file
 *
 * @author      Petr Silon <petr.silon@xtel.cz>
 * @since       2008-03-2008
 * @copyright   (c) 2008 phpMyFAQ Team
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

$PMF_LANG["metaCharset"] = "windows-1250";
$PMF_LANG["metaLanguage"] = "cs";
$PMF_LANG["language"] = "Czech";
$PMF_LANG["dir"] = "ltr"; // ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)

// Navigation
$PMF_LANG["msgCategory"] = "Kategorie";
$PMF_LANG["msgShowAllCategories"] = "Zobrazit všechny kategorie";
$PMF_LANG["msgSearch"] = "Vyhledávání";
$PMF_LANG["msgAddContent"] = "Navrhnout dotaz";
$PMF_LANG["msgQuestion"] = "Zeptat se";
$PMF_LANG["msgOpenQuestions"] = "Nezodpovìzené";
$PMF_LANG["msgHelp"] = "Nápovìda";
$PMF_LANG["msgContact"] = "Kontakt";
$PMF_LANG["msgHome"] = "Úvodní stránka FAQ";
$PMF_LANG["msgNews"] = "Nové";
$PMF_LANG["msgUserOnline"] = " Pøipojených uživatelù";
$PMF_LANG["msgXMLExport"] = "XML soubor";
$PMF_LANG["msgBack2Home"] = "zpìt na Hlavní stranu";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategorie a dotazy";
$PMF_LANG["msgFullCategoriesIn"] = "Kategorie s dotazy v ";
$PMF_LANG["msgSubCategories"] = "Podkategorie";
$PMF_LANG["msgEntries"] = "dotazù";
$PMF_LANG["msgEntriesIn"] = "Dotazy v kategorii ";
$PMF_LANG["msgViews"] = "zobrazení";
$PMF_LANG["msgPage"] = "Strana ";
$PMF_LANG["msgPages"] = "Stran";
$PMF_LANG["msgPrevious"] = "pøedchozí";
$PMF_LANG["msgNext"] = "další";
$PMF_LANG["msgCategoryUp"] = "o kategorii výše";
$PMF_LANG["msgLastUpdateArticle"] = "Aktualizováno: ";
$PMF_LANG["msgAuthor"] = "Autor: ";
$PMF_LANG["msgPrinterFriendly"] = "Verze pro tisk";
$PMF_LANG["msgPrintArticle"] = "Vytisknout tento dotaz";
$PMF_LANG["msgMakeXMLExport"] = "Exportovat jako XML soubor";
$PMF_LANG["msgAverageVote"] = "Prùmìrné hodnocení:";
$PMF_LANG["msgVoteUseability"] = "Mùžete ohodnotit tuto odpovìï:";
$PMF_LANG["msgVoteFrom"] = "z";
$PMF_LANG["msgVoteBad"] = "naprosto nepoužitelná";
$PMF_LANG["msgVoteGood"] = "velmi užiteèná";
$PMF_LANG["msgVotings"] = "Hodnocení";
$PMF_LANG["msgVoteSubmit"] = "Hodnotit";
$PMF_LANG["msgVoteThanks"] = "Dìkujeme za vaše hodnocení!";
$PMF_LANG["msgYouCan"] = "Mùžete pøidat ";
$PMF_LANG["msgWriteComment"] = "komentáø k odpovìdi";
$PMF_LANG["msgShowCategory"] = "Pøehled obsahu: ";
$PMF_LANG["msgCommentBy"] = "Komentáø od ";
$PMF_LANG["msgCommentHeader"] = "Komentáø k tomuto záznamu";
$PMF_LANG["msgYourComment"] = "Komentáø:";
$PMF_LANG["msgCommentThanks"] = "Dìkujeme za komentáø!";
$PMF_LANG["msgSeeXMLFile"] = "otevøi XML-Soubor";
$PMF_LANG["msgSend2Friend"] = "Poslat pøíteli";
$PMF_LANG["msgS2FName"] = "Jméno:";
$PMF_LANG["msgS2FEMail"] = "E-mail adresa:";
$PMF_LANG["msgS2FFriends"] = "Vaši pøátelé:";
$PMF_LANG["msgS2FEMails"] = ". e-mail adresa:";
$PMF_LANG["msgS2FText"] = "Bude odeslán následující text:";
$PMF_LANG["msgS2FText2"] = "";
$PMF_LANG["msgS2FMessage"] = "Dodateèná zpráva pro vaše pøátele:";
$PMF_LANG["msgS2FButton"] = "Odeslat";
$PMF_LANG["msgS2FThx"] = "Dìkujeme za vaše doporuèení!";
$PMF_LANG["msgS2FMailSubject"] = "Doporuèení od ";

// Search
$PMF_LANG["msgSearchWord"] = "Vyhledávání";
$PMF_LANG["msgSearchFind"] = "Hledat ve výsledcích ";
$PMF_LANG["msgSearchAmount"] = " hledat výsledek";
$PMF_LANG["msgSearchAmounts"] = " hledat výsledky";
$PMF_LANG["msgSearchCategory"] = "Kategorie: ";
$PMF_LANG["msgSearchContent"] = "Obsah: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Navrhnout nový dotaz do FAQ";
$PMF_LANG["msgNewContentAddon"] = "Váš návrh dotazu a odpovìï bude v databázi FAQ zveøejnìna po zpracování pracovníkem podpory spoleènosti XTEL. <br />
Povinná pole jsou <strong>jméno</strong>, <strong>e-mailová adresa</strong>, <strong>kategorie</strong>, <strong>pøedmìt</strong> a <strong>dotaz</strong>. <br />Klíèová slova prosím oddìlujte pouze mezerou.
<br /><br />
<strong style=\"color: Red;\">POZOR:</strong> Tento formuláø <strong>neslouží</strong> pro kontakt na technickou podporu! Lze jím pouze navrhnout nový dotaz, o kterém si myslíte, že chybí v této FAQ databázi.
<br /><br />Kontakt na technickou podporu je <a href=\"podpora\">www.xtel.cz/podpora</a><br /><br />";
$PMF_LANG["msgNewContentUBB"] = "<p>Pro vaše dotazy mùžete použít BB kód <a href=\"help/ubbcode.php\" target=\"_blank\">Nápovìda o BB kódech</a></p>";
$PMF_LANG["msgNewContentName"] = "Jméno:";
$PMF_LANG["msgNewContentMail"] = "E-mailová adresa:";
$PMF_LANG["msgNewContentCategory"] = "Kategorie";
$PMF_LANG["msgNewContentTheme"] = "Pøedmìt:";
$PMF_LANG["msgNewContentArticle"] = "Dotaz:";
$PMF_LANG["msgNewContentKeywords"] = "Klíèová slova:";
$PMF_LANG["msgNewContentLink"] = "Odkaz pro tento dotaz";
$PMF_LANG["msgNewContentSubmit"] = "Odeslat";
$PMF_LANG["msgInfo"] = "Více informací: ";
$PMF_LANG["msgNewContentThanks"] = "Dìkujeme za váš návrh dotazu do FAQ!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Momentálnì nejsou žádné nezodpovìzené dotazy.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Napište svùj dotaz:";
$PMF_LANG["msgAskCategory"] = "Kategorie dotazu";
$PMF_LANG["msgAskYourQuestion"] = "Dotaz:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>Dìkujeme za váš e-mail!</h2>";
$PMF_LANG["msgDate_User"] = "Datum / Uživatel";
$PMF_LANG["msgQuestion2"] = "Otázka";
$PMF_LANG["msg2answer"] = "Odpovìdìt";
$PMF_LANG["msgQuestionText"] = "Zde jsou dotazy ostatních uživatelù. Odpovíte-li na nìkteré z nich, vaše odpovìdi zde mohou být zveøejnìny.";

// Help
$PMF_LANG["msgHelpText"] = "<p>Struktura FAQ (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) = <strong>Èasto Kladených Dotazù</strong> je naprosto jednoduchá. <br>
Mùžete buïto procházet jednotlivé <strong><a href=\"?action=show\">kategorie</a></strong> nebo použít <strong><a href=\"?action=search\">prohledávání FAQ</a></strong> pomocí klíèových slov.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "E-mail správci:";
$PMF_LANG["msgMessage"] = "Zpráva:";

// Startsite
$PMF_LANG["msgNews"] = "Novinky";
$PMF_LANG["msgTopTen"] = "TOP 10";
$PMF_LANG["msgHomeThereAre"] = "Ve FAQ je ";
$PMF_LANG["msgHomeArticlesOnline"] = " záznamù";
$PMF_LANG["msgNoNews"] = "Žádné novinky";
$PMF_LANG["msgLatestArticles"] = "Pìt nejnovìjších dotazù:";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Dìkujeme za Váš návrh do FAQ.";
$PMF_LANG["msgMailCheck"] = "Ve FAQ je nový záznam! \nProsím zkontroluj admin sekci!";
$PMF_LANG["msgMailContact"] = "Vaše zpráva byla odeslána administrátorovi.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "Není dostupné spojení s databází.";
$PMF_LANG["err_noHeaders"] = "Žádná kategorie nenalezena.";
$PMF_LANG["err_noArticles"] = "<p>Žádné záznamy nenalezeny.</p>";
$PMF_LANG["err_badID"] = "<p>Chybné ID.</p>";
$PMF_LANG["err_noTopTen"] = "<p>Žádné Top 10 není k dispozici.</p>";
$PMF_LANG["err_nothingFound"] = "<p>Žádné záznamy nenalezeny.</p>";
$PMF_LANG["err_SaveEntries"] = "Povinná pole jsou <strong>jméno</strong>, <strong>e-mailová adresa</strong>, <strong>kategorie</strong>, <strong>pøedmìt</strong> a <strong>dotaz</strong>!<br /><br />\n<a href=\"javascript:history.back();\">O stránku zpìt</a><br /><br />\n";
$PMF_LANG["err_SaveComment"] = "Povinná pole jsou <strong>jméno</strong>, <strong>e-mailová adresa</strong> a <strong>komentáø</strong>!<br /><br />\n<a href=\"javascript:history.back();\">O stránku zpìt</a><br /><br />\n";
$PMF_LANG["err_VoteTooMuch"] = "<p>Opakované hlasování se nepoèítá. <a href=\"javascript:history.back();\">Kliknìte zde</a> pro návrat.</p>";
$PMF_LANG["err_noVote"] = "<p><strong>Není hodnocení!</strong> <a href=\"javascript:history.back();\">Prosím kliknìte zde</a> pro hlasování.</p>";
$PMF_LANG["err_noMailAdress"] = "Adresa není správná.<br /><a href=\"javascript:history.back();\">zpìt</a>";
$PMF_LANG["err_sendMail"] = "Povinná pole jsou <strong>jméno</strong>, <strong>email adresa</strong> a <strong>dotaz</strong>!<br /><br />\n<a href=\"javascript:history.back();\">O stránku zpìt</a><br /><br />\n";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "Prohledávejte databázi FAQ - Èasto Kladených Dotazù, tak jak jste zvyklí z bìžných vyhledávaèù. <br />Pro zvýšení relevance nalezených odpovìdí pøispìje použití více klíèových slov ve vašem dotazu (napø. KlíèovéSlovo1 KlíèovéSlovo2).<br /><br />";

// Menü
$PMF_LANG["ad"] = "ADMIN SEKCE";
$PMF_LANG["ad_menu_user_administration"] = "Správa Uživatelù";
$PMF_LANG["ad_menu_entry_aprove"] = "Schvalování Záznamù";
$PMF_LANG["ad_menu_entry_edit"] = "Editace Záznamù";
$PMF_LANG["ad_menu_categ_add"] = "Pøidej Kategorii";
$PMF_LANG["ad_menu_categ_edit"] = "Edituj Kategorii";
$PMF_LANG["ad_menu_news_add"] = "Pøidej Novinky";
$PMF_LANG["ad_menu_news_edit"] = "Edituj Novinky";
$PMF_LANG["ad_menu_open"] = "Edituj nezodpovìzené";
$PMF_LANG["ad_menu_stat"] = "Statistiky";
$PMF_LANG["ad_menu_cookie"] = "Cookies";
$PMF_LANG["ad_menu_session"] = "Zobraz Seance";
$PMF_LANG["ad_menu_adminlog"] = "Zobraz Adminlog";
$PMF_LANG["ad_menu_passwd"] = "Zmìò Heslo";
$PMF_LANG["ad_menu_logout"] = "Odhlaš se";
$PMF_LANG["ad_menu_startpage"] = "Úvodní strana";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Prosím identifikujte se.";
$PMF_LANG["ad_msg_passmatch"] = "Obì hesla musí být <strong>stejná</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profil ";
$PMF_LANG["ad_msg_savedsuc_2"] = "byl úspìšnì uložen.";
$PMF_LANG["ad_msg_mysqlerr"] = "Kvùli <strong>chybì databáze</strong> nemùže být profil uložen.";
$PMF_LANG["ad_msg_noauth"] = "Nemáte oprávnìní.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Strana";
$PMF_LANG["ad_gen_of"] = " ";
$PMF_LANG["ad_gen_lastpage"] = "Pøedchozí Strana";
$PMF_LANG["ad_gen_nextpage"] = "Další Strana";
$PMF_LANG["ad_gen_save"] = "Ulož";
$PMF_LANG["ad_gen_reset"] = "Reset";
$PMF_LANG["ad_gen_yes"] = "Ano";
$PMF_LANG["ad_gen_no"] = "Ne";
$PMF_LANG["ad_gen_top"] = "Vrchol stránky";
$PMF_LANG["ad_gen_ncf"] = "Žádná kategorie nenalezena!";
$PMF_LANG["ad_gen_delete"] = "Smaž";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "Administrace Uživatelù";
$PMF_LANG["ad_user_username"] = "Registrovaní Uživatelé";
$PMF_LANG["ad_user_rights"] = "Uživatelská Práva";
$PMF_LANG["ad_user_edit"] = "edituj";
$PMF_LANG["ad_user_delete"] = "smaž";
$PMF_LANG["ad_user_add"] = "Pøidej Uživatele";
$PMF_LANG["ad_user_profou"] = "Profil Uživatele";
$PMF_LANG["ad_user_name"] = "Jméno";
$PMF_LANG["ad_user_password"] = "Heslo";
$PMF_LANG["ad_user_confirm"] = "Potvrï";
$PMF_LANG["ad_user_rights"] = "Práva";
$PMF_LANG["ad_user_del_1"] = "Uživatel";
$PMF_LANG["ad_user_del_2"] = "má být smazán?";
$PMF_LANG["ad_user_del_3"] = "Urèitì?";
$PMF_LANG["ad_user_deleted"] = "Uživatel byl úspìšnì vymazán.";
$PMF_LANG["ad_user_checkall"] = "Select all"; 

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "Administrace Záznamù";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Téma";
$PMF_LANG["ad_entry_action"] = "Akce";
$PMF_LANG["ad_entry_edit_1"] = "Edituj Záznam";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Téma:";
$PMF_LANG["ad_entry_content"] = "Obsah:";
$PMF_LANG["ad_entry_keywords"] = "Klíèová slova:";
$PMF_LANG["ad_entry_author"] = "Autor:";
$PMF_LANG["ad_entry_category"] = "Kategorie:";
$PMF_LANG["ad_entry_active"] = "Aktivní?";
$PMF_LANG["ad_entry_date"] = "Datum:";
$PMF_LANG["ad_entry_changed"] = "Zmìnìno?";
$PMF_LANG["ad_entry_changelog"] = "Changelog:";
$PMF_LANG["ad_entry_commentby"] = "Komentáø od";
$PMF_LANG["ad_entry_comment"] = "Komentáøe:";
$PMF_LANG["ad_entry_save"] = "Ulož";
$PMF_LANG["ad_entry_delete"] = "smaž";
$PMF_LANG["ad_entry_delcom_1"] = "Urèitì má být komentáø uživatele";
$PMF_LANG["ad_entry_delcom_2"] = "vymazán?";
$PMF_LANG["ad_entry_commentdelsuc"] = "Komentáø byl <strong>úspìšnì</strong> vymazán.";
$PMF_LANG["ad_entry_back"] = "Zpìt k èlánku";
$PMF_LANG["ad_entry_commentdelfail"] = "Komentáø <strong>nebyl</strong> smazán.";
$PMF_LANG["ad_entry_savedsuc"] = "Zmìny byly <strong>uloženy</strong>.";
$PMF_LANG["ad_entry_savedfail"] = "Bohužel došlo k <strong>chybì databáze</strong>.";
$PMF_LANG["ad_entry_del_1"] = "Urèitì má být téma";
$PMF_LANG["ad_entry_del_2"] = " ";
$PMF_LANG["ad_entry_del_3"] = "smazáno?";
$PMF_LANG["ad_entry_delsuc"] = "Záznam <strong>úspìšnì</strong> smazán.";
$PMF_LANG["ad_entry_delfail"] = "Záznam <strong>nebyl smazán</strong>!";
$PMF_LANG["ad_entry_back"] = "Zpìt";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "Nadpis";
$PMF_LANG["ad_news_text"] = "Text Záznamu";
$PMF_LANG["ad_news_link_url"] = "Odkaz: (<strong>without http://</strong>)!";
$PMF_LANG["ad_news_link_title"] = "Název odkazu:";
$PMF_LANG["ad_news_link_target"] = "Cíl odkazu";
$PMF_LANG["ad_news_link_window"] = "Odkaz otevøe nové okno";
$PMF_LANG["ad_news_link_faq"] = "Odkaz uvnitø FAQ";
$PMF_LANG["ad_news_add"] = "Pøidej novinky";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Nadpis";
$PMF_LANG["ad_news_date"] = "Datum";
$PMF_LANG["ad_news_action"] = "Akce";
$PMF_LANG["ad_news_update"] = "aktualizuj";
$PMF_LANG["ad_news_delete"] = "smaž";
$PMF_LANG["ad_news_nodata"] = "Data nenalezena.";
$PMF_LANG["ad_news_updatesuc"] = "Novinky byly aktualizovány.";
$PMF_LANG["ad_news_del"] = "Urèitì chcete smazat tuto novinku?";
$PMF_LANG["ad_news_yesdelete"] = "ano, smaž!";
$PMF_LANG["ad_news_nodelete"] = "ne!";
$PMF_LANG["ad_news_delsuc"] = "Novinka smazána.";
$PMF_LANG["ad_news_updatenews"] = "Aktualizuj novinky";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Pøidej novou kategorii";
$PMF_LANG["ad_categ_catnum"] = "Èíslo kategorie:";
$PMF_LANG["ad_categ_subcatnum"] = "Èíslo podkategorie:";
$PMF_LANG["ad_categ_nya"] = "<em>zatím není k dispozici!</em>";
$PMF_LANG["ad_categ_titel"] = "Název Kategorie:";
$PMF_LANG["ad_categ_add"] = "Pøidej Kategorii";
$PMF_LANG["ad_categ_existing"] = "Existující Kategorie";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategorie";
$PMF_LANG["ad_categ_subcateg"] = "Podkategorie";
$PMF_LANG["ad_categ_titel"] = "Jméno kategorie";
$PMF_LANG["ad_categ_action"] = "Akce";
$PMF_LANG["ad_categ_update"] = "aktualizuj";
$PMF_LANG["ad_categ_delete"] = "smaž";
$PMF_LANG["ad_categ_updatecateg"] = "Aktualizuj Kategorii";
$PMF_LANG["ad_categ_nodata"] = "Data nenalezena";
$PMF_LANG["ad_categ_remark"] = "Prosím uvìdomte si, že existující záznamy nebudou nadále viditelné, pokud smažete kategorii. Musíte buïto pøiøadit k èlánku novou kategorii, nebo jej vymazat.";
$PMF_LANG["ad_categ_edit_1"] = "Edituj";
$PMF_LANG["ad_categ_edit_2"] = "Kategorie";
$PMF_LANG["ad_categ_add"] = "pøidej Kategorii";
$PMF_LANG["ad_categ_added"] = "Kategorie byla pøidána.";
$PMF_LANG["ad_categ_updated"] = "Kategorie byla zaktualizována.";
$PMF_LANG["ad_categ_del_yes"] = "ano, smaž!";
$PMF_LANG["ad_categ_del_no"] = "ne!";
$PMF_LANG["ad_categ_deletesure"] = "Urèitì chcete smazat kategorii?";
$PMF_LANG["ad_categ_deleted"] = "Kategorie smazána.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "Cookie <strong>úspìšnì</strong> nastaveno.";
$PMF_LANG["ad_cookie_already"] = "Cookie již bylo nastaveno. Nyní máte následující možnosti:";
$PMF_LANG["ad_cookie_again"] = "Nastav cookie znovu";
$PMF_LANG["ad_cookie_delete"] = "smaž cookie";
$PMF_LANG["ad_cookie_no"] = "Žádné cookie ještì nebylo uloženo. S cookie si mùžete uložit svoje pøihlašování, takže pøíštì si svoje uživatelské detaily nemusíte pamatovat. Nyní máte následující možnosti:";
$PMF_LANG["ad_cookie_set"] = "Nastav cookie";
$PMF_LANG["ad_cookie_deleted"] = "Cookie úspìšnì smazáno.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "AdminLog";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Zmìò svoje Heslo";
$PMF_LANG["ad_passwd_old"] = "Staré heslo:";
$PMF_LANG["ad_passwd_new"] = "Nové heslo:";
$PMF_LANG["ad_passwd_con"] = "Potvrï:";
$PMF_LANG["ad_passwd_change"] = "Zmìò heslo";
$PMF_LANG["ad_passwd_suc"] = "Heslo úspìšnì zmìnìno.";
$PMF_LANG["ad_passwd_remark"] = "<strong>POZOR:</strong><br />Cookie musí být znovu nastaveno!";
$PMF_LANG["ad_passwd_fail"] = "Staré heslo <strong>musí</strong> být napsáno správnì a obì nová se musí <strong>shodovat</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Pøidej Uživatele:";
$PMF_LANG["ad_adus_name"] = "Jméno:";
$PMF_LANG["ad_adus_password"] = "Heslo:";
$PMF_LANG["ad_adus_add"] = "Pøidej uživatele";
$PMF_LANG["ad_adus_suc"] = "Uživatel <strong>úspìšnì</strong> pøidán.";
$PMF_LANG["ad_adus_edit"] = "Edituj profil";
$PMF_LANG["ad_adus_dberr"] = "<strong>chyba databáze!</strong>";
$PMF_LANG["ad_adus_exerr"] = "Uživatel již <strong>existuje</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "ID Seance";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Èas";
$PMF_LANG["ad_sess_pageviews"] = "PageViews";
$PMF_LANG["ad_sess_search"] = "Hledej";
$PMF_LANG["ad_sess_sfs"] = "Hledej seance";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. akcí:";
$PMF_LANG["ad_sess_s_date"] = "Datum";
$PMF_LANG["ad_sess_s_after"] = "po";
$PMF_LANG["ad_sess_s_before"] = "pøed";
$PMF_LANG["ad_sess_s_search"] = "Hledej";
$PMF_LANG["ad_sess_session"] = "Seance";
$PMF_LANG["ad_sess_r"] = "Hledej ve výsledcích ";
$PMF_LANG["ad_sess_referer"] = "Referer:";
$PMF_LANG["ad_sess_browser"] = "Prohlížeè:";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategorie:";
$PMF_LANG["ad_sess_ai_artikel"] = "Záznam:";
$PMF_LANG["ad_sess_ai_sb"] = "Hledané øetìzce:";
$PMF_LANG["ad_sess_ai_sid"] = "ID Seance:";
$PMF_LANG["ad_sess_back"] = "Zpìt";

// Statistik
$PMF_LANG["ad_rs"] = "Statistiky hodnocení";
$PMF_LANG["ad_rs_rating_1"] = "Klasifikace ";
$PMF_LANG["ad_rs_rating_2"] = "uživatelù ukazuje:";
$PMF_LANG["ad_rs_red"] = "Èervenou";
$PMF_LANG["ad_rs_green"] = "Zelenou";
$PMF_LANG["ad_rs_altt"] = "s prùmìrem nižším než 2";
$PMF_LANG["ad_rs_ahtf"] = "s prùmìrem vyšším než 4";
$PMF_LANG["ad_rs_no"] = "Klasifikace není k dispozici";

// Auth
$PMF_LANG["ad_auth_insert"] = "Prosím uveïte svoje uživatelské jméno a heslo.";
$PMF_LANG["ad_auth_user"] = "Jméno:";
$PMF_LANG["ad_auth_passwd"] = "Heslo:";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Reset";
$PMF_LANG["ad_auth_fail"] = "Uživatelské jméno nebo heslo nesouhlasí.";
$PMF_LANG["ad_auth_sess"] = "The Sessions ID is passed.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Edituj nastavení";
$PMF_LANG["ad_config_save"] = "Ulož nastavení";
$PMF_LANG["ad_config_reset"] = "Reset";
$PMF_LANG["ad_config_saved"] = "Nastavení bylo úspìšnì uloženo.";
$PMF_LANG["ad_menu_editconfig"] = "Edituj nastavení";
$PMF_LANG["ad_att_none"] = "Žádné pøílohy nejsou k dispozici";
$PMF_LANG["ad_att_att"] = "Pøílohy:";
$PMF_LANG["ad_att_add"] = "Pøilož soubor";
$PMF_LANG["ad_entryins_suc"] = "Záznam byl úspìšnì uložen.";
$PMF_LANG["ad_entryins_fail"] = "Došlo k chybì.";
$PMF_LANG["ad_att_del"] = "Smaž";
$PMF_LANG["ad_att_nope"] = "Pøílohy mohou být vkládány jen pøi editování.";
$PMF_LANG["ad_att_delsuc"] = "Pøíloha byla úspìšnì smazána.";
$PMF_LANG["ad_att_delfail"] = "Pøi mazání pøílohy došlo k chybì.";
$PMF_LANG["ad_entry_add"] = "Pøidej Záznam";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "Záloha je kompletním obrazem obsahu databáze. Záloha by se mìla vytváøet alespoò jednou mìsíènì. Formát zálohy je MySQL transaction file, který lze importovat nástrojem phpMyAdmin nebo v pøíkazové øádce klienta MySQL.";
$PMF_LANG["ad_csv_link"] = "Stáhni zálohu";
$PMF_LANG["ad_csv_head"] = "Vytvoø zálohu";
$PMF_LANG["ad_att_addto"] = "Pøidej k záznamu pøílohu";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Soubor:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "Pøíloha byla úspìšnì vložena.";
$PMF_LANG["ad_att_fail"] = "Pøi vkládání pøílohy došlo k chybì.";
$PMF_LANG["ad_att_close"] = "Zavøi toto okno";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Zde mùžete obnovit data ze zálohy vytvoøené v phpmyFAQ. Existující data budou pøepsána!";
$PMF_LANG["ad_csv_file"] = "Soubor";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "backup LOGs";
$PMF_LANG["ad_csv_linkdat"] = "backup data";
$PMF_LANG["ad_csv_head2"] = "Obnovení";
$PMF_LANG["ad_csv_no"] = "Tento soubor není zálohou phpmyfaq.";
$PMF_LANG["ad_csv_prepare"] = "Pøipravuji databázové dotazy...";
$PMF_LANG["ad_csv_process"] = "Querying...";
$PMF_LANG["ad_csv_of"] = "";
$PMF_LANG["ad_csv_suc"] = "bylo úspìšné.";
$PMF_LANG["ad_csv_backup"] = "Záloha";
$PMF_LANG["ad_csv_rest"] = "Obnov zálohu";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Záloha";
$PMF_LANG["ad_logout"] = "Seance úspìšnì ukonèeny.";
$PMF_LANG["ad_news_add"] = "Pøidej novinky";
$PMF_LANG["ad_news_edit"] = "Edituj novinky";
$PMF_LANG["ad_cookie"] = "Cookies";
$PMF_LANG["ad_sess_head"] = "Zobraz seance";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Administrace Kategorií";
$PMF_LANG["ad_menu_stat"] = "Statistiky hodnocení";
$PMF_LANG["ad_kateg_add"] = "pøidej Kategorii";
$PMF_LANG["ad_kateg_rename"] = "Pøejmenuj";
$PMF_LANG["ad_adminlog_date"] = "Smaž";
$PMF_LANG["ad_adminlog_user"] = "Uživatel";
$PMF_LANG["ad_adminlog_ip"] = "IP adresa";

$PMF_LANG["ad_stat_sess"] = "Seance";
$PMF_LANG["ad_stat_days"] = "Dnù";
$PMF_LANG["ad_stat_vis"] = "Seance (Návštìvy)";
$PMF_LANG["ad_stat_vpd"] = "Návštìvy za Den";
$PMF_LANG["ad_stat_fien"] = "První Log";
$PMF_LANG["ad_stat_laen"] = "Poslední Log";
$PMF_LANG["ad_stat_browse"] = "prohlížet seance";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Èas";
$PMF_LANG["ad_sess_sid"] = "ID seance";
$PMF_LANG["ad_sess_ip"] = "IP adresa";

$PMF_LANG["ad_ques_take"] = "Upravit otázku";
$PMF_LANG["no_cats"] = "Žádné kategorie nenalezeny.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Nesprávné uživatelské jméno nebo heslo.";
$PMF_LANG["ad_log_sess"] = "Seance vypršela.";
$PMF_LANG["ad_log_edit"] = "\"Edituj Uživatele\"- pro následujícího uživatele: ";
$PMF_LANG["ad_log_crea"] = "\"Nový záznam\".";
$PMF_LANG["ad_log_crsa"] = "Nový záznam vytvoøen.";
$PMF_LANG["ad_log_ussa"] = "Aktualizuj údaje uživatele: ";
$PMF_LANG["ad_log_usde"] = "Smaž uživatele: ";
$PMF_LANG["ad_log_beed"] = "Formuláø pro editaci uživatele: ";
$PMF_LANG["ad_log_bede"] = "Smazán záznam: ";

$PMF_LANG["ad_start_visits"] = "Návštìv";
$PMF_LANG["ad_start_articles"] = "Záznamù";
$PMF_LANG["ad_start_comments"] = "Komentáøù";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "vložit";
$PMF_LANG["ad_categ_cut"] = "vyjmout";
$PMF_LANG["ad_categ_copy"] = "kopírovat";
$PMF_LANG["ad_categ_process"] = "Zpracovávám kategorie...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Pøístup zamítnut.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "pøedchozí strana";
$PMF_LANG["msgNextPage"] = "další strana";
$PMF_LANG["msgPageDoublePoint"] = "Strana: ";
$PMF_LANG["msgMainCategory"] = "Hlavní kategorie";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Vaše heslo bylo zmìnìno.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "Zobrazit jako PDF soubor";
$PMF_LANG["ad_xml_head"] = "XML-Záloha";
$PMF_LANG["ad_xml_hint"] = "Ulož všechny záznamy FAQ do jednoho XML souboru.";
$PMF_LANG["ad_xml_gen"] = "Vytvoøit XML soubor";
$PMF_LANG["ad_entry_locale"] = "Jazyk";
$PMF_LANG["msgLangaugeSubmit"] = "zmìit jazyk";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "Náhled";
$PMF_LANG["ad_attach_1"] = "Napøed prosím v nastavení vyberte adresáø pro pøílohy.";
$PMF_LANG["ad_attach_2"] = "Napøed prosím v nastavení vyberte odkaz pro pøílohy.";
$PMF_LANG["ad_attach_3"] = "Soubor s pøíponou .php nemùže být otevøen bez øádné autentifikace.";
$PMF_LANG["ad_attach_4"] = "Pøikládaný soubor musí být menší než %s Bytù.";
$PMF_LANG["ad_menu_export"] = "Exportuj FAQ";
$PMF_LANG["ad_export_1"] = "Built RSS-Feed on";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "Chyba: Nemùžu zapsat soubor.";
$PMF_LANG["ad_export_news"] = "RSS-Feed Novinek";
$PMF_LANG["ad_export_topten"] = "RSS-Feed Top 10";
$PMF_LANG["ad_export_latest"] = "RSS-Feed 5ti nejnovìjších záznamù";
$PMF_LANG["ad_export_pdf"] = "PDF-Export všech záznamù";
$PMF_LANG["ad_export_generate"] = "vytvoø RSS-Feed";

$PMF_LANG["rightsLanguage"]['adduser'] = "pøidat uživatele";
$PMF_LANG["rightsLanguage"]['edituser'] = "editovat uživatele";
$PMF_LANG["rightsLanguage"]['deluser'] = "mazat uživatele";
$PMF_LANG["rightsLanguage"]['addbt'] = "pøidat záznam";
$PMF_LANG["rightsLanguage"]['editbt'] = "editovat záznam";
$PMF_LANG["rightsLanguage"]['delbt'] = "smazat záznam";
$PMF_LANG["rightsLanguage"]['viewlog'] = "zobrazit log";
$PMF_LANG["rightsLanguage"]['adminlog'] = "zobrazit admin log";
$PMF_LANG["rightsLanguage"]['delcomment'] = "smazat komentáø";
$PMF_LANG["rightsLanguage"]['addnews'] = "pøidat novinky";
$PMF_LANG["rightsLanguage"]['editnews'] = "editovat novinky";
$PMF_LANG["rightsLanguage"]['delnews'] = "smazat novinky";
$PMF_LANG["rightsLanguage"]['addcateg'] = "pøidat kategorii";
$PMF_LANG["rightsLanguage"]['editcateg'] = "editovat kategorii";
$PMF_LANG["rightsLanguage"]['delcateg'] = "smazat kategorii";
$PMF_LANG["rightsLanguage"]['passwd'] = "zmìnit heslo";
$PMF_LANG["rightsLanguage"]['editconfig'] = "editovat konfiguraci";
$PMF_LANG["rightsLanguage"]['addatt'] = "pøidat pøílohu";
$PMF_LANG["rightsLanguage"]['delatt'] = "mazat pøílohy";
$PMF_LANG["rightsLanguage"]['backup'] = "tvoøit zálohu";
$PMF_LANG["rightsLanguage"]['restore'] = "obnovit ze zálohy";
$PMF_LANG["rightsLanguage"]['delquestion'] = "mazat nezodpovìzené dotazy";

$PMF_LANG["msgAttachedFiles"] = "pøipojené soubory:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "akce";
$PMF_LANG["ad_entry_email"] = "e-mail adresa:";
$PMF_LANG["ad_entry_allowComments"] = "povolit komentáøe";
$PMF_LANG["msgWriteNoComment"] = "K tomuto záznamu nemùžete pøipojit komentáø.";
$PMF_LANG["ad_user_realname"] = "skuteèné jméno:";
$PMF_LANG["ad_export_generate_pdf"] = "Vytvoøit PDF soubor";
$PMF_LANG["ad_export_full_faq"] = "FAQ jako PDF soubor: ";
$PMF_LANG["err_bannedIP"] = "Vaše IP adresa byla pøidána do \"nepovolených adres\".";
$PMF_LANG["err_SaveQuestion"] = "Povinná pole jsou <strong>jméno</strong>, <strong>e-mailová adresa</strong> a <strong>otázka</strong>.<br /><br /><a href=\"javascript:history.back();\">pøedchozí stránka</a><br /><br />\n";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "Barva písma: ";
$PMF_LANG["ad_entry_fontsize"] = "Velikost písma: ";

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
$LANG_CONF["main.referenceURL"] = array(0 => "input", 1 => "Base URL for link verification (e.g.: http://www.example.org/faq)");
$LANG_CONF["main.urlValidateInterval"] = array(0 => "input", 1 => "Interval between AJAX link verification (in seconds)");
$LANG_CONF["records.enableVisibilityQuestions"] = array(0 => "checkbox", 1 => "Disable visibility of new questions?");
$LANG_CONF['main.permLevel'] = array(0 => "select", 1 => "Permission level");

$PMF_LANG["ad_categ_new_main_cat"] = "as new main category";
$PMF_LANG["ad_categ_paste_error"] = "Moving this category isn't possible.";
$PMF_LANG["ad_categ_move"] = "move category";
$PMF_LANG["ad_categ_lang"] = "Language";
$PMF_LANG["ad_categ_desc"] = "Description";
$PMF_LANG["ad_categ_change"] = "Change with";

$PMF_LANG["lostPassword"] = "Zapomìli jste heslo? Kliknìte zde.";
$PMF_LANG["lostpwd_err_1"] = "Chyba: Uživatelské jméno a e-mailová adresa nenalezeny.";
$PMF_LANG["lostpwd_err_2"] = "Chyba: Chybné zadání!";
$PMF_LANG["lostpwd_text_1"] = "Dìkujeme, že jste si vyžádali vaše pøihlašovací údaje.";
$PMF_LANG["lostpwd_text_2"] = "Prosíme, nastavete si nové helso v admin sekci vašcih FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "E-mail byl odeslán.";

$PMF_LANG["ad_xmlrpc_button"] = "Get latest phpMyFAQ version number by web service";
$PMF_LANG["ad_xmlrpc_latest"] = "Latest version available on";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Zvolte jazyk sekce';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Mapa stránek FAQ';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'This entry is in revision and can not be displayed.';
$PMF_LANG['msgArticleCategories'] = 'Categories for this entry';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'Unique solution ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ record';
$PMF_LANG['ad_entry_new_revision'] = 'Create new revision?';
$PMF_LANG['ad_entry_record_administration'] = 'Record administration';
$PMF_LANG['ad_entry_changelog'] = 'Changelog';
$PMF_LANG['ad_entry_revision'] = 'Verze';
$PMF_LANG['ad_changerev'] = 'Select Revision';
$PMF_LANG['msgCaptcha'] = "Prosím, opište všechny znaky z obrázku";
$PMF_LANG['msgSelectCategories'] = 'Vyhledávej ';
$PMF_LANG['msgAllCategories'] = '... ve všech sekcích';
$PMF_LANG['ad_you_should_update'] = 'Your phpMyFAQ installation is outdated. You should update to the latest available version.';
$PMF_LANG['msgAdvancedSearch'] = 'Vyhledávání';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'Spam control center';
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "Print user email in a safe way (default: enabled).");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "Check public form content against banned words (default: enabled).");
$LANG_CONF["spam.enableCatpchaCode"] = array(0 => "checkbox", 1 => "Use a catpcha code to allow public form submission (default: enabled).");
$PMF_LANG['ad_session_expiring'] = 'Your session will expire in %d minutes: would you like to go on working?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Sessions management';
$PMF_LANG['ad_stat_choose'] = 'Choose the month';
$PMF_LANG['ad_stat_delete'] = 'Delete selected sessions immediately';

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
$PMF_LANG['ad_menu_linkconfig'] = 'Configure URL Verifier';
$PMF_LANG['ad_linkcheck_config_title'] = 'URL Verifier Configuration';
$PMF_LANG['ad_linkcheck_config_disabled'] = 'URL Verifier feature disabled';
$PMF_LANG['ad_linkcheck_config_warnlist'] = 'URLs to warn';
$PMF_LANG['ad_linkcheck_config_ignorelist'] = 'URLs to ignore';
$PMF_LANG['ad_linkcheck_config_warnlist_description'] = 'URLs prefixed with items below will be issued warning regardless of whether it is valid.<br />Use this feature to detect soon-to-be defunct URLs';
$PMF_LANG['ad_linkcheck_config_ignorelist_description'] = 'Exact URLs listed below will be assumed valid without validation.<br />Use this feature to omit URLs that fail to validate using URL Verifier';
$PMF_LANG['ad_linkcheck_config_th_id'] = 'ID#';
$PMF_LANG['ad_linkcheck_config_th_url'] = 'URL to match';
$PMF_LANG['ad_linkcheck_config_th_reason'] = 'Match reason';
$PMF_LANG['ad_linkcheck_config_th_owner'] = 'Entry owner';
$PMF_LANG['ad_linkcheck_config_th_enabled'] = 'Set to enable entry';
$PMF_LANG['ad_linkcheck_config_th_locked'] = 'Set to lock ownership';
$PMF_LANG['ad_linkcheck_config_th_chown'] = 'Set to obtain ownership';
$PMF_LANG['msgNewQuestionVisible'] = 'The question have to be reviewed first before getting public.';
$PMF_LANG['msgQuestionsWaiting'] = 'Waiting for publishing by the administrators: ';
$PMF_LANG['ad_entry_visibility'] = 'Publish?';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] =  "Please enter a password. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] =  "Passwords do not match. ";
$PMF_LANG['ad_user_error_loginInvalid'] =  "The specified user name is invalid.";
$PMF_LANG['ad_user_error_noEmail'] =  "Please enter a valid mail adress. ";
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
$PMF_LANG["ad_menu_group_administration"] = "Group Administration";
$PMF_LANG['ad_user_loggedin'] = 'You\'re logged in as ';

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
$PMF_LANG['ad_export_type_choose'] = 'Choose one of the supported formats:';
$PMF_LANG['ad_export_download_view'] = 'Download or view in-line?';
$PMF_LANG['ad_export_download'] = 'download';
$PMF_LANG['ad_export_view'] = 'view in-line';
$PMF_LANG['ad_export_gen_xhtml'] = 'Make XHTML file';
$PMF_LANG['ad_export_gen_docbook'] = 'Make Docbook file';

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
$PMF_LANG['admin_mainmenu_home'] = 'Home';
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
$PMF_LANG['msgSearchOnAllLanguages'] = 'Search over all languages:';
$PMF_LANG['ad_entry_tags'] = 'Znaèky';
$PMF_LANG['msg_tags'] = 'Znaèky';

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
$PMF_LANG['msg_related_articles'] = 'Související záznamy';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "Number of related entries");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'Pøeložit';
$PMF_LANG['ad_categ_trans_2'] = 'Kategorie';
$PMF_LANG['ad_categ_translatecateg'] = 'Pøeložit kategorii';
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
$PMF_LANG['ad_session_expiration'] = 'Time to your session expiration';
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
$PMF_LANG['msgUsersOnline'] = ' :: %d Guests and %d Registered';
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
$PMF_LANG['msgNewTranslationHeader'] = 'Návrh pøekladu';
$PMF_LANG['msgNewTranslationAddon'] = 'Váš návrh pøekladu nebude publikován okamžitì, ale až po schválení administrátorem. Povinné pole jsou <strong>vaše Jméno</strong>, <strong>vaše e-mailová adresa</strong>, <strong>titulek pøekladu</strong> a <strong>vlastní pøeklad zvoleného záznamu</strong>. Prosíme, oddìlujte klíèová slova pouze mezerou.';
$PMF_LANG['msgNewTransSourcePane'] = 'Výchozí text';
$PMF_LANG['msgNewTranslationPane'] = 'Pøeložený text';
$PMF_LANG['msgNewTranslationName'] = $PMF_LANG['msgNewContentName'];
$PMF_LANG['msgNewTranslationMail'] = $PMF_LANG['msgNewContentMail'];
$PMF_LANG['msgNewTranslationKeywords'] = $PMF_LANG['msgNewContentKeywords'];
$PMF_LANG['msgNewTranslationSubmit'] = 'Odeslat váš návrh';
$PMF_LANG['msgTranslate'] = 'Navrhnìte pøeklad tohoto záznamu do';
$PMF_LANG['msgTranslateSubmit'] = 'Pøeložit...';
$PMF_LANG['msgNewTranslationThanks'] = "Dìkujeme za váš návrh pøekladu!";

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
$PMF_LANG['ad_conf_order_id'] = 'ID (výchozí)';
$PMF_LANG['ad_conf_order_thema'] = 'Titulek';
$PMF_LANG['ad_conf_order_visits'] = 'Poèet návštìvníkù';
$PMF_LANG['ad_conf_order_datum'] = 'Datum';
$PMF_LANG['ad_conf_order_author'] = 'Autor';
$PMF_LANG['ad_conf_desc'] = 'sestupnì';
$PMF_LANG['ad_conf_asc'] = 'vzestupnì';
$PMF_LANG['mainControlCenter'] = 'Hlavní nastavení';
$PMF_LANG['recordsControlCenter'] = 'FAQ records configuration';

// added v2.0.0 - 2007-03-17 by Thorsten
$PMF_LANG['msgInstantResponse'] = 'Okamžité hledání';
$PMF_LANG['msgInstantResponseMaxRecords'] = '. Nalezeno prvních %d záznamù.';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "Activate a new records (default: deactivated)");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "Allow comments for new records (default: disallowed)");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'Záznamù v této kategorii';
$PMF_LANG['msgDescriptionInstantResponse'] = 'Zaènìte psát a zobrazí se vám odpovìdi ...';
$PMF_LANG['msgTagSearch'] = 'Oznaèkovaných záznamù';
$PMF_LANG['ad_pmf_info'] = 'phpMyFAQ Information';
$PMF_LANG['ad_online_info'] = 'Online version check';
$PMF_LANG['ad_system_info'] = 'System Information';