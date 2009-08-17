<?php
/**
* $Id: language_nb.php,v 1.22 2007-03-29 19:31:55 thorstenr Exp $
*
* Norwegian Bokmål language file
*
* @author       Hans Fredrik Nordhaug <hans@nordhaug.priv.no>
* @since        2005-08-31
* @copyright    (c) 2006 phpMyFAQ Team
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
$PMF_LANG["metaLanguage"] = "nb";
$PMF_LANG["language"] = "Norwegian Bokmål";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";

$PMF_LANG["nplurals"] = 2;
/**
 * Check inc/PMF_Language/Plurals.php to see if this language has plural form support.
 * If it doesn't English plural messages will be used.
 * You can add support for this language by editing the function plural()
 * and adding the correct expression for this language.
 * If you need any help, please contact phpMyFAQ team.
 */

// Navigation
$PMF_LANG["msgCategory"] = "Kategorier";
$PMF_LANG["msgShowAllCategories"] = "Vis alle kategorier";
$PMF_LANG["msgSearch"] = "Søk";
$PMF_LANG["msgAddContent"] = "Legg til innhold";
$PMF_LANG["msgQuestion"] = "Still spørsmål";
$PMF_LANG["msgOpenQuestions"] = "Ubesvarte spørsmål";
$PMF_LANG["msgHelp"] = "Hjelp";
$PMF_LANG["msgContact"] = "Kontakt";
$PMF_LANG["msgHome"] = "Til forsiden";
$PMF_LANG["msgNews"] = "OSS-nyheter";
$PMF_LANG["msgUserOnline"] = " brukere online";
$PMF_LANG["msgXMLExport"] = "XML-fil";
$PMF_LANG["msgBack2Home"] = "til forsiden";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategorier med innhold";
$PMF_LANG["msgFullCategoriesIn"] = "kategorier med innhold i  ";
$PMF_LANG["msgSubCategories"] = "underkategorier";
$PMF_LANG["msgEntries"] = "Innlegg";
$PMF_LANG["msgEntriesIn"] = "Innlegg i ";
$PMF_LANG["msgViews"] = "visninger";
$PMF_LANG["msgPage"] = "Side ";
$PMF_LANG["msgPages"] = "Sider";
$PMF_LANG["msgPrevious"] = "foregående";
$PMF_LANG["msgNext"] = "neste";
$PMF_LANG["msgKategoriUp"] = "en kategori opp";
$PMF_LANG["msgLastUpdateArticle"] = "Siste oppdatering: ";
$PMF_LANG["msgAuthor"] = "Forfatter: ";
$PMF_LANG["msgPrinterFriendly"] = "Utskriftsvennligversjon";
$PMF_LANG["msgPrintArticle"] = "Skriv ut dette svaret";
$PMF_LANG["msgMakeXMLExport"] = "Eksporter som XML-fil";
$PMF_LANG["msgAverageVote"] = "Gjennomsnittlig vurdering:";
$PMF_LANG["msgVoteUseability"] = "Vurder dette innlegget:";
$PMF_LANG["msgVoteFrom"] = "fra";
$PMF_LANG["msgVoteBad"] = "fullstendig&nbsp;ubrukelig";
$PMF_LANG["msgVoteGood"] = "meget&nbsp;nyttig";
$PMF_LANG["msgVotings"] = "Stemmer ";
$PMF_LANG["msgVoteSubmit"] = "Stem";
$PMF_LANG["msgVoteThanks"] = "mange takk for din stemme!";
$PMF_LANG["msgYouCan"] = "Du kan ";
$PMF_LANG["msgWriteComment"] = "Kommentere dette svar";
$PMF_LANG["msgShowKategori"] = "Innhold: ";
$PMF_LANG["msgCommentBy"] = "kommentar fra  ";
$PMF_LANG["msgCommentHeader"] = "Kommentar til dette indlegg";
$PMF_LANG["msgYourComment"] = "Dine kommentarer:";
$PMF_LANG["msgCommentThanks"] = "Mange takk for din kommentar!";
$PMF_LANG["msgSeeXMLFile"] = "Åpne XML-fil";
$PMF_LANG["msgSend2Friend"] = "Send til en venn";
$PMF_LANG["msgS2FName"] = "Ditt navn:";
$PMF_LANG["msgS2FEMail"] = "Din e-postadresse:";
$PMF_LANG["msgS2FFriends"] = "Dine venners:";
$PMF_LANG["msgS2FEMails"] = ". E-postadresser:";
$PMF_LANG["msgS2FText"] = "Den følgende tekst vil bli sendt:";
$PMF_LANG["msgS2FText2"] = "Du vil finne innlegget på følgende adresse:";
$PMF_LANG["msgS2FMessage"] = "Ytterligere informasjon til dine venner:";
$PMF_LANG["msgS2FButton"] = "send e-post";
$PMF_LANG["msgS2FThx"] = "Takk for din anbefaling!";
$PMF_LANG["msgS2FMailSubject"] = "Anbefaling fra ";

// Search
$PMF_LANG["msgSearchWord"] = "Søkeord";
$PMF_LANG["msgSearchFind"] = "Søkeresultat for ";
$PMF_LANG["msgSearchAmount"] = " søkeresultat";
$PMF_LANG["msgSearchAmounts"] = " søkeresultater";
$PMF_LANG["msgSearchCategory"] = "Kategori: ";
$PMF_LANG["msgSearchContent"] = "innhold: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Forslag til OSS";
$PMF_LANG["msgNewContentAddon"] = "Ditt innlegg blir ikke offenliggjort med det samme, da det først skal gjennomleses av en administrator. <br />Nødvendige felter: <strong>ditt navn</strong>, <strong>din e-postadresse</strong>, <strong>kategori</strong>, <strong>overskrift</strong> og <strong>ditt forslag</strong>.<br />Skill nøkkelord med mellomrom (ikke bruk komma).";
$PMF_LANG["msgNewContentName"] = "Ditt navn:";
$PMF_LANG["msgNewContentMail"] = "Din e-postadresse:";
$PMF_LANG["msgNewContentCategory"] = "Velg kategori:";
$PMF_LANG["msgNewContentTheme"] = "Overskrift:";
$PMF_LANG["msgNewContentArticle"] = "Ditt innlegg:";
$PMF_LANG["msgNewContentKeywords"] = "Nøkkelord:";
$PMF_LANG["msgNewContentLink"] = "URL til dette innlegg";
$PMF_LANG["msgNewContentSubmit"] = "Send";
$PMF_LANG["msgInfo"] = "Ytterligere informasjon: ";
$PMF_LANG["msgNewContentThanks"] = "Takk for ditt forslag!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Der er fortiden ingen utestående spørsmål.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Still ditt spørsmål nedenfor:";
$PMF_LANG["msgAskCategory  "] = "Velg kategori:";
$PMF_LANG["msgAskYourQuestion"] = "Ditt spørsmål:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>Takk for din e-post!</h2>";
$PMF_LANG["msgDate_User"] = "Dato / Bruker";
$PMF_LANG["msgQuestion2"] = "Spørsmål";
$PMF_LANG["msg2answer"] = "Svar";
$PMF_LANG["msgQuestionText"] = "Her kan du se spørsmål opprettet av andre brukere. Hvis du besvarer et spørsmål, blir ditt svar muligens brukt.";

// Help
$PMF_LANG["msgHelpText"] = "<p>Strukturen i denne OSS (<strong>O</strong>fte <strong>S</strong>tilte <strong>S</strong>pøsrmål) / FAQ (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) er virkelig enkel. Du kan enten søke i <strong><a href=\"?action=show\">kategoriene</a></strong> eller etter <strong><a href=\"?action=search\">nøkkelord</a></strong>.</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "Send e-post til webmaster:";
$PMF_LANG["msgMessage"] = "Din beskjed:";

// Startseite
$PMF_LANG["msgNews"] = " Nyheter";
$PMF_LANG["msgTopTen"] = "TOPP 10";
$PMF_LANG["msgHomeThereAre"] = "Der er ";
$PMF_LANG["msgHomeArticlesOnline"] = " aktive spørsmål";
$PMF_LANG["msgNoNews"] = "Ingen nyheter er gode nyheter.";
$PMF_LANG["msgLatestArticles"] = "De fem siste spørsmålene:";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Tusen takk for ditt innlegg.";
$PMF_LANG["msgMailCheck"] = "Det er et nytt innlegg i OSS!Kontroller administratorsiden!";
$PMF_LANG["msgMailContact"] = "Din beskjed er sendt til administratoren.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "Ingen forbindelse til databasen.";
$PMF_LANG["err_noHeaders"] = "Ingen kategori funnet.";
$PMF_LANG["err_noArticles"] = "<p>Ingen innlegg funnet.</p>";
$PMF_LANG["err_badID"] = "<p>Feil ID.</p>";
$PMF_LANG["err_noTopTen"] = "<p>Ikke nok innlegg til en Topp 10.</p>";
$PMF_LANG["err_nothingFound"] = "<p>ingen innlegg funnet.</p>";
$PMF_LANG["err_SaveEntries"] = "Nødvendige felter: <strong>ditt navn</strong>, <strong>din e-postadresse</strong>, <strong>kategori</strong>, <strong>overskrift</strong> og <strong>ditt innlegg</strong>!<br /><br />\n<a href=\"javascript:history.back();\">en side tilbake</a><br /><br />\n";
$PMF_LANG["err_SaveComment"] = "Nødvendige felter: <strong>ditt navn</strong>, <strong>din e-postadresse</strong> og <strong>dine kommentarer</strong>!<br /><br />\n<a href=\"javascript:history.back();\">en side tilbake</a><br /><br />\n";
$PMF_LANG["err_VoteTooMuch"] = "<p>Vi teller ikke dobbeltstemmer. <a href=\"javascript:history.back();\">Gå tilbake</a>.</p>";
$PMF_LANG["err_noVote"] = "<p><strong>Du vurderte ikke spørsmålet!</strong> <a href=\"javascript:history.back();\">Gå tilbake</a>, for å stemme.</p>";
$PMF_LANG["err_noMailAdress"] = "din e-postadresse er ikke korrekt.<br /><a href=\"javascript:history.back();\">tilbake</a>";
$PMF_LANG["err_sendMail"] = "Nødvendige felter: <strong>dit navn</strong>, <strong>din email addresse</strong> og <strong>dit spørsmål</strong>!<br /><br />\n<a href=\"javascript:history.back();\">en side tilbake</a><br /><br />\n";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>Søk etter spørsmål/svar:</strong><br /> Med en søketekst som <strong style=\"color: Red;\">ord1 ord2</strong> kan du gjøre et relevansesøk for to eller flere søkebetingelser.</p><p><strong>Merk:</strong> Din søkebetingelse må være minst 4 tegn langt hvis ikke vil søket ditt bli avvist.</p>";

// MenÃ¼
$PMF_LANG["ad"] = "ADMIN SECTION";
$PMF_LANG["ad_menu_user_administration"] = "Brukeradministrasjon";
$PMF_LANG["ad_menu_entry_aprove"] = "Godkjenn spørsmål/svar";
$PMF_LANG["ad_menu_entry_edit"] = "Rediger spørsmål/svar";
$PMF_LANG["ad_menu_categ_add"] = "Legg til kategori";
$PMF_LANG["ad_menu_categ_edit"] = "Rediger kategori";
$PMF_LANG["ad_menu_news_add"] = "Legg til nyheter";
$PMF_LANG["ad_menu_news_edit"] = "Rediger nyheter";
$PMF_LANG["ad_menu_open"] = "Rediger åpne spørsmål";
$PMF_LANG["ad_menu_stat"] = "Statistikk";
$PMF_LANG["ad_menu_cookie"] = "Cookies";
$PMF_LANG["ad_menu_session"] = "Se økter";
$PMF_LANG["ad_menu_adminlog"] = "Se adminlogg";
$PMF_LANG["ad_menu_passwd"] = "Endre passord";
$PMF_LANG["ad_menu_logout"] = "Logg av";
$PMF_LANG["ad_menu_startpage"] = "Startside";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Profilnavn.";
$PMF_LANG["ad_msg_passmatch"] = "Begge passord skal være <strong>like</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profil af";
$PMF_LANG["ad_msg_savedsuc_2"] = "ble lagret.";
$PMF_LANG["ad_msg_mysqlerr"] = "på grunn av <strong>databasefeil</strong>, kunne profilen ikke lagres.";
$PMF_LANG["ad_msg_noauth"] = "Du er ikke autoriseret.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Side";
$PMF_LANG["ad_gen_of"] = "av";
$PMF_LANG["ad_gen_lastpage"] = "Forrige side";
$PMF_LANG["ad_gen_nextpage"] = "Neste side";
$PMF_LANG["ad_gen_save"] = "Lagre";
$PMF_LANG["ad_gen_reset"] = "Tøm";
$PMF_LANG["ad_gen_yes"] = "Ja";
$PMF_LANG["ad_gen_no"] = "Nei";
$PMF_LANG["ad_gen_top"] = "Toppen av siden";
$PMF_LANG["ad_gen_ncf"] = "Ingen kategori funnet!";
$PMF_LANG["ad_gen_delete"] = "Slett";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "Brukeradministrasjon";
$PMF_LANG["ad_user_username"] = "Registrerede brukere";
$PMF_LANG["ad_user_rights"] = "Brukerrettigheter";
$PMF_LANG["ad_user_edit"] = "Rediger";
$PMF_LANG["ad_user_delete"] = "Slett";
$PMF_LANG["ad_user_add"] = "Legg til bruker";
$PMF_LANG["ad_user_profou"] = "Brukerprofil";
$PMF_LANG["ad_user_name"] = "Navn";
$PMF_LANG["ad_user_password"] = "Passord";
$PMF_LANG["ad_user_confirm"] = "Godkjenn";
$PMF_LANG["ad_user_rights"] = "Rettigheter";
$PMF_LANG["ad_user_del_1"] = "Brukeren";
$PMF_LANG["ad_user_del_2"] = "skal slettes?";
$PMF_LANG["ad_user_del_3"] = "Er du sikker?";
$PMF_LANG["ad_user_deleted"] = "Brukeren er slettet.";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "Administrasjon av spørsmål/svar";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Emne";
$PMF_LANG["ad_entry_action"] = "Handling";
$PMF_LANG["ad_entry_edit_1"] = "Rediger spørsmål/svar";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Tema:";
$PMF_LANG["ad_entry_content"] = "Innhold:";
$PMF_LANG["ad_entry_keywords"] = "Nøkkelord:";
$PMF_LANG["ad_entry_author"] = "Forfatter:";
$PMF_LANG["ad_entry_category"] = "Kategori:";
$PMF_LANG["ad_entry_active"] = "Aktiv?";
$PMF_LANG["ad_entry_date"] = "Dato:";
$PMF_LANG["ad_entry_changed"] = "Endret?";
$PMF_LANG["ad_entry_changelog"] = "Historikk:";
$PMF_LANG["ad_entry_commentby"] = "Kommentar av";
$PMF_LANG["ad_entry_comment"] = "Kommentarer:";
$PMF_LANG["ad_entry_save"] = "Lagre";
$PMF_LANG["ad_entry_delete"] = "Slett";
$PMF_LANG["ad_entry_delcom_1"] = "Er du sikker på at brukerens kommentar";
$PMF_LANG["ad_entry_delcom_2"] = "skal slettes?";
$PMF_LANG["ad_entry_commentdelsuc"] = "Kommentaren er <strong>slettet</strong>.";
$PMF_LANG["ad_entry_back"] = "Tilbake til artikkelen";
$PMF_LANG["ad_entry_commentdelfail"] = "Kommentaren ble <strong>ikke</strong> slettet.";
$PMF_LANG["ad_entry_savedsuc"] = "Endringene ble <strong>lagret</strong>.";
$PMF_LANG["ad_entry_savedfail"] = "Dessverre oppstod en <strong>databasefeil</strong>.";
$PMF_LANG["ad_entry_del_1"] = "Er du sikker på at emnet";
$PMF_LANG["ad_entry_del_2"] = "av";
$PMF_LANG["ad_entry_del_3"] = "skal slettes?";
$PMF_LANG["ad_entry_delsuc"] = "Er <strong>slettet</strong>.";
$PMF_LANG["ad_entry_delfail"] = "Er <strong>ikke slettet</strong>!";
$PMF_LANG["ad_entry_back"] = "Tilbake";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "Artikkeltittel";
$PMF_LANG["ad_news_text"] = "Spørsmålets tekst";
$PMF_LANG["ad_news_link_url"] = "Lenke: (<strong>uten http://</strong>)!";
$PMF_LANG["ad_news_link_title"] = "Tittel på lenke:";
$PMF_LANG["ad_news_link_target"] = "Destinasjon for lenke";
$PMF_LANG["ad_news_link_window"] = "Lenke åpner nytt vindu";
$PMF_LANG["ad_news_link_faq"] = "Lenke inne i FAQ";
$PMF_LANG["ad_news_add"] = "Legg til nyheter";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Tittel";
$PMF_LANG["ad_news_date"] = "Dato";
$PMF_LANG["ad_news_action"] = "Handling";
$PMF_LANG["ad_news_update"] = "Oppdater";
$PMF_LANG["ad_news_delete"] = "Slett";
$PMF_LANG["ad_news_nodata"] = "Ingen data funnet i databasen";
$PMF_LANG["ad_news_updatesuc"] = "Nyhetene ble oppdatert.";
$PMF_LANG["ad_news_del"] = "Er du sikker på at ville slette dette?";
$PMF_LANG["ad_news_yesdelete"] = "Ja, slett!";
$PMF_LANG["ad_news_nodelete"] = "Nei!";
$PMF_LANG["ad_news_delsuc"] = "Nyhet slettet.";
$PMF_LANG["ad_news_updatenews"] = "Oppdater nyheter";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Legg til ny kategori";
$PMF_LANG["ad_categ_catnum"] = "Kategorinummer:";
$PMF_LANG["ad_categ_subcatnum"] = "Underkategorinummer:";
$PMF_LANG["ad_categ_nya"] = "<em>Ikke klar ennu!</em>";
$PMF_LANG["ad_categ_titel"] = "Kategoritittel:";
$PMF_LANG["ad_categ_add"] = "Legg til kategori";
$PMF_LANG["ad_categ_existing"] = "Eksisterende kategorier";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategori";
$PMF_LANG["ad_categ_subcateg"] = "Underkategori";
$PMF_LANG["ad_categ_titel"] = "Kategoritittel";
$PMF_LANG["ad_categ_action"] = "Handling";
$PMF_LANG["ad_categ_update"] = "oppdater";
$PMF_LANG["ad_categ_delete"] = "slett";
$PMF_LANG["ad_categ_updatecateg"] = "Oppdater kategori";
$PMF_LANG["ad_categ_nodata"] = "Ingen data funnet i databasen";
$PMF_LANG["ad_categ_remark"] = "Vær oppmerksom på at de eksisterende data ikke vil være tilgjengelige lenger, hvis du sletter en kategori. Du må tildele en ny katefori for artikkelen eller slette artikkelen.";
$PMF_LANG["ad_categ_edit_1"] = "Ret";
$PMF_LANG["ad_categ_edit_2"] = "Kategori";
$PMF_LANG["ad_categ_add"] = "Legg til kategori";
$PMF_LANG["ad_categ_added"] = "Kategorien er lagt til.";
$PMF_LANG["ad_categ_updated"] = "Kategorien er oppdatert.";
$PMF_LANG["ad_categ_del_yes"] = "ja, slett!";
$PMF_LANG["ad_categ_del_no"] = "nei!";
$PMF_LANG["ad_categ_deletesure"] = "Er du sikker på at du vil slettet denne kategorien?";
$PMF_LANG["ad_categ_deleted"] = "Kategori slettet.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "En Cookie <strong>ble</strong> lagret.";
$PMF_LANG["ad_cookie_already"] = "En cookie er sat i forvejen. Du har følgende muligheter:";
$PMF_LANG["ad_cookie_again"] = "Lagre cookie igjen";
$PMF_LANG["ad_cookie_delete"] = "slett cookie";
$PMF_LANG["ad_cookie_no"] = "Ingen cookie er lagret enda. Med en cookie, kan du huske dine login-detaljer. Du har følgende muligheter:";
$PMF_LANG["ad_cookie_set"] = "Lagre cookie";
$PMF_LANG["ad_cookie_deleted"] = "Cookie slettet.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "Adminlogg";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Skift passord";
$PMF_LANG["ad_passwd_old"] = "Gammelt passord:";
$PMF_LANG["ad_passwd_new"] = "Nytt passord:";
$PMF_LANG["ad_passwd_con"] = "Bekreft:";
$PMF_LANG["ad_passwd_change"] = "Skift passord";
$PMF_LANG["ad_passwd_suc"] = "Passord skiftet.";
$PMF_LANG["ad_passwd_remark"] = "<strong>SE HER:</strong><br />Cookie skal lagres igjen!";
$PMF_LANG["ad_passwd_fail"] = "Det gamle passordet <strong>skal</strong> må oppgies korrekt og begge passord skal være <strong>like</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Legg til bruker";
$PMF_LANG["ad_adus_name"] = "Navn:";
$PMF_LANG["ad_adus_password"] = "Passord:";
$PMF_LANG["ad_adus_add"] = "Legg til bruker";
$PMF_LANG["ad_adus_suc"] = "Bruker <strong>lagt til</strong>.";
$PMF_LANG["ad_adus_edit"] = "Rediger profil";
$PMF_LANG["ad_adus_dberr"] = "<strong>databasefeil!</strong>";
$PMF_LANG["ad_adus_exerr"] = "Brukernavn <strong>eksisterer</strong> allerede.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "ID for økt";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Tidspunkter";
$PMF_LANG["ad_sess_pageviews"] = "Sidevisninger";
$PMF_LANG["ad_sess_search"] = "Søk";
$PMF_LANG["ad_sess_sfs"] = "Søk etter økter";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. aksjoner:";
$PMF_LANG["ad_sess_s_date"] = "Dato";
$PMF_LANG["ad_sess_s_after"] = "etter";
$PMF_LANG["ad_sess_s_before"] = "før";
$PMF_LANG["ad_sess_s_search"] = "Søk";
$PMF_LANG["ad_sess_session"] = "Økt";
$PMF_LANG["ad_sess_r"] = "Søkeresultater for";
$PMF_LANG["ad_sess_referer"] = "Henviser:";
$PMF_LANG["ad_sess_browser"] = "Nettleser:";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategori:";
$PMF_LANG["ad_sess_ai_artikel"] = "Felt:";
$PMF_LANG["ad_sess_ai_sb"] = "Søketekst:";
$PMF_LANG["ad_sess_ai_sid"] = "ID for økt:";
$PMF_LANG["ad_sess_back"] = "Tilbake";

// Statistik
$PMF_LANG["ad_rs"] = "Statistikk";
$PMF_LANG["ad_rs_rating_1"] = "Oppdeling av";
$PMF_LANG["ad_rs_rating_2"] = "brukere viser:";
$PMF_LANG["ad_rs_red"] = "Rød";
$PMF_LANG["ad_rs_green"] = "Grønn";
$PMF_LANG["ad_rs_altt"] = "med et gjennomsnitt lavere enn 2";
$PMF_LANG["ad_rs_ahtf"] = "med et gjennomsnitt højere enn 4";
$PMF_LANG["ad_rs_no"] = "Ingen oppdeling tilgjengelig";

// Auth
$PMF_LANG["ad_auth_insert"] = "Skriv nn brukernavn og passord.";
$PMF_LANG["ad_auth_user"] = "Brukernavn:";
$PMF_LANG["ad_auth_passwd"] = "Passord:";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Tøm";
$PMF_LANG["ad_auth_fail"] = "Brukernavn eller passord ikke korrekt.";
$PMF_LANG["ad_auth_sess"] = "ID for økten er ok.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Rediger konfigurasjon";
$PMF_LANG["ad_config_save"] = "Gem konfigurasjon";
$PMF_LANG["ad_config_reset"] = "Tøm";
$PMF_LANG["ad_config_saved"] = "Konfiguration blev gemt.";
$PMF_LANG["ad_menu_editconfig"] = "Rediger konfigurasjon";
$PMF_LANG["ad_att_none"] = "Ingen vedlegg tilgjengelige";
$PMF_LANG["ad_att_att"] = "Vedlegg:";
$PMF_LANG["ad_att_add"] = "Legg til vedlegg";
$PMF_LANG["ad_entryins_suc"] = "Spørsmål/svar lagret.";
$PMF_LANG["ad_entryins_fail"] = "En feil oppstod.";
$PMF_LANG["ad_att_del"] = "Slett";
$PMF_LANG["ad_att_nope"] = "Vedlegg kan kun legges til mens man rediger.";
$PMF_LANG["ad_att_delsuc"] = "Vedlegget er slettet.";
$PMF_LANG["ad_att_delfail"] = "En feil oppstod under slettingen av vedlegget.";
$PMF_LANG["ad_entry_add"] = "Opprett spørsmål/svar";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "En sikkerhetskopi er et komplett bilde av databasens innhold. En sikkerhetskopi burde foretaes minst 1 gang om måneden. Sikkerhetskopi-formatet en en MySQL transaksjonsfil, som kan importeres ved hjelp av verktøy som phpMyAdmin eller mysql kommadolinje-verktøyet.";
$PMF_LANG["ad_csv_link"] = "Hent sikkerhetskopi";
$PMF_LANG["ad_csv_head"] = "Lag sikkerhetskopi";
$PMF_LANG["ad_att_addto"] = "Legg til et vedlegg til emnet";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Fil:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "Filen er lagt til.";
$PMF_LANG["ad_att_fail"] = "En feil oppstod mens filen ble lagt til.";
$PMF_LANG["ad_att_close"] = "Luk dette vindue";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Med dette skjemaet kan du gjenopprette innholdet i databasen, laget med phpMyFAQ. Vær oppmerksom på at eksisterende data vil bli overskrevet.";
$PMF_LANG["ad_csv_file"] = "Fil";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "sikkerhetskopier loggfiler";
$PMF_LANG["ad_csv_linkdat"] = "sikkerhetskopier data";
$PMF_LANG["ad_csv_head2"] = "Gjenopprett";
$PMF_LANG["ad_csv_no"] = "Dette ser ikke ut til å være en sikkerhetskopi av phpMyFAQ.";
$PMF_LANG["ad_csv_prepare"] = "Forbereder databaseforespørsler...";
$PMF_LANG["ad_csv_process"] = "Forespørsel...";
$PMF_LANG["ad_csv_of"] = "av";
$PMF_LANG["ad_csv_suc"] = "gjennomført.";
$PMF_LANG["ad_csv_backup"] = "Sikkerhetskopi";
$PMF_LANG["ad_csv_rest"] = "Gjenopprett fra sikkerhetskopi";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Sikkerhetskopi";
$PMF_LANG["ad_logout"] = "Økt avbrudt.";
$PMF_LANG["ad_news_add"] = "Legg til nyheter";
$PMF_LANG["ad_news_edit"] = "Rediger nyheter";
$PMF_LANG["ad_cookie"] = "Cookies";
$PMF_LANG["ad_sess_head"] = "Se økter";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Kategoriadminstrasjon";
$PMF_LANG["ad_menu_stat"] = "Statistikk";
$PMF_LANG["ad_kateg_add"] = "Legg til kategori";
$PMF_LANG["ad_kateg_rename"] = "Endre navn";
$PMF_LANG["ad_adminlog_date"] = "Dato";
$PMF_LANG["ad_adminlog_user"] = "Bruker";
$PMF_LANG["ad_adminlog_ip"] = "IP-adresse";

$PMF_LANG["ad_stat_sess"] = "Økter";
$PMF_LANG["ad_stat_days"] = "Dager";
$PMF_LANG["ad_stat_vis"] = "Økter (besøk)";
$PMF_LANG["ad_stat_vpd"] = "Besøk per dag";
$PMF_LANG["ad_stat_fien"] = "Første logg";
$PMF_LANG["ad_stat_laen"] = "Siste logg";
$PMF_LANG["ad_stat_browse"] = "Se gjennom økter";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Tid";
$PMF_LANG["ad_sess_sid"] = "ID for økten";
$PMF_LANG["ad_sess_ip"] = "IP-adresse";

$PMF_LANG["ad_ques_take"] = "Besvar spørsmål";
$PMF_LANG["no_cats"] = "Ingen kategorier funnet.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Feil brukernavn eller passord.";
$PMF_LANG["ad_log_sess"] = "Økt utgått.";
$PMF_LANG["ad_log_edit"] = "\"Rediger Bruker\"-Form for følgende bruker: ";
$PMF_LANG["ad_log_crea"] = "\"Ny artikel\" form.";
$PMF_LANG["ad_log_crsa"] = "Ny registrering gemt.";
$PMF_LANG["ad_log_ussa"] = "Oppdater data på følgende bruker: ";
$PMF_LANG["ad_log_usde"] = "Slettet følgende bruker: ";
$PMF_LANG["ad_log_beed"] = "Rediger form for følgende bruker: ";
$PMF_LANG["ad_log_bede"] = "Slet følgende registrering: ";

$PMF_LANG["ad_start_visits"] = "Besøk";
$PMF_LANG["ad_start_articles"] = "Artikler";
$PMF_LANG["ad_start_comments"] = "Kommentarer";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "lim inn";
$PMF_LANG["ad_categ_cut"] = "klipp ut";
$PMF_LANG["ad_categ_copy"] = "kopier";
$PMF_LANG["ad_categ_process"] = "Bearbeider kategorier...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Du har ikke rettigheter.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "foregående side";
$PMF_LANG["msgNextPage"] = "neste side";
$PMF_LANG["msgPageDoublePoint"] = "Side: ";
$PMF_LANG["msgMainCategory"] = "Hoved kategori";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Dit passord er skiftet.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "Vis som PDF-fil";
$PMF_LANG["ad_xml_head"] = "XML-sikkerhetskopi";
$PMF_LANG["ad_xml_hint"] = "Gem alle spørsmål i FAQ i een XML-fil.";
$PMF_LANG["ad_xml_gen"] = "opprett XML-fil";
$PMF_LANG["ad_entry_locale"] = "Språk";
$PMF_LANG["msgLangaugeSubmit"] = "Skift språk";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "Forhåndsvisning";
$PMF_LANG["ad_attach_1"] = "Velg først et bibliotek for vedlegg i konfigurasjon.";
$PMF_LANG["ad_attach_2"] = "Velg først en lenke til vedlegg.";
$PMF_LANG["ad_attach_3"] = "Filen attachment.php kan ikke åpnes uten krevde rettigheter.";
$PMF_LANG["ad_attach_4"] = "Den vedlagte filen skal være mindre enn %s bytes.";
$PMF_LANG["ad_menu_export"] = "Eksporter dine OSS";
$PMF_LANG["ad_export_1"] = "Bygg RSS-strøm til";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "Feil: Kan ikke skrive til fil.";
$PMF_LANG["ad_export_news"] = "Nyheter RSS-strøm";
$PMF_LANG["ad_export_topten"] = "Topp 10 RSS-strøm";
$PMF_LANG["ad_export_latest"] = "5 seneste spørsmål RSS-Feed";
$PMF_LANG["ad_export_pdf"] = "PDF-Eksport av alle spørsmål";
$PMF_LANG["ad_export_generate"] = "bygg RSS-Feed";

$PMF_LANG["rightsLanguage"]['adduser'] = "legg til bruker";
$PMF_LANG["rightsLanguage"]['edituser'] = "rediger bruker";
$PMF_LANG["rightsLanguage"]['deluser'] = "slett bruker";
$PMF_LANG["rightsLanguage"]['addbt'] = "legg til spørsmål/svar";
$PMF_LANG["rightsLanguage"]['editbt'] = "rediger spørsmål/svar";
$PMF_LANG["rightsLanguage"]['delbt'] = "slett spørsmål/svar";
$PMF_LANG["rightsLanguage"]['viewlog'] = "se logg";
$PMF_LANG["rightsLanguage"]['adminlog'] = "se adminlogg";
$PMF_LANG["rightsLanguage"]['delcomment'] = "slett kommentarer";
$PMF_LANG["rightsLanguage"]['addnews'] = "legg til nyheter";
$PMF_LANG["rightsLanguage"]['editnews'] = "rediger nyheter";
$PMF_LANG["rightsLanguage"]['delnews'] = "slett nyheter";
$PMF_LANG["rightsLanguage"]['addcateg'] = "legg til kategori";
$PMF_LANG["rightsLanguage"]['editcateg'] = "rediger kategori";
$PMF_LANG["rightsLanguage"]['delcateg'] = "slett kategori";
$PMF_LANG["rightsLanguage"]['passwd'] = "skift passord";
$PMF_LANG["rightsLanguage"]['editconfig'] = "rediger konfigurasjon";
$PMF_LANG["rightsLanguage"]['addatt'] = "legg til vedlegg";
$PMF_LANG["rightsLanguage"]['delatt'] = "slett vedlegg";
$PMF_LANG["rightsLanguage"]['backup'] = "lag sikkerhetskopi";
$PMF_LANG["rightsLanguage"]['restore'] = "gjennopprett sikkerhetskopi";
$PMF_LANG["rightsLanguage"]['delquestion'] = "slett åpne spørsmål";

$PMF_LANG["msgAttachedFiles"] = "vedlagte filer:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "Handling";
$PMF_LANG["ad_entry_email"] = "e-postadresse:";
$PMF_LANG["ad_entry_allowComments"] = "Tillat kommentarer";
$PMF_LANG["msgWriteNoComment"] = "Du kan ikke kommentere dette innlegg";
$PMF_LANG["ad_user_realname"] = "Virkelig navn:";
$PMF_LANG["ad_export_generate_pdf"] = "generer PDF-fil";
$PMF_LANG["ad_export_full_faq"] = "Din FAQ som en PDF-fil: ";
$PMF_LANG["err_bannedIP"] = "Din IP-address nektes adgang.";
$PMF_LANG["err_SaveQuestion"] = "Nødvendige felter: <strong>ditt navn</strong>, <strong>din e-postadresse</strong> og <strong>ditt spørsmål</strong>.<br /><br /><a href=\"javascript:history.back();\">En side tilbake</a><br /><br />\n";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "Skrifttype farve: ";
$PMF_LANG["ad_entry_fontsize"] = "Skrifttype størrelse: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => "select", 1 => "Språkfil");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "Aktiver automatisk forhandling av tegnsett");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "Tittel for OSS/FAQ");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "FAQ versjon");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "Beskrivelse av siden");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "Nøkkel for søkemaskiner");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "Utgiversnavn");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "Administrators e-postadresse");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "Kontaktinformasjon");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "Tekst for send2friend-siden");
$LANG_CONF['main.maxAttachmentSize'] = array(0 => "input", 1 => "maksimum størrelse for vedlegg i byte (maks. %sByte)");
$LANG_CONF["main.disableAttachments"] = array(0 => "checkbox", 1 => "Lenke til vedlegg under oppføringene?");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "bruk Tracking?");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "bruk adminlogg?");
$LANG_CONF["main.ipCheck"] = array(0 => "checkbox", 1 => "Vil du at IP-en skal sjekkes når man sjekket UIN-en i admin.php?");
$LANG_CONF["main.numberOfRecordsPerPage"] = array(0 => "input", 1 => "Antall oppføringer per side");
$LANG_CONF["main.numberOfShownNewsEntries"] = array(0 => "input", 1 => "Antall nyhetsartikler");
$LANG_CONF['main.bannedIPs'] = array(0 => "area", 1 => "Steng ute disse IP-ene");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "Aktiver mod_rewrite støtte? (standard: deaktivert)");
$LANG_CONF["main.ldapSupport"] = array(0 => "checkbox", 1 => "Vil du aktivere LDAP-støtte? (standard: deaktivert)");

$PMF_LANG["ad_categ_new_main_cat"] = "som ny hovedkategori";
$PMF_LANG["ad_categ_paste_error"] = "Flytting av denne kategorien er ikke mulig.";
$PMF_LANG["ad_categ_move"] = "flytt kategori";
$PMF_LANG["ad_categ_lang"] = "Språk";
$PMF_LANG["ad_categ_desc"] = "Beskrivelse";
$PMF_LANG["ad_categ_change"] = "Endre med";

$PMF_LANG["lostPassword"] = "Glemt passord? Klikk her.";
$PMF_LANG["lostpwd_err_1"] = "Feil: Brukernavn og passord ikke funnet.";
$PMF_LANG["lostpwd_err_2"] = "Feil: Feil oppføringer!";
$PMF_LANG["lostpwd_text_1"] = "Takk for at du forespurte om din kontoinformasjon.";
$PMF_LANG["lostpwd_text_2"] = "Sett et nytt personlig passord på administratorsiden.";
$PMF_LANG["lostpwd_mail_okay"] = "E-post er sendt.";

$PMF_LANG["ad_xmlrpc_button"] = "Finn siste phpMyFAQ versjonsnummer ved hjelp av web service";
$PMF_LANG["ad_xmlrpc_latest"] = "Siste versjon tilgjengelig på";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Velg kategorispråk';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Sitemap';
