<?php

/**
 * Polish language file
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ v4.0.0-alpha
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matthias Sommerfeld <mso@bluebirdy.de>
 * @author Henning Schulzrinne <hgs@cs.columbia.edu>
 * @author Zięba Bogusław Chaffinch <hgs@cs.columbia.edu>
 * @copyright 2004-2024 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2024-04-14
 * @codingStandardsIgnoreFile
 */

/**
 *                !!! WAŻNA INFORMACJA !!!
 * Podczas definiowania nowych zmiennych należy wziąć pod uwagę poniższe wskazówki:
 * - jedna definicja zmiennej w każdym wierszu !!!
 * - idealnym przypadkiem jest zdefiniowanie wartości ciągu skalarnego
 * - jeśli potrzebna jest dynamiczna zawartość, użyj składni sprintf()
 * - tablice są dozwolone, ale nie zalecane
 * - brak komentarzy na końcu linii po definicji var
 * - nie używaj znaku '=' w kluczach tablicy
 *   (np. $PMF_LANG["a=b"] jest niedozwolone)
 *
 *  Prosimy zachować spójność z tym formatem, ponieważ jest on nam potrzebny aby
 *  narzędzie tłumaczenia działało poprawnie
 */

$PMF_LANG['metaCharset'] = 'UTF-8';
$PMF_LANG['metaLanguage'] = 'pl';
$PMF_LANG['language'] = 'polski';
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr";
$PMF_LANG["nplurals"] = "2";

// Navigation
$PMF_LANG["msgCategory"] = "Kategorie";
$PMF_LANG["msgShowAllCategories"] = "Wszystkie kategorie";
$PMF_LANG["msgSearch"] = "Wyszukaj";
$PMF_LANG["msgAddContent"] = "Dodaj nowe FAQ";
$PMF_LANG["msgQuestion"] = "Dodaj pytanie";
$PMF_LANG["msgOpenQuestions"] = "Otwarte pytania";
$PMF_LANG["msgHelp"] = "Pomoc";
$PMF_LANG["msgContact"] = "Kontakt";
$PMF_LANG["msgHome"] = "Strona Główna";
$PMF_LANG["msgNews"] = "FAQ Aktualności";
$PMF_LANG["msgUserOnline"] = " Użytkowników on-line";
$PMF_LANG["msgBack2Home"] = "Wróć na stronę główną";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategorie";
$PMF_LANG["msgFullCategoriesIn"] = "Kategorie z FAQs w ";
$PMF_LANG["msgSubCategories"] = "Podkategorie";
$PMF_LANG["msgEntries"] = "Zadawane pytania";
$PMF_LANG["msgEntriesIn"] = "Pytania w ";
$PMF_LANG["msgViews"] = "odsłon";
$PMF_LANG["msgPage"] = "Strona ";
$PMF_LANG["msgPages"] = " Strony";
$PMF_LANG["msgPrevious"] = "poprzednia";
$PMF_LANG["msgNext"] = "następna";
$PMF_LANG["msgCategoryUp"] = "jedną kategorię w górę";
$PMF_LANG["msgLastUpdateArticle"] = "Ostatnio aktualizowane: ";
$PMF_LANG["msgAuthor"] = "Autor: ";
$PMF_LANG["msgPrinterFriendly"] = "Wersja do wydruku";
$PMF_LANG["msgPrintArticle"] = "Wydrukuj ten wpis";
$PMF_LANG["msgMakeXMLExport"] = "Eksportuj jako plik XML";
$PMF_LANG["msgAverageVote"] = "Średnia ocena";
$PMF_LANG["msgVoteUsability"] = "Oceń to pytanie";
$PMF_LANG["msgVoteFrom"] = "out of";
$PMF_LANG["msgVoteBad"] = "w ogóle nie jest pomocny";
$PMF_LANG["msgVoteGood"] = "niezwykle pomocne";
$PMF_LANG["msgVotings"] = "Głosów ";
$PMF_LANG["msgVoteSubmit"] = "Głos";
$PMF_LANG["msgVoteThanks"] = "Dziękujemy za ocenę!";
$PMF_LANG["msgYouCan"] = "Możesz ";
$PMF_LANG["msgWriteComment"] = "Skomentować ten wpis";
$PMF_LANG["msgShowCategory"] = "Przegląd treści: ";
$PMF_LANG["msgCommentBy"] = "Komentarz od ";
$PMF_LANG["msgCommentHeader"] = "Skomentuj ten FAQ";
$PMF_LANG["msgYourComment"] = "Twój komentarz";
$PMF_LANG["msgCommentThanks"] = "Dziękujemy za komentarz!";
$PMF_LANG["msgSeeXMLFile"] = "otwórz plik XML";
$PMF_LANG["msgSend2Friend"] = "Wyślij FAQ do znajomego";
$PMF_LANG["msgS2FName"] = "Imię i nazwisko";
$PMF_LANG["msgS2FEMail"] = "Adres e-mail";
$PMF_LANG["msgS2FFriends"] = "Twoi przyjaciele";
$PMF_LANG["msgS2FEMails"] = ". adres e-mail";
$PMF_LANG["msgS2FText"] = "Poniższy tekst zostanie wysłany";
$PMF_LANG["msgS2FText2"] = "FAQ znajdziesz pod następującym adresem";
$PMF_LANG["msgS2FMessage"] = "Dodatkowa informacja dla znajomych";
$PMF_LANG["msgS2FButton"] = "Wyślij wiadomość";
$PMF_LANG["msgS2FThx"] = "Dziękujemy za polecenie !";
$PMF_LANG["msgS2FMailSubject"] = "Rekommendacja od ";

// Search
$PMF_LANG["msgSearchWord"] = "Słowo kluczowe";
$PMF_LANG["msgSearchFind"] = "Wynik wyszukiwania dla ";
$PMF_LANG["msgSearchAmount"] = " wynik wyszukiwania";
$PMF_LANG["msgSearchAmounts"] = " wyniki wyszukiwania";
$PMF_LANG["msgSearchCategory"] = "Kategoria: ";
$PMF_LANG["msgSearchContent"] = "Odpowiedź: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Propozycja nowego FAQ";
$PMF_LANG["msgNewContentAddon"] = "Twoja propozycja nie zostanie opublikowana od razu, ale może zostać opublikowana przez administratora po weryfikacji. Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres email</strong>, <strong>kategoria</strong>, <strong>pytanie</strong> i <strong>odpowiedź</strong>. Proszę rozdzielać słowa kluczowe wyłącznie przecinkami.";
$PMF_LANG["msgNewContentName"] = "Imię i nazwisko";
$PMF_LANG["msgNewContentMail"] = "Adres e-mail";
$PMF_LANG["msgNewContentCategory"] = "Kategoria";
$PMF_LANG["msgNewContentTheme"] = "Twoje pytanie";
$PMF_LANG["msgNewContentArticle"] = "Twoja odpowiedź";
$PMF_LANG["msgNewContentKeywords"] = "Słowa kluczowe";
$PMF_LANG["msgNewContentLink"] = "Link do tego FAQ";
$PMF_LANG["msgNewContentSubmit"] = "Zatwierdź";
$PMF_LANG["msgInfo"] = "Więcej informacji: ";
$PMF_LANG["msgNewContentThanks"] = "Dziękujemy za twoją sugestię!";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Zadaj poniżej swoje pytanie";
$PMF_LANG["msgAskCategory"] = "Kategoria";
$PMF_LANG["msgAskYourQuestion"] = "Twoje pytanie";
$PMF_LANG["msgAskThx4Mail"] = "Dziękujemy za pytanie!";
$PMF_LANG["msgDate_User"] = "Data / Użytkownik";
$PMF_LANG["msgQuestion2"] = "Pytanie";
$PMF_LANG["msg2answer"] = "Odpowiedź";
$PMF_LANG["msgQuestionText"] = "Tutaj możesz zobaczyć pytania zadawane przez innych użytkowników. Jeśli odpowiesz na te pytania, Twoje odpowiedzi mogą zostać umieszczone w FAQ.";
$PMF_LANG["msgNoQuestionsAvailable"] = "W tej chwili nie ma żadnych oczekujących pytań.";

// Contact
$PMF_LANG["msgContactEMail"] = "Wyślij e-mail do właściciela FAQ";
$PMF_LANG["msgMessage"] = "Twoja wiadomość";

// Homepage
$PMF_LANG["msgTopTen"] = "Najbardziej popularne FAQ";
$PMF_LANG["msgHomeThereAre"] = "Są tam ";
$PMF_LANG["msgHomeArticlesOnline"] = " FAQ on-line";
$PMF_LANG["msgNoNews"] = "Brak wiadomości to dobra wiadomość.";
$PMF_LANG["msgLatestArticles"] = "Najnowsze Pytania";

// Email notification
$PMF_LANG["msgMailThanks"] = "Dziękujemy za Twoją propozycję do FAQ!";
$PMF_LANG["msgMailCheck"] = "W FAQ pojawił się nowy wpis! Sprawdź to tutaj lub w dziale administracyjnym.";
$PMF_LANG["msgMailContact"] = "Twoja wiadomość została wysłana do administratora.";

// Error messages
$PMF_LANG["err_noDatabase"] = "Brak połączenia z bazą danych.";
$PMF_LANG["err_noHeaders"] = "Nie znaleziono kategorii.";
$PMF_LANG["err_noArticles"] = "Brak dostępnych pytań i odpowiedzi.";
$PMF_LANG["err_badID"] = "Zły ID.";
$PMF_LANG["err_noTopTen"] = "Brak dostępnych FAQs.";
$PMF_LANG["err_nothingFound"] = "Nie znaleziono wpisu.";
$PMF_LANG["err_SaveEntries"] = "Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres email</strong>, <strong>kategoria</strong>, <strong>pytanie</strong>, <strong>Twój wpis</strong> oraz, na żądanie <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Przeczytaj więcej o Captcha w Wikipedii\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_SaveComment"] = "Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres email</strong>, <strong>Twój komentarz</strong> oraz, na żądanie, <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Przeczytaj więcej o Captcha w Wikipedii\" target=\"_blank\">Captcha</a> code</strong>!";
$PMF_LANG["err_VoteTooMuch"] = "Nie liczymy głosów wielokrotnych.";
$PMF_LANG["err_noVote"] = "Nie oceniłeś pytania!";
$PMF_LANG["err_noMailAdress"] = "Twój adres e-mail jest nieprawidłowy.";
$PMF_LANG["err_sendMail"] = "Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres email</strong>, <strong>Twoje pytanie</strong> oraz, na żądanie, <strong><a href=\"https://en.wikipedia.org/wiki/Captcha\" title=\"Przeczytaj więcej o Captcha w Wikipedii\" target=\"_blank\">Captcha</a> code</strong>!";

// Search help
$PMF_LANG["help_search"] = "<strong>Szukaj wpisów:</strong><br>Z wpisem typu <strong style=\"color: Red;\">word1 word2</strong> możesz przeprowadzić wyszukiwanie według trafności malejąco dla dwóch lub więcej kryteriów wyszukiwania.<strong>Uwaga</strong> Twoje kryterium wyszukiwania musi składać się z co najmniej 4 liter, w przeciwnym razie Twoje zapytanie zostanie odrzucone.";

// Menu
$PMF_LANG["ad"] = "Administracja";
$PMF_LANG["ad_menu_user_administration"] = "Użytkownicy";
$PMF_LANG["ad_menu_entry_aprove"] = "Zatwierdź zadawane pytania";
$PMF_LANG["ad_menu_entry_edit"] = "Edytuj  zadawane pytania";
$PMF_LANG["ad_menu_categ_add"] = "Dodaj nową kategorię";
$PMF_LANG["ad_menu_categ_edit"] = "Kategorie";
$PMF_LANG["ad_menu_news_add"] = "Dodaj newsy";
$PMF_LANG["ad_menu_news_edit"] = "FAQ Aktualności";
$PMF_LANG["ad_menu_open"] = "Pytania otwarte";
$PMF_LANG["ad_menu_stat"] = "Statystyki";
$PMF_LANG["ad_menu_cookie"] = "Ustaw pliki cookie";
$PMF_LANG["ad_menu_session"] = "Zobacz sesje";
$PMF_LANG["ad_menu_adminlog"] = "Zobacz log administratora";
$PMF_LANG["ad_menu_passwd"] = "Zmień hasło";
$PMF_LANG["ad_menu_logout"] = "Wyloguj";
$PMF_LANG["ad_menu_startpage"] = "Strona główna";

// Messages
$PMF_LANG["ad_msg_identify"] = "Proszę o identyfikację.";
$PMF_LANG["ad_msg_passmatch"] = "Oba hasła muszą być <strong>identyczne</strong>!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profil ";
$PMF_LANG["ad_msg_savedsuc_2"] = "został pomyślnie zapisany.";
$PMF_LANG["ad_msg_mysqlerr"] = "Z powodu <strong>błędu bazy danych</strong>, nie można zapisać profilu.";
$PMF_LANG["ad_msg_noauth"] = "Nie jesteś autoryzowany.";

// General
$PMF_LANG["ad_gen_page"] = "Strona";
$PMF_LANG["ad_gen_of"] = "z";
$PMF_LANG["ad_gen_lastpage"] = "Poprzednia strona";
$PMF_LANG["ad_gen_nextpage"] = "Następna strona";
$PMF_LANG["ad_gen_save"] = "Zapisz";
$PMF_LANG["ad_gen_reset"] = "Anuluj";
$PMF_LANG["ad_gen_yes"] = "Tak";
$PMF_LANG["ad_gen_no"] = "Nie";
$PMF_LANG["ad_gen_top"] = "Do góry";
$PMF_LANG["ad_gen_ncf"] = "Nie znaleziono kategorii!";
$PMF_LANG["ad_gen_delete"] = "Usuń";
$PMF_LANG['ad_gen_or'] = "lub";

// User administration
$PMF_LANG["ad_user"] = "Zarządzanie użytkownikami";
$PMF_LANG["ad_user_username"] = "Zarejestrowani użytkownicy";
$PMF_LANG["ad_user_rights"] = "Prawa użytkownika";
$PMF_LANG["ad_user_edit"] = "Edytuj";
$PMF_LANG["ad_user_delete"] = "Usuń";
$PMF_LANG["ad_user_add"] = "Dodaj użytkownika";
$PMF_LANG["ad_user_profou"] = "Profil użytkownika";
$PMF_LANG["ad_user_name"] = "Nazwa";
$PMF_LANG["ad_user_password"] = "Hasło";
$PMF_LANG["ad_user_confirm"] = "Potwierdź";
$PMF_LANG["ad_user_del_1"] = "Użytkownik";
$PMF_LANG["ad_user_del_2"] = "zostanie usunięty?";
$PMF_LANG["ad_user_del_3"] = "Jesteś pewny?";
$PMF_LANG["ad_user_deleted"] = "Użytkownik został pomyślnie usunięty.";
$PMF_LANG["ad_user_checkall"] = "Zaznacz wszystko";

// Contribution management
$PMF_LANG["ad_entry_aor"] = "FAQ administracja";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Temat";
$PMF_LANG["ad_entry_action"] = "Działanie";
$PMF_LANG["ad_entry_edit_1"] = "Edytuj Rekord";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Pytanie";
$PMF_LANG["ad_entry_content"] = "Odpowiedź";
$PMF_LANG["ad_entry_keywords"] = "Słowa kluczowe";
$PMF_LANG["ad_entry_author"] = "Autor";
$PMF_LANG["ad_entry_category"] = "Kategoria";
$PMF_LANG["ad_entry_active"] = "Widoczne";
$PMF_LANG["ad_entry_date"] = "Data";
$PMF_LANG["ad_entry_status"] = "FAQ status";
$PMF_LANG["ad_entry_changed"] = "Zmieniony?";
$PMF_LANG["ad_entry_changelog"] = "Dziennik zmian";
$PMF_LANG["ad_entry_commentby"] = "Komentowany ";
$PMF_LANG["ad_entry_comment"] = "Komentarze";
$PMF_LANG["ad_entry_save"] = "Zapisz";
$PMF_LANG["ad_entry_delete"] = "usuń";
$PMF_LANG["ad_entry_delcom_1"] = "Jesteś pewny ";
$PMF_LANG["ad_entry_delcom_2"] = "komentarz powinien zostać usunięty?";
$PMF_LANG["ad_entry_commentdelsuc"] = "Komentarz został <strong>pomyślnie</strong> usunięty.";
$PMF_LANG["ad_entry_back"] = "Wróć do artykułu";
$PMF_LANG["ad_entry_commentdelfail"] = "Komentarz <strong>nie</strong> został usunięty.";
$PMF_LANG["ad_entry_savedsuc"] = "Zmiany zostały <strong>pomyślnie</strong> zapisane.";
$PMF_LANG["ad_entry_savedfail"] = "Niestety wystąpił <strong>błąd bazy danych</strong>.";
$PMF_LANG["ad_entry_del_1"] = "Czy na pewno temat";
$PMF_LANG["ad_entry_del_2"] = "z";
$PMF_LANG["ad_entry_del_3"] = "powinien zostać usunięty?";
$PMF_LANG["ad_entry_delsuc"] = "Problem <strong>został</strong> usunięty.";
$PMF_LANG["ad_entry_delfail"] = "Problem <strong>nie został usunięty</strong>!";
$PMF_LANG["ad_entry_back"] = "Wróć";

// News management
$PMF_LANG["ad_news_header"] = "Nagłówek artykułu";
$PMF_LANG["ad_news_text"] = "Tekst Wpisu";
$PMF_LANG["ad_news_link_url"] = "Link";
$PMF_LANG["ad_news_link_title"] = "Tytuł linku";
$PMF_LANG["ad_news_link_target"] = "Link docelowy";
$PMF_LANG["ad_news_link_window"] = "Link otwiera nowe okno";
$PMF_LANG["ad_news_link_faq"] = "Link w FAQ";
$PMF_LANG["ad_news_add"] = "Dodaj wpis Wiadomości";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Nagłówek";
$PMF_LANG["ad_news_date"] = "Data";
$PMF_LANG["ad_news_action"] = "Akcja";
$PMF_LANG["ad_news_update"] = "aktualizacja";
$PMF_LANG["ad_news_delete"] = "usuń";
$PMF_LANG["ad_news_nodata"] = "Brak wpisów, w bazie danych";
$PMF_LANG["ad_news_updatesuc"] = "Wpis wiadomości został pomyślnie zaktualizowany.";
$PMF_LANG["ad_news_del"] = "Czy na pewno chcesz usunąć ten wpis wiadomości?";
$PMF_LANG["ad_news_yesdelete"] = "tak, usuń!";
$PMF_LANG["ad_news_nodelete"] = "nie";
$PMF_LANG["ad_news_delsuc"] = "Wpis wiadomości został pomyślnie usunięty.";
$PMF_LANG["ad_news_updatenews"] = "Wpis aktualności został zaktualizowany.";

// Category management
$PMF_LANG["ad_categ_new"] = "Dodaj nową kategorię";
$PMF_LANG["ad_categ_catnum"] = "Numer kategorii";
$PMF_LANG["ad_categ_subcatnum"] = "Numer podkategorii";
$PMF_LANG["ad_categ_nya"] = "<em>jeszcze nie dostępne!</em>";
$PMF_LANG["ad_categ_titel"] = "Tytuł kategorii";
$PMF_LANG["ad_categ_add"] = "Dodaj kategorię";
$PMF_LANG["ad_categ_existing"] = "Istniejące kategorie";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategoria";
$PMF_LANG["ad_categ_subcateg"] = "Podkategoria";
$PMF_LANG["ad_categ_titel"] = "Tytuł kategorii";
$PMF_LANG["ad_categ_action"] = "Akcja";
$PMF_LANG["ad_categ_update"] = "aktualizacja";
$PMF_LANG["ad_categ_delete"] = "usuń";
$PMF_LANG["ad_categ_updatecateg"] = "Aktualizuj Kategorię";
$PMF_LANG["ad_categ_nodata"] = "Brak danych, w bazie danych";
$PMF_LANG["ad_categ_remark"] = "Pamiętaj, że istniejące wpisy nie będą już widoczne, jeśli usuniesz kategorię. Musisz albo przypisać nową kategorię do artykułu, albo usunąć artykuł.";
$PMF_LANG["ad_categ_edit_1"] = "Edytuj";
$PMF_LANG["ad_categ_edit_2"] = "Kategoria";
$PMF_LANG["ad_categ_added"] = "Kategoria została dodana.";
$PMF_LANG["ad_categ_updated"] = "Kategoria została zaktualizowana.";
$PMF_LANG["ad_categ_del_yes"] = "tak, usuń!";
$PMF_LANG["ad_categ_del_no"] = "nie!";
$PMF_LANG["ad_categ_deletesure"] = "Czy na pewno chcesz usunąć tę kategorię?";
$PMF_LANG["ad_categ_deleted"] = "Kategoria usunięta.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "Plik cookie został <strong>pomyślnie</strong> ustawiony.";
$PMF_LANG["ad_cookie_already"] = "Plik cookie został już ustawiony. Masz teraz następujące opcje ";
$PMF_LANG["ad_cookie_again"] = "Ustaw ponownie plik cookie";
$PMF_LANG["ad_cookie_delete"] = "Usuń plik cookie";
$PMF_LANG["ad_cookie_no"] = "Obecnie nie ma zapisanego żadnego pliku cookie. Za pomocą pliku cookie możesz zapisać swoją sesję logowania, dzięki czemu nie musisz zapamiętywać danych logowania przy każdej wizycie. Masz teraz następujące opcje";
$PMF_LANG["ad_cookie_set"] = "Ustaw cookie";
$PMF_LANG["ad_cookie_deleted"] = "Plik cookie został pomyślnie usunięty.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "Dziennik administratora";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Zmień swoje hasło";
$PMF_LANG["ad_passwd_old"] = "Stare hasło";
$PMF_LANG["ad_passwd_new"] = "Nowe hasło";
$PMF_LANG["ad_passwd_con"] = "Wpisz ponownie hasło";
$PMF_LANG["ad_passwd_change"] = "Zmień hasło";
$PMF_LANG["ad_passwd_suc"] = "Hasło zostało pomyślnie zmienione.";
$PMF_LANG["ad_passwd_remark"] = "<strong>UWAGA:</strong><br>Plik cookie musi być ponownie ustawiony!";
$PMF_LANG["ad_passwd_fail"] = "Stare hasło <strong>musi</strong> być wpisane poprawnie i oba nowe muszą <strong>muszą się zgadzać</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Dodaj nowe konto użytkownika";
$PMF_LANG["ad_adus_name"] = "Nazwa użytkownika";
$PMF_LANG["ad_adus_password"] = "Hasło";
$PMF_LANG["ad_adus_add"] = "Dodaj użytkownika";
$PMF_LANG["ad_adus_suc"] = "Użytkownik <strong>pomyślnie</strong> dodany.";
$PMF_LANG["ad_adus_edit"] = "Edycja profilu";
$PMF_LANG["ad_adus_dberr"] = "Błąd bazy danych";
$PMF_LANG["ad_adus_exerr"] = "Użytkownik <strong>już istnieje</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "ID sesji";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Czas";
$PMF_LANG["ad_sess_pageviews"] = "Wyświetlenia strony";
$PMF_LANG["ad_sess_search"] = "Wyszukaj";
$PMF_LANG["ad_sess_sfs"] = "Wyszukaj sesje";
$PMF_LANG["ad_sess_s_ip"] = "IP";
$PMF_LANG["ad_sess_s_minct"] = "min. działania";
$PMF_LANG["ad_sess_s_date"] = "Data";
$PMF_LANG["ad_sess_s_after"] = "po";
$PMF_LANG["ad_sess_s_before"] = "przed";
$PMF_LANG["ad_sess_s_search"] = "Wyszukaj";
$PMF_LANG["ad_sess_session"] = "Sesja";
$PMF_LANG["ad_sess_r"] = "Wyniki wyszukiwania dla";
$PMF_LANG["ad_sess_referer"] = "Refererujący";
$PMF_LANG["ad_sess_browser"] = "Przeglądarka";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategoria";
$PMF_LANG["ad_sess_ai_artikel"] = "Wpis";
$PMF_LANG["ad_sess_ai_sb"] = "Wyszukaj ciągi";
$PMF_LANG["ad_sess_ai_sid"] = "ID sesji";
$PMF_LANG["ad_sess_back"] = "Wróć";
$PMF_LANG['ad_sess_noentry'] = "Brak wpisu";

// Statistics
$PMF_LANG["ad_rs"] = "Statystyki ocen";
$PMF_LANG["ad_rs_rating_1"] = "Ranking z";
$PMF_LANG["ad_rs_rating_2"] = "pokazuje użytkowników";
$PMF_LANG["ad_rs_red"] = "Czerwony";
$PMF_LANG["ad_rs_green"] = "Zielony";
$PMF_LANG["ad_rs_altt"] = "ze średnią niższą niż 20%";
$PMF_LANG["ad_rs_ahtf"] = "ze średnią niższą niż 80%";
$PMF_LANG["ad_rs_no"] = "Brak dostępnego rankingu";

// Auth
$PMF_LANG["ad_auth_insert"] = "Proszę wpisać swoją nazwę użytkownika i hasło.";
$PMF_LANG["ad_auth_user"] = "Nazwa użytkownika";
$PMF_LANG["ad_auth_passwd"] = "Hasło";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "Resetuj";
$PMF_LANG["ad_auth_fail"] = "Zła nazwa użytkownika lub hasło.";
$PMF_LANG["ad_auth_sess"] = "ID sesji jest poprawny.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Edytuj konfigurację";
$PMF_LANG["ad_config_save"] = "Zapisz konfigurację";
$PMF_LANG["ad_config_reset"] = "Resetuj";
$PMF_LANG["ad_config_saved"] = "Konfiguracja została pomyślnie zapisana.";
$PMF_LANG["ad_menu_editconfig"] = "Edycja konfiguracji";
$PMF_LANG["ad_att_none"] = "Brak dostępnych załączników";
$PMF_LANG["ad_att_add"] = "Dodaj nowy załącznik";
$PMF_LANG["ad_entryins_suc"] = "Rekord został pomyślnie zapisany.";
$PMF_LANG["ad_entryins_fail"] = "Wystąpił błąd.";
$PMF_LANG["ad_att_del"] = "Usuń";
$PMF_LANG["ad_att_nope"] = "Załączniki można dodawać wyłącznie podczas edycji.";
$PMF_LANG["ad_att_delsuc"] = "Załącznik został pomyślnie usunięty.";
$PMF_LANG["ad_att_delfail"] = "Wystąpił błąd podczas usuwania załącznika.";
$PMF_LANG["ad_entry_add"] = "Dodaj nowe FAQ";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "Kopia zapasowa to pełny obraz zawartości bazy danych. Format kopii zapasowej to plik transakcyjny SQL, który można zaimportować za pomocą narzędzi takich jak phpMyAdmin lub klient SQL wiersza poleceń. Kopia zapasowa powinna być wykonywana przynajmniej raz w miesiącu.";
$PMF_LANG["ad_csv_link"] = "Pobierz kopię zapasową";
$PMF_LANG["ad_csv_head"] = "Utwórz kopię zapasową";
$PMF_LANG["ad_att_addto"] = "Dodaj załącznik do zgłoszenia";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Wybierz załącznik";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "Plik został pomyślnie dołączony.";
$PMF_LANG["ad_att_fail"] = "Wystąpił błąd podczas dołączania pliku.";
$PMF_LANG["ad_att_close"] = "Zamknij to okno";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Za pomocą tego formularza możesz przywrócić zawartość bazy danych, korzystając z kopii zapasowej utworzonej za pomocą phpMyFAQ. Należy pamiętać, że istniejące dane zostaną nadpisane.";
$PMF_LANG["ad_csv_file"] = "Plik";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "kopie zapasowe plików dziennika";
$PMF_LANG["ad_csv_linkdat"] = "kopia zapasowa danych";
$PMF_LANG["ad_csv_head2"] = "Przywrć";
$PMF_LANG["ad_csv_no"] = "To nie wygląda na kopię zapasową phpMyFAQ.";
$PMF_LANG["ad_csv_prepare"] = "Przygotowywanie zapytań do bazy danych...";
$PMF_LANG["ad_csv_process"] = "Zapytanie...";
$PMF_LANG["ad_csv_of"] = "z";
$PMF_LANG["ad_csv_suc"] = "zakończyły się sukcesem.";
$PMF_LANG["ad_csv_backup"] = "Kopia zapasowa";
$PMF_LANG["ad_csv_rest"] = "Przywróć kopię zapasową";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Kopia zapasowa";
$PMF_LANG["ad_logout"] = "Sesja została pomyślnie zakończona.";
$PMF_LANG["ad_news_add"] = "Dodaj newsy";
$PMF_LANG["ad_news_edit"] = "Edytuj newsy";
$PMF_LANG["ad_cookie"] = "Ciasteczka";
$PMF_LANG["ad_sess_head"] = "Zobacz sesje";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_stat"] = "Statystyki Ocen";
$PMF_LANG["ad_kateg_add"] = "Dodaj nową kategorię najwyższego poziomu";
$PMF_LANG["ad_kateg_rename"] = "Edytuj";
$PMF_LANG["ad_adminlog_date"] = "Data";
$PMF_LANG["ad_adminlog_user"] = "Użytkownik";
$PMF_LANG["ad_adminlog_ip"] = "Adres IP";

$PMF_LANG["ad_stat_sess"] = "Sesje";
$PMF_LANG["ad_stat_days"] = "Dni";
$PMF_LANG["ad_stat_vis"] = "Sesje (wizyty)";
$PMF_LANG["ad_stat_vpd"] = "Wizyt dziennie";
$PMF_LANG["ad_stat_fien"] = "Pierwszy dziennik";
$PMF_LANG["ad_stat_laen"] = "Ostatni dziennik";
$PMF_LANG["ad_stat_browse"] = "przeglądaj sesje";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "Czas";
$PMF_LANG["ad_sess_sid"] = "ID Sesji";
$PMF_LANG["ad_sess_ip"] = "IP adres";

$PMF_LANG["ad_ques_take"] = "Odpowiedz na pytanie";
$PMF_LANG["no_cats"] = "Nie znaleziono kategorii.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Nieprawidłowy użytkownik lub hasło.";
$PMF_LANG["ad_log_sess"] = "Sesja wygasła.";
$PMF_LANG["ad_log_edit"] = "\"Edycja Użytkownika\" formularz dla następującego użytkownika: ";
$PMF_LANG["ad_log_crea"] = "\"Nowy artykuł\" formularz.";
$PMF_LANG["ad_log_crsa"] = "Utworzono nowy wpis.";
$PMF_LANG["ad_log_ussa"] = "Zaktualizuj dane dla następującego użytkownika: ";
$PMF_LANG["ad_log_usde"] = "Usunięto następującego użytkownika: ";
$PMF_LANG["ad_log_beed"] = "Edytuj formularz dla następującego użytkownika: ";
$PMF_LANG["ad_log_bede"] = "Usunięto następujący wpis: ";

$PMF_LANG["ad_start_visits"] = "Wizyty";
$PMF_LANG["ad_start_articles"] = "Artykuły";
$PMF_LANG["ad_start_comments"] = "Komentarze";

// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "wklej";
$PMF_LANG["ad_categ_cut"] = "wytnij";
$PMF_LANG["ad_categ_copy"] = "kopiuj";
$PMF_LANG["ad_categ_process"] = "Przetwarzanie kategorii...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Nie masz uprawnień.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "poprzednia strona";
$PMF_LANG["msgNextPage"] = "następna strona";
$PMF_LANG["msgPageDoublePoint"] = "Strona: ";
$PMF_LANG["msgMainCategory"] = "Główna kategoria";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Twoje hasło zostało zmienione.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["ad_xml_gen"] = "Utwórz eksport XML";
$PMF_LANG["ad_entry_locale"] = "Język";
$PMF_LANG["msgLanguageSubmit"] = "Zmień język";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_attach_4"] = "Załączony plik musi być mniejszy niż %s bajtów.";
$PMF_LANG["ad_menu_export"] = "Eksportuj swój FAQ";

$PMF_LANG['rightsLanguage::add_user'] = "Dodaj użytkownika";
$PMF_LANG['rightsLanguage::edit_user'] = "Edytuj użytkownika";
$PMF_LANG['rightsLanguage::delete_user'] = "Usuń użytkownika";
$PMF_LANG['rightsLanguage::add_faq'] = "Dodaj rekord";
$PMF_LANG['rightsLanguage::edit_faq'] = "Edytuj rekord";
$PMF_LANG['rightsLanguage::delete_faq'] = "Usuń rekord";
$PMF_LANG['rightsLanguage::viewlog'] = "Zobacz dzienniki";
$PMF_LANG['rightsLanguage::adminlog'] = "Wyświetl dziennik administratora";
$PMF_LANG['rightsLanguage::delcomment'] = "Usuń komentarz";
$PMF_LANG['rightsLanguage::addnews'] = "Dodaj wiadomości";
$PMF_LANG['rightsLanguage::editnews'] = "Edytuj wiadomości";
$PMF_LANG['rightsLanguage::delnews'] = "Usuń wiadomości";
$PMF_LANG['rightsLanguage::addcateg'] = "Dodaj kategorię";
$PMF_LANG['rightsLanguage::editcateg'] = "Edytuj kategorię";
$PMF_LANG['rightsLanguage::delcateg'] = "Usuń kategorię";
$PMF_LANG['rightsLanguage::passwd'] = "Zmień hasła";
$PMF_LANG['rightsLanguage::editconfig'] = "Edytuj konfigurację";
$PMF_LANG['rightsLanguage::addatt'] = "Dodaj załączniki";
$PMF_LANG['rightsLanguage::delatt'] = "Usuń załączniki";
$PMF_LANG['rightsLanguage::backup'] = "Twórz kopie zapasowe";
$PMF_LANG['rightsLanguage::restore'] = "Przywróć kopię zapasową";
$PMF_LANG['rightsLanguage::delquestion'] = "Usuń otwarte pytania";
$PMF_LANG['rightsLanguage::changebtrevs'] = "Edytuj wersje";

$PMF_LANG["msgAttachedFiles"] = "Załączone pliki";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "Akcja";
$PMF_LANG["ad_entry_email"] = "E-mail";
$PMF_LANG["ad_entry_allowComments"] = "Zezwalaj na komentarze";
$PMF_LANG["msgWriteNoComment"] = "Nie możesz komentować tego wpisu";
$PMF_LANG["ad_user_realname"] = "Prawdziwe imię";
$PMF_LANG["ad_export_generate_pdf"] = "Utwórz plik PDF";
$PMF_LANG["ad_export_full_faq"] = "Twoje FAQ w formacie PDF: ";
$PMF_LANG["err_bannedIP"] = "Twój adres IP został zablokowany.";
$PMF_LANG["err_SaveQuestion"] = "Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres e-mail</strong>, <strong>Twoje pytanie</strong> i, jeśli jest to wymagane, <strong><a href=\"https://pl.wikipedia.org/wiki/CAPTCHA\" title=\"Przeczytaj więcej o Captcha na Wikipedii\" target=\"_blank\">Captcha</a> code</strong>.";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = ["select", "Język"];
$LANG_CONF["main.languageDetection"] = ["checkbox", "Włącz automatyczne wykrywanie języka"];
$LANG_CONF['main.titleFAQ'] = ["input", "Tytuł FAQ"];
$LANG_CONF['main.currentVersion'] = ["print", "phpMyFAQ Wersja"];
$LANG_CONF["main.metaDescription"] = ["input", "Opis"];
$LANG_CONF["main.metaKeywords"] = ["input", "Słowa kluczowe dla Robotów"];
$LANG_CONF["main.metaPublisher"] = ["input", "Wydawca"];
$LANG_CONF['main.administrationMail'] = ["input", "Adres e-mail Administratora"];
$LANG_CONF["main.contactInformation"] = ["area", "Informacje kontaktowe"];
$LANG_CONF["main.send2friendText"] = ["area", "Tekst strony wysyłania do znajomego"];
$LANG_CONF['records.maxAttachmentSize'] = ["input", "Maksymalny rozmiar załączników w bajtach (max. %s bajtów)"];
$LANG_CONF["records.disableAttachments"] = ["checkbox", "Włącz widoczność załączników"];
$LANG_CONF["main.enableUserTracking"] = ["checkbox", "Włącz śledzenie użytkowników"];
$LANG_CONF["main.enableAdminLog"] = ["checkbox", "Użyć dziennika administratora?"];
$LANG_CONF["main.enableCategoryRestrictions"] = ["checkbox", "Włącz ograniczenia kategorii"];
$LANG_CONF["security.ipCheck"] = ["checkbox", "Sprawdź IP w administracji"];
$LANG_CONF["records.numberOfRecordsPerPage"] = ["input", "Liczba wyświetlanych tematów na stronie"];
$LANG_CONF["records.numberOfShownNewsEntries"] = ["input", "Liczba artykułów"];
$LANG_CONF['security.bannedIPs'] = ["area", "Zablokuj te adresy IP"];
$LANG_CONF["main.enableRewriteRules"] = ["checkbox", "Włączyć obsługę rewrite adresów URL? (domyślnie: wyłączone)"];
$LANG_CONF["ldap.ldapSupport"] = ["checkbox", "Włączyć obsługę LDAP? (domyślnie: wyłączone)"];
$LANG_CONF["main.referenceURL"] = ["input", "Adres URL FAQ (np.:: https://www.example.org/faq/)"];
$LANG_CONF["main.urlValidateInterval"] = ["input", "Odstęp między weryfikacją łącza AJAX (w sekundach)"];
$LANG_CONF["records.enableVisibilityQuestions"] = ["checkbox", "Wyłączyć widoczność nowych pytań?"];
$LANG_CONF['security.permLevel'] = ["select", "Poziom uprawnień"];

$PMF_LANG["ad_categ_new_main_cat"] = "jako nową kategorię główną";
$PMF_LANG["ad_categ_paste_error"] = "Przeniesienie tej kategorii nie jest możliwe.";
$PMF_LANG["ad_categ_move"] = "przenieś kategorię";
$PMF_LANG["ad_categ_lang"] = "Język";
$PMF_LANG["ad_categ_desc"] = "Opis";
$PMF_LANG["ad_categ_change"] = "Zmień z";

$PMF_LANG["lostPassword"] = "Zapomniałeś hasło?";
$PMF_LANG["lostpwd_err_1"] = "Błąd: Nie znaleziono nazwy użytkownika i adresu e-mail.";
$PMF_LANG["lostpwd_err_2"] = "Błąd: Nieprawidłowe wpisy!";
$PMF_LANG["lostpwd_text_1"] = "Dziękujemy za przesłanie informacji o koncie.";
$PMF_LANG["lostpwd_text_2"] = "Proszę ustawić nowe hasło osobiste w sekcji administracyjnej swojego FAQ.";
$PMF_LANG["lostpwd_mail_okay"] = "Email został wysłany.";

$PMF_LANG["ad_xmlrpc_button"] = "Kliknij, aby sprawdzić wersję instalacji phpMyFAQ";
$PMF_LANG["ad_xmlrpc_latest"] = "Najnowsza dostępna wersja";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'Wybierz język kategorii';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'Mapa witryny';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'Dostęp zabroniony';
$PMF_LANG['msgArticleCategories'] = 'Kategorie dla tego wpisu';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = 'Unikalny ID rozwiązania';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ rekord';
$PMF_LANG['ad_entry_new_revision'] = 'Utworzyć nową wersję?';
$PMF_LANG['ad_entry_record_administration'] = 'Zarządzanie rekordami';
$PMF_LANG['ad_entry_revision'] = 'Wersja';
$PMF_LANG['ad_changerev'] = 'Wybierz wersję';
$PMF_LANG['msgCaptcha'] = "Proszę wpisać kod captcha";
$PMF_LANG['msgSelectCategories'] = 'Szukaj w...';
$PMF_LANG['msgAllCategories'] = '... wszystkich kategoriach';
$PMF_LANG['ad_you_should_update'] = 'Twoja instalacja phpMyFAQ jest nieaktualna.Powinieneś dokonać aktualizacji do najnowszej dostępnej wersji.';
$PMF_LANG['msgAdvancedSearch'] = 'Wyszukiwanie zaawansowane';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'Centrum kontroli spamu';
$LANG_CONF["spam.enableSafeEmail"] = ["checkbox", "Drukuj e-maile użytkowników w bezpieczny sposób."];
$LANG_CONF["spam.checkBannedWords"] = ["checkbox", "Sprawdź treść formularza publicznego pod kątem zabronionych słów."];
$LANG_CONF["spam.enableCaptchaCode"] = ["checkbox", "Użyj kodu captcha, aby umożliwić przesłanie formularza publicznego."];
$PMF_LANG['ad_session_expiring'] = 'Twoja sesja wygaśnie za %d minut: czy chcesz kontynuować pracę?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'Zarządzanie sesjami';
$PMF_LANG['ad_stat_choose'] = 'Wybierz miesiąc';
$PMF_LANG['ad_stat_delete'] = 'Natychmiast usunąć wybrane sesje?';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = 'Słowniczek FAQ';
$PMF_LANG['ad_glossary_add'] = 'Dodaj wpis do słownika';
$PMF_LANG['ad_glossary_edit'] = 'Edytuj wpis w słowniku';
$PMF_LANG['ad_glossary_item'] = 'Tytuł';
$PMF_LANG['ad_glossary_definition'] = 'Definicja';
$PMF_LANG['ad_glossary_save'] = 'Zapisz słowniczek';
$PMF_LANG['ad_glossary_save_success'] = 'Wpis do słownika został pomyślnie zapisany!';
$PMF_LANG['ad_glossary_save_error'] = 'Nie można zapisać wpisu słownika, ponieważ wystąpił błąd.';
$PMF_LANG['ad_glossary_update_success'] = 'Wpis w słowniku został pomyślnie zaktualizowany!';
$PMF_LANG['ad_glossary_update_error'] = 'Nie można zaktualizować wpisu w słowniku, ponieważ wystąpił błąd.';
$PMF_LANG['ad_glossary_delete'] = 'Usuń wpis';
$PMF_LANG['ad_glossary_delete_success'] = 'Wpis w słowniku został pomyślnie usunięty!';
$PMF_LANG['ad_glossary_delete_error'] = 'Nie można usunąć wpisu słownika, ponieważ wystąpił błąd.';
$PMF_LANG['msgNewQuestionVisible'] = 'Pytanie należy najpierw sprawdzić, zanim zostanie upublicznione.';
$PMF_LANG['msgQuestionsWaiting'] = 'Oczekujue na publikację przez administratorów: ';
$PMF_LANG['ad_entry_visibility'] = 'opublikowane';
$PMF_LANG['ad_entry_not_visibility'] = "nie opublikowane";

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = "Proszę wpisać hasło. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] = "Hasła nie pasują do siebie. ";
$PMF_LANG['ad_user_error_loginInvalid'] = "Podana nazwa użytkownika jest nieprawidłowa.";
$PMF_LANG['ad_user_error_noEmail'] = "Proszę wpisać aktualny adres e-mail. ";
$PMF_LANG['ad_user_error_noRealName'] = "Proszę wpisać swoje prawdziwe imię i nazwisko. ";
$PMF_LANG['ad_user_error_delete'] = "Nie można usunąć konta użytkownika. ";
$PMF_LANG['ad_user_error_noId'] = "Nie określono ID . ";
$PMF_LANG['ad_user_error_protectedAccount'] = "Konto użytkownika jest zabezpieczone. ";
$PMF_LANG['ad_user_deleteUser'] = "Usuń użytkownika";
$PMF_LANG['ad_user_status'] = "Status";
$PMF_LANG['ad_user_lastModified'] = "ostatnia modyfikacja";
$PMF_LANG['ad_gen_cancel'] = "Anuluj";
$PMF_LANG['rightsLanguage::addglossary'] = "Dodaj pozycję słownika";
$PMF_LANG['rightsLanguage::editglossary'] = "Edytuj pozycję słownika";
$PMF_LANG['rightsLanguage::delglossary'] = "Usuń pozycję słownika";
$PMF_LANG["ad_menu_group_administration"] = "Grupy";
$PMF_LANG['ad_user_loggedin'] = 'Zalogowany jako ';
$PMF_LANG['ad_group_details'] = "Szczegóły grupy";
$PMF_LANG['ad_group_add'] = "Dodaj grupę";
$PMF_LANG['ad_group_add_link'] = "Dodaj grupę";
$PMF_LANG['ad_group_name'] = "Nazwa";
$PMF_LANG['ad_group_description'] = "Opis";
$PMF_LANG['ad_group_autoJoin'] = "Automatyczne dołączenie";
$PMF_LANG['ad_group_suc'] = "Grupa <strong>pomyślnie</strong> została dodana.";
$PMF_LANG['ad_group_error_noName'] = "Wpisz nazwę grupy. ";
$PMF_LANG['ad_group_error_delete'] = "Nie można usunąć grupy. ";
$PMF_LANG['ad_group_deleted'] = "Grupa została pomyślnie usunięta.";
$PMF_LANG['ad_group_deleteGroup'] = "Usuń grupę";
$PMF_LANG['ad_group_deleteQuestion'] = "Czy na pewno chcesz usunąć tę grupę?";
$PMF_LANG['ad_user_uncheckall'] = "Odznacz wszystko";
$PMF_LANG['ad_group_membership'] = "Członkostwo w Grupie";
$PMF_LANG['ad_group_members'] = "Członkowie";
$PMF_LANG['ad_group_addMember'] = "+";
$PMF_LANG['ad_group_removeMember'] = "-";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'Ogranicz eksport danych z FAQ (opcjonalnie)';
$PMF_LANG['ad_export_cat_downwards'] = 'Uwzględnić kategorie podrzędne?';
$PMF_LANG['ad_export_type'] = 'Format eksportu';
$PMF_LANG['ad_export_type_choose'] = 'Obsługiwane formaty:';
$PMF_LANG['ad_export_download_view'] = 'Pobrać lub wyświetlić bezpośrednio?';
$PMF_LANG['ad_export_download'] = 'pobierz';
$PMF_LANG['ad_export_view'] = 'zobacz on-line';
$PMF_LANG['ad_export_gen_xhtml'] = 'Utwórz plik XHTML';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'Aktualności FAQ';
$PMF_LANG['ad_news_author_name'] = 'Imię autora:';
$PMF_LANG['ad_news_author_email'] = 'Adres e-mail autora:';
$PMF_LANG['ad_news_set_active'] = 'Aktywuj';
$PMF_LANG['ad_news_allowComments'] = 'Zezwalaj na komentarze:';
$PMF_LANG['ad_news_expiration_window'] = 'Okno czasowe ważności wiadomości (opcjonalnie)';
$PMF_LANG['ad_news_from'] = 'Od:';
$PMF_LANG['ad_news_to'] = 'Do:';
$PMF_LANG['ad_news_insertfail'] = 'Podczas wstawiania wpisu wiadomości do bazy danych wystąpił błąd.';
$PMF_LANG['ad_news_updatefail'] = 'Podczas aktualizacji wpisu wiadomości do bazy danych wystąpił błąd.';
$PMF_LANG['newsShowCurrent'] = 'Pokaż aktualne wiadomości.';
$PMF_LANG['newsShowArchive'] = 'Pokaż zarchiwizowane wiadomości.';
$PMF_LANG['newsArchive'] = ' Archiwum wiadomości';
$PMF_LANG['newsWriteComment'] = 'komentarz do tego wpisu';
$PMF_LANG['newsCommentDate'] = 'Dodano: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'Rejestruj okno czasu wygaśnięcia (opcjonalnie)';
$PMF_LANG['admin_mainmenu_home'] = 'Panel';
$PMF_LANG['admin_mainmenu_users'] = 'Użytkownicy';
$PMF_LANG['admin_mainmenu_content'] = 'Treść';
$PMF_LANG['admin_mainmenu_statistics'] = 'Statystyki';
$PMF_LANG['admin_mainmenu_exports'] = 'Eksport';
$PMF_LANG['admin_mainmenu_backup'] = 'Kopia zapasowa';
$PMF_LANG['admin_mainmenu_configuration'] = 'Konfiguracja';
$PMF_LANG['admin_mainmenu_logout'] = 'Wyloguj';

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = 'Właściciel kategorii';
$PMF_LANG['adminSection'] = 'Administrowanie';
$PMF_LANG['err_expiredArticle'] = 'Ten wpis wygasł i nie można go wyświetlić';
$PMF_LANG['err_expiredNews'] = 'Ten wpis wiadomości wygasł i nie można go wyświetlić';
$PMF_LANG['err_inactiveNews'] = 'Ten wpis wiadomości jest w wersji poprawionej i nie można go wyświetlić';
$PMF_LANG['msgSearchOnAllLanguages'] = 'szukaj we wszystkich językach';
$PMF_LANG['ad_entry_tags'] = 'Etykiety';
$PMF_LANG['msg_tags'] = 'Etykiety';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = 'Powiązane wpisy';
$LANG_CONF['records.numberOfRelatedArticles'] = ["input", "Liczba powiązanych wpisóws"];

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = 'Przetłumacz';
$PMF_LANG['ad_categ_trans_2'] = 'Kategoria';
$PMF_LANG['ad_categ_translatecateg'] = 'Przetłumacz Kategorię';
$PMF_LANG['ad_categ_translate'] = 'Przetłumacz';
$PMF_LANG['ad_categ_transalready'] = 'Już przetłumaczone na: ';
$PMF_LANG["ad_categ_deletealllang"] = 'Usunąć we wszystkich językach?';
$PMF_LANG["ad_categ_deletethislang"] = 'Usunąć tylko w tym języku?';
$PMF_LANG["ad_categ_translated"] = "Kategoria została przetłumaczona.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "Przegląd kategorii";
$PMF_LANG['ad_menu_categ_structure'] = "Przegląd Kategorii łącznie z jej językami";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'Uprawnienia użytkownika:';
$PMF_LANG['ad_entry_grouppermission'] = 'Uprawnienia grupy:';
$PMF_LANG['ad_entry_all_users'] = 'Dostęp dla wszystkich użytkowników';
$PMF_LANG['ad_entry_restricted_users'] = 'Ograniczony dostęp do';
$PMF_LANG['ad_entry_all_groups'] = 'Dostęp dla wszystkich grup';
$PMF_LANG['ad_entry_restricted_groups'] = 'Ograniczony dostęp do';
$PMF_LANG['ad_session_expiration'] = 'Sesja wygasa za';
$PMF_LANG['ad_user_active'] = 'atywny';
$PMF_LANG['ad_user_blocked'] = 'zablokowany';
$PMF_LANG['ad_user_protected'] = 'chroniony';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'Wybierz rekord FAQ, aby wstawić go jako link...';

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "Wklej po";
$PMF_LANG["ad_categ_remark_move"] = "Zamiana 2 kategorii jest możliwa tylko na tym samym poziomie.";
$PMF_LANG["ad_categ_remark_overview"] = "Prawidłowa kolejność kategorii zostanie wyświetlona, jeśli dla danego języka zdefiniowano wszystkie kategorie (pierwsza kolumna).";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d Gości i %d Zarejestrowanych';
$PMF_LANG['ad_adminlog_del_older_30d'] = 'Natychmiast usuwaj dzienniki starsze niż 30 dni';
$PMF_LANG['ad_adminlog_delete_success'] = 'Starsze logi zostały pomyślnie usunięte.';
$PMF_LANG['ad_adminlog_delete_failure'] = 'Nie usunięto żadnych logów: wystąpił błąd podczas wykonywania żądania.';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['ad_quicklinks'] = 'Szybkie linki';
$PMF_LANG['ad_quick_category'] = 'Dodaj nową kategorię';
$PMF_LANG['ad_quick_record'] = 'Dodaj nowy wpis FAQ';
$PMF_LANG['ad_quick_user'] = 'Dodaj nowego użytkownika';
$PMF_LANG['ad_quick_group'] = 'Dodaj nową grupę';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = 'Propozycja tłumaczenia';
$PMF_LANG['msgNewTranslationAddon'] = 'Twoja propozycja nie zostanie od razu opublikowana, ale może zostać opublikowana przez administratora po sprawdzeniu. Wymagane pola to <strong>Twoje imię</strong>, <strong>Twój adres e-mail</strong>, <strong>Tłumaczenie Twojego pytania</strong> i <strong>Tłumaczenie Twojej odpowiedzi</strong>. Wszelkie słowa kluczowe proszę oddzielać wyłącznie przecinkami.';
$PMF_LANG['msgNewTransSourcePane'] = 'Panel źródłowy';
$PMF_LANG['msgNewTranslationPane'] = 'Panel tłumaczeń';
$PMF_LANG['msgNewTranslationName'] = "Twoje imię";
$PMF_LANG['msgNewTranslationMail'] = "Twój adres email";
$PMF_LANG['msgNewTranslationKeywords'] = "Słowa kluczowe";
$PMF_LANG['msgNewTranslationSubmit'] = 'Prześlij swoją propozycję';
$PMF_LANG['msgTranslate'] = 'Przetłumacz to FAQ';
$PMF_LANG['msgTranslateSubmit'] = 'Rozpocznij tłumaczenie...';
$PMF_LANG['msgNewTranslationThanks'] = "Dziękujemy za propozycję tłumaczenia!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG['rightsLanguage::addgroup'] = "Dodaj konta grup";
$PMF_LANG['rightsLanguage::editgroup'] = "Edytowanie kont grup";
$PMF_LANG['rightsLanguage::delgroup'] = "Usuń konta grup";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = 'Link otwiera się w oknie nadrzędnym';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'Uwagi';
$PMF_LANG['ad_comment_administration'] = 'Zarządzanie komentarzami';
$PMF_LANG['ad_comment_faqs'] = 'Komentarze w wpisach FAQ:';
$PMF_LANG['ad_comment_news'] = 'Komentarze w wpisach News:';
$PMF_LANG['msgPDF'] = 'Wersja PDF';
$PMF_LANG['ad_groups'] = 'Grupy';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = ['select', 'Sortowanie rekordów (według właściwości)'];
$LANG_CONF['records.sortby'] = ['select', 'Sortowanie rekordów (malejąco lub rosnąco)'];
$PMF_LANG['ad_conf_order_id'] = 'ID<br>(ddomyślnie)';
$PMF_LANG['ad_conf_order_thema'] = 'Tytuł';
$PMF_LANG['ad_conf_order_visits'] = 'Liczba odwiedzających';
$PMF_LANG['ad_conf_order_updated'] = 'Data';
$PMF_LANG['ad_conf_order_author'] = 'Autor';
$PMF_LANG['ad_conf_desc'] = 'malejąco';
$PMF_LANG['ad_conf_asc'] = 'rosnąco';
$PMF_LANG['mainControlCenter'] = 'Główny';
$PMF_LANG['recordsControlCenter'] = 'FAQs';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = ["checkbox", "Aktywuj nowe rekordy"];
$LANG_CONF['records.defaultAllowComments'] = ["checkbox", "Zezwalaj na komentarze nowych rekordów<br>(domyślnie: niedozwolone)"];

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'Rekordy w tej kategorii';
$PMF_LANG['msgTagSearch'] = 'Otagowane wpisy';
$PMF_LANG['ad_pmf_info'] = 'Informacje o phpMyFAQ';
$PMF_LANG['ad_online_info'] = 'Sprawdzanie wersji online';
$PMF_LANG['ad_system_info'] = 'Informacje Systemowe';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = 'Zapisz się';
$PMF_LANG["ad_user_loginname"] = 'Nazwa użytkownika:';
$PMF_LANG['errorRegistration'] = 'To pole jest wymagane!';
$PMF_LANG['submitRegister'] = 'Utwórz';
$PMF_LANG['msgUserData'] = 'Dane użytkownika wymagane do rejestracji';
$PMF_LANG['captchaError'] = 'Proszę wpisać właściwy kod captcha!';
$PMF_LANG['msgRegError'] = 'Wystąpiły następujące błędy. Popraw je:';
$PMF_LANG['successMessage'] = 'Twoja rejestracja przebiegła pomyślnie. Wkrótce otrzymasz wiadomość e-mail z potwierdzeniem zawierającą dane do logowania!';
$PMF_LANG['msgRegThankYou'] = 'Dziękujemy za rejestrację!';
$PMF_LANG['emailRegSubject'] = '[%sitename%] Rejestracja: nowego użytkownika';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'Najpopularniejsze wyszukiwania';
$LANG_CONF['main.enableWysiwygEditor'] = ["checkbox", "Włącz dołączony edytor WYSIWYG"];

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = 'Statystyki Wyszukiwania';
$PMF_LANG['ad_searchstats_search_term'] = 'Słowo kluczowe';
$PMF_LANG['ad_searchstats_search_term_count'] = 'Ilość';
$PMF_LANG['ad_searchstats_search_term_lang'] = 'Język';
$PMF_LANG['ad_searchstats_search_term_percentage'] = 'Procentowo';

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = 'Przyklej';
$PMF_LANG['ad_entry_sticky'] = 'Przyklej';
$PMF_LANG['stickyRecordsHeader'] = 'Przyklejone FAQs';

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = 'Słowo Zatrzymujące';
$PMF_LANG['ad_config_stopword_input'] = 'Dodaj nowe słowo zatrzymujące';

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = 'Nie, nadal nie ma odpowiedniej odpowiedzi (wyślę e-mail)';
$PMF_LANG['msgSendMailIfNothingIsFound'] = 'Czy poszukiwana odpowiedź znajduje się w powyższych wynikach?';

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = 'Proszę wybrać język tłumaczenia';
$PMF_LANG['msgLangDirIsntWritable'] = 'Folder <strong>/lang</strong> , w którym znajdują się pliki tłumaczeń, nie jest zapisywalny.';
$PMF_LANG['ad_menu_translations'] = 'Tłumaczenie Interfejsu';
$PMF_LANG['ad_start_notactive'] = 'Oczekiwanie na aktywację';

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = 'Dodaj nowe tłumaczenie';
$PMF_LANG['msgTransToolLanguage'] = 'Język';
$PMF_LANG['msgTransToolActions'] = 'Działania';
$PMF_LANG['msgTransToolWritable'] = 'Zapisywalny';
$PMF_LANG['msgEdit'] = 'Edytuj';
$PMF_LANG['msgDelete'] = 'Usuń';
$PMF_LANG['msgYes'] = 'Tak';
$PMF_LANG['msgNo'] = 'Nie';
$PMF_LANG['msgTransToolSureDeleteFile'] = 'Czy na pewno chcesz usunąć ten plik językowy?';
$PMF_LANG['msgTransToolFileRemoved'] = 'Plik językowy został pomyślnie usunięty';
$PMF_LANG['msgTransToolErrorRemovingFile'] = 'Błąd podczas usuwania pliku językowego';
$PMF_LANG['msgVariable'] = 'Zmienna';
$PMF_LANG['msgCancel'] = 'Anuluj';
$PMF_LANG['msgSave'] = 'Zapisz';
$PMF_LANG['msgSaving3Dots'] = 'zapisywanie ...';
$PMF_LANG['msgRemoving3Dots'] = 'usuwanie ...';
$PMF_LANG['msgTransToolFileSaved'] = 'Plik językowy został pomyślnie zapisany';
$PMF_LANG['msgTransToolErrorSavingFile'] = 'Błąd podczas zapisywania pliku językowego';
$PMF_LANG['msgLanguage'] = 'Język';
$PMF_LANG['msgTransToolLanguageCharset'] = 'Kodowanie języka';
$PMF_LANG['msgTransToolLanguageDir'] = 'Kierunek języka';
$PMF_LANG['msgTransToolLanguageDesc'] = 'Opis języka';
$PMF_LANG['msgAuthor'] = 'Autor';
$PMF_LANG['msgTransToolAddAuthor'] = 'Dodaj autora';
$PMF_LANG['msgTransToolCreateTranslation'] = 'Utwórz Tłumaczenie';
$PMF_LANG['msgTransToolTransCreated'] = 'Pomyślnie utworzono nowe tłumaczenie';
$PMF_LANG['msgTransToolCouldntCreateTrans'] = 'Nie można utworzyć nowego tłumaczenia';
$PMF_LANG['msgAdding3Dots'] = 'edycja ...';
$PMF_LANG['msgTransToolSendToTeam'] = 'Wyślij do zespołu phpMyFAQ';
$PMF_LANG['msgSending3Dots'] = 'wysyłanie ...';
$PMF_LANG['msgTransToolFileSent'] = 'Plik językowy został pomyślnie wysłany do zespołu phpMyFAQ. Dziękuję bardzo za udostępnienie tego.';
$PMF_LANG['msgTransToolErrorSendingFile'] = 'Wystąpił błąd podczas wysyłania pliku językowego';
$PMF_LANG['msgTransToolPercent'] = 'Procentowo';

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = ["input", "Ścieżka, w której będą zapisywane załączniki.<br><small>Ścieżka względna oznacza folder w katalogu głównym</small>"];

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "Plik, który próbujesz pobrać, nie został znaleziony na tym serwerze";

// added 2.6.0-alpha - 2009-07-30 by Aurimas FiĹˇeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"][0] = "%d użytkownik on-line";
$PMF_LANG["plmsgUserOnline"][1] = "%d użytkowników on-line";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = ["select", "Zestaw szablonów do użycia"];

// added 2.6.0-alpha - 2009-08-16 by Aurimas FiĹˇeras
$PMF_LANG['msgTransToolRemove'] = 'Usuń';
$PMF_LANG["msgTransToolLanguageNumberOfPlurals"] = "Liczba form liczby mnogiej";
$PMF_LANG['msgTransToolLanguageOnePlural'] = 'Język ten ma tylko jedną formę liczby mnogiej';
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = "Obsługa form liczby mnogiej dla języka %s jest wyłączona (nie ustawiono liczby mnogiej)";

// added 2.6.0-alpha - 2009-08-16 by Aurimas FiĹˇeras - Plural messages
$PMF_LANG["plmsgHomeArticlesOnline"][0] = "Znajduje się tam %d FAQ on-line";
$PMF_LANG["plmsgHomeArticlesOnline"][1] = "Znajduje się tam %d FAQs on-line";
$PMF_LANG["plmsgViews"][0] = "%d wyświetleń";
$PMF_LANG["plmsgViews"][1] = "%d wyświetleń";

// added 2.6.0-alpha - 2009-08-30 by Aurimas FiĹˇeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = '%d Gość';
$PMF_LANG['plmsgGuestOnline'][1] = '%d Gości';
$PMF_LANG['plmsgRegisteredOnline'][0] = ' i %d Zarejestrowanych';
$PMF_LANG['plmsgRegisteredOnline'][1] = ' i %d Zarejestrowanych';
$PMF_LANG["plmsgSearchAmount"][0] = "%d wynik wyszukiwania";
$PMF_LANG["plmsgSearchAmount"][1] = "%d wyników wyszukiwania";
$PMF_LANG["plmsgPagesTotal"][0] = " %d Strona";
$PMF_LANG["plmsgPagesTotal"][1] = " %d Stron";
$PMF_LANG["plmsgVotes"][0] = "%d Głos";
$PMF_LANG["plmsgVotes"][1] = "%d Głosów";
$PMF_LANG["plmsgEntries"][0] = "%d FAQ";
$PMF_LANG["plmsgEntries"][1] = "%d FAQs";

// added 2.6.0-alpha - 2009-09-06 by Aurimas FiĹˇeras
$PMF_LANG['rightsLanguage::addtranslation'] = "Dodaj tłumaczenie";
$PMF_LANG['rightsLanguage::edittranslation'] = "Edytuj tłumaczenie";
$PMF_LANG['rightsLanguage::deltranslation'] = "Usuń tłumaczenie";
$PMF_LANG['rightsLanguage::approverec'] = "Zatwierdź wpisy";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["records.enableAttachmentEncryption"] = ["checkbox", "Włącz szyfrowanie załączników <br><small>Ignorowane, gdy załączniki są wyłączone</small>"];
$LANG_CONF["records.defaultAttachmentEncKey"] = ["input", 'Domyślny klucz szyfrowania załączników>Ignorowany, jeśli szyfrowanie załączników jest wyłączone</small><br><small><span class="text-danger">OSTRZEŻENIE: Nie zmieniaj tego po ustawieniu i włączeniu szyfrowania plików! !</span></small>'];

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'Aktualizacja';
$PMF_LANG['ad_you_shouldnt_update'] = 'Masz najnowszą wersję phpMyFAQ. Nie ma potrzeby aktualizacji.';
$LANG_CONF['security.useSslForLogins'] = ['checkbox', "Zezwalać na logowanie tylko za pośrednictwem połączenia SSL?"];
$PMF_LANG['msgSecureSwitch'] = "Aby się zalogować, przejdź do trybu bezpiecznego!";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = 'Pamiętaj, że żadne pliki nie zostaną zapisane, dopóki nie klikniesz przycisku Zapisz';
$PMF_LANG['msgTransToolPageBufferRecorded'] = 'Bufor strony %d został pomyślnie zarejestrowany';
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = 'Błąd zapisu strony %d bufora';
$PMF_LANG['msgTransToolRecordingPageBuffer'] = 'Zapis bufora strony %d %d';

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = 'Aktywny';

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = 'Załącznik jest nieprawidłowy, proszę poinformować administratora';

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms'] = ['input', 'Liczba wyszukiwanych haseł na liście'];
$LANG_CONF['records.orderingPopularFaqs'] = ["select", "Sortowanie najpopularniejszych FAQs"];
$PMF_LANG['list_all_users'] = 'Lista wszystkich użytkowników';

$PMF_LANG['records.orderingPopularFaqs.visits'] = "lista najczęściej odwiedzanych wpisów";
$PMF_LANG['records.orderingPopularFaqs.voting'] = "lista wpisów, na które głosowano najczęściej";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = 'Proszę oddzielić słowa przecinkami.';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = 'aktualizuj';
$PMF_LANG['msgKeepFaqDate'] = 'pozostaw';
$PMF_LANG['msgEditFaqDat'] = 'edytuj';
$LANG_CONF['main.optionalMailAddress'] = ['checkbox', 'Adres e-mail jako pole obowiązkowe'];

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = ['select', 'Sortuj według trafności'];
$LANG_CONF["search.enableRelevance"] = ["checkbox", "Aktywować obsługę trafności?"];
$PMF_LANG['searchControlCenter'] = 'Szukaj';
$PMF_LANG['search.relevance.thema-content-keywords'] = 'Pytanie – Odpowiedź – Słowa Kluczowe';
$PMF_LANG['search.relevance.thema-keywords-content'] = 'Pytanie – Słowa kluczowe – Odpowiedź';
$PMF_LANG['search.relevance.content-thema-keywords'] = 'Odpowiedź - Pytanie - Słowa kluczowe';
$PMF_LANG['search.relevance.content-keywords-thema'] = 'Odpowiedź - Słowa kluczowe - Pytanie';
$PMF_LANG['search.relevance.keywords-content-thema'] = 'Słowa kluczowe - Odpowiedź - Pytanie';
$PMF_LANG['search.relevance.keywords-thema-content'] = 'Słowa kluczowe – Pytanie – Odpowiedź';

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = 'Zaloguj się';
$PMF_LANG['socialNetworksControlCenter'] = 'Sieci społecznościowe';
$LANG_CONF['socialnetworks.enableTwitterSupport'] = ['checkbox', 'X (Twitter) wsparcie'];
$LANG_CONF['socialnetworks.twitterConsumerKey'] = ['input', 'X (Twitter) Klucz Klienta'];
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = ['input', 'X (Twitter) Consumer Secret'];

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = ['input', 'X (Twitter) Access Token Key'];
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = ['input', 'X (Twitter) Access Token Secret'];

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG["ad_menu_attachments"] = "FAQ Załączniki";
$PMF_LANG["ad_menu_attachment_admin"] = "Administracja załącznikami";
$PMF_LANG['msgAttachmentsFilename'] = 'Nazwa pliku';
$PMF_LANG['msgAttachmentsFilesize'] = 'Rozmiar pliku';
$PMF_LANG['msgAttachmentsMimeType'] = 'Typ MIME';
$PMF_LANG['msgAttachmentsWannaDelete'] = 'Czy na pewno chcesz usunąć ten załącznik?';
$PMF_LANG['msgAttachmentsDeleted'] = 'Załącznik <strong>pomyślnie</strong> został usunięty.';

// added v2.7.0-alpha2 - 2010-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = 'Raporty';
$PMF_LANG["ad_stat_report_fields"] = "Pola";
$PMF_LANG["ad_stat_report_category"] = "Kategoria";
$PMF_LANG["ad_stat_report_sub_category"] = "Podkategoria";
$PMF_LANG["ad_stat_report_translations"] = "Tłumaczenia";
$PMF_LANG["ad_stat_report_language"] = "Język";
$PMF_LANG["ad_stat_report_id"] = "ID FAQ";
$PMF_LANG["ad_stat_report_sticky"] = "Sticky FAQ";
$PMF_LANG["ad_stat_report_title"] = "Pytanie";
$PMF_LANG["ad_stat_report_creation_date"] = "Data";
$PMF_LANG["ad_stat_report_owner"] = "Autor oryginału";
$PMF_LANG["ad_stat_report_last_modified_person"] = "Ostatni autor";
$PMF_LANG["ad_stat_report_url"] = "URL";
$PMF_LANG["ad_stat_report_visits"] = "Wizyty";
$PMF_LANG["ad_stat_report_make_report"] = "Wygeneruj Raport";
$PMF_LANG["ad_stat_report_make_csv"] = "Eksport pliku CSV";

// added v2.7.0-alpha2 - 2010-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = 'Rejestracja';
$PMF_LANG['msgRegistrationCredentials'] = 'Aby się zarejestrować, wpisz swoje imię i nazwisko, nazwę użytkownika i ważny adres e-mail!';
$PMF_LANG['msgRegistrationNote'] = 'Po pomyślnym przesłaniu tego formularza otrzymasz wiadomość e-mail, gdy administrator zatwierdzi Twoją rejestrację.';

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = "Historia zmian";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = ['checkbox', 'Włącz obsługę pojedynczego logowania'];
$LANG_CONF['security.ssoLogoutRedirect'] = ['input', 'Usługa przekierowywania wylogowania przy pojedynczym logowaniu URL'];
$LANG_CONF['main.dateFormat'] = ['input', 'Format daty (domyślny: d-m-Y H:i)'];
$LANG_CONF['security.enableLoginOnly'] = ['checkbox', 'Kompletne zabezpieczone FAQ'];

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = 'Bezpieczeństwo';
$PMF_LANG['ad_search_delsuc'] = 'Wyszukiwane hasło zostało pomyślnie usunięte.';
$PMF_LANG['ad_search_delfail'] = 'Wyszukiwane hasło nie zostało usunięte.';

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = 'O tym FAQ';
$LANG_CONF['security.useSslOnly'] = ['checkbox', 'FAQ tylko z SSL'];
$PMF_LANG['msgTableOfContent'] = 'Table of Content';

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG["msgExportAllFaqs"] = "Wydrukuj wszystko jako plik PDF";
$PMF_LANG["ad_online_verification"] = "Kontrola weryfikacji on-line";
$PMF_LANG["ad_verification_button"] = "Kliknij, aby zweryfikować instalację phpMyFAQ";
$PMF_LANG["ad_verification_notokay"] = "Twoja wersja phpMyFAQ zawiera lokalne zmiany";
$PMF_LANG["ad_verification_okay"] = "Twoja wersja phpMyFAQ została pomyślnie zweryfikowana.";

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = 'Wyszukaj FAQs';

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF["records.enableCloseQuestion"] = ["checkbox", "Zamknąć otwarte pytanie po odpowiedzi?"];
$LANG_CONF["records.enableDeleteQuestion"] = ["checkbox", "Usunąć otwarte pytanie po odpowiedzi?"];
$PMF_LANG["msg2answerFAQ"] = "Odpowiedziano";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG["headerUserControlPanel"] = 'Panel Kontrolny Użytkownika';

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG["rememberMe"] = 'Następnym razem zaloguj mnie automatycznie';
$PMF_LANG["ad_menu_instances"] = "FAQ Multisites";

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = 'FAQs nieaktywny';
$LANG_CONF["main.maintenanceMode"] = ["checkbox", "Ustaw FAQ w trybie konserwacji"];
$PMF_LANG['msgMode'] = "Modus";
$PMF_LANG['msgMaintenanceMode'] = "FAQ jest w konserwacji";
$PMF_LANG['msgOnlineMode'] = "FAQ jest on-line";

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG['msgShowMore'] = "pokaż więcej";
$PMF_LANG['msgQuestionAnswered'] = "Odpowiedź na pytanie";
$PMF_LANG['msgMessageQuestionAnswered'] = "Odpowiedź na Twoje pytanie w %s została udzielona. Sprawdź to tutaj";

// added v2.8.0-alpha3 - 2012-11-03 by Thorsten
$PMF_LANG['rightsLanguage::addattachment'] = "Dodaj załączniki";
$PMF_LANG['rightsLanguage::editattachment'] = "Edytuj załączniki";
$PMF_LANG['rightsLanguage::delattachment'] = "Usuń załączniki";
$PMF_LANG['rightsLanguage::dlattachment'] = "Pobierz załączniki";
$PMF_LANG['rightsLanguage::reports'] = "Generuj raporty";
$PMF_LANG['rightsLanguage::addfaq'] = "Dodaj FAQs w serwisie";
$PMF_LANG['rightsLanguage::addquestion'] = "Dodaj pytania w serwisie";
$PMF_LANG['rightsLanguage::addcomment'] = "Dodaj komentarze w serwisie";
$PMF_LANG['rightsLanguage::editinstances'] = "Edytuj Multisites";
$PMF_LANG['rightsLanguage::addinstances'] = "Dodaj Multisites";
$PMF_LANG['rightsLanguage::delinstances'] = "Usuń Multisites";
$PMF_LANG['rightsLanguage::export'] = "Eksport FAQs";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF["records.randomSort"] = ["checkbox", "Sortuj losowo FAQs"];
$LANG_CONF['main.enableWysiwygEditorFrontend'] = ["checkbox", "Włącz w interfejsie dołączony edytor WYSIWYG"];

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF["main.enableGravatarSupport"] = ["checkbox", "Wsparcie Gravatar"];

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG["ad_stopwords_desc"] = "Wybierz język, aby dodać lub edytować słowa zatrzymania.";
$PMF_LANG["ad_visits_per_day"] = "Wizyt dziennie";

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG["ad_instance_add"] = "Dodaj nową instancję wieloserwisową phpMyFAQ";
$PMF_LANG["ad_instance_error_notwritable"] = "Folder /multisite nie jest przeznaczony do zapisu.";
$PMF_LANG["ad_instance_url"] = "URL instancji";
$PMF_LANG["ad_instance_path"] = "Ścieżka instancji";
$PMF_LANG["ad_instance_name"] = "Nazwa instancji";
$PMF_LANG["ad_instance_email"] = "Twój adres e-mail administratora";
$PMF_LANG["ad_instance_admin"] = "Nazwa użytkownika administratora";
$PMF_LANG["ad_instance_password"] = "Hasło administratora";
$PMF_LANG["ad_instance_hint"] = "Uwaga: Utworzenie nowej instancji phpMyFAQ może zająć kilka sekund!";
$PMF_LANG["ad_instance_button"] = "Zapisz instancję";
$PMF_LANG["ad_instance_error_cannotdelete"] = "Nie można usunąć instancji ";
$PMF_LANG["ad_instance_config"] = "Konfiguracja instancji";

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG["msgAboutThisNews"] = "O tej wiadomości";

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG["msgAccessDenied"] = "Brak dostępu.";

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG['msgSeeFAQinFrontend'] = 'Zobacz FAQ w serwisie';

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG["msgRelatedTags"] = 'Dodaj słowo wyszukiwania';
$PMF_LANG["msgPopularTags"] = 'Najpopularniejsze wyszukiwania';
$LANG_CONF["search.enableHighlighting"] = ["checkbox", "Podświetl wyszukiwane hasła"];
$LANG_CONF["records.allowCommentsForGuests"] = ["checkbox", "Zezwalaj na komentowanie gości"];
$LANG_CONF["records.allowQuestionsForGuests"] = ["checkbox", "Zezwalaj na dodawanie pytań do gości"];
$LANG_CONF["records.allowNewFaqsForGuests"] = ["checkbox", "Zezwalaj na dodawanie nowych FAQ dla gości"];
$PMF_LANG["ad_searchterm_del"] = 'Usuń wszystkie zapisane wyszukiwane hasła';
$PMF_LANG["ad_searchterm_del_suc"] = 'Pomyślnie usunięto wszystkie wyszukiwane hasła.';
$PMF_LANG["ad_searchterm_del_err"] = 'Nie można usunąć wszystkich wyszukiwanych haseł.';
$LANG_CONF["records.hideEmptyCategories"] = ["checkbox", "Ukryj puste kategorie"];
$LANG_CONF["search.searchForSolutionId"] = ["checkbox", "Wyszukaj ID rozwiązania"];
$LANG_CONF["socialnetworks.disableAll"] = ["checkbox", "Wyłącz wszystkie sieci społecznościowe"];
$LANG_CONF["main.enableGzipCompression"] = ["checkbox", "Włącz kompresję GZIP"];

// added v2.9.0-alpha2 - 2014-08-16 by Thorsten
$PMF_LANG["ad_tag_delete_success"] = "Etykieta została pomyślnie usunięta.";
$PMF_LANG["ad_tag_delete_error"] = "Etykieta nie została usunięta, ponieważ wystąpił błąd.";
$PMF_LANG["seoCenter"] = "SEO";
$LANG_CONF["seo.metaTagsHome"] = ["select", "Meta Tagi strony startowa"];
$LANG_CONF["seo.metaTagsFaqs"] = ["select", "Meta Tagi FAQs"];
$LANG_CONF["seo.metaTagsCategories"] = ["select", "Meta Tagi strony kategorii"];
$LANG_CONF["seo.metaTagsPages"] = ["select", "Meta Tagi stron statycznych"];
$LANG_CONF["seo.metaTagsAdmin"] = ["select", "Meta Tagi Administrator"];
$PMF_LANG["msgMatchingQuestions"] = "Poniższe wyniki ściśle odpowiadają Twojemu zapytaniu";
$PMF_LANG["msgFinishSubmission"] = "Jeśli żadna z powyższych sugestii nie pasuje do Twojego pytania, kliknij poniższy przycisk, aby zakończyć przesyłanie pytania.";
$LANG_CONF['spam.manualActivation'] = ['checkbox', 'Ręcznie aktywuj nowych użytkowników (domyślnie: aktywowani)'];

// added v2.9.0-alpha2 - 2014-10-13 by Christopher Andrews ( Chris--A )
$PMF_LANG['mailControlCenter'] = 'Konfiguracja poczty';
$LANG_CONF['mail.remoteSMTP'] = ['checkbox', 'Użyj zdalnego serwera SMTP (domyślnie: wyłączone)'];
$LANG_CONF['mail.remoteSMTPServer'] = ['input', 'Adres serwera'];
$LANG_CONF['mail.remoteSMTPUsername'] = ['input', 'Nazwa użytkownika'];
$LANG_CONF['mail.remoteSMTPPassword'] = ['password', 'Hasło'];
$LANG_CONF['security.enableRegistration'] = ['checkbox', 'Włącz rejestrację dla gości'];

// added v2.9.0-alpha3 - 2015-02-08 by Thorsten
$LANG_CONF['main.customPdfHeader'] = ['area', 'Niestandardowy nagłówek PDF (dozwolony HTML)'];
$LANG_CONF['main.customPdfFooter'] = ['area', 'Niestandardowa stopka PDF (dozwolony HTML)'];
$LANG_CONF['records.allowDownloadsForGuests'] = ['checkbox', 'Zezwalaj na pobieranie dla gości'];
$PMF_LANG["ad_msgNoteAboutPasswords"] = "Uwaga! Jeśli wpiszesz hasło, nadpiszesz hasło użytkownika.";
$PMF_LANG["ad_delete_all_votings"] = "Usuń wszystkie głosy";
$PMF_LANG["ad_categ_moderator"] = "Moderatorzy";
$PMF_LANG['ad_clear_all_visits'] = "Wyczyść wszystkie wizyty";
$PMF_LANG['ad_reset_visits_success'] = 'Reset wizyt przebiegł pomyślnie.';
$LANG_CONF['main.enableMarkdownEditor'] = ['checkbox', 'Włącz dołączony edytor Markdown'];

// added v2.9.0-beta - 2015-09-27 by Thorsten
$PMF_LANG['faqOverview'] = 'Przeglądaj FAQ';
$PMF_LANG['ad_dir_missing'] = 'Brakuje katalogu %s .';
$LANG_CONF['main.enableSmartAnswering'] = ['checkbox', 'Włącz inteligentne odpowiedzi na pytania użytkowników'];

// added v2.9.0-beta2 - 2015-12-23 by Thorsten
$LANG_CONF['search.enableElasticsearch'] = ['checkbox', 'Włącz obsługę Elasticsearch'];
$PMF_LANG['ad_menu_elasticsearch'] = 'Konfiguracja Elasticsearch';
$PMF_LANG['ad_es_create_index'] = 'Utwórz Indeks';
$PMF_LANG['ad_es_drop_index'] = 'Usuń Indeks';
$PMF_LANG['ad_es_bulk_index'] = 'Pełny import';
$PMF_LANG['ad_es_create_index_success'] = 'Indeks został pomyślnie utworzony.';
$PMF_LANG['ad_es_create_import_success'] = 'Import przebiegł pomyślnie.';
$PMF_LANG['ad_es_drop_index_success'] = 'Indeks został pomyślnie usunięty.';
$PMF_LANG['ad_export_generate_json'] = 'Utwórz plik JSON';
$PMF_LANG['ad_media_name_search'] = 'Szukaj nazwy nośnika';

// added v2.9.0-RC - 2016-02-19 by Thorsten
$PMF_LANG['ad_admin_notes'] = 'Notatki Prywatne';
$PMF_LANG['ad_admin_notes_hint'] = '%s (widoczne tylko dla redaktorów)';

// added v2.9.10 - 2018-02-17 by Thorsten
$PMF_LANG['ad_quick_entry'] = 'Dodaj nowe FAQ w tej kategorii';

// added 2.10.0-alpha - 2016-08-08 by Thorsten
$LANG_CONF['ldap.ldap_mapping.name'] = ['input', 'Mapowanie LDAP dla nazwy, "cn" podczas korzystania z ADS'];
$LANG_CONF['ldap.ldap_mapping.username'] = ['input', 'Mapowanie LDAP dla nazwy użytkownika, "samAccountName" podczas korzystania z ADS'];
$LANG_CONF['ldap.ldap_mapping.mail'] = ['input', 'Mapowanie LDAP dla poczty e-mail, "mail" podczas korzystania z ADS'];
$LANG_CONF['ldap.ldap_mapping.memberOf'] = ['input', 'Mapowanie LDAP dla "member of" podczas korzystania z grup LDAP'];
$LANG_CONF['ldap.ldap_use_domain_prefix'] = ['checkbox', 'Prefiks domeny LDAP, np. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'] = ['input', 'Wersja protokołu LDAP (domyślnie: 3)'];
$LANG_CONF['ldap.ldap_options.LDAP_OPT_REFERRALS'] = ['input', 'Skierowania LDAP (domyślnie: 0)'];
$LANG_CONF['ldap.ldap_use_memberOf'] = ['checkbox', 'Włącz obsługę grup LDAP, np. "DOMAIN\username"'];
$LANG_CONF['ldap.ldap_use_sasl'] = ['checkbox', 'Włącz obsługę LDAP SASL'];
$LANG_CONF['ldap.ldap_use_multiple_servers'] = ['checkbox', 'Włącz obsługę wielu serwerów LDAP'];
$LANG_CONF['ldap.ldap_use_anonymous_login'] = ['checkbox', 'Włącz anonimowe połączenia LDAP'];
$LANG_CONF['ldap.ldap_use_dynamic_login'] = ['checkbox', 'Włącz dynamiczne powiązanie użytkownika LDAP'];
$LANG_CONF['ldap.ldap_dynamic_login_attribute'] = ['input', 'Atrybut LDAP do dynamicznego powiązania użytkownika, "uid" podczas korzystania z ADS'];
$LANG_CONF['seo.enableXMLSitemap'] = ['checkbox', 'Włącz mapę witryny XML'];
$PMF_LANG['ad_category_image'] = 'Obraz kategorii';
$PMF_LANG["ad_user_show_home"] = "Pokaż na stronie startowej";

// added v.2.10.0-alpha - 2017-11-09 by Brian Potter (BrianPotter)
$PMF_LANG['ad_view_faq'] = 'Zobacz FAQ';

// added 3.0.0-alpha - 2018-01-04 by Thorsten
$LANG_CONF['main.enableCategoryRestrictions'] = ['checkbox', 'Włącz ograniczenia kategorii'];
$LANG_CONF['main.enableSendToFriend'] = ['checkbox', 'Włącz wysyłanie do przyjaciół'];
$PMF_LANG['msgUserRemovalText'] = 'Możesz żądać usunięcia swojego konta i danych osobowych. E-mail zostanie wysłany do zespołu administracyjnego. Zespół  nasz usunie Twoje konto, komentarze i pytania. Ponieważ jest to proces ręczny, może zająć do 24 godzin. Następnie otrzymasz e-mail z potwierdzeniem usunięcia. ';
$PMF_LANG["msgUserRemoval"] = "Żądaj usunięcia użytkownika";
$PMF_LANG["ad_menu_RequestRemove"] = "Żądaj usunięcia użytkownika";
$PMF_LANG["msgContactRemove"] = "Żądanie usunięcia od zespołu administratorów";
$PMF_LANG["msgContactPrivacyNote"] = "Proszę zwrócić uwagę na naszą";
$PMF_LANG["msgPrivacyNote"] = "Polityka Ochrony Prywatności";

// added 3.0.0-alpha2 - 2018-03-27 by Thorsten
$LANG_CONF['main.enableAutoUpdateHint'] = ['checkbox', 'Automatyczne sprawdzenie nowych wersji'];
$PMF_LANG['ad_user_is_superadmin'] = 'Super-Admin';
$PMF_LANG['ad_user_overwrite_passwd'] = 'Nadpisz hasło';
$LANG_CONF['records.enableAutoRevisions'] = ['checkbox', 'Wersjonowanie wszystkich zmian w FAQ'];
$PMF_LANG['rightsLanguage::view_faqs'] = 'Zobacz FAQs';
$PMF_LANG['rightsLanguage::view_categories'] = 'Zobacz kategorie';
$PMF_LANG['rightsLanguage::view_sections'] = 'Zobacz sekcje';
$PMF_LANG['rightsLanguage::view_news'] = 'Zobacz wiadomości';
$PMF_LANG['rightsLanguage::add_section'] = 'Dodaj sekcje';
$PMF_LANG['rightsLanguage::edit_section'] = 'Edytuj sekcje';
$PMF_LANG['rightsLanguage::delete_section'] = 'Usuń sekcje';
$PMF_LANG['rightsLanguage::administrate_sections'] = 'Zarządzaj sekcjami';
$PMF_LANG['rightsLanguage::administrate_groups'] = 'Zarządzaj grupami';
$PMF_LANG['ad_group_rights'] = 'Uprawnienia grupy';
$PMF_LANG['ad_menu_meta'] = 'Metadane Szablonu';
$PMF_LANG['ad_meta_add'] = 'Dodaj metadane szablonu';
$PMF_LANG['ad_meta_page_id'] = 'Typ strony';
$PMF_LANG['ad_meta_type'] = 'Typ zawartości';
$PMF_LANG['ad_meta_content'] = 'Zawartość';
$PMF_LANG['ad_meta_copy_snippet'] = 'Kopiuj fragment kodu szablonów';

// added v3.0.0-alpha.3 - 2018-09-20 by Timo
$PMF_LANG['ad_menu_section_administration'] = "Sekcje";
$PMF_LANG['ad_section_add'] = "Dodaj Sekcję";
$PMF_LANG['ad_section_add_link'] = "Dodaj Sekcję";
$PMF_LANG['ad_sections'] = 'Sekcje';
$PMF_LANG['ad_section_details'] = "Szczegóły Sekcji";
$PMF_LANG['ad_section_name'] = "Nazwa";
$PMF_LANG['ad_section_description'] = "Opis";
$PMF_LANG['ad_section_membership'] = "Przypisanie Sekcji";
$PMF_LANG['ad_section_members'] = "Przydziały";
$PMF_LANG['ad_section_addMember'] = "+";
$PMF_LANG['ad_section_removeMember'] = "-";
$PMF_LANG['ad_section_deleteSection'] = "Usuń Sekcję";
$PMF_LANG['ad_section_deleteQuestion'] = "Czy na pewno chcesz usunąć tę sekcję?";
$PMF_LANG['ad_section_error_delete'] = "Sekcja nie mogła zostać usunięta. ";
$PMF_LANG['ad_section_error_noName'] = "Proszę wprowadzić nazwę sekcji. ";
$PMF_LANG['ad_section_suc'] = "Sekcja została <strong>pomyślnie</strong> dodana.";
$PMF_LANG['ad_section_deleted'] = "Sekcja została pomyślnie usunięta.";
$PMF_LANG['rightsLanguage::viewadminlink'] = 'Zobacz link administratora';

// added v3.0.0-beta.3 - 2019-09-22 by Thorsten
$LANG_CONF['mail.remoteSMTPPort'] = ['input', 'Port serwera SMTP'];
$LANG_CONF['mail.remoteSMTPEncryption'] = ['input', 'Kodowanie serwera SMTP'];
$PMF_LANG['ad_record_faq'] = 'Pytanie i odpowiedź';
$PMF_LANG['ad_record_permissions'] = 'Uprawnienia';
$PMF_LANG['loginPageMessage'] = 'Zaloguj się ';

// added v3.0.5 - 2020-10-03 by Thorsten
$PMF_LANG['ad_menu_faq_meta'] = 'FAQ metadata';

// added v3.0.8 - 2021-01-22
$LANG_CONF['main.privacyURL'] = ['input', 'URL Polityki Prywatności'];

// added v3.1.0-alpha - 2020-03-27 by Thorsten
$PMF_LANG['ad_user_data_is_visible'] = 'Nazwa użytkownika powinna być widoczna';
$PMF_LANG['ad_user_is_visible'] = 'Widoczne';
$PMF_LANG['ad_categ_save_order'] = 'Nowe sortowanie zostało pomyślnie zapisane.';
$PMF_LANG['ad_add_user_change_password'] = 'Użytkownik musi zmienić hasło po pierwszym logowaniu';
$LANG_CONF['api.enableAccess'] = ['checkbox', 'REST API włączony'];
$LANG_CONF['api.apiClientToken'] = ['input', 'Token klienta API'];
$LANG_CONF['security.domainWhiteListForRegistrations'] = ['area', 'Dozwolone hosty do rejestracji'];
$LANG_CONF['security.loginWithEmailAddress'] = ['checkbox', 'Zaloguj się wyłącznie za pomocą adresu e-mail'];

// added v3.2.0-alpha - 2022-09-10 by Thorsten
$PMF_LANG['msgSignInWithMicrosoft'] = 'Logowanie za pomocą Microsoft)';
$LANG_CONF['security.enableSignInWithMicrosoft'] = ['checkbox', 'Włącz logowanie za pomocą Microsoft (Entra ID)'];
$LANG_CONF['main.enableAskQuestions'] = ['checkbox', 'Włącz opcję "Zadaj pytanie"'];
$LANG_CONF['main.enableNotifications'] = ['checkbox', 'Włącz powiadomienia'];
$LANG_CONF['mail.sendTestEmail'] = ['button', 'Wyślij e-mail testowy do administratora poprzez SMTP'];
$PMF_LANG['mail.sendTestEmail'] = 'Wyślij e-mail testowy do administratora';
$PMF_LANG['msgGoToCategory'] = 'Przejdź do kategorii';
$LANG_CONF['security.enableGoogleReCaptchaV2'] = ['checkbox', 'Włącz Niewidoczne Google ReCAPTCHA v2'];
$LANG_CONF['security.googleReCaptchaV2SiteKey'] = ['input', 'Google ReCAPTCHA v2 site key'];
$LANG_CONF['security.googleReCaptchaV2SecretKey'] = ['input', 'Google ReCAPTCHA v2 secret key'];

// added v3.2.0-alpha - 2023-03-11 by Jan
$PMF_LANG['msgTwofactorEnabled'] = "Włączono uwierzytelnianie dwupoziomowe";
$PMF_LANG['msgTwofactorConfig'] = "Skonfiguruj Uwierzytelnianie Dwupoziomowe";
$PMF_LANG['msgTwofactorConfigModelTitle'] = "Konfiguracja Uwierzytelniania Dwupoziomowego";
$PMF_LANG['qr_code_secret_alt'] = "QR-Code Secret-Key";
$PMF_LANG['msgTwofactorNewSecret'] = "Usunąć aktualny sekret kod?";
$PMF_LANG['msgTwofactorTokenModelTitle'] = "Uwierzytelnianie dwuetapowe – wprowadź token:";
$PMF_LANG['msgEnterTwofactorToken'] = "Wprowadź 6-cyfrowy kod z aplikacji uwierzytelniającej.";
$PMF_LANG['msgTwofactorCheck'] = "Sprawdź";
$PMF_LANG['msgTwofactorErrorToken'] = "Wpisałeś nieprawidłowy kod!";
$PMF_LANG['ad_user_overwrite_twofactor'] = "Resetuj uwierzytelnianie dwupoziomowe";

// added v3.2.0-alpha.2 - 2023-04-06 by Thorsten
$PMF_LANG['msgRedirect'] = 'Za 5 sekund nastąpi automatyczne przekierowanie.';
$PMF_LANG['msgCategoryMissingButTranslationAvailable'] = 'Nie znaleziono kategorii w wybranym języku, ale możesz wybrać następujące języki:';
$PMF_LANG['msgCategoryDescription'] = 'Tutaj znajdziesz przegląd wszystkich kategorii wraz z liczbą najczęściej zadawanych pytań.';
$PMF_LANG['msgSubCategoryContent'] = 'Wybierz kategorię główną.';
$PMF_LANG['ad_open_question_deleted'] = 'Pytanie zostało pomyślnie usunięte.';
$LANG_CONF['mail.remoteSMTPDisableTLSPeerVerification'] = ['checkbox', 'Wyłącz weryfikację równorzędną SMTP TLS (niezalecane)'];

// added v3.2.0-beta.2 - 2023-05-03 by Jan
$LANG_CONF['main.contactInformationHTML'] = ['checkbox', 'Informacje kontaktowe jako HTML?'];

// added v3.2.0-RC - 2023-05-18 by Thorsten
$PMF_LANG['msgAuthenticationSource'] = 'Usługa uwierzytelniania';

// added v3.2.0-RC - 2023-05-27 by Jan
$LANG_CONF['spam.mailAddressInExport'] = ['checkbox', 'Pokaż adres e-mail w eksporcie'];
$PMF_LANG['msgNewQuestionAdded'] = 'Dodano nowe pytanie. Możesz je sprawdzić tutaj lub w sekcji administracyjnej:';

// added v4.0.0-alpha - 2023-07-02 by Thorsten
$LANG_CONF['upgrade.onlineUpdateEnabled'] = ['checkbox', 'Aktualizacja online włączona'];
$LANG_CONF['upgrade.releaseEnvironment'] = ['select', 'Środowisko Wydania'];
$LANG_CONF['upgrade.dateLastChecked'] = ['print', 'Ostatnie sprawdzenie dostępności aktualizacji'];
$PMF_LANG['upgradeControlCenter'] = 'Aktualizacja Online';

// added v4.0.0-alpha - 2023-07-19 by Jan
$PMF_LANG['msgAddBookmark'] = 'Dodaj zakładkę';
$PMF_LANG['removeBookmark'] = 'Usuń zakładkę';
$PMF_LANG['msgBookmarkAdded'] = 'Zakładka została pomyślnie dodana!';
$PMF_LANG['msgBookmarkRemoved'] = 'Zakładka została pomyślnie usunięta!';

// added v4.0.0-alpha - 2023-07-11 by Jan
$PMF_LANG['headerCheckHealth'] = '1. Sprawdź stan systemu';
$PMF_LANG['headerCheckUpdates'] = '2. Sprawdź aktualizacje';
$PMF_LANG['headerDownloadPackage'] = '3. Pobierz phpMyFAQ';
$PMF_LANG['headerExtractPackage'] = '4. Rozpakuj pobrany pakiet';

$PMF_LANG['msgHealthCheck'] = 'Sprawdza to poprawność uprawnień do plików i strukturę folderów instalacji phpMyFAQ.';
$PMF_LANG['msgUpdateCheck'] = 'Możesz sprawdzić dostępność nowych wersji phpMyFAQ, ponownie zainstalować lub zaktualizować swoją instalację.';
$PMF_LANG['msgDownloadPackage'] = 'Pobieranie nowych wersji phpMyFAQ w zależności od ustawionego środowiska wydania.';
$PMF_LANG['msgExtractPackage'] = 'Spowoduje to rozpakowanie pobranego pakietu do systemu plików, może to chwilę zająć.';
$PMF_LANG['alertNightlyBuild'] = 'Używasz rozwojowej wersji phpMyFAQ. Możesz zaktualizować do najnowszej wersji nightly.';
$PMF_LANG['buttonCheckHealth'] = 'Sprawdź teraz stan systemu';
$PMF_LANG['buttonCheckUpdates'] = 'Sprawdź teraz dostępność aktualizacji';
$PMF_LANG['buttonDownloadPackage'] = 'Pobierz teraz';
$PMF_LANG['buttonExtractPackage'] = 'Wyodrębnij teraz pobrany pakiet';
$PMF_LANG['versionIsUpToDate'] = 'âś… Twoja zainstalowana wersja jest aktualna!';
$PMF_LANG['healthCheckOkay'] = 'âś… Twoja zainstalowana wersja jest w dobrej kondycji!';
$PMF_LANG['downloadSuccessful'] = 'âś… Pakiet został pomyślnie pobrany!';
$PMF_LANG['extractSuccessful'] = 'âś… Pakiet został pomyślnie wyodrębniony!';
$PMF_LANG['downloadFailure'] = 'âťŚ Nie można pobrać pakietu do pobrania.';
$PMF_LANG['verificationFailure'] = 'âťŚ Nie można zweryfikować pobranego pakietu.';
$PMF_LANG['extractFailure'] = 'âťŚ Nie można wyodrębnić pobranego pakietu.';
$PMF_LANG['currentVersion'] = 'Aktualna wersja: ';
$PMF_LANG['msgLastCheckDate'] = 'Ostatnia kontrola aktualizacji: ';
$PMF_LANG['msgLastVersionAvailable'] = 'Dostępna najnowsza wersja: ';
$PMF_LANG['msgReleaseEnvironment'] = 'Wydanie-Środowisko: ';

// added v4.0.0-alpha - 2023-07-19 by Jan
$PMF_LANG['msgAddBookmark'] = 'Dodaj zakładkę';
$PMF_LANG['removeBookmark'] = 'Usuń zakładkę';
$PMF_LANG['msgBookmarkAdded'] = 'Zakładka dodana pomyślnie!';
$PMF_LANG['msgBookmarkRemoved'] = 'Zakładka została pomyślnie usunięta!';
$PMF_LANG['msgBookmarks'] = 'Zakładki';
$PMF_LANG['msgMyBookmarks'] = 'Moje Zakładki';

// added v4.0.0-alpha - 2023-09-20 by Jan
$PMF_LANG['msgNoHashAllowed'] = "Używanie jest niedozwolone '#'.";

// added v4.0.0-alpha - 2023-12-24 by Jan
$LANG_CONF['main.botIgnoreList'] = ['area', 'Lista ignorowanych botów (oddzielonych przecinkami)'];

// added v4.0.0-alpha - 2023-12-26 by Thorsten
$PMF_LANG['msgGravatar'] = 'Obraz Gravatara';

// added v4.0.0-alpha - 2023-12-27 by Jan
$PMF_LANG['msgOrderStickyFaqsCustomDeactivated'] = 'Niestandardow kolejność przyklejonych wpisów jest wyłączona w głównej konfiguracji. Jeśli chcesz z niego skorzystać, aktywuj go w <a href="./?action=config">main configuration</a> -> rekordów.';
$LANG_CONF['records.orderStickyFaqsCustom'] = ['checkbox', 'Niestandardowa kolejność przyklejonych wpisów'];
$PMF_LANG['msgNoStickyFaqs'] = 'Nie masz jeszcze żadnych przyklejonych wpisów. Możesz oznaczyć rekordy jako przyklejone w pliku <a href="./?action=faqs-overview" class="alert-link">FAQ Przegląd</a>.';

// added v4.0.0-alpha - 2023-12-29 by Thorsten
$LANG_CONF['main.enableCookieConsent'] = ['checkbox', 'Aktywuj zgodę na pliki cookie'];
$PMF_LANG['msgSessionExpired'] = 'Twoja sesja wygasła. Proszę, zaloguj się ponownie.';

// added v4.0.0-alpha - 2024-01-12 by Jan
$PMF_LANG['msgLanguageCode'] = 'Kod języka (np. en, de ...)';
$PMF_LANG['msgSeperateWithCommas'] = '(oddziel przecinkami)';
$PMF_LANG['msgImportRecordsColumnStructure'] = 'Plik CSV do zaimportowania musi zawierać następujące kolumny w tej kolejności, bez nagłówków kolumn. Każdy wiersz ma na celu zdefiniowanie wpisu FAQ. Wszystkie komórki w kolumnach oznaczonych gwiazdką * muszą zawierać wartość.';
$PMF_LANG['msgImportRecords'] = 'FAQ Import';
$PMF_LANG['msgImportCSVFile'] = 'Zaimportuj plik CSV';
$PMF_LANG['msgImportCSVFileBody'] = 'Tutaj możesz zaimportować plik CSV z danymi rekordu i podaną strukturą (patrz wyżej).';
$PMF_LANG['msgImport'] = 'Import';
$PMF_LANG['msgColumnStructure'] = 'Struktura kolumnowa';
$PMF_LANG['msgImportSuccessful'] = 'Import powiódł się!';
$PMF_LANG['msgCSVImportTrueOrFalse'] = '(true lub false)';
$PMF_LANG['admin_mainmenu_imports_exports'] = 'Import & Eksport';
$PMF_LANG['msgCSVFileNotValidated'] = 'Wygląda na to, że plik nie ma odpowiedniej struktury. Proszę ponownie sprawdzić strukturę w oparciu o podane wymagania.';

// added v4.0.0-alpha - 2024-01-13 by Jan
$PMF_LANG['msgExportSessionsAsCSV'] = 'Eksportuj sesje jako plik CSV';
$PMF_LANG['msgExportSessions'] = 'Eksport Sesji';
$PMF_LANG['msgExportSessionsFrom'] = 'Z';
$PMF_LANG['msgExportSessionsTo'] = 'Do';

return $PMF_LANG;
