<?php
/**
 * $Id: language_fi.php,v 1.29 2007-03-29 19:31:54 thorstenr Exp $
 *
 * Finnish language file
 *
 * @author      Juha Tuomala <tuomala@iki.fi>
 * @author      Matti Kröger <matti.kroger@hotmail.com>
 * @since       2004-02-19
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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
$PMF_LANG["metaLanguage"] = "fi";
$PMF_LANG["language"] = "finnish";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";

$PMF_LANG["nplurals"] = "2";
/**
 * This parameter is used with the function 'plural' from inc/PMF_Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 */

// Navigation
$PMF_LANG["msgCategory"] = "Kategoriat";
$PMF_LANG["msgShowAllCategories"] = "Näytä kaikki kategoriat";
$PMF_LANG["msgSearch"] = "Haku";
$PMF_LANG["msgAddContent"] = "Lisää sisältöä";
$PMF_LANG["msgQuestion"] = "Kysy kysymys";
$PMF_LANG["msgOpenQuestions"] = "Avoimet kysymykset";
$PMF_LANG["msgHelp"] = "Ohjeet";
$PMF_LANG["msgContact"] = "Yhteystiedot";
$PMF_LANG["msgHome"] = "Koti";
$PMF_LANG["msgNews"] = "FAQ-Uutiset";
$PMF_LANG["msgUserOnline"] = " käyttäjä paikalla";
$PMF_LANG["msgXMLExport"] = "XML-tiedosto";
$PMF_LANG["msgBack2Home"] = "takaisin kotisivulle";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategoriat joissa on tietueita";
$PMF_LANG["msgFullCategoriesIn"] = "Kategoriat joissa on tietueita";
$PMF_LANG["msgSubCategories"] = "Alikategoriat";
$PMF_LANG["msgEntries"] = "Tietueet";
$PMF_LANG["msgEntriesIn"] = "Kysymyksiä ";
$PMF_LANG["msgViews"] = "näkymät";
$PMF_LANG["msgPage"] = "Sivu ";
$PMF_LANG["msgPages"] = "Sivut";
$PMF_LANG["msgPrevious"] = "edellinen";
$PMF_LANG["msgNext"] = "seuraava";
$PMF_LANG["msgCategoryUp"] = "kategoria ylös";
$PMF_LANG["msgLastUpdateArticle"] = "Viimeinen päivitys: ";
$PMF_LANG["msgAuthor"] = "Luoja: ";
$PMF_LANG["msgPrinterFriendly"] = "tulostus-ystävällinen versio";
$PMF_LANG["msgPrintArticle"] = "Tulosta tämä tietue";
$PMF_LANG["msgMakeXMLExport"] = "lataa XML-tiedostona";
$PMF_LANG["msgAverageVote"] = "Keskimääräinen arvostus:";
$PMF_LANG["msgVoteUseability"] = "Arvostele tämä tietue:";
$PMF_LANG["msgVoteFrom"] = "mistä";
$PMF_LANG["msgVoteBad"] = "täysin hyödytön";
$PMF_LANG["msgVoteGood"] = "hyvin hyödyllinen";
$PMF_LANG["msgVotings"] = "Arvosteluita ";
$PMF_LANG["msgVoteSubmit"] = "Arvostele";
$PMF_LANG["msgVoteThanks"] = "Kiitoksia paljon mielipiteestäsi!";
$PMF_LANG["msgYouCan"] = "Sinä voit ";
$PMF_LANG["msgWriteComment"] = "kommentoi tätä tietuetta";
$PMF_LANG["msgShowCategory"] = "Sisällön näkymä: ";
$PMF_LANG["msgCommentBy"] = "Kommentoija ";
$PMF_LANG["msgCommentHeader"] = "Kommentti tästä tietueesta";
$PMF_LANG["msgYourComment"] = "Sinun kommentit:";
$PMF_LANG["msgCommentThanks"] = "Kiitoksia paljon kommenteistasi!";
$PMF_LANG["msgSeeXMLFile"] = "avaa XML-tiedosto";
$PMF_LANG["msgSend2Friend"] = "Lähetä tuttavalle";
$PMF_LANG["msgS2FName"] = "Nimesi:";
$PMF_LANG["msgS2FEMail"] = "Sähköpostiosoitteesi:";
$PMF_LANG["msgS2FFriends"] = "Tuttavasi:";
$PMF_LANG["msgS2FEMails"] = ". sähköpostiosoite:";
$PMF_LANG["msgS2FText"] = "Seuraava tullaan lähettämään:";
$PMF_LANG["msgS2FText2"] = "Löydät tietueen seuraavasta osoitteesta:";
$PMF_LANG["msgS2FMessage"] = "Lisä viesti tuttavillesi:";
$PMF_LANG["msgS2FButton"] = "lähetä sähköposti";
$PMF_LANG["msgS2FThx"] = "Kiitoksia suosittelustasi!";
$PMF_LANG["msgS2FMailSubject"] = "Suosittelijasi ";

// Search
$PMF_LANG["msgSearchWord"] = "Avainsana";
$PMF_LANG["msgSearchFind"] = "Hakutulokset haulle ";
$PMF_LANG["msgSearchAmount"] = " hakutulos";
$PMF_LANG["msgSearchAmounts"] = " hakutulokset";
$PMF_LANG["msgSearchCategory"] = "Kategoria: ";
$PMF_LANG["msgSearchContent"] = "Sisältö: ";

/* new Content - Correction to msgNewContentAddon & msgNoQuestionsAvailable msg by Matti Kröger*/
$PMF_LANG["msgNewContentHeader"] = "FAQ ehdotus";
$PMF_LANG["msgNewContentAddon"] = "Ehdotustasi ei tulla julkaisemaan heti, vaan vasta sitten kun ylläpitäjä on sen tarkistanut. Vaadittavat kentät ovat <strong>Nimesi</strong>, <strong>sähköposti osoitteesi</strong>, <strong>kategoria</strong>, <strong>otsikko</strong> and <strong>kysymyksesi</strong>. Ole ystävällinen ja eroita avainsanat vain välilyönnillä.";
$PMF_LANG["msgNewContentName"] = "Nimesi:";
$PMF_LANG["msgNewContentMail"] = "Sähköposti osoitteesi:";
$PMF_LANG["msgNewContentCategory"] = "Valittava kategoria?";
$PMF_LANG["msgNewContentTheme"] = "Otsikko:";
$PMF_LANG["msgNewContentArticle"] = "FAQ tietueesi:";
$PMF_LANG["msgNewContentKeywords"] = "Avainsanat:";
$PMF_LANG["msgNewContentLink"] = "Linkki tähän tietueeseen";
$PMF_LANG["msgNewContentSubmit"] = "tallenna";
$PMF_LANG["msgInfo"] = "Lisää tietoa: ";
$PMF_LANG["msgNewContentThanks"] = "Kiitoksia ehdotuksestasi!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Tällä hetkellä ei ole avoimia kysymyksiä.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Kysy kysymyksesi alhaalla:";
$PMF_LANG["msgAskCategory"] = "Kysymyksesi kategoria";
$PMF_LANG["msgAskYourQuestion"] = "Kysymyksesi:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>Kiitoksia kysymyksestäsi!</h2>";
$PMF_LANG["msgDate_User"] = "Päivämäärä / Käyttäjä";
$PMF_LANG["msgQuestion2"] = "Kysymys";
$PMF_LANG["msg2answer"] = "vastaus";
$PMF_LANG["msgQuestionText"] = "Täältä näet muiden käyttäjien kysymät kysymykset. Jos vastaat kysymykseen, vastauksesi voi päätyä FAQ tietoihin.";

// Help
$PMF_LANG["msgHelpText"] = "<p>FAQ:n (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) rakenne on melko yksinkertainen. Voit joko selata <strong><a href=\"?action=show\">kategorioita</a></strong> tai anna <strong><a href=\"?action=search\">FAQ hakukoneen</a></strong> etsiä avainsanoja.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "sähköposti ylläpitäjälle:";
$PMF_LANG["msgMessage"] = "Viestisi:";

// Startseite - Correction to msgLatestArticles msg by Matti Kröger
$PMF_LANG["msgNews"] = " Uutiset";
$PMF_LANG["msgTopTen"] = "Kymmenen suosituinta";
$PMF_LANG["msgHomeThereAre"] = "Tällä hetkellä  ";
$PMF_LANG["msgHomeArticlesOnline"] = " tietuetta aktiivisena";
$PMF_LANG["msgNoNews"] = "Hyvät uutiset ovat, että ei ole uutisia.";
$PMF_LANG["msgLatestArticles"] = "Viisi uusinta kysymystä:";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Monet kiitokset FAQ ehdotuksestasi.";
$PMF_LANG["msgMailCheck"] = "Uusi FAQ saatavilla!\nOle hyvä ja tarkasta ylläpito tehtävät!";
$PMF_LANG["msgMailContact"] = "Viestisi on lähetetty ylläpitäjälle.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "Tietokantayhteyttä ei ole saatavilla.";
$PMF_LANG["err_noHeaders"] = "Kategoriaa ei löydy.";
$PMF_LANG["err_noArticles"] = "<p>Tietueita ei ole saatavilla.</p>";
$PMF_LANG["err_badID"] = "<p>Väärä ID.</p>";
$PMF_LANG["err_noTopTen"] = "<p>Kymmentä suosituinta ei ole vielä saatavilla.</p>";
$PMF_LANG["err_nothingFound"] = "<p>Tietuetta ei löydy.</p>";
$PMF_LANG["err_SaveEntries"] = "Vaadittavat kentät ovat <strong>Nimesi</strong>, <strong>sähköposti osoitteesi</strong>, <strong>kategoria</strong>, <strong>otsikko</strong> ja <strong>Tietueesi</strong>!<br /><br />\n<a href=\"javascript:history.back();\">yksi sivu takaisin</a><br /><br />\n";
$PMF_LANG["err_SaveComment"] = "Vaadittavat kentät ovat <strong>Nimesi</strong>, <strong>sähköposti osoitteesi</strong> ja <strong>kommenttisi</strong>!<br /><br />\n<a href=\"javascript:history.back();\">yksi sivu takaisin</a><br /><br />\n";
$PMF_LANG["err_VoteTooMuch"] = "<p>Emme laske kuin yhden arvostelun. <a href=\"javascript:history.back();\">Klikkaa tästä</a>, päästäksesi takaisin.</p>";
$PMF_LANG["err_noVote"] = "<p><strong>Et arvostellut kysymystä!</strong> <a href=\"javascript:history.back();\">Ole hyvä ja klikkaa tästä</a>, arvostellaksesi.</p>";
$PMF_LANG["err_noMailAdress"] = "Sähköpostiosoitteesi ei ole oikein.<br /><a href=\"javascript:history.back();\">takaisin</a>";
$PMF_LANG["err_sendMail"] = "Vaadittavat kentät ovat <strong>nimesi</strong>, <strong>sähköposti osoitteesi</strong> ja <strong>kysymyksesi</strong>!";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>Etsi tietueita:</strong><br />Tietueella kuten <strong style=\"color: Red;\">sana1 sana2</strong> voit tehdä asiaan kuuluvan laskevan haun kahdella tai useammalla hakukriteerillä.</p><p><strong>Huomaa:</strong> Haku kriteerisi tulee olla ainakin 4 merkkiä pitkä tai muuten hakusi hylätään.</p>";

// Menü
$PMF_LANG["ad"] = "YLLÄPITO";
$PMF_LANG["ad_menu_user_administration"] = "Käyttäjien Hallinta";
$PMF_LANG["ad_menu_entry_aprove"] = "Hyväksy Tietueita";
$PMF_LANG["ad_menu_entry_edit"] = "Muokkaa Tietueita";
$PMF_LANG["ad_menu_categ_add"] = "Lisää Kategoria";
$PMF_LANG["ad_menu_categ_edit"] = "Muokkaa Kategoriaa";
$PMF_LANG["ad_menu_news_add"] = "Lisää Uutisia";
$PMF_LANG["ad_menu_news_edit"] = "Muokkaa Uutisia";
$PMF_LANG["ad_menu_open"] = "Muokkaa avoimia kysymyksiä";
$PMF_LANG["ad_menu_stat"] = "Tilastot";
$PMF_LANG["ad_menu_cookie"] = "Keksit";
$PMF_LANG["ad_menu_session"] = "Selaa Istuntoja";
$PMF_LANG["ad_menu_adminlog"] = "Selaa Ylläpitolokia";
$PMF_LANG["ad_menu_passwd"] = "Vaihda Salasana";
$PMF_LANG["ad_menu_logout"] = "Kirjaudu Ulos";
$PMF_LANG["ad_menu_startpage"] = "Aloitussivu";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Ole hyvä ja tunnistaudu.";
$PMF_LANG["ad_msg_passmatch"] = "Kummankin salasanan täytyy olla <strong>samoja</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profiili joka kuuluu käyttäjälle ";
$PMF_LANG["ad_msg_savedsuc_2"] = "tallennettiin onnistuneesti.";
$PMF_LANG["ad_msg_mysqlerr"] = "<strong>Tietokanta virheen</strong> takia, profiilia ei voitu tallentaa.";
$PMF_LANG["ad_msg_noauth"] = "Et ole oikeutettu.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Sivu";
$PMF_LANG["ad_gen_of"] = "";
$PMF_LANG["ad_gen_lastpage"] = "Edellinen Sivu";
$PMF_LANG["ad_gen_nextpage"] = "Seuraava Sivu";
$PMF_LANG["ad_gen_save"] = "Tallenna";
$PMF_LANG["ad_gen_reset"] = "Tyhjennä";
$PMF_LANG["ad_gen_yes"] = "Kyllä";
$PMF_LANG["ad_gen_no"] = "Ei";
$PMF_LANG["ad_gen_top"] = "Sivun alku";
$PMF_LANG["ad_gen_ncf"] = "Kategoriaa ei löydy!";
$PMF_LANG["ad_gen_delete"] = "Tuhoa";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "Käyttäjien Hallinta";
$PMF_LANG["ad_user_username"] = "Rekisteröityneet käyttäjät";
$PMF_LANG["ad_user_rights"] = "Käyttäjän Oikeudet";
$PMF_LANG["ad_user_edit"] = "muokkaa";
$PMF_LANG["ad_user_delete"] = "tuhoa";
$PMF_LANG["ad_user_add"] = "Lisää Käyttäjä";
$PMF_LANG["ad_user_profou"] = "Käyttäjän Profiili";
$PMF_LANG["ad_user_name"] = "Nimi";
$PMF_LANG["ad_user_password"] = "Salasana";
$PMF_LANG["ad_user_confirm"] = "Varmista";
$PMF_LANG["ad_user_rights"] = "Oikeudet";
$PMF_LANG["ad_user_del_1"] = "Käyttäjä";
$PMF_LANG["ad_user_del_2"] = "tullaan tuhoamaan?";
$PMF_LANG["ad_user_del_3"] = "Oletko varma?";
$PMF_LANG["ad_user_deleted"] = "Käyttäjä tuhottiin onnistuneesti.";
$PMF_LANG["ad_user_checkall"] = "Valitse kaikki";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "Tietueiden Hallinta";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Aihe";
$PMF_LANG["ad_entry_action"] = "Toiminto";
$PMF_LANG["ad_entry_edit_1"] = "Muokkaa Tietuetta";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Teema:";
$PMF_LANG["ad_entry_content"] = "Sisältö:";
$PMF_LANG["ad_entry_keywords"] = "Avainsanat:";
$PMF_LANG["ad_entry_author"] = "Tekijä:";
$PMF_LANG["ad_entry_category"] = "Kategoria:";
$PMF_LANG["ad_entry_active"] = "Aktiivinen?";
$PMF_LANG["ad_entry_date"] = "Päivämäärä:";
$PMF_LANG["ad_entry_changed"] = "Muutettu?";
$PMF_LANG["ad_entry_changelog"] = "Muutosloki:";
$PMF_LANG["ad_entry_commentby 	"] = "Kommentoija";
$PMF_LANG["ad_entry_comment"] = "Kommentit:";
$PMF_LANG["ad_entry_save"] = "Tallenna";
$PMF_LANG["ad_entry_delete"] = "tuhoa";
$PMF_LANG["ad_entry_delcom_1"] = "Oletko varma, että käyttäjän";
$PMF_LANG["ad_entry_delcom_2"] = "kommentti pitää tuhota?";
$PMF_LANG["ad_entry_commentdelsuc"] = "Kommentti <strong>onnistuneesti</strong> tuhottu.";
$PMF_LANG["ad_entry_back"] = "Takaisin artikkeliin";
$PMF_LANG["ad_entry_commentdelfail"] = "Kommenttia <strong>ei</strong> tuhottu.";
$PMF_LANG["ad_entry_savedsuc"] = "Muutokset tallennettiin <strong>onnistuneesti</strong>.";
$PMF_LANG["ad_entry_savedfail 	"] = "Valitettavasti tapahtui <strong>tietokantavirhe</strong>.";
$PMF_LANG["ad_entry_del_1"] = "Oletko varma, että aihe";
$PMF_LANG["ad_entry_del_2"] = "";
$PMF_LANG["ad_entry_del_3"] = "tulisi tuhota?";
$PMF_LANG["ad_entry_delsuc"] = "Aihe tuhottu <strong>onnistuneesti</strong>.";
$PMF_LANG["ad_entry_delfail"] = "Aihetta <strong>ei tuhottu</strong>!";
$PMF_LANG["ad_entry_back"] = "Takaisin";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "Uutisen Otsikko";
$PMF_LANG["ad_news_text"] = "Uutisen Teksti";
$PMF_LANG["ad_news_link_url"] = "Linkki: (<strong>ilman http://</strong>)!";
$PMF_LANG["ad_news_link_title"] = "Linkin otsikko:";
$PMF_LANG["ad_news_link_target"] = "Linkin kohde";
$PMF_LANG["ad_news_link_window"] = "Linkki avaa uuden ikkunan";
$PMF_LANG["ad_news_link_faq"] = "Linkki FAQ:n sisällä";
$PMF_LANG["ad_news_add"] = "Lisää Uutinen";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Otsikko";
$PMF_LANG["ad_news_date"] = "Päivämäärä";
$PMF_LANG["ad_news_action"] = "Toiminto";
$PMF_LANG["ad_news_update"] = "päivitä";
$PMF_LANG["ad_news_delete"] = "tuhoa";
$PMF_LANG["ad_news_nodata"] = "Tietoa ei löytynyt tietokannasta";
$PMF_LANG["ad_news_updatesuc"] = "Uutiset päivitettiin.";
$PMF_LANG["ad_news_del"] = "Oletko varma, että haluat tuhota tämän uutisen?";
$PMF_LANG["ad_news_yesdelete"] = "kyllä, tuhoa!";
$PMF_LANG["ad_news_nodelete"] = "ei!";
$PMF_LANG["ad_news_delsuc"] = "Uutinen tuhottu.";
$PMF_LANG["ad_news_updatenews"] = "Päivitä uutinen";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Lisää uusi kategoria";
$PMF_LANG["ad_categ_catnum"] = "Kategoria numero:";
$PMF_LANG["ad_categ_subcatnum"] = "Alikategorian numero:";
$PMF_LANG["ad_categ_nya"] = "<em>ei ole vielä saatavilla!</em>";
$PMF_LANG["ad_categ_titel"] = "Kategorian Otsikko:";
$PMF_LANG["ad_categ_add"] = "Lisää Kategoria";
$PMF_LANG["ad_categ_existing"] = "Olemassaolevat Kategoriat";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategoria";
$PMF_LANG["ad_categ_subcateg"] = "Alikategoria";
$PMF_LANG["ad_categ_titel"] = "Kategoria otsikko";
$PMF_LANG["ad_categ_action"] = "Toiminto";
$PMF_LANG["ad_categ_update"] = "päivitä";
$PMF_LANG["ad_categ_delete"] = "tuhoa";
$PMF_LANG["ad_categ_updatecateg"] = "Päivitä Kategoria";
$PMF_LANG["ad_categ_nodata"] = "Tietoa ei löydy tietokannasta";
$PMF_LANG["ad_categ_remark"] = "Huomaa, että olemassaolevat tietueet eivät ole enää nähtävissä jos tuhoat kategorian. Tietueille tulee määritellä uudet kategoriat tai ne tulee tuhota.";
$PMF_LANG["ad_categ_edit_1"] = "Muokkaa";
$PMF_LANG["ad_categ_edit_2"] = "Kategoria";
$PMF_LANG["ad_categ_add"] = "lisää Kategoria";
$PMF_LANG["ad_categ_added"] = "Kategoria lisättiin.";
$PMF_LANG["ad_categ_updated"] = "Kategoria päivitettiin.";
$PMF_LANG["ad_categ_del_yes"] = "kyllä, tuhoa!";
$PMF_LANG["ad_categ_del_no"] = "ei!";
$PMF_LANG["ad_categ_deletesure"] = "Oletko varma, että haluat tuhota tämän kategorian?";
$PMF_LANG["ad_categ_deleted"] = "Kategoria tuhottu.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc 	"] = "Keksi tallennettu <strong>onnistuneesti</strong>.";
$PMF_LANG["ad_cookie_already"] = "Keksi oli jo tallennettu. Sinulla on seuraavat vaihtoehdot:";
$PMF_LANG["ad_cookie_again"] = "Tallenna keksi uudelleen";
$PMF_LANG["ad_cookie_delete"] = "tuhoa keksi";
$PMF_LANG["ad_cookie_no"] = "Keksiä ei ole vielä tallenettu. Keksin avulla voisit tallentaa sisäänkirjautumis tietosi, jolloin niitä ei tarvitsisi muistaa. Seuraavat vaihtoehdot ovat mahdollisia:";
$PMF_LANG["ad_cookie_set"] = "Tallenna keksi";
$PMF_LANG["ad_cookie_deleted"] = "Keksi tuhottu onnistuneesti.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "Ylläpitoloki";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Vaihda salasanasi";
$PMF_LANG["ad_passwd_old"] = "Vanha salasana:";
$PMF_LANG["ad_passwd_new"] = "Uusi salasana:";
$PMF_LANG["ad_passwd_con"] = "Varmista:";
$PMF_LANG["ad_passwd_change"] = "Vaihda salasana";
$PMF_LANG["ad_passwd_suc"] = "Salasana vaihdettu onnistuneesti.";
$PMF_LANG["ad_passwd_remark"] = "<strong>HUOMIO:</strong><br />Keksi täytyy tallentaa uudelleen!";
$PMF_LANG["ad_passwd_fail"] = "Vanha salasana <strong>täytyy</strong> syöttää oikein ja kummankin uuden tulee <strong>vastata</strong> toisiaan.";

// Adduser - Correction to ad_adus_suc msg by Matti Kröger
$PMF_LANG["ad_adus_adduser"] = "Lisää käyttäjä";
$PMF_LANG["ad_adus_name"] = "Nimi:";
$PMF_LANG["ad_adus_password"] = "Salasana:";
$PMF_LANG["ad_adus_add"] = "Lisää käyttäjä";
$PMF_LANG["ad_adus_suc"] = "Käyttäjä lisätty <strong>onnistuneesti</strong>.";
$PMF_LANG["ad_adus_edit"] = "Muokkaa profiilia";
$PMF_LANG["ad_adus_dberr"] = "<strong>tietokanta virhe!</strong>";
$PMF_LANG["ad_adus_exerr"] = "Käyttäjätunnus jo <strong>olemassa</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "Istunnon ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Aika";
$PMF_LANG["ad_sess_pageviews"] = "Sivun latauksia";
$PMF_LANG["ad_sess_search"] = "Hae";
$PMF_LANG["ad_sess_sfs"] = "Hae istuntoa";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "minimi. toiminnot:";
$PMF_LANG["ad_sess_s_date"] = "Päivämäärä";
$PMF_LANG["ad_sess_s_after"] = "jälkeen";
$PMF_LANG["ad_sess_s_before"] = "ennen";
$PMF_LANG["ad_sess_s_search"] = "Hae";
$PMF_LANG["ad_sess_session"] = "Istunto";
$PMF_LANG["ad_sess_r"] = "Hakutulokset haulle";
$PMF_LANG["ad_sess_referer"] = "Viittaaja:";
$PMF_LANG["ad_sess_browser"] = "Selain:";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategoria:";
$PMF_LANG["ad_sess_ai_artikel"] = "Tietue:";
$PMF_LANG["ad_sess_ai_sb"] = "Haetut merkkijonot:";
$PMF_LANG["ad_sess_ai_sid"] = "Istunnon ID:";
$PMF_LANG["ad_sess_back"] = "Takaisin";

// Statistik - Correction to ad_rs msg by Matti Kröger
$PMF_LANG["ad_rs"] = "Arvostelu Statistiikat";
$PMF_LANG["ad_rs_rating_1"] = "Tietueelle ";
$PMF_LANG["ad_rs_rating_2"] = "käyttäjät arvostelevat:";
$PMF_LANG["ad_rs_red"] = "Punainen";
$PMF_LANG["ad_rs_green"] = "Vihreä";
$PMF_LANG["ad_rs_altt"] = "on alle 2 keskiarvon";
$PMF_LANG["ad_rs_ahtf"] = "on yli 4 keskiarvon";
$PMF_LANG["ad_rs_no"] = "Arvostelua ei ole saatavilla";

// Auth
$PMF_LANG["ad_auth_insert"] = "Ole hyvä ja syötä käyttäjä tunnuksesi ja salasanasi.";
$PMF_LANG["ad_auth_user"] = "Käyttäjä:";
$PMF_LANG["ad_auth_passwd"] = "Salansana:";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Tyhjennä";
$PMF_LANG["ad_auth_fail"] = "Käyttäjä tai salasana ei ole oikea.";
$PMF_LANG["ad_auth_sess"] = "Istunnon ID on hapannut.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Muokkaa asetuksia";
$PMF_LANG["ad_config_save"] = "Tallenna asetukset";
$PMF_LANG["ad_config_reset"] = "Tyhjennä";
$PMF_LANG["ad_config_saved"] = "Asetuksien tallentaminen onnistui.";
$PMF_LANG["ad_menu_editconfig"] = "Muokkaa asetuksia";
$PMF_LANG["ad_att_none"] = "Liitteitä ei ole saatavilla";
$PMF_LANG["ad_att_att"] = "Liitteet:";
$PMF_LANG["ad_att_add"] = "Liitä tiedosto";
$PMF_LANG["ad_entryins_suc"] = "Tietueen tallentaminen onnistui.";
$PMF_LANG["ad_entryins_fail"] = "Tapahtui virhe.";
$PMF_LANG["ad_att_del"] = "Tuhoa";
$PMF_LANG["ad_att_nope"] = "Liitteitä voi lisätä vain muokatessa.";
$PMF_LANG["ad_att_delsuc"] = "Liitteen tuhoaminen onnistui.";
$PMF_LANG["ad_att_delfail"] = "Liitteen tuhoamisessa tapahtui virhe.";
$PMF_LANG["ad_entry_add"] = "Luo Tietue";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "Varmuuskopio on täydellinen tiedosto tietokanan sisällöstä. Varmuuskopiointi tulisi suorittaa ainakin kerran kuukaudessa. Varmuuskopion muoto on MySQL transaktio tiedosto, joka voidaan palauttaa tietokantaan käyttämällä työkaluja kuten phpMyAdmin tai MySQL tietokannan komentorivi asiakasohjelmaa.";
$PMF_LANG["ad_csv_link"] = "Lataa varmuuskopio";
$PMF_LANG["ad_csv_head"] = "Tee varmuuskopio";
$PMF_LANG["ad_att_addto"] = "Lisää liite tietueeseen";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Tiedosto:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "Tiedoston liittäminen onnistui.";
$PMF_LANG["ad_att_fail"] = "Tiedoston liittämisessä tapahtui virhe.";
$PMF_LANG["ad_att_close"] = "Sulje tämä ikkuna";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Tällä lomakkeella voit palauttaa tietokannan sisällön käyttämällä varmuuskopiota joka on luotu phpmyfaq työkalulla. Huomaa, että olemassaoleva tieto ylikirjoitetaan.";
$PMF_LANG["ad_csv_file"] = "Tiedosto";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "varmuuskopioi lokit";
$PMF_LANG["ad_csv_linkdat"] = "varmuuskopioi data";
$PMF_LANG["ad_csv_head2"] = "Palauta";
$PMF_LANG["ad_csv_no"] = "Tämä ei vaikuta olevan phpmyfaq varmuuskopio.";
$PMF_LANG["ad_csv_prepare"] = "Tietokantatapahtumia valmistellaan...";
$PMF_LANG["ad_csv_process"] = "Suoritetaan...";
$PMF_LANG["ad_csv_of"] = "";
$PMF_LANG["ad_csv_suc"] = "onnistui.";
$PMF_LANG["ad_csv_backup"] = "Varmuuskopio";
$PMF_LANG["ad_csv_rest"] = "Palauta varmuuskopio";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Varmistus";
$PMF_LANG["ad_logout"] = "Istunnon lopetus onnistui.";
$PMF_LANG["ad_news_add"] = "Lisää uutinen";
$PMF_LANG["ad_news_edit"] = "Muokkaa uutisia";
$PMF_LANG["ad_cookie"] = "Keksit";
$PMF_LANG["ad_sess_head"] = "Selaa istuntoja";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Kategorioiden Hallinta";
$PMF_LANG["ad_menu_stat"] = "Arvostelu Tilastot";
$PMF_LANG["ad_kateg_add"] = "lisää Kategoria";
$PMF_LANG["ad_kateg_rename"] = "Nimeä uudelleen";
$PMF_LANG["ad_adminlog_date"] = "Päivämäärä";
$PMF_LANG["ad_adminlog_user"] = "Käyttäjä";
$PMF_LANG["ad_adminlog_ip"] = "IP-Osoite";

$PMF_LANG["ad_stat_sess"] = "Istunnot";
$PMF_LANG["ad_stat_days"] = "Päivät";
$PMF_LANG["ad_stat_vis"] = "Istunnot (Käynnit)";
$PMF_LANG["ad_stat_vpd"] = "Käyntejä per Päivä";
$PMF_LANG["ad_stat_fien"] = "Ensimmäinen Loki";
$PMF_LANG["ad_stat_laen"] = "Viimeinen Loki";
$PMF_LANG["ad_stat_browse"] = "selaa Istuntoja";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Aika";
$PMF_LANG["ad_sess_sid"] = "Istunnon-ID";
$PMF_LANG["ad_sess_ip"] = "IP-Osoite";

$PMF_LANG["ad_ques_take"] = "Ota kysymys ja muokkaa";
$PMF_LANG["no_cats"] = "Kategorioita ei löytynyt.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Väärä käyttäjä tai salasana.";
$PMF_LANG["ad_log_sess"] = "Hapannut istunto.";
$PMF_LANG["ad_log_edit"] = "\"Muokkaa käyttäjää\"-Lomake seuraavalle käyttäjälle: ";
$PMF_LANG["ad_log_crea"] = "\"Uusi Tietue\" lomake.";
$PMF_LANG["ad_log_crsa"] = "Uusi tietue luotu.";
$PMF_LANG["ad_log_ussa"] = "Päivitä tiedot seuraavalle käyttäjälle: ";
$PMF_LANG["ad_log_usde"] = "Seuraava käyttäjä on tuhottu: ";
$PMF_LANG["ad_log_beed"] = "Muokkaus lomake seuraavalle käyttäjälle: ";
$PMF_LANG["ad_log_bede"] = "Seuraava tietue on tuhottu: ";

$PMF_LANG["ad_start_visits"] = "Käynnit";
$PMF_LANG["ad_start_articles"] = "Tietueita";
$PMF_LANG["ad_start_comments"] = "Kommentteja";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "liitä";
$PMF_LANG["ad_categ_cut"] = "leikkaa";
$PMF_LANG["ad_categ_copy"] = "kopioi";
$PMF_LANG["ad_categ_process"] = "Kategorioita käsitellään...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Et ole oikeutettu.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "edellinen sivu";
$PMF_LANG["msgNextPage"] = "seuraava sivu";
$PMF_LANG["msgPageDoublePoint"] = "Sivu: ";
$PMF_LANG["msgMainCategory"] = "Pääkategoria";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Salasanasi on vaihdettu.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "Näytä tämä PDF tiedostona";
$PMF_LANG["ad_xml_head"] = "XML-Varmuuskopio";
$PMF_LANG["ad_xml_hint"] = "Tallenna kaikki FAQ tietueet yhtenä XML tiedostona.";
$PMF_LANG["ad_xml_gen"] = "tee XML tiedosto";
$PMF_LANG["ad_entry_locale"] = "Kieli";
$PMF_LANG["msgLangaugeSubmit"] = "vaihda kieli";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "Esikatsele";
$PMF_LANG["ad_attach_1"] = "Valitse ensin asetuksista hakemisto liitetiedostoille.";
$PMF_LANG["ad_attach_2"] = "Valitse ensin asetuksista linkki liitetiedostoille.";
$PMF_LANG["ad_attach_3"] = "Tiedostoa attachment.php ei voida avata ilman riittäviä oikeuksia.";
$PMF_LANG["ad_attach_4"] = "Liitettävän tiedoston tulee olla pienempi kuin %s tavua.";
$PMF_LANG["ad_menu_export"] = "Export your FAQ";
$PMF_LANG["ad_export_1"] = "Luo RSS-lähde ";
$PMF_LANG["ad_export_2"] = " lähteestä.";
$PMF_LANG["ad_export_file"] = "Virhe: Tiedostoa ei voida kirjoittaa.";
$PMF_LANG["ad_export_news"] = "Uutisten RSS-lähde";
$PMF_LANG["ad_export_topten"] = "Kymmenen suositumman RSS-lähde";
$PMF_LANG["ad_export_latest"] = "Viiden viimeisimmän RSS-lähde";
$PMF_LANG["ad_export_pdf"] = "PDF-kooste kaikista tietueista";
$PMF_LANG["ad_export_generate"] = "luo RSS-lähde";

$PMF_LANG["rightsLanguage"][0] = "lisää käyttäjä";
$PMF_LANG["rightsLanguage"][1] = "muokkaa käyttäjää";
$PMF_LANG["rightsLanguage"][2] = "tuhoa käyttäjä";
$PMF_LANG["rightsLanguage"][3] = "lisää tietue";
$PMF_LANG["rightsLanguage"][4] = "muokkaa tietuetta";
$PMF_LANG["rightsLanguage"][5] = "tuhoa tietue";
$PMF_LANG["rightsLanguage"][6] = "selaa lokia";
$PMF_LANG["rightsLanguage"][7] = "selaa admin lokia";
$PMF_LANG["rightsLanguage"][8] = "tuhoa kommentti";
$PMF_LANG["rightsLanguage"][9] = "lisää uutinen";
$PMF_LANG["rightsLanguage"][10] = "muokkaa uutisia";
$PMF_LANG["rightsLanguage"][11] = "tuhoa uutinen";
$PMF_LANG["rightsLanguage"][12] = "lisää kategoria";
$PMF_LANG["rightsLanguage"][13] = "muokkaa kategoriaa";
$PMF_LANG["rightsLanguage"][14] = "tuhoa kategoria";
$PMF_LANG["rightsLanguage"][15] = "vaihda salasana";
$PMF_LANG["rightsLanguage"][16] = "muokkaa asetuksia";
$PMF_LANG["rightsLanguage"][17] = "lisää liitteitä";
$PMF_LANG["rightsLanguage"][18] = "tuhoa liitteitä";
$PMF_LANG["rightsLanguage"][19] = "luo varmuuskopio";
$PMF_LANG["rightsLanguage"][20] = "palauta varmuuskopio";
$PMF_LANG["rightsLanguage"][21] = "tuhoa avoimia kysymyksiä";

$PMF_LANG["msgAttachedFiles"] = "liitetyt tiedostot:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "toiminto";
$PMF_LANG["ad_entry_email"] = "sähköpostiosoite:";
$PMF_LANG["ad_entry_allowComments"] = "salli kommentointi";
$PMF_LANG["msgWriteNoComment"] = "Tämän tietueen kommentointi ei ole mahdollista";
$PMF_LANG["ad_user_realname"] = "oikea nimi:";
$PMF_LANG["ad_export_generate_pdf"] = "koosta PDF tiedosto";
$PMF_LANG["ad_export_full_faq"] = "FAQ kooste PDF tiedostona: ";
$PMF_LANG["err_bannedIP"] = "Käyttö IP-osoitteestasi on estetty.";
$PMF_LANG["err_SaveQuestion"] = "Vaadittavat kentät ovat <strong>nimesi</strong>, <strong>sähköposti osoitteesi</strong> ja <strong>kysymyksesi</strong>.<br /><br /><a href=\"javascript:history.back();\">yksi sivu taaksepäin</a><br /><br />\n";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "Tekstin väri: ";
$PMF_LANG["ad_entry_fontsize"] = "Tekstin koko: ";

/* added v1.4.0 - 2003-12-04 by Thorsten / Mathias - Translation of main.metaKeywords, mod_rewrite & main.ldapSupport msg by Matti Kröger*/
$LANG_CONF['main.language'] = array(0 => "select", 1 => "Kieli-Tiedosto");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "Salli automaattinen sisällön kätteleminen");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "FAQ Otsikko");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "FAQ Versio");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "Sivun Kuvaus");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "Avainsanat hakukoneille");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "Julkaisijan nimi");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "Ylläpitäjän sähköposti osoite");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "Yhteystiedot");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "Teksti lähetä tuttavalle sivulle");
$LANG_CONF['main.maxAttachmentSize'] = array(0 => "input", 1 => "suurin sallittu koko liitetiedostoille tavuina on  (max. %stavua)");
$LANG_CONF["main.disableAttachments"] = array(0 => "checkbox", 1 => "Linkitä liitteet tietueiden alpuolelle?");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "käytä lokia?");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "käytä ylläpitolokia?");
$LANG_CONF["main.ipCheck"] = array(0 => "checkbox", 1 => "Haluatko, että admin.php:ssa IP tarkastetaan samalla kun UIN?");
$LANG_CONF["records.numberOfRecordsPerPage"] = array(0 => "input", 1 => "Näytettyjen tietueiden määrä per sivu");
$LANG_CONF["records.numberOfShownNewsEntries"] = array(0 => "input", 1 => "Uutisten määrä");
$LANG_CONF['main.bannedIPs'] = array(0 => "area", 1 => "Estä käyttö näistä IP-osoitteista");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "Aktivoi mod_rewrite tuki? (oletus: ei käytössä)");
$LANG_CONF["main.ldapSupport"] = array(0 => "checkbox", 1 => "Haluatko laittaa LDAP tuen päälle(enabled)? (oletus: ei käytössä)");

/*Correction to ad_categ_desc & ad_categ_paste_error msg by Matti Kröger*/
$PMF_LANG["ad_categ_new_main_cat"] = "uutena pää-kategoriana";
$PMF_LANG["ad_categ_paste_error"] = "Tämän kategorian siirto ei ole mahdollista.";
$PMF_LANG["ad_categ_move"] = "siirrä kategoriaa";
$PMF_LANG["ad_categ_lang"] = "Kieli";
$PMF_LANG["ad_categ_desc"] = "Kuvaus";
$PMF_LANG["ad_categ_change"] = "Vaihda kanssa ";

//Correction to lostpwd_mail_okay msg by Matti Kröger
$PMF_LANG["lostPassword"] = "Unohtuiko salasana? Klikkaa tästä.";
$PMF_LANG["lostpwd_err_1"] = "Virhe: Käyttäjää ja sähköposti osoitetta ei löydy.";
$PMF_LANG["lostpwd_err_2"] = "Virhe: Väärät syötteet!";
$PMF_LANG["lostpwd_text_1"] = "Kiitokset käyttöoikeuksesi tietojen pyytämisestä.";
$PMF_LANG["lostpwd_text_2"] = "Ole hyvä ja aseta uusi henkilökohtainen salasanasi FAQ:n ylläptio osiolle.";
$PMF_LANG["lostpwd_mail_okay"] = "Sähköposti on lähetetty.";

$PMF_LANG["ad_xmlrpc_button"] = "Saa viimeisin phpMyFAQ versio numero webistä";
$PMF_LANG["ad_xmlrpc_latest"] = "Viimeisin saatavilla oleva versio on";

/* added v1.5.0 - 2005-07-31 by Thorsten - Translation of ad_categ_select msg by Matti Kröger*/
$PMF_LANG['ad_categ_select'] = 'Valitse kategorian kieli';

/* added v1.5.1 - 2005-09-06 by Thorsten - Translation of msgSitemap msg by Matti Kröger*/
$PMF_LANG['msgSitemap'] = 'Sivukartta';

// added v1.5.2 - 2005-09-23 by Lars - Translation to Finnish by Matti Kröger
$PMF_LANG['err_inactiveArticle'] = 'Tämä artikkeli on päivityksen alaisena ja sitä ei voida näyttää tällä hetkellä';
$PMF_LANG['msgArticleCategories'] = 'Kategoriat tälle artikkelille';

/* added v1.5.3 - 2005-10-04 by Thorsten and Periklis - Translation to Finnish by Matti Kröger*/
$PMF_LANG['ad_menu_searchplugin'] = 'Firefox haku -lisäosa';
$PMF_LANG['ad_search_plugin_install'] = 'Asenna Firefox haku -lisäosa';
$PMF_LANG['ad_search_plugin_title'] = 'Luo Firefoxin haun lisäosa';
$PMF_LANG['ad_search_plugin_ttitle'] = 'Otsikko Firefoxin hakukentälle';
$PMF_LANG['ad_search_plugin_tdesc'] = 'Kuvaus';
$PMF_LANG['ad_search_plugin_create'] = 'Luo Firefoxin haun lisäosa';
$PMF_LANG['ad_search_plugin_success'] = 'Mozilla Firefox haku -lisäosa luotu onnistuneesti!';

/* added v1.6.0 - 2006-02-02 by Thorsten - Translation to Finnish by Matti Kröger*/
$PMF_LANG['ad_entry_solution_id'] = 'Ainutkertainen ratkaisu ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ tietue';
$PMF_LANG['ad_entry_new_revision'] = 'Luo uusi tarkistettu versio, revisio?';
$PMF_LANG['ad_entry_record_administration'] = 'Tietueen ylläpito';
$PMF_LANG['ad_entry_changelog'] = 'Muutosrekisteri';
$PMF_LANG['ad_entry_revision'] = 'Revisio';
$PMF_LANG['ad_changerev'] = 'Valitse revisio';
$PMF_LANG['msgCaptcha'] = 'Kirjoita merkit kuvassa';
$PMF_LANG['msgSelectCategories'] = 'Etsi...';
$PMF_LANG['msgAllCategories'] = '... kaikissa kategorioissa';
$PMF_LANG['ad_you_should_update'] = 'Teidän phpMyFAQ-asennuksenne on vanhentunut. Teidän tulisi päivittää uusimpaan versioon';
$PMF_LANG['msgAdvancedSearch'] = 'Tarkennettu haku';

/* added v1.6.1 - 2006-04-25 by Matteo and Thorsten - Translation to Finnish by Matti Kröger*/
$PMF_LANG['spamControlCenter'] = 'Roskapostin hallinta keskus';
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "Tulosta käyttäjän email turvallisessa muodossa (oletus: aktiivinen).");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "Tarkista julkaistavien lomakkeiden sisältö kiellettyjen sanojen listaan (oletus: aktiivinen).");
$LANG_CONF["spam.enableCaptchaCode"] = array(0 => "checkbox", 1 => "Käytä captcha koodia julkisten lomakkeiden t&#228;ytön yhteydessä varmistamaan henkilön aitous (oletus: aktiivinen).");
$PMF_LANG['ad_firefoxsearch_plugin_title'] = 'Luo Firefoxin haun lisäosa';
$PMF_LANG['ad_msiesearch_plugin_install'] = 'Asenna Microsoft Internet Explorer 7 haun lisäosa';
$PMF_LANG['ad_msiesearch_plugin_title'] = 'Luo Microsoft Internet Explorer 7 haun lisäosa';
$PMF_LANG['ad_msiesearch_plugin_ttitle'] = 'Otsikko MSIE 7 hakukentälle:';
$PMF_LANG['ad_msiesearch_plugin_create'] = 'Luo Microsoft Internet Explorer 7 haun lisäosa.';
$PMF_LANG['ad_msiesearch_plugin_success'] = 'Microsoft Internet Explorer 7 haun lisäosa luotiin onnistuneesti!';
$PMF_LANG['ad_session_expiring'] = 'Istuntosi er&#228;&#228;ntyy %d minuutin p&#228;&#228;st&#228;: haluatko kuitenkin jatkaa ty&#246;skentely&#228;?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Istuntojen hallinta';
$PMF_LANG['ad_stat_choose'] = 'Valitse kuukaus';
$PMF_LANG['ad_stat_delete'] = 'Tuhoa valitut istunnot välittömästi';
