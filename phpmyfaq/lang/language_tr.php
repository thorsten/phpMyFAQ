<?php

/**
 * The Turkish language file - try to be the best of Turkish
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Can Kirca <cankirca@gmail.com>
 * @author Zafer Gürsoy <zafergursoy@yahoo.com>
 * @author Evren Yurtesen <yurtesen@ispro.net.tr>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2004-02-19
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
$PMF_LANG["metaLanguage"] = "tr";
$PMF_LANG["language"] = "Turkish";
$PMF_LANG["dir"] = "ltr";
$PMF_LANG["nplurals"] = "1";

// Navigation
$PMF_LANG["msgCategory"] = "Kategoriler";
$PMF_LANG["msgShowAllCategories"] = "Tüm kategoriler";
$PMF_LANG["msgSearch"] = "Ara";
$PMF_LANG["msgAddContent"] = "Yeni SSS ekle";
$PMF_LANG["msgQuestion"] = "Soru sor";
$PMF_LANG["msgOpenQuestions"] = "Aktif sorular";
$PMF_LANG["msgHelp"] = "Yardım";
$PMF_LANG["msgContact"] = "İletişim";
$PMF_LANG["msgHome"] = "Anasayfa";
$PMF_LANG["msgNews"] = "Duyurular";
$PMF_LANG["msgUserOnline"] = " Çevrimiçi kullanıcı";
$PMF_LANG["msgBack2Home"] = "Anasayfaya dön";

// Contentpages
$PMF_LANG["msgFullCategories"] = "Kategoriler";
$PMF_LANG["msgFullCategoriesIn"] = "Categories with FAQs in ";
$PMF_LANG["msgSubCategories"] = "Alt kategoriler";
$PMF_LANG["msgEntries"] = "SSSler";
$PMF_LANG["msgEntriesIn"] = "Questions in ";
$PMF_LANG["msgViews"] = "görüntüleme";
$PMF_LANG["msgPage"] = "Sayfa ";
$PMF_LANG["msgPages"] = " Sayfa";
$PMF_LANG["msgPrevious"] = "önceki";
$PMF_LANG["msgNext"] = "sonraki";
$PMF_LANG["msgCategoryUp"] = "bir kategori yukarı";
$PMF_LANG["msgLastUpdateArticle"] = "Son güncelleme: ";
$PMF_LANG["msgAuthor"] = "Oluşturan: ";
$PMF_LANG["msgPrinterFriendly"] = "yazıcı uyumlu sürüm";
$PMF_LANG["msgPrintArticle"] = "İçeriği yazdır";
$PMF_LANG["msgMakeXMLExport"] = "XML dosyası olarak dışa aktar";
$PMF_LANG["msgAverageVote"] = "Oy ortalaması";
$PMF_LANG["msgVoteUseability"] = "Bu içeriği oyla";
$PMF_LANG["msgVoteFrom"] = "out of";
$PMF_LANG["msgVoteBad"] = "gereksiz";
$PMF_LANG["msgVoteGood"] = "idare eder";
$PMF_LANG["msgVotings"] = "Oy ";
$PMF_LANG["msgVoteSubmit"] = "Oyla";
$PMF_LANG["msgVoteThanks"] = "Oyunuz için teşekkürler!";
$PMF_LANG["msgYouCan"] = "You can ";
$PMF_LANG["msgWriteComment"] = "comment this FAQ";
$PMF_LANG["msgShowCategory"] = "Content Overview: ";
$PMF_LANG["msgCommentBy"] = "Comment of ";
$PMF_LANG["msgCommentHeader"] = "Comment this FAQ";
$PMF_LANG["msgYourComment"] = "Yorumunuz";
$PMF_LANG["msgCommentThanks"] = "Yorumunuz için teşekkürler!";
$PMF_LANG["msgSeeXMLFile"] = "XML dosyasını aç";
$PMF_LANG["msgSend2Friend"] = "Arkadaşınıza önerin";
$PMF_LANG["msgS2FName"] = "Adınız";
$PMF_LANG["msgS2FEMail"] = "E-mail adresiniz";
$PMF_LANG["msgS2FFriends"] = "Arkadaşınız";
$PMF_LANG["msgS2FEMails"] = ". e-mail adresi";
$PMF_LANG["msgS2FText"] = "Gönderilecek metin";
$PMF_LANG["msgS2FText2"] = "İçeriği şu bağlantıda bulabilirsiniz";
$PMF_LANG["msgS2FMessage"] = "Eklemek istedikleriniz";
$PMF_LANG["msgS2FButton"] = "e-mail gönder";
$PMF_LANG["msgS2FThx"] = "Paylaştığınız için teşekkürler!";
$PMF_LANG["msgS2FMailSubject"] = "Öneren ";

// Search
$PMF_LANG["msgSearchWord"] = "Anahtar kelime";
$PMF_LANG["msgSearchFind"] = "Şu ifade için arama sonucu ";
$PMF_LANG["msgSearchAmount"] = " arama sonucu";
$PMF_LANG["msgSearchAmounts"] = " arama sonucu";
$PMF_LANG["msgSearchCategory"] = "Kategori: ";
$PMF_LANG["msgSearchContent"] = "Cevap: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "Yeni SSS önerisi";
$PMF_LANG["msgNewContentAddon"] = "Soru öneriniz yönetici onayından sonra yayınlanacaktır. Gerekli alanlar <strong>isim</strong>, <strong>email adresi</strong>, <strong>kategori</strong>, <strong>soru</strong> ve <strong>cevap</strong>. Lütfen anahtar sözcükleri virgül ile ayırın.";
$PMF_LANG["msgNewContentName"] = "Adınız";
$PMF_LANG["msgNewContentMail"] = "E-mail adresiniz";
$PMF_LANG["msgNewContentCategory"] = "Kategori";
$PMF_LANG["msgNewContentTheme"] = "Soru";
$PMF_LANG["msgNewContentArticle"] = "Cevap";
$PMF_LANG["msgNewContentKeywords"] = "Anahtar sözcükler";
$PMF_LANG["msgNewContentLink"] = "Bu soruya ait link";
$PMF_LANG["msgNewContentSubmit"] = "gönder";
$PMF_LANG["msgInfo"] = "Detaylı bilgi: ";
$PMF_LANG["msgNewContentThanks"] = "Katkınız için teşekkürler!";
$PMF_LANG["msgNoQuestionsAvailable"] = "Şu an bekleyen soru bulunmuyor.";

// ask Question
$PMF_LANG["msgNewQuestion"] = "Lütfen aşağıda sorunuzu belirtin";
$PMF_LANG["msgAskCategory"] = "Kategori";
$PMF_LANG["msgAskYourQuestion"] = "ne sormak istersiniz?";
$PMF_LANG["msgAskThx4Mail"] = "Soru için teşekkürler!";
$PMF_LANG["msgDate_User"] = "Tarih / Kullanıcı";
$PMF_LANG["msgQuestion2"] = "Soru";
$PMF_LANG["msg2answer"] = "Cevap";
$PMF_LANG["msgQuestionText"] = "Bu sayfada, diğer kullanıcılar tarafından sorulan soruları görüntüleyebilirsiniz. Dilerseniz, cevabını bildiğiniz soruları yanıtlayarak bilgi bankamıza katkı sağlayabilirsiniz.";

// Contact
$PMF_LANG["msgContactEMail"] = "Site İletişim Formu";
$PMF_LANG["msgMessage"] = "Mesajınız";

// Startseite
$PMF_LANG["msgTopTen"] = "En popüler sorular";
$PMF_LANG["msgHomeThereAre"] = "Toplam ";
$PMF_LANG["msgHomeArticlesOnline"] = " soru bulunuyor";
$PMF_LANG["msgNoNews"] = "Yeni duyuru eklenmemiş.";
$PMF_LANG["msgLatestArticles"] = "En yeni sorular";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "Katkınız için teşekkürler";
$PMF_LANG["msgMailCheck"] = "Yeni bir içerik mevcut! Lütfen yönetici alanını kontrol edin!";
$PMF_LANG["msgMailContact"] = "Mesajınız yöneticiye gönderildi.";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "Veritabanı bağlantısı başarısız.";
$PMF_LANG["err_noHeaders"] = "Kategori bulunamadı.";
$PMF_LANG["err_noArticles"] = "Soru bulunamadı.";
$PMF_LANG["err_badID"] = "Geçersiz ID.";
$PMF_LANG["err_noTopTen"] = "Henüz popüler soru bulunmuyor.";
$PMF_LANG["err_nothingFound"] = "Kayıt bulunamadı.";
$PMF_LANG["err_SaveEntries"] = "Doldurulması zorunlu alanlar <strong>adınız</strong>, <strong>e-mail adresiniz</strong>, <strong>kategori</strong>, <strong>soru</strong>, <strong>cevap</strong> ve, eğer isteniyorsa <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Wikipediada Captcha hakkında daha fazlasını oku\" target=\"_blank\">Captcha</a> kodu</strong>!";
$PMF_LANG["err_SaveComment"] = "Doldurulması zorunlu alanlar <strong>adınız</strong>, <strong>e-mail adresiniz</strong>, <strong>yorumunuz</strong> ve, eğer isteniyorsa <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Wikipediada Captcha hakkında daha fazlasını oku\" target=\"_blank\">Captcha</a> kodu</strong>!";
$PMF_LANG["err_VoteTooMuch"] = "Çoklu oylar sayılmamaktadır.";
$PMF_LANG["err_noVote"] = "Soru oylanamadı!";
$PMF_LANG["err_noMailAdress"] = "E-mail adresiniz geçersiz.";
$PMF_LANG["err_sendMail"] = "Doldurulması zorunlu alanlar <strong>adınız</strong>, <strong>e-mail adresiniz</strong>, <strong>sorunuz</strong> ve, eğer isteniyorsa <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Wikipediada Captcha hakkında daha fazlasını oku\" target=\"_blank\">Captcha</a> kodu</strong>!";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<strong>Arama ipuçları:</strong><br />Şunun gibi kelimeler <strong style=\"color: Red;\">kelime1 kelime2</strong> you can do a relevance descending search for two or more search criterion.<strong>Önemli:</strong> aranacak sözcük en az 4 karakter uzunluğunda olmalıdır.";

// Menu
$PMF_LANG["ad"] = "Yönetim";
$PMF_LANG["ad_menu_user_administration"] = "Kullanıcılar";
$PMF_LANG["ad_menu_entry_aprove"] = "Soru onayla";
$PMF_LANG["ad_menu_entry_edit"] = "Soru düzenle";
$PMF_LANG["ad_menu_categ_add"] = "Kategori ekle";
$PMF_LANG["ad_menu_categ_edit"] = "Kategori düzenle";
$PMF_LANG["ad_menu_news_add"] = "Duyuru ekle";
$PMF_LANG["ad_menu_news_edit"] = "Duyurular";
$PMF_LANG["ad_menu_open"] = "Yanıtsız sorular";
$PMF_LANG["ad_menu_stat"] = "İstatistikler";
$PMF_LANG["ad_menu_cookie"] = "Çerezleri ayarla";
$PMF_LANG["ad_menu_session"] = "Oturumları görüntüle";
$PMF_LANG["ad_menu_adminlog"] = "Yönetici günlüğü";
$PMF_LANG["ad_menu_passwd"] = "Parola değiştir";
$PMF_LANG["ad_menu_logout"] = "Çıkış";
$PMF_LANG["ad_menu_startpage"] = "Giriş sayfası";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "Lütfen kendinizi tanıtın.";
$PMF_LANG["ad_msg_passmatch"] = "Girilen parolalar <strong>aynı</strong> olmalıdır!";
$PMF_LANG["ad_msg_savedsuc_1"] = "Profil sahibi";
$PMF_LANG["ad_msg_savedsuc_2"] = "için yapılan değişiklikler kaydedildi.";
$PMF_LANG["ad_msg_mysqlerr"] = "Oluşan bir <strong>database</strong> hatası sebebiyle değişiklikler kaydedilemedi.";
$PMF_LANG["ad_msg_noauth"] = "Yetkiniz bulunmuyor.";

// Allgemein
$PMF_LANG["ad_gen_page"] = "Sayfa";
$PMF_LANG["ad_gen_of"] = "-";
$PMF_LANG["ad_gen_lastpage"] = "Önceki sayfa";
$PMF_LANG["ad_gen_nextpage"] = "Sonraki sayfa";
$PMF_LANG["ad_gen_save"] = "Kaydet";
$PMF_LANG["ad_gen_reset"] = "Sıfırla";
$PMF_LANG["ad_gen_yes"] = "Evet";
$PMF_LANG["ad_gen_no"] = "Hayır";
$PMF_LANG["ad_gen_top"] = "Sayfa başı";
$PMF_LANG["ad_gen_ncf"] = "Kategori bulunamadı!";
$PMF_LANG["ad_gen_delete"] = "Sil";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "Kullanıcı yönetimi";
$PMF_LANG["ad_user_username"] = "Kayıtlı kullanıcılar";
$PMF_LANG["ad_user_rights"] = "Kullanıcı yetkileri";
$PMF_LANG["ad_user_edit"] = "düzenle";
$PMF_LANG["ad_user_delete"] = "sil";
$PMF_LANG["ad_user_add"] = "Kullanıcı ekle";
$PMF_LANG["ad_user_profou"] = "Kullanıcı profili";
$PMF_LANG["ad_user_name"] = "İsim";
$PMF_LANG["ad_user_password"] = "Şifre";
$PMF_LANG["ad_user_confirm"] = "Doğrula";
$PMF_LANG["ad_user_rights"] = "Yetkiler";
$PMF_LANG["ad_user_del_1"] = "Kullanıcı";
$PMF_LANG["ad_user_del_2"] = "silinecek?";
$PMF_LANG["ad_user_del_3"] = "Emin misiniz?";
$PMF_LANG["ad_user_deleted"] = "Kullanıcı başarıyla silindi.";
$PMF_LANG["ad_user_checkall"] = "Tümünü seç";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "Soru yönetimi";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "Konu";
$PMF_LANG["ad_entry_action"] = "Eylem";
$PMF_LANG["ad_entry_edit_1"] = "Düzenle";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "Soru";
$PMF_LANG["ad_entry_content"] = "Cevap";
$PMF_LANG["ad_entry_keywords"] = "Anahtar sözcükler";
$PMF_LANG["ad_entry_author"] = "Oluşturan";
$PMF_LANG["ad_entry_category"] = "Kategori";
$PMF_LANG["ad_entry_active"] = "Görünür";
$PMF_LANG["ad_entry_date"] = "Tarih";
$PMF_LANG["ad_entry_changed"] = "Değişiklik?";
$PMF_LANG["ad_entry_changelog"] = "Değişiklik günlüğü";
$PMF_LANG["ad_entry_commentby"] = "Yorumlayan";
$PMF_LANG["ad_entry_comment"] = "Yorum";
$PMF_LANG["ad_entry_save"] = "kaydet";
$PMF_LANG["ad_entry_delete"] = "Sil";
$PMF_LANG["ad_entry_delcom_1"] = "Şu kullanıcıya ait yorum silinecek";
$PMF_LANG["ad_entry_delcom_2"] = "onaylıyor musunuz??";
$PMF_LANG["ad_entry_commentdelsuc"] = "Yorum <strong>başarıyla</strong> silindi.";
$PMF_LANG["ad_entry_back"] = "İçeriğe geri dön";
$PMF_LANG["ad_entry_commentdelfail"] = "Yorum <strong>başarıyla</strong> silinemedi.";
$PMF_LANG["ad_entry_savedsuc"] = "Değişiklikler <strong>başarıyla</strong> kaydedildi.";
$PMF_LANG["ad_entry_savedfail"] = "Maalesef, bir <strong>database</strong> hatası oluştu.";
$PMF_LANG["ad_entry_del_1"] = "Are you sure that the topic";
$PMF_LANG["ad_entry_del_2"] = "of";
$PMF_LANG["ad_entry_del_3"] = "should be deleted?";
$PMF_LANG["ad_entry_delsuc"] = "Issue <strong>successfully</strong> deleted.";
$PMF_LANG["ad_entry_delfail"] = "Issue was <strong>not deleted</strong>!";
$PMF_LANG["ad_entry_back"] = "Geri";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "Article header";
$PMF_LANG["ad_news_text"] = "Text of the Record";
$PMF_LANG["ad_news_link_url"] = "Bağlantı";
$PMF_LANG["ad_news_link_title"] = "Bağlantı başlığı";
$PMF_LANG["ad_news_link_target"] = "Bağlantı hedefi";
$PMF_LANG["ad_news_link_window"] = "Bağlantı yeni pencerede açılır";
$PMF_LANG["ad_news_link_faq"] = "Bağlantı soru içerisinde";
$PMF_LANG["ad_news_add"] = "Duyuru girdisi ekle";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "Başlık";
$PMF_LANG["ad_news_date"] = "Tarih";
$PMF_LANG["ad_news_action"] = "Eylem";
$PMF_LANG["ad_news_update"] = "güncelle";
$PMF_LANG["ad_news_delete"] = "sil";
$PMF_LANG["ad_news_nodata"] = "Veritabanında kayıt bulunamadı";
$PMF_LANG["ad_news_updatesuc"] = "Duyuru başarıyla güncellendi.";
$PMF_LANG["ad_news_del"] = "Bu duyuru öğesini silmek istediğinizden emin misiniz??";
$PMF_LANG["ad_news_yesdelete"] = "evet, sil!";
$PMF_LANG["ad_news_nodelete"] = "hayır";
$PMF_LANG["ad_news_delsuc"] = "Duyuru öğesi başarıyla silindi.";
$PMF_LANG["ad_news_updatenews"] = "Duyuru öğesi başarıyla güncellendi.";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "Yeni kategori ekle";
$PMF_LANG["ad_categ_catnum"] = "Kategori no";
$PMF_LANG["ad_categ_subcatnum"] = "Alt kategori no";
$PMF_LANG["ad_categ_nya"] = "<em>henüz geçerli değil!</em>";
$PMF_LANG["ad_categ_titel"] = "Kategori başlığı";
$PMF_LANG["ad_categ_add"] = "Kategori ekle";
$PMF_LANG["ad_categ_existing"] = "Mevcut kategoriler";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "Kategori";
$PMF_LANG["ad_categ_subcateg"] = "Alt kategori";
$PMF_LANG["ad_categ_titel"] = "Kategori başlığı";
$PMF_LANG["ad_categ_action"] = "Eylem";
$PMF_LANG["ad_categ_update"] = "güncelle";
$PMF_LANG["ad_categ_delete"] = "sil";
$PMF_LANG["ad_categ_updatecateg"] = "Kategori Güncelle";
$PMF_LANG["ad_categ_nodata"] = "Veritabanında kayıt bulunamadı";
$PMF_LANG["ad_categ_remark"] = "Lütfen dikkat, bir kategoriyi sildiğinizde, kategoriye ait içerikler başka bir kategoriyle eşleştirilinceye kadar görüntülenemezler.";
$PMF_LANG["ad_categ_edit_1"] = "düzenle";
$PMF_LANG["ad_categ_edit_2"] = "Kategori";
$PMF_LANG["ad_categ_added"] = "Kategori eklendi.";
$PMF_LANG["ad_categ_updated"] = "Kategori güncellendi.";
$PMF_LANG["ad_categ_del_yes"] = "evet, sil!";
$PMF_LANG["ad_categ_del_no"] = "hayır!";
$PMF_LANG["ad_categ_deletesure"] = "Bu kategoriyi silmek istediğinizden emin misiniz??";
$PMF_LANG["ad_categ_deleted"] = "Kategori silindi.";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "Çerez başarıyla <strong>ayarlandı.</strong>";
$PMF_LANG["ad_cookie_already"] = "Çerez daha önceden ayarlanmış. Yapabileceğiniz işlemler şunlar";
$PMF_LANG["ad_cookie_again"] = "Çerezi yeniden ayarla";
$PMF_LANG["ad_cookie_delete"] = "Çerez kaydını sil";
$PMF_LANG["ad_cookie_no"] = "Henüz bir çerez ayarlanmamış. Bir çerez ayarlayarak, giriş bilgilerinizin otomatik hatırlanmasını sağlayabilirsiniz. Gerçekleştirebileceğiniz işlemler";
$PMF_LANG["ad_cookie_set"] = "Çerez ayarla";
$PMF_LANG["ad_cookie_deleted"] = "Çerez başarıyla silindi.";

// Adminlog
$PMF_LANG["ad_adminlog"] = "Yönetici Günlüğü";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "Şifre değiştir";
$PMF_LANG["ad_passwd_old"] = "Eski şifre";
$PMF_LANG["ad_passwd_new"] = "Yeni şifre";
$PMF_LANG["ad_passwd_con"] = "Yeni şifre tekrar";
$PMF_LANG["ad_passwd_change"] = "Şifreyi değiştir";
$PMF_LANG["ad_passwd_suc"] = "Şifre değiştirildi.";
$PMF_LANG["ad_passwd_remark"] = "<strong>DİKKAT:</strong><br />çerez yeniden ayarlanmalıdır!";
$PMF_LANG["ad_passwd_fail"] = "Eski şifre <strong>doğru</strong> girilmeli ve yeni şifreler <strong>eşleşmelidir</strong>.";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "Yeni kullanıcı hesabı ekle";
$PMF_LANG["ad_adus_name"] = "Kullanıcı adı";
$PMF_LANG["ad_adus_password"] = "Şifre";
$PMF_LANG["ad_adus_add"] = "Kullanıcı ekle";
$PMF_LANG["ad_adus_suc"] = "Kullanıcı <strong>başarıyla</strong> eklendi.";
$PMF_LANG["ad_adus_edit"] = "Profili düzenle";
$PMF_LANG["ad_adus_dberr"] = "<strong>veritabanı hatası!</strong>";
$PMF_LANG["ad_adus_exerr"] = "Girilen kullanıcı adı sistemde <strong>kayıtlıdır</strong>.";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "Oturum ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "Zaman";
$PMF_LANG["ad_sess_pageviews"] = "Sayfa görüntüleme";
$PMF_LANG["ad_sess_search"] = "Arama";
$PMF_LANG["ad_sess_sfs"] = "Oturumlarda ara";
$PMF_LANG["ad_sess_s_ip"] = "IP";
$PMF_LANG["ad_sess_s_minct"] = "en az eylem";
$PMF_LANG["ad_sess_s_date"] = "Tarih";
$PMF_LANG["ad_sess_s_after"] = "sonra";
$PMF_LANG["ad_sess_s_before"] = "önce";
$PMF_LANG["ad_sess_s_search"] = "Ara";
$PMF_LANG["ad_sess_session"] = "Oturum";
$PMF_LANG["ad_sess_r"] = "Şunun için arama sonucu";
$PMF_LANG["ad_sess_referer"] = "Referer";
$PMF_LANG["ad_sess_browser"] = "Browser";
$PMF_LANG["ad_sess_ai_rubrik"] = "Kategori";
$PMF_LANG["ad_sess_ai_artikel"] = "Kayıt";
$PMF_LANG["ad_sess_ai_sb"] = "Arama terimi";
$PMF_LANG["ad_sess_ai_sid"] = "Oturum ID";
$PMF_LANG["ad_sess_back"] = "Geri";

// Statistik
$PMF_LANG["ad_rs"] = "Oylama istatistikleri";
$PMF_LANG["ad_rs_rating_1"] = "The ranking of";
$PMF_LANG["ad_rs_rating_2"] = "users shows";
$PMF_LANG["ad_rs_red"] = "Kırmızı";
$PMF_LANG["ad_rs_green"] = "Yeşil";
$PMF_LANG["ad_rs_altt"] = "20% değerinden daha düşük ortalama";
$PMF_LANG["ad_rs_ahtf"] = "%80 değerinden daha yüksek ortalama";
$PMF_LANG["ad_rs_no"] = "Değerlendirme bulunmuyor";

// Auth
$PMF_LANG["ad_auth_insert"] = "Lütfen kullanıcı adı ve şifrenizi girin.";
$PMF_LANG["ad_auth_user"] = "Kullanıcı adı";
$PMF_LANG["ad_auth_passwd"] = "Şifre";
$PMF_LANG["ad_auth_ok"] = "Tamam";
$PMF_LANG["ad_auth_reset"] = "Sıfırla";
$PMF_LANG["ad_auth_fail"] = "Geçersiz kullanıcı adı ya da şifre.";
$PMF_LANG["ad_auth_sess"] = "Oturum ID geçti.";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "Yapılandırmayı düzenle";
$PMF_LANG["ad_config_save"] = "Yapılandırmayı kaydet";
$PMF_LANG["ad_config_reset"] = "Sıfırla";
$PMF_LANG["ad_config_saved"] = "Yapılandırma seçenekleri başarıyla kaydedildi.";
$PMF_LANG["ad_menu_editconfig"] = "Yapılandırmayı düzenle";
$PMF_LANG["ad_att_none"] = "Eklenti mevcut değil";
$PMF_LANG["ad_att_att"] = "Eklentiler";
$PMF_LANG["ad_att_add"] = "Yeni eklenti ekle";
$PMF_LANG["ad_entryins_suc"] = "Kayıt başarıyla eklendi.";
$PMF_LANG["ad_entryins_fail"] = "Bir hata oluştu.";
$PMF_LANG["ad_att_del"] = "Sil";
$PMF_LANG["ad_att_nope"] = "Eklentiler yalnızca düzenleme sırasında eklenebilir.";
$PMF_LANG["ad_att_delsuc"] = "Seçilen eklenti başarıyla silindi.";
$PMF_LANG["ad_att_delfail"] = "Eklenti silinirken bir hata oluştu.";
$PMF_LANG["ad_entry_add"] = "Yeni soru ekle";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "Yedekleme fonksiyonu ile, veritabanınızın tam bir kopyasını oluşturabilirsiniz. Oluşturulan yedek dosyasının formatı SQL olduğundan, phpMyAdmin ya da SQL komut satırı istemcisiyle geri yüklenebilir. Yedekleme işleminin ayda en az bir kez yapılması önerilir.";
$PMF_LANG["ad_csv_link"] = "Yedeği indir";
$PMF_LANG["ad_csv_head"] = "Yedek oluştur";
$PMF_LANG["ad_att_addto"] = "Bir eklenti ekle";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "Dosya";
$PMF_LANG["ad_att_butt"] = "Tamam";
$PMF_LANG["ad_att_suc"] = "Dosya başarıyla eklendi.";
$PMF_LANG["ad_att_fail"] = "Dosya eklenirken bir hata oluştu.";
$PMF_LANG["ad_att_close"] = "Pencereyi kapat";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "Bu form aracılığıyla, sistem üzerinden daha önceden alınan bir yedeği geri yükleyebilirsiniz. Önemli hatırlatma! geri yükleme sırasında önceki verilerin üzerine yazılacaktır.";
$PMF_LANG["ad_csv_file"] = "Dosya";
$PMF_LANG["ad_csv_ok"] = "Tamam";
$PMF_LANG["ad_csv_linklog"] = "yedekleme günlüğü";
$PMF_LANG["ad_csv_linkdat"] = "Yedekleme";
$PMF_LANG["ad_csv_head2"] = "Geri yükle";
$PMF_LANG["ad_csv_no"] = "Geçerli bir veritabanı yedeği değil.";
$PMF_LANG["ad_csv_prepare"] = "Veritabanı sorguları hazırlanıyor...";
$PMF_LANG["ad_csv_process"] = "Sorgular çalıştırılıyor...";
$PMF_LANG["ad_csv_of"] = "of";
$PMF_LANG["ad_csv_suc"] = "başarılı.";
$PMF_LANG["ad_csv_backup"] = "Yedekleme";
$PMF_LANG["ad_csv_rest"] = "Yedeği geri yükle";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "Yedekleme";
$PMF_LANG["ad_logout"] = "Oturum başarıyla sonlandırıldı.";
$PMF_LANG["ad_news_add"] = "Duyuru ekle";
$PMF_LANG["ad_news_edit"] = "Duyuru düzenle";
$PMF_LANG["ad_cookie"] = "Çerezler";
$PMF_LANG["ad_sess_head"] = "Oturumları görüntüle";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "Soru Kategorileri";
$PMF_LANG["ad_menu_stat"] = "Oylama İstatistikleri";
$PMF_LANG["ad_kateg_add"] = "Üst seviye kategori ekle";
$PMF_LANG["ad_kateg_rename"] = "Düzenle";
$PMF_LANG["ad_adminlog_date"] = "Tarih";
$PMF_LANG["ad_adminlog_user"] = "Kullanıcı";
$PMF_LANG["ad_adminlog_ip"] = "IP-Adresi";

$PMF_LANG["ad_stat_sess"] = "Oturum";
$PMF_LANG["ad_stat_days"] = "Gün";
$PMF_LANG["ad_stat_vis"] = "Oturum (Ziyaret)";
$PMF_LANG["ad_stat_vpd"] = "Günlük Ziyaret";
$PMF_LANG["ad_stat_fien"] = "İlk Günlük";
$PMF_LANG["ad_stat_laen"] = "Son Günlük";
$PMF_LANG["ad_stat_browse"] = "Oturumlara gözat";
$PMF_LANG["ad_stat_ok"] = "Tamam";

$PMF_LANG["ad_sess_time"] = "Zaman";
$PMF_LANG["ad_sess_sid"] = "ID";
$PMF_LANG["ad_sess_ip"] = "IP";

$PMF_LANG["ad_ques_take"] = "Soruyu yanıtla";
$PMF_LANG["no_cats"] = "Kategori bulunamadı.";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "Geçersiz kullanıcı ya da şifre.";
$PMF_LANG["ad_log_sess"] = "Oturum sona erdi.";
$PMF_LANG["ad_log_edit"] = "\"Edit User\"-Form for the following user: ";
$PMF_LANG["ad_log_crea"] = "\"New article\" form.";
$PMF_LANG["ad_log_crsa"] = "Yeni girdi oluşturuldu.";
$PMF_LANG["ad_log_ussa"] = "Şu kullanıcı için verileri güncelle: ";
$PMF_LANG["ad_log_usde"] = "Şu kullanıcı silindi: ";
$PMF_LANG["ad_log_beed"] = "Şu kullanıcı için düzenle: ";
$PMF_LANG["ad_log_bede"] = "Şu kayıt silindi: ";

$PMF_LANG["ad_start_visits"] = "Ziyaret";
$PMF_LANG["ad_start_articles"] = "İçerikler";
$PMF_LANG["ad_start_comments"] = "Yorumlar";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "yapıştır";
$PMF_LANG["ad_categ_cut"] = "kes";
$PMF_LANG["ad_categ_copy"] = "kopyala";
$PMF_LANG["ad_categ_process"] = "Kategoriler işleniyor...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>Yetkiniz bulunmuyor.</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "Önceki sayfa";
$PMF_LANG["msgNextPage"] = "Sonraki sayfa";
$PMF_LANG["msgPageDoublePoint"] = "Sayfa: ";
$PMF_LANG["msgMainCategory"] = "Ana kategori";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "Şifreniz değiştirildi.";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["ad_xml_gen"] = "XML çıktısı oluştur";
$PMF_LANG["ad_entry_locale"] = "Dil";
$PMF_LANG["msgLanguageSubmit"] = "Dil değiştir";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_attach_4"] = "Eklenti en fazla %s Byte olmalıdır.";
$PMF_LANG["ad_menu_export"] = "Soruları dışa aktar";

$PMF_LANG["rightsLanguage"]['add_user'] = "Kullanıcı ekle";
$PMF_LANG["rightsLanguage"]['edit_user'] = "Kullanıcı düzenle";
$PMF_LANG["rightsLanguage"]['delete_user'] = "Kullanıcı sil";
$PMF_LANG["rightsLanguage"]['add_faq'] = "Kayıt ekle";
$PMF_LANG["rightsLanguage"]['edit_faq'] = "Kayıt düzenle";
$PMF_LANG["rightsLanguage"]['delete_faq'] = "Kayıt sil";
$PMF_LANG["rightsLanguage"]['viewlog'] = "Günlüğü görüntüle";
$PMF_LANG["rightsLanguage"]['adminlog'] = "Yönetici günlüğünü görüntüle";
$PMF_LANG["rightsLanguage"]['delcomment'] = "Yorumu silDelete comment";
$PMF_LANG["rightsLanguage"]['addnews'] = "Duyuru ekle";
$PMF_LANG["rightsLanguage"]['editnews'] = "Duyuru düzenle";
$PMF_LANG["rightsLanguage"]['delnews'] = "Duyuru sil";
$PMF_LANG["rightsLanguage"]['addcateg'] = "Kategori ekle";
$PMF_LANG["rightsLanguage"]['editcateg'] = "Kategori düzenle";
$PMF_LANG["rightsLanguage"]['delcateg'] = "Kategori sil";
$PMF_LANG["rightsLanguage"]['passwd'] = "Şifre değiştir";
$PMF_LANG["rightsLanguage"]['editconfig'] = "Yapılandırmayı düzenle";
$PMF_LANG["rightsLanguage"]['addatt'] = "Eklenti ekle";
$PMF_LANG["rightsLanguage"]['delatt'] = "Eklenti sil";
$PMF_LANG["rightsLanguage"]['backup'] = "Yedek oluştur";
$PMF_LANG["rightsLanguage"]['restore'] = "Yedeği geri yükle";
$PMF_LANG["rightsLanguage"]['delquestion'] = "Yanıtsız soruları sil";
$PMF_LANG["rightsLanguage"]['changebtrevs'] = "Sürümleri düzenle";

$PMF_LANG["msgAttachedFiles"] = "Ekli dosyalar";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "Eylem";
$PMF_LANG["ad_entry_email"] = "Email";
$PMF_LANG["ad_entry_allowComments"] = "Yorumlara izin ver";
$PMF_LANG["msgWriteNoComment"] = "Bu içerik için yorum yapamazsınız";
$PMF_LANG["ad_user_realname"] = "Gerçek adınız";
$PMF_LANG["ad_export_generate_pdf"] = "PDF oluştur";
$PMF_LANG["ad_export_full_faq"] = "soruların PDf çıktısı: ";
$PMF_LANG["err_bannedIP"] = "IP adresiniz yasaklandı.";
$PMF_LANG["err_SaveQuestion"] = "Doldurulması zorunlu alanlar <strong>adınız</strong>, <strong>e-mail adresiniz</strong>, <strong>sorunuz</strong> ve, eğer isteniyorsa <strong><a href=\"http://en.wikipedia.org/wiki/Captcha\" title=\"Wikipediada Captcha hakkında daha fazlasını oku\" target=\"_blank\">Captcha</a> kodu</strong>!";

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
$LANG_CONF["main.enableCategoryRestrictions"] = array(0 => "checkbox", 1 => "Enable category restrictions");
$LANG_CONF["security.ipCheck"] = array(0 => "checkbox", 1 => "Check the IP in administration");
$LANG_CONF["records.numberOfRecordsPerPage"] = array(0 => "input", 1 => "Number of displayed topics per page");
$LANG_CONF["records.numberOfShownNewsEntries"] = array(0 => "input", 1 => "Number of news articles");
$LANG_CONF['security.bannedIPs'] = array(0 => "area", 1 => "Ban these IPs");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "Enable URL rewrite support? (default: disabled)");
$LANG_CONF["ldap.ldapSupport"] = array(0 => "checkbox", 1 => "Enable LDAP support? (default: disabled)");
$LANG_CONF["main.referenceURL"] = array(0 => "input", 1 => "URL of your FAQ (e.g.: http://www.example.org/faq/)");
$LANG_CONF["main.urlValidateInterval"] = array(0 => "input", 1 => "Interval between AJAX link verification (in seconds)");
$LANG_CONF["records.enableVisibilityQuestions"] = array(0 => "checkbox", 1 => "Disable visibility of new questions?");
$LANG_CONF['security.permLevel'] = array(0 => "select", 1 => "Permission level");

$PMF_LANG["ad_categ_new_main_cat"] = "ana kategori olarak";
$PMF_LANG["ad_categ_paste_error"] = "Bu kategori taşınamaz.";
$PMF_LANG["ad_categ_move"] = "Kategori taşı";
$PMF_LANG["ad_categ_lang"] = "Dil";
$PMF_LANG["ad_categ_desc"] = "Açıklama";
$PMF_LANG["ad_categ_change"] = "Şununla değiştir";

$PMF_LANG["lostPassword"] = "Şifrenizi mi unuttunuz?";
$PMF_LANG["lostpwd_err_1"] = "Hata: kullanıcı adı ve email adresi bulunamadı.";
$PMF_LANG["lostpwd_err_2"] = "Hata: geçersiz girdi!";
$PMF_LANG["lostpwd_text_1"] = "Talebiniz alındı.";
$PMF_LANG["lostpwd_text_2"] = "Lütfen yönetici panelinden yeni bir kişisel şifre belirleyiniz.";
$PMF_LANG["lostpwd_mail_okay"] = "Email gönderildi.";

$PMF_LANG["ad_xmlrpc_button"] = "Sistem sürümünü kontrol etmek için tıklayın";
$PMF_LANG["ad_xmlrpc_latest"] = "Yeni sürüm kullanılabilir durumda";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = "Kategori dilini seç";

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = "Site haritası";

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = "Bu girdi görüntülenemez.";
$PMF_LANG['msgArticleCategories'] = "Bu girdiye ait kategoriler";

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = "Benzersiz çözüm ID";
$PMF_LANG['ad_entry_faq_record'] = "Soru kaydı";
$PMF_LANG['ad_entry_new_revision'] = "Yeni sürüm oluştur?";
$PMF_LANG['ad_entry_record_administration'] = "Kayıt yönetimi";
$PMF_LANG['ad_entry_changelog'] = "Değişiklik günlüğü";
$PMF_LANG['ad_entry_revision'] = "Sürüm";
$PMF_LANG['ad_changerev'] = "Sürüm seç";
$PMF_LANG['msgCaptcha'] = "Lütfen güvenlik kodunu girin";
$PMF_LANG['msgSelectCategories'] = "Şurada ara...";
$PMF_LANG['msgAllCategories'] = "... tüm kategoriler";
$PMF_LANG['ad_you_should_update'] = "Yazılım sürümü eski, lütfen PHPMyFAQ sürümünüzü güncelleyin.";
$PMF_LANG['msgAdvancedSearch'] = "Gelişmiş arama";

// added v1.6.1 - 2006-04-25 by Matteoï and Thorsten
$PMF_LANG['spamControlCenter'] = "Spam kontrol merkezi";
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "Print user email in a safe way.");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "Check public form content against banned words.");
$LANG_CONF["spam.enableCaptchaCode"] = array(0 => "checkbox", 1 => "Use a captcha code to allow public form submission.");
$PMF_LANG['ad_session_expiring'] = "Oturumunuz %d dakika içerisinde sona erecek. çalışmaya devam etmek istiyor musunuz?";

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = "Oturum yönetimi";
$PMF_LANG['ad_stat_choose'] = "Ay seçin";
$PMF_LANG['ad_stat_delete'] = "Seçilen oturumları hemen silmek istiyor musunuz?";

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = "Terimler sözlüğü";
$PMF_LANG['ad_glossary_add'] = "Yeni terim ekle";
$PMF_LANG['ad_glossary_edit'] = "Terim düzenle";
$PMF_LANG['ad_glossary_item'] = "Başlık";
$PMF_LANG['ad_glossary_definition'] = "Açıklama";
$PMF_LANG['ad_glossary_save'] = "Kaydet";
$PMF_LANG['ad_glossary_save_success'] = "Terim başarıyla eklendi!";
$PMF_LANG['ad_glossary_save_error'] = "Terim eklenirken bir hata oluştu.";
$PMF_LANG['ad_glossary_update_success'] = "Terim başarıyla güncellendi!";
$PMF_LANG['ad_glossary_update_error'] = "Terim güncellenirken bir hata oluştu.";
$PMF_LANG['ad_glossary_delete'] = "Terimi sil";
$PMF_LANG['ad_glossary_delete_success'] = "Terim başarıyla silindi!";
$PMF_LANG['ad_glossary_delete_error'] = "Terim silinirken bir hata oluştu.";
$PMF_LANG['ad_linkcheck_noReferenceURL'] = "Otomatik link doğrulama devredışı (temel doğrulama URL adresi tanımlanmamış)";
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = "Otomatik link doğrulama devredışı (PHP allow_url_fopen desteği etkin değil)";
$PMF_LANG['ad_linkcheck_checkResult'] = "Otomatik link doğrulama sonucu";
$PMF_LANG['ad_linkcheck_checkSuccess'] = "Başarılı";
$PMF_LANG['ad_linkcheck_checkFailed'] = "başarısız";
$PMF_LANG['ad_linkcheck_failReason'] = "Hata açıklaması";
$PMF_LANG['ad_linkcheck_noLinksFound'] = "Link doğrulama aracıyla uyumlu bağlantı bulunamadı.";
$PMF_LANG['ad_linkcheck_searchbadonly'] = "Yalnızca kırık bağlantılarla";
$PMF_LANG['ad_linkcheck_infoReason'] = "Ekstra bilgi";
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = "<strong>%s</strong> test edilirken bulundu: ";
$PMF_LANG['ad_linkcheck_openurl_notready'] = "Link doğrulama aracı hazır değil.";
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = "Maksimum yönlendirme limiti <strong>%d</strong> aşıldı.";
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = "Boş bir adreste sonuçlanıyor.";
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = "Host <strong>%s</strong> çok yavaş ya da yanıt vermiyor.";
$PMF_LANG['ad_linkcheck_openurl_nodns'] = "host <strong>%s</strong> DNS çözümlemesi çok yavaş ya da başarısız. Bunun sebebi yerel ya da uzak DNS yapılandırması olabilir. ";
$PMF_LANG['ad_linkcheck_openurl_redirected'] = "Bağlantı şu adrese yönlendirilmiş <strong>%s</strong>.";
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = "Geçerçsiz HTTP durumu <strong>%s</strong> saptandı.";
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = "The <em>HEAD</em> metodu  host <strong>%s</strong> tarafından desteklenmiyor, desteklenen metodlar: <strong>%s</strong>.";
$PMF_LANG['ad_linkcheck_openurl_not_found'] = "Host üzerindeki <strong>%s</strong> üzerindeki kaynağa erişilemedi.";
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = "%s protokolü otomatik link doğrulama aracı tarafından desteklenmiyor.";
$PMF_LANG['msgNewQuestionVisible'] = "Soru yayınlanmadan önce editör onayından geçmelidir.";
$PMF_LANG['msgQuestionsWaiting'] = "Yönetici tarafından yayına alınması beklenen: ";
$PMF_LANG['ad_entry_visibility'] = "Yayında?";

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] = "Please enter a password. ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] = "Passwords do not match. ";
$PMF_LANG['ad_user_error_loginInvalid'] = "The specified user name is invalid.";
$PMF_LANG['ad_user_error_noEmail'] = "Please enter a valid mail address. ";
$PMF_LANG['ad_user_error_noRealName'] = "Please enter your real name. ";
$PMF_LANG['ad_user_error_delete'] = "User account could not be deleted. ";
$PMF_LANG['ad_user_error_noId'] = "No ID specified. ";
$PMF_LANG['ad_user_error_protectedAccount'] = "User account is protected. ";
$PMF_LANG['ad_user_deleteUser'] = "Kullanıcı Sil";
$PMF_LANG['ad_user_status'] = "Durum";
$PMF_LANG['ad_user_lastModified'] = "Son değişiklik";
$PMF_LANG['ad_gen_cancel'] = "İptal";
$PMF_LANG["rightsLanguage"]['addglossary'] = "Sözlük girdisi ekle";
$PMF_LANG["rightsLanguage"]['editglossary'] = "Sözlük girdisi düzenle";
$PMF_LANG["rightsLanguage"]['delglossary'] = "Sözlük girdisi sil";
$PMF_LANG["ad_menu_group_administration"] = "Gruplar";
$PMF_LANG['ad_user_loggedin'] = "Giriş yapan kullanıcı ";
$PMF_LANG['ad_group_details'] = "Grup Ayrıntıları";
$PMF_LANG['ad_group_add'] = "Grup Ekle";
$PMF_LANG['ad_group_add_link'] = "Grup Ekle";
$PMF_LANG['ad_group_name'] = "Grup adı";
$PMF_LANG['ad_group_description'] = "Grup açıklaması";
$PMF_LANG['ad_group_autoJoin'] = "Otomatik-katılım";
$PMF_LANG['ad_group_suc'] = "Grup <strong>başarıyla</strong> eklendi.";
$PMF_LANG['ad_group_error_noName'] = "Lütfen grup adı girin. ";
$PMF_LANG['ad_group_error_delete'] = "Grup silinemedi. ";
$PMF_LANG['ad_group_deleted'] = "Grup başarıyla silindi";
$PMF_LANG['ad_group_deleteGroup'] = "Grubu sil";
$PMF_LANG['ad_group_deleteQuestion'] = "Bu grubu silmek istediğinizden emin misiniz?";
$PMF_LANG['ad_user_uncheckall'] = "Tümünü Seç";
$PMF_LANG['ad_group_membership'] = "Grup Üyeliği";
$PMF_LANG['ad_group_members'] = "Üyeler";
$PMF_LANG['ad_group_addMember'] = "ekle";
$PMF_LANG['ad_group_removeMember'] = "çıkar";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = "Dışa aktarılacak veri sayısını sınırla (isteğe bağlı)";
$PMF_LANG['ad_export_cat_downwards'] = "Alt kategoriler de eklensin mi?";
$PMF_LANG['ad_export_type'] = "Dışa aktarma biçimi";
$PMF_LANG['ad_export_type_choose'] = "Desteklenen biçimler";
$PMF_LANG['ad_export_download_view'] = "İndir veya görüntüle?";
$PMF_LANG['ad_export_download'] = "İndir";
$PMF_LANG['ad_export_view'] = "Online görüntüle";
$PMF_LANG['ad_export_gen_xhtml'] = "XHTML dosyası oluştur";

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = "Duyurular";
$PMF_LANG['ad_news_author_name'] = "Yazan";
$PMF_LANG['ad_news_author_email'] = "Email";
$PMF_LANG['ad_news_set_active'] = "Aktifleştir";
$PMF_LANG['ad_news_allowComments'] = "Yorumlara izin ver";
$PMF_LANG['ad_news_expiration_window'] = "Duyuru geçerlilik süresi (isteğe bağlı)";
$PMF_LANG['ad_news_from'] = "Şu tarihten";
$PMF_LANG['ad_news_to'] = "Şu tarihe";
$PMF_LANG['ad_news_insertfail'] = "Duyuru eklenirken bir hata oluştu.";
$PMF_LANG['ad_news_updatefail'] = "Duyuru güncellenirken bir hata oluştu.";
$PMF_LANG['newsShowCurrent'] = "Geçerli duyuruları görüntüle.";
$PMF_LANG['newsShowArchive'] = "Arşivlenen duyuruları görüntüle.";
$PMF_LANG['newsArchive'] = " Duyuru arşivi";
$PMF_LANG['newsWriteComment'] = "Bu girdiye ait yorumlar";
$PMF_LANG['newsCommentDate'] = "Eklenme: ";

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = "Kayıt geçerlilik zamanı (isteğe bağlı)";
$PMF_LANG['admin_mainmenu_home'] = "Anasayfa";
$PMF_LANG['admin_mainmenu_users'] = "Kullanıcılar";
$PMF_LANG['admin_mainmenu_content'] = "İçerik";
$PMF_LANG['admin_mainmenu_statistics'] = "İstatistikler";
$PMF_LANG['admin_mainmenu_exports'] = "Dışa aktar";
$PMF_LANG['admin_mainmenu_backup'] = "Yedekleme";
$PMF_LANG['admin_mainmenu_configuration'] = "Yapılandırma";
$PMF_LANG['admin_mainmenu_logout'] = "Çıkış";

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = "Kategori sahibi";
$PMF_LANG['adminSection'] = "Yönetim";
$PMF_LANG['err_expiredArticle'] = "Bu girdinin geçerlilik tarihi sona erdi ve görüntülenemez";
$PMF_LANG['err_expiredNews'] = "Bu duyurunun geçerlilik tarihi sona erdi ve görüntülenemez";
$PMF_LANG['err_inactiveNews'] = "Bu duyuru taslak halinde olduğundan görüntülenemez";
$PMF_LANG['msgSearchOnAllLanguages'] = "tüm dillerde ara";
$PMF_LANG['ad_entry_tags'] = "Etiketler";
$PMF_LANG['msg_tags'] = "Etiketler";

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = "Kontrol ediliyor...";
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = "Kontrol ediliyor.";
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = "Kontrol ediliyor...";
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = "Kontrol ediliyor...";
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = "Devredışı";
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = "Links KO";
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = "Links OK";
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = "Erişim yok";
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = "AJAX yok";
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = "Link yok";
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = "Script yok";

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = "İlişkili gönderiler";
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "Number of related entries");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = "Çevir";
$PMF_LANG['ad_categ_trans_2'] = "Kategori";
$PMF_LANG['ad_categ_translatecateg'] = "Kategoriyi çevir";
$PMF_LANG['ad_categ_translate'] = "Çevir";
$PMF_LANG['ad_categ_transalready'] = "Şuna çevrilmiş: ";
$PMF_LANG["ad_categ_deletealllang"] = "Tüm dillerden sil?";
$PMF_LANG["ad_categ_deletethislang"] = "Yalnızca bu dilden sil?";
$PMF_LANG["ad_categ_translated"] = "Kategori tercüme edildi.";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "Kategori özeti";
$PMF_LANG['ad_menu_categ_structure'] = "Kategori özeti ilgili dilleri de kapsar";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = "Kullanıcı izinleri";
$PMF_LANG['ad_entry_grouppermission'] = "Grup izinleri";
$PMF_LANG['ad_entry_all_users'] = "Tüm kullanıcılara izin ver";
$PMF_LANG['ad_entry_restricted_users'] = "Şu kullanıcıyla sınırlandır";
$PMF_LANG['ad_entry_all_groups'] = "Tüm gruplara izin ver";
$PMF_LANG['ad_entry_restricted_groups'] = "Şu grupla sınırlandır";
$PMF_LANG['ad_session_expiration'] = "Oturum sonlanma zamanı";
$PMF_LANG['ad_user_active'] = "Etkin";
$PMF_LANG['ad_user_blocked'] = "Yasaklı";
$PMF_LANG['ad_user_protected'] = "Korumalı";

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = "Bağlantı olarak eklemek istediğiniz içeriği seçin...";

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "Kendisinden sonra yapıştır";
$PMF_LANG["ad_categ_remark_move"] = "Yalnızca aynı seviyedeki iki kategori birbiriyle değiştirilebilir.";
$PMF_LANG["ad_categ_remark_overview"] = "Eğer dil ayarları doğru yapılandırıldıysa (ilk sütunda), kategori sıralaması doğru olarak gösterilir.";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = "%d Ziyaretçi ve %d Kayıtlı";
$PMF_LANG['ad_adminlog_del_older_30d'] = "30 günden eski günlük kayıtlarını sil";
$PMF_LANG['ad_adminlog_delete_success'] = "Eski günlük kayıtları başarıyla silindi.";
$PMF_LANG['ad_adminlog_delete_failure'] = "Günlük kayıtları silinirken bir hata oluştu.";

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = "arama modülü ekle";
$PMF_LANG['ad_quicklinks'] = "Hızlı linkller";
$PMF_LANG['ad_quick_category'] = "Yeni kategori ekle";
$PMF_LANG['ad_quick_record'] = "Yeni soru girdisi ekle";
$PMF_LANG['ad_quick_user'] = "Yeni kullanıcı ekle";
$PMF_LANG['ad_quick_group'] = "Yeni grup ekle";

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = "Çeviri öner";
$PMF_LANG['msgNewTranslationAddon'] = "Çeviri öneriniz kaydedilecek, ancak yönetici onayından sonra yayınlanacaktır. doldurulması zorunlu alanlar: <strong>isim</strong>, <strong>e-mail adresi</strong>, <strong>soru çevirisi</strong> ve <strong>cevap çevirisi</strong>. lütfen anahtar kelimeleri virgülle ayırın.";
$PMF_LANG['msgNewTransSourcePane'] = "Kaynak";
$PMF_LANG['msgNewTranslationPane'] = "Çeviri";
$PMF_LANG['msgNewTranslationName'] = "Adınız";
$PMF_LANG['msgNewTranslationMail'] = "E-mail adresiniz";
$PMF_LANG['msgNewTranslationKeywords'] = "Anahtar sözcükler";
$PMF_LANG['msgNewTranslationSubmit'] = "Öneride bulun";
$PMF_LANG['msgTranslate'] = "Çevir";
$PMF_LANG['msgTranslateSubmit'] = "Çeviriye başla...";
$PMF_LANG['msgNewTranslationThanks'] = "Çeviriye katkıda bulunduğunuz için teşekkürler!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG["rightsLanguage"]['addgroup'] = "Grup hesabı ekle";
$PMF_LANG["rightsLanguage"]['editgroup'] = "Grup hesaplarını düzenle";
$PMF_LANG["rightsLanguage"]['delgroup'] = "Grup hesaplarını sil";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = "Bağlantı yeni sekmede açılır";

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = "yorumlar";
$PMF_LANG['ad_comment_administration'] = "Yorum moderasyonu";
$PMF_LANG['ad_comment_faqs'] = "Soru yorumları";
$PMF_LANG['ad_comment_news'] = "Duyuru yorumları";
$PMF_LANG['msgPDF'] = "PDF versiyonu";
$PMF_LANG['ad_groups'] = "Gruplar";

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'Record sorting (according to property)');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'Record sorting (descending or ascending)');
$PMF_LANG['ad_conf_order_id'] = "ID<br>(varsayılan)";
$PMF_LANG['ad_conf_order_thema'] = "Başlık";
$PMF_LANG['ad_conf_order_visits'] = "Ziyaretçi sayısı";
$PMF_LANG['ad_conf_order_updated'] = "Tarih";
$PMF_LANG['ad_conf_order_author'] = "Yazar";
$PMF_LANG['ad_conf_desc'] = "eskiden yeniye";
$PMF_LANG['ad_conf_asc'] = "yeniden eskiye";
$PMF_LANG['mainControlCenter'] = "AnasayfaMain";
$PMF_LANG['recordsControlCenter'] = "Sorular";

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "Activate new records");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "Allow comments for new records<br>(default: disallowed)");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = "Bu kategorideki sorular";
$PMF_LANG['msgTagSearch'] = "Etiketli gönderiler";
$PMF_LANG['ad_pmf_info'] = "phpMyFAQ Bilgisi";
$PMF_LANG['ad_online_info'] = "Online versiyon kontrolü";
$PMF_LANG['ad_system_info'] = "System Bilgisi";

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = "Kayıt ol";
$PMF_LANG["ad_user_loginname"] = "Kullanıcı adı";
$PMF_LANG['errorRegistration'] = "Bu alan zorunludur!";
$PMF_LANG['submitRegister'] = "Kaydı tamamla";
$PMF_LANG['msgUserData'] = "Kullanıcı hesabı için gereken bilgiler";
$PMF_LANG['captchaError'] = "Lütfen güvenlik kodunu kontrol edin!";
$PMF_LANG['msgRegError'] = "Aşağıdaki hatalar oluştu, lütfen devam etmeden önce bu hataları düzeltin";
$PMF_LANG['successMessage'] = "Kullanıcı hesabınız başarıyla oluşturuldu. Kısa süre içerisinde giriş bilgilerinizi içeren bir e-posta alacaksınız!";
$PMF_LANG['msgRegThankYou'] = "Kayıt olduğunuz için teşekkürler!";
$PMF_LANG['emailRegSubject'] = "[%sitename%] Kullanıcı hesabınız hakkında";

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = "En çok aranan içerikler";
$LANG_CONF['main.enableWysiwygEditor'] = array(0 => "checkbox", 1 => "Enable bundled WYSIWYG editor");

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = "Arama İstatistikleri";
$PMF_LANG['ad_searchstats_search_term'] = "Sözcük";
$PMF_LANG['ad_searchstats_search_term_count'] = "Aranma";
$PMF_LANG['ad_searchstats_search_term_lang'] = "Dil";
$PMF_LANG['ad_searchstats_search_term_percentage'] = "Yüzde";

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = "Sabitlenmiş";
$PMF_LANG['ad_entry_sticky'] = "Sabitlenmiş";
$PMF_LANG['stickyRecordsHeader'] = "Sabitlenmiş içerikler";

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = "Yasaklı Kelimeler";
$PMF_LANG['ad_config_stopword_input'] = "Yeni kelime ekle";

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = "Hayır, aranılan cevap mevcut değil (e-posta gönderilecek)";
$PMF_LANG['msgSendMailIfNothingIsFound'] = "Aradığınız cevap arama sonuçlarında mevcut mu?";

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = "Lütfen tercüme için bir dil seçin";
$PMF_LANG['msgLangDirIsntWritable'] = "<strong>/lang</strong> dizini yazılabilir değil.";
$PMF_LANG['ad_menu_translations'] = "Arayüz Çevirisi";
$PMF_LANG['ad_start_notactive'] = "Etkinleştirme bekleniyor";

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = "Yeni çeviri ekle";
$PMF_LANG['msgTransToolLanguage'] = "Dil";
$PMF_LANG['msgTransToolActions'] = "Eylemler";
$PMF_LANG['msgTransToolWritable'] = "Yazılabilir";
$PMF_LANG['msgEdit'] = "Düzenle";
$PMF_LANG['msgDelete'] = "Sil";
$PMF_LANG['msgYes'] = "Evet";
$PMF_LANG['msgNo'] = "Hayır";
$PMF_LANG['msgTransToolSureDeleteFile'] = "Bu dil dosyasını silmek istediğinizden emin misiniz?";
$PMF_LANG['msgTransToolFileRemoved'] = "Dil dosyası başarıyla silindi.";
$PMF_LANG['msgTransToolErrorRemovingFile'] = "Dil dosyası silinemedi";
$PMF_LANG['msgVariable'] = "Değişken";
$PMF_LANG['msgCancel'] = "İptal";
$PMF_LANG['msgSave'] = "kaydet";
$PMF_LANG['msgSaving3Dots'] = "Kaydediliyor...";
$PMF_LANG['msgRemoving3Dots'] = "Siliniyor...";
$PMF_LANG['msgTransToolFileSaved'] = "Dil dosyası başarıyla kaydedildi.";
$PMF_LANG['msgTransToolErrorSavingFile'] = "Dil dosyası kaydedilirken hata oluştu";
$PMF_LANG['msgLanguage'] = "Dil";
$PMF_LANG['msgTransToolLanguageCharset'] = "Karakter kümesi";
$PMF_LANG['msgTransToolLanguageDir'] = "dil yönü";
$PMF_LANG['msgTransToolLanguageDesc'] = "Dil açıklaması";
$PMF_LANG['msgAuthor'] = "Çevirmen";
$PMF_LANG['msgTransToolAddAuthor'] = "Çevirmen ekle";
$PMF_LANG['msgTransToolCreateTranslation'] = "Çeviri oluştur";
$PMF_LANG['msgTransToolTransCreated'] = "Yeni çeviri başarıyla oluşturulduNew translation successfully created";
$PMF_LANG['msgTransToolCouldntCreateTrans'] = "Yeni çeviri oluşturulamadı";
$PMF_LANG['msgAdding3Dots'] = "Ekleniyor ...";
$PMF_LANG['msgTransToolSendToTeam'] = "phpMyFAQ takımına gönder";
$PMF_LANG['msgSending3Dots'] = "gönderiliyor ...";
$PMF_LANG['msgTransToolFileSent'] = "Dil dosyası phpMyFAQ takımına başarıyla gönderildi. Katkınız için teşekkür ederiz.";
$PMF_LANG['msgTransToolErrorSendingFile'] = "Dil dosyası gönderilirken hata oluştu";
$PMF_LANG['msgTransToolPercent'] = "Yüzde";

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['records.attachmentsPath'] = array(0 => "input", 1 => "Path where attachments will be saved.<br /><small>Relative path means a folder within web root</small>");

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "İndirmeye çalıştığınız dosya sunucuda mevcut değil";
$PMF_LANG['ad_sess_noentry'] = "Kayıt bulunamadı";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"][0] = "%d kullanıcı çevrimiçi";
$PMF_LANG["plmsgUserOnline"][1] = "%d kullanıcı çevrimiçi";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = array(0 => "select", 1 => "Template set to be used");

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = "Sil";
$PMF_LANG["msgTransToolLanguageNumberOfPlurals"] = "Çoğuul form sayısı";
$PMF_LANG['msgTransToolLanguageOnePlural'] = "Bu dilde yalnızca bir çoğul formu destekleniyor";
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = "%s dili için çoğul desteği devredışı, (nplurals belirlenmemiş)";

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG["plmsgHomeArticlesOnline"][0] = "Toplam %d içerik mevcut";
$PMF_LANG["plmsgHomeArticlesOnline"][1] = "Toplam %d içerik mevcut";
$PMF_LANG["plmsgViews"][0] = "%d görüntüleme";
$PMF_LANG["plmsgViews"][1] = "%d görüntüleme";

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = "%d Ziyaretçi";
$PMF_LANG['plmsgGuestOnline'][1] = "%d Ziyaretçi";
$PMF_LANG['plmsgRegisteredOnline'][0] = " ve %d Kayıtlı";
$PMF_LANG['plmsgRegisteredOnline'][1] = " ve %d Kayıtlı";
$PMF_LANG["plmsgSearchAmount"][0] = "%d arama sonucu";
$PMF_LANG["plmsgSearchAmount"][1] = "%d arama sonucu";
$PMF_LANG["plmsgPagesTotal"][0] = " %d Sayfa";
$PMF_LANG["plmsgPagesTotal"][1] = " %d Sayfa";
$PMF_LANG["plmsgVotes"][0] = "%d Oy";
$PMF_LANG["plmsgVotes"][1] = "%d Oy";
$PMF_LANG["plmsgEntries"][0] = "%d Soru";
$PMF_LANG["plmsgEntries"][1] = "%d Soru";

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG["rightsLanguage"]['addtranslation'] = "çeviri ekle";
$PMF_LANG["rightsLanguage"]['edittranslation'] = "çeviri düzenle";
$PMF_LANG["rightsLanguage"]['deltranslation'] = "çeviri sil";
$PMF_LANG["rightsLanguage"]['approverec'] = "kayıt onayla";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["records.enableAttachmentEncryption"] = array(0 => "checkbox", 1 => "Enable attachment encryption <br><small>Ignored when attachments is disabled</small>");
$LANG_CONF["records.defaultAttachmentEncKey"] = array(0 => "input", 1 => 'Default attachment encryption key <br><small>Ignored if attachment encryption is disabled</small><br><small><^font color="red">WARNING: Do not change this once set and enabled file encryption!!!</font></small>');
//$LANG_CONF["records.attachmentsStorageType"] = array(0 => "select", 1 => "Attachment storage type");
//$PMF_LANG['att_storage_type'][0] = 'Filesystem';
//$PMF_LANG['att_storage_type'][1] = 'Database';

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = "Yükselt";
$PMF_LANG['ad_you_shouldnt_update'] = "Şu an phpMyFAQ yazılımının son sürümünü kullanıyorsunuz. Yükseltme işlemi gerekli değil.";
$LANG_CONF['security.useSslForLogins'] = array(0 => 'checkbox', 1 => "Only allow logins over SSL connection?");
$PMF_LANG['msgSecureSwitch'] = "Giriş yapabilmek için güvenli moda geçin!";

// added 2.6.0-alpha - 2009-10-03 by Anatoliy Belsky
$PMF_LANG['msgTransToolNoteFileSaving'] = "Lütfen dikkat, kaydet butonuna basılıncaya kadar hiçbir dosyaya yazılmayacaktır";
$PMF_LANG['msgTransToolPageBufferRecorded'] = "Sayfa %d başarıyla önbelleğe alındı";
$PMF_LANG['msgTransToolErrorRecordingPageBuffer'] = "Sayfa %d önbelleğe alınırken hata oluştu";
$PMF_LANG['msgTransToolRecordingPageBuffer'] = "Sayfa %d önbelleğe alınıyor";

// added 2.6.0-alpha - 2009-11-02 by Anatoliy Belsky
$PMF_LANG['ad_record_active'] = "Etkin";

// added 2.6.0-alpha - 2009-11-01 by Anatoliy Belsky
$PMF_LANG['msgAttachmentInvalid'] = "Eklenti hatalı, lütfen yöneticiyle iletişim kurun";

// added 2.6.0-alpha - 2009-11-02 by max
$LANG_CONF['search.numberSearchTerms'] = array(0 => 'input', 1 => 'Number of listed search terms');
$LANG_CONF['records.orderingPopularFaqs'] = array(0 => "select", 1 => "Sorting of the top FAQ's");
$PMF_LANG['list_all_users'] = "Tüm kullanıcıları listele";

$PMF_LANG['records.orderingPopularFaqs.visits'] = "En çok görüntülenen içerikleri listele";
$PMF_LANG['records.orderingPopularFaqs.voting'] = "En çok oylanan içerikleri listele";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = "Lütfen kelimeleri virgülle ayırın";

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = "güncelle";
$PMF_LANG['msgKeepFaqDate'] = "sakla";
$PMF_LANG['msgEditFaqDat'] = "düzenle";
$LANG_CONF['main.optionalMailAddress'] = array(0 => 'checkbox', 1 => 'Mail address as mandatory field');

// added v2.6.99 - 2010-11-24 by Gustavo Solt
$LANG_CONF['search.relevance'] = array(0 => 'select', 1 => 'Sort by relevance');
$LANG_CONF["search.enableRelevance"] = array(0 => "checkbox", 1 => "Activate relevance support?");
$PMF_LANG['searchControlCenter'] = "Ara";
$PMF_LANG['search.relevance.thema-content-keywords'] = "Soru - Cevap - Anahtar sözcük";
$PMF_LANG['search.relevance.thema-keywords-content'] = "Soru - Anahtar sözcük - Cevap";
$PMF_LANG['search.relevance.content-thema-keywords'] = "Cevap - Soru - Anahtar sözcük";
$PMF_LANG['search.relevance.content-keywords-thema'] = "Cevap - Anahtar sözcük - Soru";
$PMF_LANG['search.relevance.keywords-content-thema'] = "Anahtar sözcük - Cevap - Soru";
$PMF_LANG['search.relevance.keywords-thema-content'] = "Anahtar sözcük - Soru - Cevap";

// added 2.7.0-alpha - 2010-09-13 by Thorsten
$PMF_LANG['msgLoginUser'] = "Giriş";
$PMF_LANG['socialNetworksControlCenter'] = "Sosyal ağlar";
$LANG_CONF['socialnetworks.enableTwitterSupport'] = array(0 => 'checkbox', 1 => 'Twitter support');
$LANG_CONF['socialnetworks.twitterConsumerKey'] = array(0 => 'input', 1 => 'Twitter Consumer Key');
$LANG_CONF['socialnetworks.twitterConsumerSecret'] = array(0 => 'input', 1 => 'Twitter Consumer Secret');

// added 2.7.0-alpha - 2010-10-14 by Tom Zeithaml
$LANG_CONF['socialnetworks.twitterAccessTokenKey'] = array(0 => 'input', 1 => 'Twitter Access Token Key');
$LANG_CONF['socialnetworks.twitterAccessTokenSecret'] = array(0 => 'input', 1 => 'Twitter Access Token Secret');
$LANG_CONF['socialnetworks.enableFacebookSupport'] = array(0 => 'checkbox', 1 => 'Facebook support');

// added 2.7.0-alpha - 2010-12-21 by Anatoliy Belsky
$PMF_LANG["ad_menu_attachments"] = "Eklentiler";
$PMF_LANG["ad_menu_attachment_admin"] = "Dosya Eklenti yönetimi";
$PMF_LANG['msgAttachmentsFilename'] = "Dosya adı";
$PMF_LANG['msgAttachmentsFilesize'] = "Boyut";
$PMF_LANG['msgAttachmentsMimeType'] = "Tür";
$PMF_LANG['msgAttachmentsWannaDelete'] = "Bu eklentiyi silmek istediğinizden emin misiniz??";
$PMF_LANG['msgAttachmentsDeleted'] = "Eklenti <strong>başarıyla</strong> silindi.";

// added v2.7.0-alpha2 - 2010-01-12 by Gustavo Solt
$PMF_LANG['ad_menu_reports'] = "Raporlar";
$PMF_LANG["ad_stat_report_fields"] = "Alanlar";
$PMF_LANG["ad_stat_report_category"] = "Kategori";
$PMF_LANG["ad_stat_report_sub_category"] = "Alt kategori";
$PMF_LANG["ad_stat_report_translations"] = "Çeviriler";
$PMF_LANG["ad_stat_report_language"] = "Dil";
$PMF_LANG["ad_stat_report_id"] = "İçerik ID";
$PMF_LANG["ad_stat_report_sticky"] = "Sabit içerik";
$PMF_LANG["ad_stat_report_title"] = "Soru";
$PMF_LANG["ad_stat_report_creation_date"] = "Tarih";
$PMF_LANG["ad_stat_report_owner"] = "Orijinal yazarı";
$PMF_LANG["ad_stat_report_last_modified_person"] = "Son düzenleyen";
$PMF_LANG["ad_stat_report_url"] = "URL";
$PMF_LANG["ad_stat_report_visits"] = "Ziyaret";
$PMF_LANG["ad_stat_report_make_report"] = "Rapor oluştur";
$PMF_LANG["ad_stat_report_make_csv"] = "CSV olarak dışa aktar";

// added v2.7.0-alpha2 - 2010-02-05 by Thorsten Rinne
$PMF_LANG['msgRegistration'] = "Yeni Kayıt";
$PMF_LANG['msgRegistrationCredentials'] = "Kayıt olmak için, lütfen adınızı, geçerli e-mail adresinizi ve kullanıcı adınızı belirtin.";
$PMF_LANG['msgRegistrationNote'] = "Kayıt işlemini tamamlamanızın ardından, detaylar yönetici tarafından kontrol edilecek ve onaylanması durumunda yanıt alacaksınız.";

// added v2.7.0-beta - 2011-06-13 by Thorsten
$PMF_LANG['ad_entry_changelog_history'] = "Değişiklik geçmişi";

// added v2.7.0-beta2 - 2011-06-22 by Thorsten
$LANG_CONF['security.ssoSupport'] = array(0 => 'checkbox', 1 => 'Single Sign On Support');
$LANG_CONF['security.ssoLogoutRedirect'] = array(0 => 'input', 1 => 'Single Sign On logout redirect service URL');
$LANG_CONF['main.dateFormat'] = array(0 => 'input', 1 => 'Date format (default: Y-m-d H:i)');
$LANG_CONF['security.enableLoginOnly'] = array(0 => 'checkbox', 1 => 'Complete secured FAQ');

// added v2.7.0-RC - 2011-08-18 by Thorsten
$PMF_LANG['securityControlCenter'] = "Güvenlik";
$PMF_LANG['ad_search_delsuc'] = "Arama terimi başarıyla silindi.";
$PMF_LANG['ad_search_delfail'] = "Arama terimi silinemedi";

// added 2.7.1 - 2011-09-30 by Thorsten
$PMF_LANG['msg_about_faq'] = "hakkında";
$LANG_CONF['security.useSslOnly'] = array(0 => 'checkbox', 1 => 'FAQ with SSL only');
$PMF_LANG['msgTableOfContent'] = "İçindekiler";

// added 2.7.5 - 2012-03-02 by Thorsten
$PMF_LANG["msgExportAllFaqs"] = "Tümünü PDF olarak yazdır";
$PMF_LANG["ad_online_verification"] = "Online doğrulama kontrolü";
$PMF_LANG["ad_verification_button"] = "phpMyFAQ kurulumunuzu doğrulamak için tıklayın";
$PMF_LANG["ad_verification_notokay"] = "Sisteminizde değiştirilmiş dosyalar tespit edildi";
$PMF_LANG["ad_verification_okay"] = "phpMyFAQ kurulumunuz başarıyla doğrulandı.";

// added v2.8.0-alpha - 2011-09-29 by Thorsten
$PMF_LANG['ad_menu_searchfaqs'] = "İçeriklerde ara";

// added v2.8.0-alpha - 2012-01-13 by Peter
$LANG_CONF["records.enableCloseQuestion"] = array(0 => "checkbox", 1 => "Close open question after answer?");
$LANG_CONF["records.enableDeleteQuestion"] = array(0 => "checkbox", 1 => "Delete open question after answer?");
$PMF_LANG["msg2answerFAQ"] = "Cevaplanmış";

// added v2.8.0-alpha - 2012-01-16 by Thorsten
$PMF_LANG["headerUserControlPanel"] = "Kullanıcı Paneli";

// added v2.8.0-alpha2 - 2012-03-15 by Thorsten
$PMF_LANG["rememberMe"] = "Giriş bilgilerimi hatırla";
$PMF_LANG["ad_menu_instances"] = "Çoklu site";

// added v2.8.0-alpha2 - 2012-07-07 by Anatoliy
$LANG_CONF['records.autosaveActive'] = array(0 => 'checkbox', 1 => 'Activate FAQ autosaving');
$LANG_CONF['records.autosaveSecs'] = array(0 => 'input', 1 => 'Interval for autosaving in seconds, default 180');

// added v2.8.0-alpha2 - 2012-08-06 by Thorsten
$PMF_LANG['ad_record_inactive'] = "Pasif sorular";
$LANG_CONF["main.maintenanceMode"] = array(0 => "checkbox", 1 => "Set FAQ in maintenance mode");
$PMF_LANG['msgMode'] = "Modus";
$PMF_LANG['msgMaintenanceMode'] = "Site bakımda";
$PMF_LANG['msgOnlineMode'] = "Site aktif";

// added v2.8.0-alpha3 - 2012-08-30 by Thorsten
$PMF_LANG['msgShowMore'] = "Daha fazlası";
$PMF_LANG['msgQuestionAnswered'] = "Soru yanıtlandı";
$PMF_LANG['msgMessageQuestionAnswered'] = "%s üzerinde sorduğunuz soru yanıtlandı. Lütfen şuradan kontrol edin";

// added v2.8.0-beta - 2012-12-24 by Thorsten
$LANG_CONF["records.randomSort"] = array(0 => "checkbox", 1 => "Sort FAQs randomly");
$LANG_CONF['main.enableWysiwygEditorFrontend'] = array(0 => "checkbox", 1 => "Enable bundled WYSIWYG editor in frontend");

// added v2.8.0-beta3 - 2013-01-15 by Thorsten
$LANG_CONF["main.enableGravatarSupport"] = array(0 => "checkbox", 1 => "Gravatar Support");

// added v2.8.0-RC - 2013-01-29 by Thorsten
$PMF_LANG["ad_stopwords_desc"] = "Yasaklı kelime eklemek ya da düzenlemek için bir dil seçin.";
$PMF_LANG["ad_visits_per_day"] = "Günlük ziyaret";

// added v2.8.0-RC2 - 2013-02-17 by Thorsten
$PMF_LANG["ad_instance_add"] = "Yeni bir site kopyası ekle";
$PMF_LANG["ad_instance_error_notwritable"] = "/multisite dizini yazılabilir değil.";
$PMF_LANG["ad_instance_url"] = "Yeni site adresi";
$PMF_LANG["ad_instance_path"] = "Site dizini";
$PMF_LANG["ad_instance_name"] = "Site adı";
$PMF_LANG["ad_instance_email"] = "Yönetici e-posta adresi";
$PMF_LANG["ad_instance_admin"] = "Yönetici kullanıcı adı";
$PMF_LANG["ad_instance_password"] = "Yönetici şifresi";
$PMF_LANG["ad_instance_hint"] = "Dikkat: yeni bir phpMyFAQ kopyası oluşturmak biraz zaman alacak!";
$PMF_LANG["ad_instance_button"] = "Kaydet";
$PMF_LANG["ad_instance_error_cannotdelete"] = "Kopya silinemedi ";
$PMF_LANG["ad_instance_config"] = "Yapılandırma";

// added v2.8.0-RC3 - 2013-03-03 by Thorsten
$PMF_LANG["msgAboutThisNews"] = "Hakkında";

// added v.2.8.1 - 2013-06-23 by Thorsten
$PMF_LANG["msgAccessDenied"] = "Erişim reddedildi.";

// added v.2.8.21 - 2015-02-17 by Thorsten
$PMF_LANG['msgSeeFAQinFrontend'] = "Siteyi ziyaret et";

// added v.2.9.0-alpha - 2013-12-26 by Thorsten
$PMF_LANG["msgRelatedTags"] = "Arama terimi ekle";
$PMF_LANG["msgPopularTags"] = "Aranan popüler kelimeler";
$LANG_CONF["search.enableHighlighting"] = array(0 => "checkbox", 1 => "Highlight search terms");
$LANG_CONF["main.enableRssFeeds"] = array(0 => "checkbox", 1 => "RSS Feeds");
$LANG_CONF["records.allowCommentsForGuests"] = array(0 => "checkbox", 1 => "Allow comments for guests");
$LANG_CONF["records.allowQuestionsForGuests"] = array(0 => "checkbox", 1 => "Allow adding questions for guests");
$LANG_CONF["records.allowNewFaqsForGuests"] = array(0 => "checkbox", 1 => "Allow adding new FAQs");
$PMF_LANG["ad_searchterm_del"] = "Kaydedilen tüm arama terimleri silindi";
$PMF_LANG["ad_searchterm_del_suc"] = "Tüm arama terimleri başarıyla silindi.";
$PMF_LANG["ad_searchterm_del_err"] = "Arama terimleri silinemedi.";
$LANG_CONF["records.hideEmptyCategories"] = array(0 => "checkbox", 1 => "Hide empty categories");
$LANG_CONF["search.searchForSolutionId"] = array(0 => "checkbox", 1 => "Search for solution ID");
$LANG_CONF["socialnetworks.disableAll"] = array(0 => "checkbox", 1 => "Disable all social networks");
$LANG_CONF["main.enableGzipCompression"] = array(0 => "checkbox", 1 => "Enable GZIP compression");

// added v2.9.0-alpha2 - 2014-08-16 by Thorsten
$PMF_LANG["ad_tag_delete_success"] = "Etiket başarıyla silindi.";
$PMF_LANG["ad_tag_delete_error"] = "Etiket silinirken bir hata oluştu.";
$PMF_LANG["seoCenter"] = "SEO";
$LANG_CONF["seo.metaTagsHome"] = array(0 => "select", 1 => "Meta Tags start page");
$LANG_CONF["seo.metaTagsFaqs"] = array(0 => "select", 1 => "Meta Tags FAQs");
$LANG_CONF["seo.metaTagsCategories"] = array(0 => "select", 1 => "Meta Tags category pages");
$LANG_CONF["seo.metaTagsPages"] = array(0 => "select", 1 => "Meta Tags static pages");
$LANG_CONF["seo.metaTagsAdmin"] = array(0 => "select", 1 => "Meta Tags Admin");
$PMF_LANG["msgMatchingQuestions"] = "Sorunuzla ilgili olabilecek sonuçlar şunlardır";
$PMF_LANG["msgFinishSubmission"] = "Eğer gösterilecek sonuçlar aradığınız cevabı içermiyorsa, sorunuzu hemen bize iletebilirsiniz!";
$LANG_CONF["main.enableLinkVerification"] = array(0 => "checkbox", 1 => "Enable automatic link verification");
$LANG_CONF['spam.manualActivation'] = array(0 => 'checkbox', 1 => 'Manually activate new users (default: activated)');

// added v2.9.0-alpha2 - 2014-10-13 by Christopher Andrews ( Chris--A )
$PMF_LANG['mailControlCenter'] = "Mail kurulumu";
$LANG_CONF['mail.remoteSMTP'] = array(0 => 'checkbox', 1 => 'Use remote SMTP server (default: deactivated)');
$LANG_CONF['mail.remoteSMTPServer'] = array(0 => 'input', 1 => 'Server address');
$LANG_CONF['mail.remoteSMTPUsername'] = array(0 => 'input', 1 => 'User name');
$LANG_CONF['mail.remoteSMTPPassword'] = array(0 => 'password', 1 => 'Password');
$LANG_CONF['security.enableRegistration'] = array('checkbox', 'Enable registration for visitors');

// added v2.9.0-alpha3 - 2015-02-08 by Thorsten
$LANG_CONF['main.customPdfHeader'] = array('area', 'Custom PDF Header (HTML allowed)');
$LANG_CONF['main.customPdfFooter'] = array('area', 'Custom PDF Footer (HTML allowed)');
$LANG_CONF['records.allowDownloadsForGuests'] = array('checkbox', 'Allow downloads for guests');
$PMF_LANG["ad_msgNoteAboutPasswords"] = "Dikkat! girdiğiniz şifre kullanıcı şifresinin üzerine yazılacaktır.";
$PMF_LANG["ad_delete_all_votings"] = "Tüm oylamaları temizle";
$PMF_LANG["ad_categ_moderator"] = "Moderatörler";
$PMF_LANG['ad_clear_all_visits'] = "Tüm ziyaretleri temizle";
$PMF_LANG['ad_reset_visits_success'] = "Ziyaretçi kayıtları başarıyla sıfırlandı.";
$LANG_CONF['main.enableMarkdownEditor'] = array('checkbox', 'Enable bundled Markdown editor');

// added v2.9.0-beta - 2015-09-27 by Thorsten
$PMF_LANG['faqOverview'] = "Özet bilgi";
$PMF_LANG['ad_dir_missing'] = "%s dizini bulunamadı.";
$LANG_CONF['main.enableSmartAnswering'] = array('checkbox', 'Enable smart answering for user questions');

// added v2.9.0-beta2 - 2015-12-23 by Thorsten
$LANG_CONF['search.enableElasticsearch'] = array('checkbox', 'Enable Elasticsearch support');
$PMF_LANG['ad_menu_elasticsearch'] = "Elasticsearch yapılandırması";
$PMF_LANG['ad_es_create_index'] = "Index oluştur";
$PMF_LANG['ad_es_drop_index'] = "Index kaldır";
$PMF_LANG['ad_es_bulk_index'] = "Tam içe aktarma";
$PMF_LANG['ad_es_create_index_success'] = "Index başarıyla oluşturuldu.";
$PMF_LANG['ad_es_drop_index_success'] = "Index başarıyla kaldırıldı.";
$PMF_LANG['ad_export_generate_json'] = "JSON dosyası oluştur";
$PMF_LANG['ad_image_name_search'] = "Görsel adıyla ara";

// added v2.9.0-RC - 2016-02-19 by Thorsten
$PMF_LANG['ad_admin_notes'] = "Özel Not";
$PMF_LANG['ad_admin_notes_hint'] = "%s (yalnızca editörler tarafından görülebilir)";

// added 2.10.0-alpha - 2016-08-08 by Thorsten
$LANG_CONF['ldap.ldap_mapping.name'] = array(0 => 'input', 1 => 'LDAP mapping for name, "cn" when using an ADS');
$LANG_CONF['ldap.ldap_mapping.username'] = array(0 => 'input', 1 => 'LDAP mapping for username, "samAccountName" when using an ADS');
$LANG_CONF['ldap.ldap_mapping.mail'] = array(0 => 'input', 1 => 'LDAP mapping for email, "mail" when using an ADS');
$LANG_CONF['ldap.ldap_mapping.memberOf'] = array(0 => 'input', 1 => 'LDAP mapping for "member of" when using LDAP groups');
$LANG_CONF['ldap.ldap_use_domain_prefix'] = array('checkbox', 'LDAP domain prefix, e.g. "DOMAIN\username"');
$LANG_CONF['ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'] = array(0 => 'input', 1 => 'LDAP protocol version (default: 3)');
$LANG_CONF['ldap.ldap_options.LDAP_OPT_REFERRALS'] = array(0 => 'input', 1 => 'LDAP referrals (default: 0)');
$LANG_CONF['ldap.ldap_use_memberOf'] = array('checkbox', 'Enable LDAP group support, e.g. "DOMAIN\username"');
$LANG_CONF['ldap.ldap_use_sasl'] = array('checkbox', 'Enable LDAP SASL support');
$LANG_CONF['ldap.ldap_use_multiple_servers'] = array('checkbox', 'Enable multiple LDAP servers support');
$LANG_CONF['ldap.ldap_use_anonymous_login'] = array('checkbox', 'Enable anonymous LDAP connections');
$LANG_CONF['ldap.ldap_use_dynamic_login'] = array('checkbox', 'Enable LDAP dynamic user binding');
$LANG_CONF['ldap.ldap_dynamic_login_attribute'] = array(0 => 'input', 1 => 'LDAP attribute for dynamic user binding, "uid" when using an ADS');
$LANG_CONF['seo.enableXMLSitemap'] = array('checkbox', 'Enable XML sitemap');
$PMF_LANG['ad_category_image'] = "Kategori görseli";
$PMF_LANG["ad_user_show_home"] = "başlangıç sayfasında göster";

// added v.2.10.0-alpha - 2017-11-09 by Brian Potter (BrianPotter)
$PMF_LANG['ad_view_faq'] = "Görüntüle";

// added 3.0.0-alpha - 2018-01-04 by Thorsten
$LANG_CONF['main.enableCategoryRestrictions'] = ['checkbox', 'Enable category restrictions'];
$LANG_CONF['main.enableSendToFriend'] = ['checkbox', 'Enable send to friends'];
$PMF_LANG['msgUserRemovalText'] = "Hesabınızın silinmesini talep edebilirsiniz. BU işlem size ait tüm verilerin silinmesi için yöneticileri bilgilendirir ve manuel bir işlemdir. İşlemin tamamlanmasının ardından tarafınıza bir e-posta gönderilir. İşlem 24 saati bulabilmektedir. İşlem sonucunda size ait hesap detayları, soru ve cevap kayıtları, duyurular ve yorumlar silinmiş olacaktır.";
$PMF_LANG["msgUserRemoval"] = "Hesap silme talebi";
$PMF_LANG["ad_menu_RequestRemove"] = "Hesap silme talebi";
$PMF_LANG["msgContactRemove"] = "Yönetici ekibinden silinme talebi";
$PMF_LANG["msgContactPrivacyNote"] = "Lütfen göszden geçirin";
$PMF_LANG["msgPrivacyNote"] = "Gizlilik sözleşmesi";

// added 3.0.0-alpha2 - 2018-03-27 by Thorsten
$LANG_CONF['main.enableAutoUpdateHint'] = ['checkbox', 'Automatic check for new versions'];
$PMF_LANG['ad_quick_entry'] = "Bu kategoriye soru ekle";
$PMF_LANG['ad_user_is_superadmin'] = "Super-Yönetici";
$PMF_LANG['ad_user_override_passwd'] = "Şifrenin üzerine yaz";
$LANG_CONF['records.enableAutoRevisions'] = ['checkbox', 'Versioning of all FAQ changes'];
$PMF_LANG['rightsLanguage']['view_faqs'] = "Soruları görüntüle";
$PMF_LANG['rightsLanguage']['view_categories'] = "Kategorileri görüntüle";
$PMF_LANG['rightsLanguage']['view_sections'] = "Bölümleri görüntüle";
$PMF_LANG['rightsLanguage']['view_news'] = "Duyuruları görüntüle";
$PMF_LANG['rightsLanguage']['add_section'] = "Bölüm ekle";
$PMF_LANG['rightsLanguage']['edit_section'] = "Bölüm düzenle";
$PMF_LANG['rightsLanguage']['delete_section'] = "Bölüm sil";
$PMF_LANG['rightsLanguage']['administrate_sections'] = "Bölüm yönet";
$PMF_LANG['rightsLanguage']['administrate_groups'] = "Grupları yönet";
$PMF_LANG['ad_group_rights'] = "Grup izinleri";
$PMF_LANG['ad_menu_meta'] = "Meta verisi";
$PMF_LANG['ad_meta_add'] = "meta verisi ekle";
$PMF_LANG['ad_meta_page_id'] = "Sayfa türü";
$PMF_LANG['ad_meta_type'] = "İçerik türü";
$PMF_LANG['ad_meta_content'] = "İçerik";

// added v3.0.0-alpha.3 - 2018-09-20 by Timo
$PMF_LANG['ad_menu_section_administration'] = "Bölüm";
$PMF_LANG['ad_section_add'] = "Bölüm ekle";
$PMF_LANG['ad_section_add_link'] = "Bölüm ekle";
$PMF_LANG['ad_sections'] = "Bölümler";
$PMF_LANG['ad_section_details'] = "Bölüm detayları";
$PMF_LANG['ad_section_name'] = "İsim";
$PMF_LANG['ad_section_description'] = "Açıklama";
$PMF_LANG['ad_section_membership'] = "Bölüm ilişkilendir";
$PMF_LANG['ad_section_members'] = "İlişkilendirmeler";
$PMF_LANG['ad_section_addMember'] = "ekle";
$PMF_LANG['ad_section_removeMember'] = "çıkar";
$PMF_LANG['ad_section_deleteSection'] = "Bölüm sil";
$PMF_LANG['ad_section_deleteQuestion'] = "Bu bölümü silmek istediğinizden emin misiniz?";
$PMF_LANG['ad_section_error_delete'] = "Bölüm silinemedi.";
$PMF_LANG['ad_section_error_noName'] = "Lütfen bir bölüm adı girin.";
$PMF_LANG['ad_section_suc'] = "Bölüm <strong>başarıyla</strong> eklendi.";
$PMF_LANG['ad_section_deleted'] = "Bölüm başarıyla silindi.";
$PMF_LANG['rightsLanguage']['viewadminlink'] = "Yönetici bağlantısını görüntüle";
