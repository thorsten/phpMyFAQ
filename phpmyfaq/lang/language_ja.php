<?php
/**
 * The Japanese language file - try to be the best of Japanese
 *
 * @package    phpMyFAQ
 * @subpackage i18n
 * @author     Tadashi Jokagi <http://poyo.jp/>
 * @author     Minoru TODA <todam@netjapan.co.jp>
 * @since      2004-02-19
 * @copyright  2004-2009 phpMyFAQ Team]
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
//  based: 2449f37ecd1f8992d121cb2f9b7a4c16486fc2db (language_en.php)

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "ja";
$PMF_LANG["language"] = "日本語";
// ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)
$PMF_LANG["dir"] = "ltr"; // ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)

$PMF_LANG["nplurals"] = "0";
/**
 * This parameter is used with the function 'plural' from inc/PMF_Language/Plurals.php
 * If this parameter and function are not in sync plural form support will be broken.
 *
 * If you add a translation for a new language, correct plural form support will be missing
 * (English plural messages will be used) until you add a correct expression to the function
 * 'plural' mentioned above.
 * If you need any help, please contact phpMyFAQ team.
 */

// Navigation
$PMF_LANG["msgCategory"] = "カテゴリー";
$PMF_LANG["msgShowAllCategories"] = "全カテゴリーを表示する";
$PMF_LANG["msgSearch"] = "検索";
$PMF_LANG["msgAddContent"] = "内容の追加";
$PMF_LANG["msgQuestion"] = "質問をする";
$PMF_LANG["msgOpenQuestions"] = "質問を開く";
$PMF_LANG["msgHelp"] = "ヘルプ";
$PMF_LANG["msgContact"] = "問い合わせ";
$PMF_LANG["msgHome"] = "FAQ ホーム";
$PMF_LANG["msgNews"] = "FAQ お知らせ";
$PMF_LANG["msgUserOnline"] = " ユーザーがオンライン";
$PMF_LANG["msgBack2Home"] = "メイン ページに戻る";

// Contentpages
$PMF_LANG["msgFullCategories"] = "FAQ とカテゴリー";
$PMF_LANG["msgFullCategoriesIn"] = "カテゴリー";
$PMF_LANG["msgSubCategories"] = "下位カテゴリー";
$PMF_LANG["msgEntries"] = "個のFAQ";
$PMF_LANG["msgEntriesIn"] = "カテゴリー名: ";
$PMF_LANG["msgViews"] = "回の閲覧";
$PMF_LANG["msgPage"] = "ページ ";
$PMF_LANG["msgPages"] = "ページ中";
$PMF_LANG["msgPrevious"] = "前へ";
$PMF_LANG["msgNext"] = "次へ";
$PMF_LANG["msgCategoryUp"] = "上位カテゴリーへ";
$PMF_LANG["msgLastUpdateArticle"] = "最終更新: ";
$PMF_LANG["msgAuthor"] = "作成者: ";
$PMF_LANG["msgPrinterFriendly"] = "印刷用バージョン";
$PMF_LANG["msgPrintArticle"] = "このレコードを印刷する";
$PMF_LANG["msgMakeXMLExport"] = "XML ファイルエクスポート";
$PMF_LANG["msgAverageVote"] = "評価点数:";
$PMF_LANG["msgVoteUseability"] = "この FAQ を評価してください:";
$PMF_LANG["msgVoteFrom"] = " - ";
$PMF_LANG["msgVoteBad"] = "完全に役に立たない";
$PMF_LANG["msgVoteGood"] = "最も価値がある";
$PMF_LANG["msgVotings"] = "個の投票 ";
$PMF_LANG["msgVoteSubmit"] = "投票";
$PMF_LANG["msgVoteThanks"] = "投票を非常に感謝します!";
$PMF_LANG["msgYouCan"] = "";
$PMF_LANG["msgWriteComment"] = "この FAQ にコメントする";
$PMF_LANG["msgShowCategory"] = "内容の概要: ";
$PMF_LANG["msgCommentBy"] = "コメント作成は";
$PMF_LANG["msgCommentHeader"] = "この FAQ にコメント";
$PMF_LANG["msgYourComment"] = "あなたのコメント:";
$PMF_LANG["msgCommentThanks"] = "コメントを非常に感謝します!";
$PMF_LANG["msgSeeXMLFile"] = "XML ファイルを開く";
$PMF_LANG["msgSend2Friend"] = "友達に教える";
$PMF_LANG["msgS2FName"] = "名前:";
$PMF_LANG["msgS2FEMail"] = "メールアドレス:";
$PMF_LANG["msgS2FFriends"] = "あなたの友達:";
$PMF_LANG["msgS2FEMails"] = ". 電子メールアドレス:";
$PMF_LANG["msgS2FText"] = "追加して送るテキストを入力してください:";
$PMF_LANG["msgS2FText2"] = "次の URL からこの FAQ bが確認できます:";
$PMF_LANG["msgS2FMessage"] = "友達への補足メッセージ:";
$PMF_LANG["msgS2FButton"] = "メール送信";
$PMF_LANG["msgS2FThx"] = "推薦してくれてありがとうございます!";
$PMF_LANG["msgS2FMailSubject"] = "Recommendation from ";

// Search
$PMF_LANG["msgSearchWord"] = "キーワード";
$PMF_LANG["msgSearchFind"] = "検索結果 ";
$PMF_LANG["msgSearchAmount"] = " 検索結果";
$PMF_LANG["msgSearchAmounts"] = " 検索結果";
$PMF_LANG["msgSearchCategory"] = "カテゴリー: ";
$PMF_LANG["msgSearchContent"] = "回答: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "新しい FAQ を提案する";
$PMF_LANG["msgNewContentAddon"] = "提案する内容はすぐに追加はされません。管理者の承認後、追加されます。<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>カテゴリー</strong>、<strong>件名</strong>、<strong>FAQ の内容</strong>は必須項目です。キーワードには半角空白で分割して入力してください。";
$PMF_LANG["msgNewContentName"] = "名前:";
$PMF_LANG["msgNewContentMail"] = "電子メールアドレス:";
$PMF_LANG["msgNewContentCategory"] = "カテゴリー:";
$PMF_LANG["msgNewContentTheme"] = "質問:";
$PMF_LANG["msgNewContentArticle"] = "回答:";
$PMF_LANG["msgNewContentKeywords"] = "キーワード:";
$PMF_LANG["msgNewContentLink"] = "関連リンク先:";
$PMF_LANG["msgNewContentSubmit"] = "送信";
$PMF_LANG["msgInfo"] = "追加情報: ";
$PMF_LANG["msgNewContentThanks"] = "ご提案ありがとうございます!";
$PMF_LANG["msgNoQuestionsAvailable"] = "現在処理すべき質問がありません。";

// ask Question
$PMF_LANG["msgNewQuestion"] = "質問したい内容を入力してください:";
$PMF_LANG["msgAskCategory"] = "カテゴリー:";
$PMF_LANG["msgAskYourQuestion"] = "質問:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>ご質問、ありがとうございます!</h2>";
$PMF_LANG["msgDate_User"] = "日付 / ユーザー";
$PMF_LANG["msgQuestion2"] = "質問";
$PMF_LANG["msg2answer"] = "回答";
$PMF_LANG["msgQuestionText"] = "他のユーザーが質問した内容を確認することができます。質問に答えた場合、管理者の確認後、FAQに追加されます。";

// Help
$PMF_LANG["msgHelpText"] = "<p>このFAQ (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) の利用方法が簡単です。<strong><a href=\"?action=show\">カテゴリー</a></strong> から関連内容を項目別に探すか <strong><a href=\"?action=search\">検索</a></strong> からキーワードを入力して探すことができます。</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "管理者に電子メール:";
$PMF_LANG["msgMessage"] = "メッセージ:";

// Startseite
$PMF_LANG["msgNews"] = " お知らせ";
$PMF_LANG["msgTopTen"] = "最も人気の FAQ";
$PMF_LANG["msgHomeThereAre"] = "合計 ";
$PMF_LANG["msgHomeArticlesOnline"] = " 個の FAQ があります。";
$PMF_LANG["msgNoNews"] = "新しいお知らせはありません。";
$PMF_LANG["msgLatestArticles"] = "最近の FAQ";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "FAQ に提案してくれてありがとうございます。";
$PMF_LANG["msgMailCheck"] = "新しい質問があります。管理者ページを確認してください。";
$PMF_LANG["msgMailContact"] = "メッセージは管理者に送信されました。";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "データベース接続が有効ではありません。";
$PMF_LANG["err_noHeaders"] = "カテゴリーが見つかりません。";
$PMF_LANG["err_noArticles"] = "<p>登録されている FAQ がありません。</p>";
$PMF_LANG["err_badID"] = "<p>間違った ID です。</p>";
$PMF_LANG["err_noTopTen"] = "<p>人気の FAQ がまだ利用できません。</p>";
$PMF_LANG["err_nothingFound"] = "<p>エントリーが見つかりません。</p>";
$PMF_LANG["err_SaveEntries"] = "<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>カテゴリー</strong>、<strong>件名</strong>、<strong>FAQ 内容</strong>、要求された場合は <strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"Wikipedia で Captcha について読む\" target=\"_blank\">Captcha</a> コード</strong> は必須フィールドです!<br /><br /><a href=\"javascript:history.back();\">戻る</a><br /><br />";
$PMF_LANG["err_SaveComment"] = "<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>コメント</strong>と要求された場合は <strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"Wikipedia で Captcha について読む\" target=\"_blank\">Captcha</a> コード</strong> は必須項目です!<br /><br /><a href=\"javascript:history.back();\">戻る</a><br /><br />";
$PMF_LANG["err_VoteTooMuch"] = "<p>複数回の評価はできません。<a href=\"javascript:history.back();\">ここ</a>をクリックすると戻ります。</p>";
$PMF_LANG["err_noVote"] = "<p><strong>評価点数を選択してください。</strong> 評価をするためには<a href=\"javascript:history.back();\">ここ</a>をクリックしてください。</p>";
$PMF_LANG["err_noMailAdress"] = "メールアドレスが正しくありません。<br /><a href=\"javascript:history.back();\">戻る</a>";
$PMF_LANG["err_sendMail"] = "<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>質問</strong>と要求された場合は <strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"Wikipedia で Captcha について読む\" target=\"_blank\">Captcha</a> コード</strong> は必須項目です!";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>内容検索: </strong><br /><strong style=\"color: Red;\">言葉1 言葉2</strong>のように検索すると、2 個以上の検索結果が関連度が高い順番で表示されます。</p><p><strong>注意:</strong> 英文を検索する際には、少なくとも 4 文字以上を入力してください。</p>";

// Menï¿½
$PMF_LANG["ad"] = "管理者ページ";
$PMF_LANG["ad_menu_user_administration"] = "ユーザー";
$PMF_LANG["ad_menu_entry_aprove"] = "FAQ の認証";
$PMF_LANG["ad_menu_entry_edit"] = "FAQ の編集";
$PMF_LANG["ad_menu_categ_add"] = "カテゴリーの追加";
$PMF_LANG["ad_menu_categ_edit"] = "カテゴリーの変更";
$PMF_LANG["ad_menu_news_add"] = "お知らせの追加";
$PMF_LANG["ad_menu_news_edit"] = "お知らせの変更";
$PMF_LANG["ad_menu_open"] = "質問を開く";
$PMF_LANG["ad_menu_stat"] = "統計";
$PMF_LANG["ad_menu_cookie"] = "Cookie の設定";
$PMF_LANG["ad_menu_session"] = "セッションの閲覧";
$PMF_LANG["ad_menu_adminlog"] = "管理ログの閲覧";
$PMF_LANG["ad_menu_passwd"] = "パスワードの変更";
$PMF_LANG["ad_menu_logout"] = "ログアウト";
$PMF_LANG["ad_menu_startpage"] = "開始ページ";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "ログインをしてください。";
$PMF_LANG["ad_msg_passmatch"] = "パスワードは必ず <strong>一致</strong> する必要があります。";
$PMF_LANG["ad_msg_savedsuc_1"] = "";
$PMF_LANG["ad_msg_savedsuc_2"] = "　のプロフィールの保存に成功しました。";
$PMF_LANG["ad_msg_mysqlerr"] = "<strong>データベースのエラー</strong>のため、プロフィールが保存できません。";
$PMF_LANG["ad_msg_noauth"] = "使用権限がありません。";

// Allgemein
$PMF_LANG["ad_gen_page"] = "ページ";
$PMF_LANG["ad_gen_of"] = "of";
$PMF_LANG["ad_gen_lastpage"] = "前のページ";
$PMF_LANG["ad_gen_nextpage"] = "次のページ";
$PMF_LANG["ad_gen_save"] = "保存";
$PMF_LANG["ad_gen_reset"] = "リセット";
$PMF_LANG["ad_gen_yes"] = "はい";
$PMF_LANG["ad_gen_no"] = "いいえ";
$PMF_LANG["ad_gen_top"] = "ページの先頭";
$PMF_LANG["ad_gen_ncf"] = "カテゴリーが見つかりません!";
$PMF_LANG["ad_gen_delete"] = "削除";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "ユーザー管理";
$PMF_LANG["ad_user_username"] = "登録済みユーザー一覧";
$PMF_LANG["ad_user_rights"] = "ユーザー権限一覧";
$PMF_LANG["ad_user_edit"] = "変更";
$PMF_LANG["ad_user_delete"] = "削除";
$PMF_LANG["ad_user_add"] = "ユーザー追加";
$PMF_LANG["ad_user_profou"] = "ユーザーのプロフィール";
$PMF_LANG["ad_user_name"] = "ID";
$PMF_LANG["ad_user_password"] = "パスワード";
$PMF_LANG["ad_user_confirm"] = "パスワードの確認";
$PMF_LANG["ad_user_rights"] = "権限一覧";
$PMF_LANG["ad_user_del_1"] = "ユーザー";
$PMF_LANG["ad_user_del_2"] = "を削除しますか?";
$PMF_LANG["ad_user_del_3"] = "本当に削除しますか?";
$PMF_LANG["ad_user_deleted"] = "ユーザーの削除に成功しました。";
$PMF_LANG["ad_user_checkall"] = "すべて選択";

// Beitragsverwaltung
$PMF_LANG["ad_entry_aor"] = "レコードの管理";
$PMF_LANG["ad_entry_id"] = "ID";
$PMF_LANG["ad_entry_topic"] = "トピック";
$PMF_LANG["ad_entry_action"] = "操作";
$PMF_LANG["ad_entry_edit_1"] = "レコードの変更";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "質問:";
$PMF_LANG["ad_entry_content"] = "回答:";
$PMF_LANG["ad_entry_keywords"] = "キーワード:";
$PMF_LANG["ad_entry_author"] = "作成者:";
$PMF_LANG["ad_entry_category"] = "カテゴリー:";
$PMF_LANG["ad_entry_active"] = "有効にする";
$PMF_LANG["ad_entry_date"] = "日付:";
$PMF_LANG["ad_entry_changed"] = "変更しますか?";
$PMF_LANG["ad_entry_changelog"] = "変更履歴:";
$PMF_LANG["ad_entry_commentby"] = "コメント作成者";
$PMF_LANG["ad_entry_comment"] = "コメント:";
$PMF_LANG["ad_entry_save"] = "保存";
$PMF_LANG["ad_entry_delete"] = "削除";
$PMF_LANG["ad_entry_delcom_1"] = " ";
$PMF_LANG["ad_entry_delcom_2"] = "　さんのコメントを削除しますか？";
$PMF_LANG["ad_entry_commentdelsuc"] = "コメントの<strong>削除に成功</strong>しました。";
$PMF_LANG["ad_entry_back"] = "戻る";
$PMF_LANG["ad_entry_commentdelfail"] = "コメントの<strong>削除に失敗</strong>しました。";
$PMF_LANG["ad_entry_savedsuc"] = "変更の保存に <strong>成功</strong>しました。";
$PMF_LANG["ad_entry_savedfail   "] = "<strong>データベースのエラー</strong>が発生しました。";
$PMF_LANG["ad_entry_del_1"] = " ";
$PMF_LANG["ad_entry_del_2"] = "に関する";
$PMF_LANG["ad_entry_del_3"] = " さんのレコードを削除しますか？";
$PMF_LANG["ad_entry_delsuc"] = "削除に<strong>成功</strong>しました。";
$PMF_LANG["ad_entry_delfail"] = "削除に<strong>失敗</strong>しました。";
$PMF_LANG["ad_entry_back"] = "戻る";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "お知らせの件名:";
$PMF_LANG["ad_news_text"] = "内容:";
$PMF_LANG["ad_news_link_url"] = "関連リンク:";
$PMF_LANG["ad_news_link_title"] = "リンクのタイトル:";
$PMF_LANG["ad_news_link_target"] = "リンクのターゲット:";
$PMF_LANG["ad_news_link_window"] = "新規ウィンドウでリンクを開く";
$PMF_LANG["ad_news_link_faq"] = "FAQ 内のリンク";
$PMF_LANG["ad_news_add"] = "お知らせの追加";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "件名";
$PMF_LANG["ad_news_date"] = "日付";
$PMF_LANG["ad_news_action"] = "操作";
$PMF_LANG["ad_news_update"] = "更新";
$PMF_LANG["ad_news_delete"] = "削除";
$PMF_LANG["ad_news_nodata"] = "データベースにデータが見つかりませんでした。";
$PMF_LANG["ad_news_updatesuc"] = "更新しました。";
$PMF_LANG["ad_news_del"] = "これを削除しますか?";
$PMF_LANG["ad_news_yesdelete"] = "はい、削除します!";
$PMF_LANG["ad_news_nodelete"] = "いいえ!";
$PMF_LANG["ad_news_delsuc"] = "削除しました。";
$PMF_LANG["ad_news_updatenews"] = "ニュースの項目を更新しました";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "新しいカテゴリーの追加";
$PMF_LANG["ad_categ_catnum"] = "カテゴリー番号:";
$PMF_LANG["ad_categ_subcatnum"] = "サブカテゴリー番号:";
$PMF_LANG["ad_categ_nya"] = "<em>利用できません!</em>";
$PMF_LANG["ad_categ_titel"] = "カテゴリー名:";
$PMF_LANG["ad_categ_add"] = "カテゴリーの追加";
$PMF_LANG["ad_categ_existing"] = "存在するカテゴリー一覧";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "カテゴリー";
$PMF_LANG["ad_categ_subcateg"] = "下位カテゴリー";
$PMF_LANG["ad_categ_titel"] = "カテゴリー名";
$PMF_LANG["ad_categ_action"] = "操作";
$PMF_LANG["ad_categ_update"] = "更新";
$PMF_LANG["ad_categ_delete"] = "削除";
$PMF_LANG["ad_categ_updatecateg"] = "カテゴリーの変更";
$PMF_LANG["ad_categ_nodata"] = "データベースにデータが見つかりません。";
$PMF_LANG["ad_categ_remark"] = "カテゴリーを削除すると、該当カテゴリーのレコード(FAQ)も削除されます。カテゴリーを削除する前に、レコード(FAQ)を他のカテゴリーに指定してください。";
$PMF_LANG["ad_categ_edit_1"] = "変更";
$PMF_LANG["ad_categ_edit_2"] = "カテゴリー";
$PMF_LANG["ad_categ_add"] = "カテゴリー追加";
$PMF_LANG["ad_categ_added"] = "カテゴリーを追加しました。";
$PMF_LANG["ad_categ_updated"] = "カテゴリーを変更しました。";
$PMF_LANG["ad_categ_del_yes"] = "はい、削除します!";
$PMF_LANG["ad_categ_del_no"] = "いいえ!";
$PMF_LANG["ad_categ_deletesure"] = "本当にこのカテゴリーを削除しますか?";
$PMF_LANG["ad_categ_deleted"] = "カテゴリーを削除しました。";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc"] = "Cookie の設定に<strong>成功</strong>しました。";
$PMF_LANG["ad_cookie_already"] = "Cookie は既に設定されています。現在次のオプションがあります:";
$PMF_LANG["ad_cookie_again"] = "もう一度 Cookie を設定する";
$PMF_LANG["ad_cookie_delete"] = "Cookie 削除する";
$PMF_LANG["ad_cookie_no"] = "保存されている Cookie がありません。Cookie にてログインスクリプトを保存します。再びあなたのログイン詳細を覚えることはありません。次のようなオプションがあります:";
$PMF_LANG["ad_cookie_set"] = "Cookie の設定";
$PMF_LANG["ad_cookie_deleted"] = "Cookie の削除に成功しました。";

// Adminlog
$PMF_LANG["ad_adminlog"] = "管理ログ";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "パスワード変更";
$PMF_LANG["ad_passwd_old"] = "現在のパスワード:";
$PMF_LANG["ad_passwd_new"] = "新しいパスワード:";
$PMF_LANG["ad_passwd_con"] = "新しいパスワードの再確認:";
$PMF_LANG["ad_passwd_change"] = "パスワード変更";
$PMF_LANG["ad_passwd_suc"] = "パスワードの変更に成功しました。";
$PMF_LANG["ad_passwd_remark"] = "<strong>注意:</strong><br />もう一度クッキーを設定してください。";
$PMF_LANG["ad_passwd_fail"] = "'現在のパスワード'を <strong>正しく</strong> 入力し、「新しいパスワード」と「新しいパスワードの再確認」は必ず <strong>一致</strong>するように入力してください。";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "ユーザーの追加";
$PMF_LANG["ad_adus_name"] = "ID:";
$PMF_LANG["ad_adus_password"] = "パスワード:";
$PMF_LANG["ad_adus_add"] = "ユーザー追加";
$PMF_LANG["ad_adus_suc"] = "ユーザーの追加に<strong>成功</strong>しました。";
$PMF_LANG["ad_adus_edit"] = "プロフィールの変更";
$PMF_LANG["ad_adus_dberr"] = "<strong>データベースエラーです!</strong>";
$PMF_LANG["ad_adus_exerr"] = "ユーザーは既に<strong>存在します</strong>。";

// Sessions
$PMF_LANG["ad_sess_id"] = "ID";
$PMF_LANG["ad_sess_sid"] = "セッション ID";
$PMF_LANG["ad_sess_ip"] = "IP";
$PMF_LANG["ad_sess_time"] = "時間";
$PMF_LANG["ad_sess_pageviews"] = "ページビュー";
$PMF_LANG["ad_sess_search"] = "検索";
$PMF_LANG["ad_sess_sfs"] = "セッションから検索";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. actions:";
$PMF_LANG["ad_sess_s_date"] = "日付";
$PMF_LANG["ad_sess_s_after"] = "以後";
$PMF_LANG["ad_sess_s_before"] = "以前";
$PMF_LANG["ad_sess_s_search"] = "検索";
$PMF_LANG["ad_sess_session"] = "セッション";
$PMF_LANG["ad_sess_r"] = "検索結果 - ";
$PMF_LANG["ad_sess_referer"] = "リファラー:";
$PMF_LANG["ad_sess_browser"] = "ブラウザー:";
$PMF_LANG["ad_sess_ai_rubrik"] = "カテゴリー:";
$PMF_LANG["ad_sess_ai_artikel"] = "レコード:";
$PMF_LANG["ad_sess_ai_sb"] = "検索文字列:";
$PMF_LANG["ad_sess_ai_sid"] = "セッション ID:";
$PMF_LANG["ad_sess_back"] = "戻る";

// Statistik
$PMF_LANG["ad_rs"] = "評価統計";
$PMF_LANG["ad_rs_rating_1"] = "";
$PMF_LANG["ad_rs_rating_2"] = "ランクのユーザーを見る:";
$PMF_LANG["ad_rs_red"] = "赤";
$PMF_LANG["ad_rs_green"] = "緑";
$PMF_LANG["ad_rs_altt"] = "平均で 2 以下";
$PMF_LANG["ad_rs_ahtf"] = "平均で 2 以上";
$PMF_LANG["ad_rs_no"] = "有効な評価は有効ありません。";

// Auth
$PMF_LANG["ad_auth_insert"] = "ID とパスワードを入力してください。";
$PMF_LANG["ad_auth_user"] = "ID:";
$PMF_LANG["ad_auth_passwd"] = "パスワード:";
$PMF_LANG["ad_auth_ok"] = "OK";
$PMF_LANG["ad_auth_reset"] = "リセット";
$PMF_LANG["ad_auth_fail"] = "IDかパスワードが正しくありません。";
$PMF_LANG["ad_auth_sess"] = "セッション ID が終了しました。";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "環境設定";
$PMF_LANG["ad_config_save"] = "保存";
$PMF_LANG["ad_config_reset"] = "リセット";
$PMF_LANG["ad_config_saved"] = "環境設定の保存に成功しました。";
$PMF_LANG["ad_menu_editconfig"] = "環境設定";
$PMF_LANG["ad_att_none"] = "ファイルの添付ができません。";
$PMF_LANG["ad_att_att"] = "添付:";
$PMF_LANG["ad_att_add"] = "添付ファイル";
$PMF_LANG["ad_entryins_suc"] = "保存に成功しました。";
$PMF_LANG["ad_entryins_fail"] = "エラーが発生しました。";
$PMF_LANG["ad_att_del"] = "削除";
$PMF_LANG["ad_att_nope"] = "添付ファイルは内容の変更中にのみ追加できます。";
$PMF_LANG["ad_att_delsuc"] = "添付ファイルの削除に成功しました。";
$PMF_LANG["ad_att_delfail"] = "添付ファイルの削除中にエラーが発生しました。";
$PMF_LANG["ad_entry_add"] = "FAQ の追加";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "データベースの内容をそのままバックアップします。少なくとも月 1 回のバックアップをするようにしてください。バックアップファイルは MySQL のファイル形式で、phpMyAdmin または、MySQL クライアントからも読むことが可能です。 ";
$PMF_LANG["ad_csv_link"] = "バックアップダウンロード";
$PMF_LANG["ad_csv_head"] = "バックアップ作成";
$PMF_LANG["ad_att_addto"] = "ファイルを添付";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "ファイル:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "ファイルの添付に成功しました。";
$PMF_LANG["ad_att_fail"] = "ファイルの添付中にエラーが発生しました。";
$PMF_LANG["ad_att_close"] = "このウィンドウと閉じる";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "phpMyFAQ でバックアップしたデータをリストアします。リストアする場合、既存のデータは復元することはできません。 ";
$PMF_LANG["ad_csv_file"] = "ファイル";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "ログバックアップ";
$PMF_LANG["ad_csv_linkdat"] = "データバックアップ";
$PMF_LANG["ad_csv_head2"] = "復元する";
$PMF_LANG["ad_csv_no"] = "phpMyFAQのバックアップファイルのフォマットではありません。";
$PMF_LANG["ad_csv_prepare"] = "データーベース照会の準備中...";
$PMF_LANG["ad_csv_process"] = "照会中...";
$PMF_LANG["ad_csv_of"] = "";
$PMF_LANG["ad_csv_suc"] = " が成功しました。";
$PMF_LANG["ad_csv_backup"] = "バックアップ";
$PMF_LANG["ad_csv_rest"] = "バックアップ復元";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "バックアップ";
$PMF_LANG["ad_logout"] = "セッションが終了されました。";
$PMF_LANG["ad_news_add"] = "お知らせの追加";
$PMF_LANG["ad_news_edit"] = "お知らせの変更";
$PMF_LANG["ad_cookie"] = "Cookie";
$PMF_LANG["ad_sess_head"] = "セッション閲覧";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "カテゴリー";
$PMF_LANG["ad_menu_stat"] = "評価統計";
$PMF_LANG["ad_kateg_add"] = "メインカテゴリーの追加";
$PMF_LANG["ad_kateg_rename"] = "名称変更";
$PMF_LANG["ad_adminlog_date"] = "日付";
$PMF_LANG["ad_adminlog_user"] = "ユーザー";
$PMF_LANG["ad_adminlog_ip"] = "IP アドレス";

$PMF_LANG["ad_stat_sess"] = "セッション";
$PMF_LANG["ad_stat_days"] = "日数";
$PMF_LANG["ad_stat_vis"] = "セッション (訪問数)";
$PMF_LANG["ad_stat_vpd"] = "1日中の訪問数";
$PMF_LANG["ad_stat_fien"] = "最初のログ";
$PMF_LANG["ad_stat_laen"] = "最後のログ";
$PMF_LANG["ad_stat_browse"] = "セッション情報表示";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "時間";
$PMF_LANG["ad_sess_sid"] = "セッション ID";
$PMF_LANG["ad_sess_ip"] = "IP アドレス";

$PMF_LANG["ad_ques_take"] = "質問と編集を受け付ける";
$PMF_LANG["no_cats"] = "カテゴリーが見つかりません。";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "IDまたはパスワードが正しくありません。";
$PMF_LANG["ad_log_sess"] = "セッションは終了しました。";
$PMF_LANG["ad_log_edit"] = "-次のユーザーの「ユーザー編集」フォーム: ";
$PMF_LANG["ad_log_crea"] = "「新規記事」フォーム.";
$PMF_LANG["ad_log_crsa"] = "新規エントリーを作成しました。";
$PMF_LANG["ad_log_ussa"] = "次のユーザーのデータを更新しました: ";
$PMF_LANG["ad_log_usde"] = "次のユーザーを削除しました: ";
$PMF_LANG["ad_log_beed"] = "次のユーザーの変更フォーム: ";
$PMF_LANG["ad_log_bede"] = "次のエントリーを削除: ";

$PMF_LANG["ad_start_visits"] = "訪問数";
$PMF_LANG["ad_start_articles"] = "記事数";
$PMF_LANG["ad_start_comments"] = "コメント数";


// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "貼り付け";
$PMF_LANG["ad_categ_cut"] = "切り取り";
$PMF_LANG["ad_categ_copy"] = "コピー";
$PMF_LANG["ad_categ_process"] = "カテゴリー処理中...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>使用権限がありません。</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "前のページ";
$PMF_LANG["msgNextPage"] = "次のページ";
$PMF_LANG["msgPageDoublePoint"] = "ページ: ";
$PMF_LANG["msgMainCategory"] = "メインカテゴリー";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "パスワードを変更しました。";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "PDF ファイルで表示する";
$PMF_LANG["ad_xml_head"] = "XML にバックアップする";
$PMF_LANG["ad_xml_hint"] = "FAQ の全レコードを 1 つの XML ファイルに保存する";
$PMF_LANG["ad_xml_gen"] = "XML ファイルを生成する";
$PMF_LANG["ad_entry_locale"] = "言語";
$PMF_LANG["msgLangaugeSubmit"] = "言語を選択する";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "プレビュー";
$PMF_LANG["ad_attach_1"] = "環境設定から添付ファイルを保存するディレクトリを先に設定してください。";
$PMF_LANG["ad_attach_2"] = "環境設定から添付ファイルのリンクを先に設定してください。";
$PMF_LANG["ad_attach_3"] = "attachment.php ファイルを権限なしではオープンできません。";
$PMF_LANG["ad_attach_4"] = "添付ファイルのサイズは %s バイトより大きくてはいけません。";
$PMF_LANG["ad_menu_export"] = "FAQ エクスポート";
$PMF_LANG["ad_export_1"] = "Built RSS-Feed on";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "エラー: ファイルの書き込みができません。";
$PMF_LANG["ad_export_news"] = "お知らせ RSS フィード";
$PMF_LANG["ad_export_topten"] = "トップ 10 RSS フィード";
$PMF_LANG["ad_export_latest"] = "最新 5 件の RSS フィード";
$PMF_LANG["ad_export_pdf"] = "全レコードの PDF エクスポート";
$PMF_LANG["ad_export_generate"] = "RSS フィードを構築";

$PMF_LANG["rightsLanguage"]['adduser'] = "ユーザーの追加";
$PMF_LANG["rightsLanguage"]['edituser'] = "ユーザーの編集";
$PMF_LANG["rightsLanguage"]['deluser'] = "ユーザーの削除";
$PMF_LANG["rightsLanguage"]['addbt'] = "レコードの追加";
$PMF_LANG["rightsLanguage"]['editbt'] = "レコードの編集";
$PMF_LANG["rightsLanguage"]['delbt'] = "レコードの削除";
$PMF_LANG["rightsLanguage"]['viewlog'] = "ログの閲覧";
$PMF_LANG["rightsLanguage"]['adminlog'] = "管理ログの閲覧";
$PMF_LANG["rightsLanguage"]['delcomment'] = "コメントの削除";
$PMF_LANG["rightsLanguage"]['addnews'] = "ニュースの追加";
$PMF_LANG["rightsLanguage"]['editnews'] = "ニュースの編集";
$PMF_LANG["rightsLanguage"]['delnews'] = "ニュースの削除";
$PMF_LANG["rightsLanguage"]['addcateg'] = "カテゴリーの追加";
$PMF_LANG["rightsLanguage"]['editcateg'] = "カテゴリーの編集";
$PMF_LANG["rightsLanguage"]['delcateg'] = "カテゴリーの削除";
$PMF_LANG["rightsLanguage"]['passwd'] = "パスワードの編集";
$PMF_LANG["rightsLanguage"]['editconfig'] = "構成の編集";
$PMF_LANG["rightsLanguage"]['addatt'] = "添付の追加";
$PMF_LANG["rightsLanguage"]['delatt'] = "添付の削除";
$PMF_LANG["rightsLanguage"]['backup'] = "バックアップの作成";
$PMF_LANG["rightsLanguage"]['restore'] = "バックアップの復元";
$PMF_LANG["rightsLanguage"]['delquestion'] = "開いた質問の削除";
$PMF_LANG["rightsLanguage"]['changebtrevs'] = "改訂の編集";

$PMF_LANG["msgAttachedFiles"] = "添付ファイル:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "操作";
$PMF_LANG["ad_entry_email"] = "メールアドレス:";
$PMF_LANG["ad_entry_allowComments"] = "コメントを許可する";
$PMF_LANG["msgWriteNoComment"] = "このエントリーにコメントできません。";
$PMF_LANG["ad_user_realname"] = "本名:";
$PMF_LANG["ad_export_generate_pdf"] = "PDF ファイル生成";
$PMF_LANG["ad_export_full_faq"] = "FAQ を PDF ファイルにする: ";
$PMF_LANG["err_bannedIP"] = "あなたのIPアドレスからのアクセスは遮断されています。";
$PMF_LANG["err_SaveQuestion"] = "<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>質問</strong>と要求された場合は <strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"Wikipedia で Captcha について読む\" target=\"_blank\">Captcha</a> コード</strong> は必須項目です。<br /><br /><a href=\"javascript:history.back();\">戻る</a><br /><br />";


// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "フォント色: ";
$PMF_LANG["ad_entry_fontsize"] = "フォントサイズ: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF['main.language'] = array(0 => "select", 1 => "言語");
$LANG_CONF["main.languageDetection"] = array(0 => "checkbox", 1 => "言語の自動認識を有効にする");
$LANG_CONF['main.titleFAQ'] = array(0 => "input", 1 => "FAQ の題名");
$LANG_CONF['main.currentVersion'] = array(0 => "print", 1 => "FAQ バージョン");
$LANG_CONF["main.metaDescription"] = array(0 => "input", 1 => "ページの説明");
$LANG_CONF["main.metaKeywords"] = array(0 => "input", 1 => "検索ロボット用キーワード");
$LANG_CONF["main.metaPublisher"] = array(0 => "input", 1 => "管理者名");
$LANG_CONF['main.administrationMail'] = array(0 => "input", 1 => "管理者の電子メールアドレス");
$LANG_CONF["main.contactInformations"] = array(0 => "area", 1 => "問い合わせ情報");
$LANG_CONF["main.send2friendText"] = array(0 => "area", 1 => "友達に送信ページのテキスト");
$LANG_CONF['main.maxAttachmentSize'] = array(0 => "input", 1 => "添付ファイルの最大サイズ (最大 %s バイト)");
$LANG_CONF["main.disableAttachments"] = array(0 => "checkbox", 1 => "エントリーの下に添付のリンクを表示する");
$LANG_CONF["main.enableUserTracking"] = array(0 => "checkbox", 1 => "追跡機能を使用する");
$LANG_CONF["main.enableAdminLog"] = array(0 => "checkbox", 1 => "管理ログを使用する");
$LANG_CONF["main.ipCheck"] = array(0 => "checkbox", 1 => "admin.php で UIN のチェック時に IP アドレスを確認するか");
$LANG_CONF["main.numberOfRecordsPerPage"] = array(0 => "input", 1 => "ページ毎に表示するトピック数");
$LANG_CONF["main.numberOfShownNewsEntries"] = array(0 => "input", 1 => "お知らせの表示数");
$LANG_CONF['main.bannedIPs'] = array(0 => "area", 1 => "拒否する IP アドレス");
$LANG_CONF["main.enableRewriteRules"] = array(0 => "checkbox", 1 => "mod_rewrite のサポートを使用しますか? (初期値: 無効)");
$LANG_CONF["main.ldapSupport"] = array(0 => "checkbox", 1 => "LDAP のサポートを有効にしますか? (初期値: 無効)");
$LANG_CONF["main.referenceURL"] = array(0 => "input", 1 => "リンク確認の基準 URL (例: http://www.example.org/faq)");
$LANG_CONF["main.urlValidateInterval"] = array(0 => "input", 1 => "AJAX リンクの確認間隔 (秒単位)");
$LANG_CONF["records.enableVisibilityQuestions"] = array(0 => "checkbox", 1 => "新しい質問の表示を無効にする");
$LANG_CONF['main.permLevel'] = array(0 => "select", 1 => "パーミッションレベル");

$PMF_LANG["ad_categ_new_main_cat"] = "ROOT の下位カテゴリーへ";
$PMF_LANG["ad_categ_paste_error"] = "このカテゴリーは移動できません。";
$PMF_LANG["ad_categ_move"] = "カテゴリー移動";
$PMF_LANG["ad_categ_lang"] = "言語";
$PMF_LANG["ad_categ_desc"] = "説明";
$PMF_LANG["ad_categ_change"] = "選択したカテゴリーと入れ替え";

$PMF_LANG["lostPassword"] = "パスワードを忘れましたか? その時はここをクリックしてください。";
$PMF_LANG["lostpwd_err_1"] = "エラー: ユーザー名と電子メールアドレスが見つかりません。";
$PMF_LANG["lostpwd_err_2"] = "エラー: 不正な入力です!";
$PMF_LANG["lostpwd_text_1"] = "アカウント情報を要求してくれてありがとうございます。";
$PMF_LANG["lostpwd_text_2"] = "FAQ の管理セクションの中で新しい個人のパスワードを設定してください。";
$PMF_LANG["lostpwd_mail_okay"] = "電子メールを送信しました。";

$PMF_LANG["ad_xmlrpc_button"] = "最新の phpMyFAQ バージョンをウェブで確認する";
$PMF_LANG["ad_xmlrpc_latest"] = "最新バージョンを次のサイトから利用することができます:";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'カテゴリー言語を選択する';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'サイトマップ';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'このエントリーは改訂中で、表示できません。';
$PMF_LANG['msgArticleCategories'] = 'このエントリーのカテゴリー';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = '一意的なソリューション ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ レコード';
$PMF_LANG['ad_entry_new_revision'] = '新しい改訂を作成しますか?';
$PMF_LANG['ad_entry_record_administration'] = 'レコード管理';
$PMF_LANG['ad_entry_changelog'] = '変更履歴';
$PMF_LANG['ad_entry_revision'] = '改訂';
$PMF_LANG['ad_changerev'] = '改訂の選択';
$PMF_LANG['msgCaptcha'] = "画像の中で読める文字を入力してください";
$PMF_LANG['msgSelectCategories'] = '検索 ...';
$PMF_LANG['msgAllCategories'] = '... すべてのカテゴリー';
$PMF_LANG['ad_you_should_update'] = 'Your インストールされている phpMyFAQ は旧式です。最新の利用可能なバージョンに更新するべきです。';
$PMF_LANG['msgAdvancedSearch'] = '高度な検索';

// added v1.6.1 - 2006-04-25 by Matteoï¿½andï¿½Thorsten
$PMF_LANG['spamControlCenter'] = 'スパム制御センター';
$LANG_CONF["spam.enableSafeEmail"] = array(0 => "checkbox", 1 => "安全にユーザーの電子メールを表示する (初期値: 有効)");
$LANG_CONF["spam.checkBannedWords"] = array(0 => "checkbox", 1 => "公開フォームの内容に対する禁止単語を確認する (初期値: 有効)");
$LANG_CONF["spam.enableCaptchaCode"] = array(0 => "checkbox", 1 => "公開フォームの送信を許可するために captcha を使用する (初期値: 有効)");
$PMF_LANG['ad_session_expiring'] = 'セッションの期限切れは %d 分です: 作業しますか?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'セッション管理';
$PMF_LANG['ad_stat_choose'] = '月の選択';
$PMF_LANG['ad_stat_delete'] = '選択されたセッションを直接削除します。';

// added v2.0.0 - 2005-09-15 by Thorsten and by Minoru TODA
$PMF_LANG['ad_menu_glossary'] = '用語集';
$PMF_LANG['ad_glossary_add'] = '用語集のエントリーの追加';
$PMF_LANG['ad_glossary_edit'] = '用語集のエントリーの編集';
$PMF_LANG['ad_glossary_item'] = '項目';
$PMF_LANG['ad_glossary_definition'] = '定義';
$PMF_LANG['ad_glossary_save'] = 'エントリーの保存';
$PMF_LANG['ad_glossary_save_success'] = '用語集のエントリーの保存に成功しました!';
$PMF_LANG['ad_glossary_save_error'] = '用語集のエントリーはエラーが発生したために保存できませんでした。';
$PMF_LANG['ad_glossary_update_success'] = '用語集のエントリーの更新に成功しました!';
$PMF_LANG['ad_glossary_update_error'] = '用語集のエントリーはエラーが発生したために更新できませんでした。';
$PMF_LANG['ad_glossary_delete'] = 'エントリーの削除';
$PMF_LANG['ad_glossary_delete_success'] = '用語集のエントリーの削除に成功しました!';
$PMF_LANG['ad_glossary_delete_error'] = '用語集のエントリーはエラーが発生したために削除できませんでした。';
$PMF_LANG['ad_linkcheck_noReferenceURL'] = 'リンクの自動チェック機能は無効です (環境設定で基点URLを指定してください)';
$PMF_LANG['ad_linkcheck_noAllowUrlOpen'] = 'リンクの自動チェック機能は無効です (allow_url_fopen PHP オプションが設定されていません)';
$PMF_LANG['ad_linkcheck_checkResult'] = 'エントリー内のリンク確認結果';
$PMF_LANG['ad_linkcheck_checkSuccess'] = '成功';
$PMF_LANG['ad_linkcheck_checkFailed'] = '失敗';
$PMF_LANG['ad_linkcheck_failReason'] = 'リンクの自動チェックに失敗した理由:';
$PMF_LANG['ad_linkcheck_noLinksFound'] = '自動チェックできる種類のリンクはみつかりませんでした。';
$PMF_LANG['ad_linkcheck_searchbadonly'] = 'リンク破損項目のみ';
$PMF_LANG['ad_linkcheck_infoReason'] = '追加情報:';
$PMF_LANG['ad_linkcheck_openurl_infoprefix'] = '<strong>%s</strong>の確認中に検出: ';
$PMF_LANG['ad_linkcheck_openurl_notready'] = 'LinkVerifierの動作条件が揃っていません。';
$PMF_LANG['ad_linkcheck_openurl_maxredirect'] = '他サイトへのリダイレクト数が上限の <strong>%d 回</strong> を超えました.';
$PMF_LANG['ad_linkcheck_openurl_urlisblank'] = 'リダイレクト先が未指定です。';
$PMF_LANG['ad_linkcheck_openurl_tooslow'] = '<strong>%s</strong> は遅いか応答していません。';
$PMF_LANG['ad_linkcheck_openurl_nodns'] = '<strong>%s</strong> は遅いかDNSの登録がありません。';
$PMF_LANG['ad_linkcheck_openurl_redirected'] = 'URLは <strong>%s</strong> にリダイレクトされました。';
$PMF_LANG['ad_linkcheck_openurl_ambiguous'] = 'サーバーは不確定な応答 <strong>%s</strong> を返しました。';
$PMF_LANG['ad_linkcheck_openurl_not_allowed'] = '<em>HEAD</em> メソッドはこのホスト(<strong>%s</strong>)ではサポートしていません。許可されたメソッド: <strong>%s</strong>';
$PMF_LANG['ad_linkcheck_openurl_not_found'] = 'このリソースはホスト「<strong>%s</strong>」を見つけることができませんでした。';
$PMF_LANG['ad_linkcheck_protocol_unsupported'] = '%s プロトコルの自動チェックには対応していません。';
$PMF_LANG['ad_menu_linkconfig'] = 'URL 確認';
$PMF_LANG['ad_linkcheck_config_title'] = 'URL 確認設定';
$PMF_LANG['ad_linkcheck_config_disabled'] = 'URL 確認機能を無効にしました';
$PMF_LANG['ad_linkcheck_config_warnlist'] = '警告の URL';
$PMF_LANG['ad_linkcheck_config_ignorelist'] = '無視する URL';
$PMF_LANG['ad_linkcheck_config_warnlist_description'] = 'URLs prefixed with items below will be issued warning regardless of whether it is valid.<br />Use this feature to detect soon-to-be defunct URLs';
$PMF_LANG['ad_linkcheck_config_ignorelist_description'] = '下の一覧に一致する URL は検査なく有効(警告、無効)とみなされるでしょう。<br />Use this feature to omit URLs that fail to validate using URL Verifier';
$PMF_LANG['ad_linkcheck_config_th_id'] = 'ID#';
$PMF_LANG['ad_linkcheck_config_th_url'] = '一致した URL';
$PMF_LANG['ad_linkcheck_config_th_reason'] = '一致した理由';
$PMF_LANG['ad_linkcheck_config_th_owner'] = 'エントリーの所有者';
$PMF_LANG['ad_linkcheck_config_th_enabled'] = 'エントリーを有効の設定';
$PMF_LANG['ad_linkcheck_config_th_locked'] = '所有者のロックの設定';
$PMF_LANG['ad_linkcheck_config_th_chown'] = '所有者の観察の設定';
$PMF_LANG['msgNewQuestionVisible'] = '質問は公開の前にはじめにレビューします。';
$PMF_LANG['msgQuestionsWaiting'] = '管理者による公開を待っています: ';
$PMF_LANG['ad_entry_visibility'] = '公開しますか?';

// added v2.0.0 - 2006-01-02 by Lars
$PMF_LANG['ad_user_error_password'] =  "パスワードを入力してください。 ";
$PMF_LANG['ad_user_error_passwordsDontMatch'] =  "パスワードが一致しません。";
$PMF_LANG['ad_user_error_loginInvalid'] =  "指定されたユーザーIDは無効です。";
$PMF_LANG['ad_user_error_noEmail'] =  "有効なメールアドレスを入力してください。";
$PMF_LANG['ad_user_error_noRealName'] =  "本名を入力してください。";
$PMF_LANG['ad_user_error_delete'] =  "ユーザーアカウントを削除できませんでした。";
$PMF_LANG['ad_user_error_noId'] =  "ID を指定していません。";
$PMF_LANG['ad_user_error_protectedAccount'] =  "このユーザーは保護されています。";
$PMF_LANG['ad_user_deleteUser'] = "ユーザーの削除";
$PMF_LANG['ad_user_status'] = "状態:";
$PMF_LANG['ad_user_lastModified'] = "最終変更日時:";
$PMF_LANG['ad_gen_cancel'] = "取り消す";
$PMF_LANG["rightsLanguage"]['addglossary'] = "用語の追加";
$PMF_LANG["rightsLanguage"]['editglossary'] = "用語の編集";
$PMF_LANG["rightsLanguage"]['delglossary'] = "用語の削除";
$PMF_LANG["ad_menu_group_administration"] = "グループの管理";
$PMF_LANG['ad_user_loggedin'] = '次の名前でログイン中: ';

$PMF_LANG['ad_group_details'] = "グループの詳細";
$PMF_LANG['ad_group_add'] = "グループの追加";
$PMF_LANG['ad_group_add_link'] = "グループを追加する";
$PMF_LANG['ad_group_name'] = "名称:";
$PMF_LANG['ad_group_description'] = "説明:";
$PMF_LANG['ad_group_autoJoin'] = "自動参加:";
$PMF_LANG['ad_group_suc'] = "グループの追加に<strong>成功</strong>しました。";
$PMF_LANG['ad_group_error_noName'] = "グループの名称を指定してください。";
$PMF_LANG['ad_group_error_delete'] = "グループを削除できません。";
$PMF_LANG['ad_group_deleted'] = "グループを削除しました。";
$PMF_LANG['ad_group_deleteGroup'] = "グループの削除";
$PMF_LANG['ad_group_deleteQuestion'] = "このグループを本当に削除しますか？";
$PMF_LANG['ad_user_uncheckall'] = "全て解除";
$PMF_LANG['ad_group_membership'] = "グループのメンバー設定";
$PMF_LANG['ad_group_members'] = "グループのメンバー一覧";
$PMF_LANG['ad_group_addMember'] = "追加 &gt;&gt;";
$PMF_LANG['ad_group_removeMember'] = "&lt;&lt; 削除";

// added v2.0.0 - 2006-07-20 by Matteo
$PMF_LANG['ad_export_which_cat'] = 'エクスポートする FAQ データの制限 (オプション)';
$PMF_LANG['ad_export_cat_downwards'] = 'ダウンロードしますか?';
$PMF_LANG['ad_export_type'] = 'エクスポートの形式';
$PMF_LANG['ad_export_type_choose'] = 'サポートされる形式をひとつ選びます:';
$PMF_LANG['ad_export_download_view'] = '員ファイン表示かダウンロードしますか?';
$PMF_LANG['ad_export_download'] = 'ダウンロード';
$PMF_LANG['ad_export_view'] = 'インライン表示';
$PMF_LANG['ad_export_gen_xhtml'] = 'XHTML ファイルを作成する';
$PMF_LANG['ad_export_gen_docbook'] = 'Docbook ファイルを作成する';

// added v2.0.0 - 2006-07-22 by Matteo
$PMF_LANG['ad_news_data'] = 'ニュースデータ';
$PMF_LANG['ad_news_author_name'] = '制作者名:';
$PMF_LANG['ad_news_author_email'] = '制作電子メール:';
$PMF_LANG['ad_news_set_active'] = '有効化:';
$PMF_LANG['ad_news_allowComments'] = 'コメントの許可:';
$PMF_LANG['ad_news_expiration_window'] = 'News expiration time window (optional)';
$PMF_LANG['ad_news_from'] = '差出人:';
$PMF_LANG['ad_news_to'] = '宛先:';
$PMF_LANG['ad_news_insertfail'] = 'データベースにニュース項目を追加中にエラーです。';
$PMF_LANG['ad_news_updatefail'] = 'データエースのニュース項目を更新中にエラーです。';
$PMF_LANG['newsShowCurrent'] = '現在のニュースを表示します。';
$PMF_LANG['newsShowArchive'] = '書庫化されたニュースを表示します。';
$PMF_LANG['newsArchive'] = ' ニュース書庫';
$PMF_LANG['newsWriteComment'] = 'このエントリーのコメント';
$PMF_LANG['newsCommentDate'] = 'Added at: ';

// added v2.0.0 - 2006-07-29 by Matteo & Thorsten
$PMF_LANG['ad_record_expiration_window'] = 'Record expiration time window (オプション)';
$PMF_LANG['admin_mainmenu_home'] = 'ダッシュボード';
$PMF_LANG['admin_mainmenu_users'] = 'ユーザー';
$PMF_LANG['admin_mainmenu_content'] = 'コンテンツ';
$PMF_LANG['admin_mainmenu_statistics'] = '統計';
$PMF_LANG['admin_mainmenu_exports'] = 'エクスポート';
$PMF_LANG['admin_mainmenu_backup'] = 'バップアップ';
$PMF_LANG['admin_mainmenu_configuration'] = '設定';
$PMF_LANG['admin_mainmenu_logout'] = 'ログアウト';

// added v2.0.0 - 2006-08-15 by Thorsten and Matteo
$PMF_LANG["ad_categ_owner"] = 'カテゴリー所有者';
$PMF_LANG['adminSection'] = '管理';
$PMF_LANG['err_expiredArticle'] = 'このエントリーは期限切れで表示できません';
$PMF_LANG['err_expiredNews'] = 'このニュースは期限切れで表示できません';
$PMF_LANG['err_inactiveNews'] = 'このニュースは改訂中で表示できません';
$PMF_LANG['msgSearchOnAllLanguages'] = 'すべての言語での検索:';
$PMF_LANG['ad_entry_tags'] = 'タグ';
$PMF_LANG['msg_tags'] = 'タグ';

// added v2.0.0 - 2006-09-03 by Matteo
$PMF_LANG['ad_linkcheck_feedback_url-batch1'] = '確認中...';
$PMF_LANG['ad_linkcheck_feedback_url-batch2'] = '確認中...';
$PMF_LANG['ad_linkcheck_feedback_url-batch3'] = '確認中...';
$PMF_LANG['ad_linkcheck_feedback_url-checking'] = '確認中...';
$PMF_LANG['ad_linkcheck_feedback_url-disabled'] = 'Disabled';
$PMF_LANG['ad_linkcheck_feedback_url-linkbad'] = 'Links KO';
$PMF_LANG['ad_linkcheck_feedback_url-linkok'] = 'Links OK';
$PMF_LANG['ad_linkcheck_feedback_url-noaccess'] = 'アクセスがありません';
$PMF_LANG['ad_linkcheck_feedback_url-noajax'] = 'AJAX がありません';
$PMF_LANG['ad_linkcheck_feedback_url-nolinks'] = 'リンクがありません';
$PMF_LANG['ad_linkcheck_feedback_url-noscript'] = 'スクリプトがありません';

// added v2.0.0 - 2006-09-02 by Thomas
$PMF_LANG['msg_related_articles'] = '関連エントリー';
$LANG_CONF['records.numberOfRelatedArticles'] = array(0 => "input", 1 => "関連エントリーの数");

// added v2.0.0 - 2006-09-09 by Rudi
$PMF_LANG['ad_categ_trans_1'] = '翻訳';
$PMF_LANG['ad_categ_trans_2'] = 'カテゴリー';
$PMF_LANG['ad_categ_translatecateg'] = '翻訳カテゴリー';
$PMF_LANG['ad_categ_translate'] = '翻訳';
$PMF_LANG['ad_categ_transalready'] = 'Already translated in: ';
$PMF_LANG["ad_categ_deletealllang"] = 'すべての言語を削除しますか?';
$PMF_LANG["ad_categ_deletethislang"] = 'この言語のみ削除しますか?';
$PMF_LANG["ad_categ_translated"] = "カテゴリーを翻訳しました。";

// added v2.0.0 - 2006-09-21 by Rudi
$PMF_LANG["ad_categ_show"] = "概要";
$PMF_LANG['ad_menu_categ_structure'] = "言語を含むカテゴリー概要";

// added v2.0.0 - 2006-09-26 by Thorsten
$PMF_LANG['ad_entry_userpermission'] = 'ユーザー権限:';
$PMF_LANG['ad_entry_grouppermission'] = 'グループ権限:';
$PMF_LANG['ad_entry_all_users'] = 'すべてのユーザーのアクセス';
$PMF_LANG['ad_entry_restricted_users'] = '次のアクセスを制限しました:';
$PMF_LANG['ad_entry_all_groups'] = 'すべてのグループのアクセス';
$PMF_LANG['ad_entry_restricted_groups'] = '次のアクセスを制限しました:';
$PMF_LANG['ad_session_expiration'] = 'セッションの期限切れ時間';
$PMF_LANG['ad_user_active'] = '有効';
$PMF_LANG['ad_user_blocked'] = 'ブロック';
$PMF_LANG['ad_user_protected'] = '保護';

// added v2.0.0 - 2006-10-07 by Matteo
$PMF_LANG['ad_entry_intlink'] = 'リンクとして挿入する FAQ レコードを選択します...';

//added 2.0.0 - 2006-10-10 by Rudi
$PMF_LANG["ad_categ_paste2"] = "Paste after";
$PMF_LANG["ad_categ_remark_move"] = "ふたつのカテゴリーの交換は同じレベルでのみできます。";
$PMF_LANG["ad_categ_remark_overview"] = "カテゴリーがすべて実際の言語に対して定義された場合、カテゴリーの順序は正しく表示されるでしょう。 (はじめのカラム).";

// added v2.0.0 - 2006-10-15 by Matteo
$PMF_LANG['msgUsersOnline'] = '%d 人のゲストと %d 人の登録者です';
$PMF_LANG['ad_adminlog_del_older_30d'] = '30日より古いログをすぐに削除する';
$PMF_LANG['ad_adminlog_delete_success'] = 'より古いログの削除に成功しました。';
$PMF_LANG['ad_adminlog_delete_failure'] = '削除したログはありません: 要求の実行時にエラーが発生しました。';

// added 2.0.0 - 2006-11-19 by Thorsten
$PMF_LANG['opensearch_plugin_install'] = '検索プラグインの追加';
$PMF_LANG['ad_quicklinks'] = 'Quicklinks';
$PMF_LANG['ad_quick_category'] = '新規カテゴリーの追加';
$PMF_LANG['ad_quick_record'] = '新規 FAQ レコードの追加';
$PMF_LANG['ad_quick_user'] = '新規ユーザーの追加';
$PMF_LANG['ad_quick_group'] = '新規グループの追加';

// added v2.0.0 - 2006-12-30 by Matteo
$PMF_LANG['msgNewTranslationHeader'] = '翻訳の承認';
$PMF_LANG['msgNewTranslationAddon'] = 'Your proposal will not be published right away, but will be released by the administrator upon receipt. Required  fields are <strong>your Name</strong>, <strong>your email address</strong>, <strong>your headline translation</strong> and <strong>your faq translation</strong>. Please separate the keywords with space only.';
$PMF_LANG['msgNewTransSourcePane'] = '元ペイン';
$PMF_LANG['msgNewTranslationPane'] = '翻訳ペイン';
$PMF_LANG['msgNewTranslationName'] = "名前:";
$PMF_LANG['msgNewTranslationMail'] = "電子メールアドレス:";
$PMF_LANG['msgNewTranslationKeywords'] = "キーワード:";
$PMF_LANG['msgNewTranslationSubmit'] = '提案の送信';
$PMF_LANG['msgTranslate'] = 'Propose a translation for';
$PMF_LANG['msgTranslateSubmit'] = '翻訳の開始...';
$PMF_LANG['msgNewTranslationThanks'] = "翻訳の提案ありがとうございます!";

// added v2.0.0 - 2007-02-27 by Matteo
$PMF_LANG["rightsLanguage"]['addgroup'] = "グループアカウントの追加";
$PMF_LANG["rightsLanguage"]['editgroup'] = "グループアカウントの編集";
$PMF_LANG["rightsLanguage"]['delgroup'] = "グループアカウントの削除";

// added v2.0.0 - 2007-02-27 by Thorsten
$PMF_LANG['ad_news_link_parent'] = '親ウィンドウでリンクを開く';

// added v2.0.0 - 2007-03-04 by Thorsten
$PMF_LANG['ad_menu_comments'] = 'コメント';
$PMF_LANG['ad_comment_administration'] = 'コメント管理';
$PMF_LANG['ad_comment_faqs'] = 'FAQ レコードのコメント:';
$PMF_LANG['ad_comment_news'] = '新規レコードのコメント:';
$PMF_LANG['ad_groups'] = 'グループ';

// added v2.0.0 - 2007-03-10 by Thorsten
$LANG_CONF['records.orderby'] = array(0 => 'select', 1 => 'レコードの並び替え (優先度に従う)');
$LANG_CONF['records.sortby'] = array(0 => 'select', 1 => 'レコードの並び替え (降順もしくは昇順)');
$PMF_LANG['ad_conf_order_id'] = 'ID (デフォルト)';
$PMF_LANG['ad_conf_order_thema'] = '題名';
$PMF_LANG['ad_conf_order_visits'] = '訪問者数';
$PMF_LANG['ad_conf_order_datum'] = '日付';
$PMF_LANG['ad_conf_order_author'] = '制作者';
$PMF_LANG['ad_conf_desc'] = '降順';
$PMF_LANG['ad_conf_asc'] = '昇順';
$PMF_LANG['mainControlCenter'] = 'メイン設定';
$PMF_LANG['recordsControlCenter'] = 'FAQ レコード設定';

// added v2.0.0 - 2007-03-17 by Thorsten
$PMF_LANG['msgInstantResponse'] = '即時回答';
$PMF_LANG['msgInstantResponseMaxRecords'] = '. 下の %d 件を見つけました。';

// added v2.0.0 - 2007-03-29 by Thorsten
$LANG_CONF['records.defaultActivation'] = array(0 => "checkbox", 1 => "新規レコードを有効にする (初期値: 無効)");
$LANG_CONF['records.defaultAllowComments'] = array(0 => "checkbox", 1 => "新規レコードへのコメントを許可する (初期値: 許可しない)");

// added v2.0.0 - 2007-04-04 by Thorsten
$PMF_LANG['msgAllCatArticles'] = 'このカテゴリー内のレコード';
$PMF_LANG['msgDescriptionInstantResponse'] = 'まず入力して答えを見つけてください ...';
$PMF_LANG['msgTagSearch'] = 'タグ付けされたエントリー';
$PMF_LANG['ad_pmf_info'] = 'phpMyFAQ 情報';
$PMF_LANG['ad_online_info'] = 'オンラインのバージョン確認';
$PMF_LANG['ad_system_info'] = 'システム情報';

// added 2.5.0-alpha - 2008-01-25 by Elger
$PMF_LANG['msgRegisterUser'] = '登録したいですか?';
$PMF_LANG["ad_user_loginname"] = 'ログイン名:';
$PMF_LANG['errorRegistration'] = 'この項目は必須です!';
$PMF_LANG['submitRegister'] = '登録';
$PMF_LANG['msgUserData'] = '登録にはユーザー情報が必要です';
$PMF_LANG['captchaError'] = '次のキャプチャーコードを入力してください!';
$PMF_LANG['msgRegError'] = '次のエラーが発生しました。正しくしてください:';
$PMF_LANG['successMessage'] = '登録に成功しました。ログインデータを含む確認メールを受け取るでしょう!';
$PMF_LANG['msgRegThankYou'] = '登録ありがとうございます';
$PMF_LANG['emailRegSubject'] = '[%sitename%] 登録: 新規ユーザー';

// added 2.5.0-alpha2 - 2009-01-24 by Thorsten
$PMF_LANG['msgMostPopularSearches'] = 'もっとも人気の検索:';
$LANG_CONF['main.enableWysiwygEditor'] = array(0 => "checkbox", 1 => "同梱の WYSIWYG エディターを有効にする (初期値: 有効)");

// added 2.5.0-beta - 2009-03-30 by Anatoliy
$PMF_LANG['ad_menu_searchstats'] = '統計の検索';
$PMF_LANG['ad_searchstats_search_term'] = 'キーワード';
$PMF_LANG['ad_searchstats_search_term_count'] = '回数';
$PMF_LANG['ad_searchstats_search_term_lang'] = '言語';
$PMF_LANG['ad_searchstats_search_term_percentage'] = '達成率';

// added 2.5.0-beta - 2009-03-31 by Anatoliy
$PMF_LANG['ad_record_sticky'] = 'スティッキー';
$PMF_LANG['ad_entry_sticky'] = 'スティッキー';
$PMF_LANG['stickyRecordsHeader'] = 'Sticky FAQs';

// added 2.5.0-beta - 2009-04-01 by Anatoliy
$PMF_LANG['ad_menu_stopwordsconfig'] = 'Stop Words';
$PMF_LANG['ad_config_stopword_input'] = 'Add new stop word';

// added 2.5.0-beta - 2009-04-06 by Anatoliy
$PMF_LANG['msgSendMailDespiteEverything'] = 'No, there is still no adequate answer (will send the mail)';
$PMF_LANG['msgSendMailIfNothingIsFound'] = 'Is the wanted answer listed in the results above?';

// added 2.5.0-RC - 2009-05-11 by Anatoliy & Thorsten
$PMF_LANG['msgChooseLanguageToTranslate'] = '翻訳する言語を選んでください';
$PMF_LANG['msgLangDirIsntWritable'] = '翻訳ディレクトリーを書き込めません';
$PMF_LANG['ad_menu_translations'] = '翻訳インターフェース';
$PMF_LANG['ad_start_notactive'] = '有効化待ち';

// added 2.5.0-RC - 2009-05-20 by Anatoliy
$PMF_LANG['msgTransToolAddNewTranslation'] = '新規翻訳の追加';
$PMF_LANG['msgTransToolLanguage'] = '言語';
$PMF_LANG['msgTransToolActions'] = '操作';
$PMF_LANG['msgTransToolWritable'] = '書き込み可能';
$PMF_LANG['msgEdit'] = '編集';
$PMF_LANG['msgDelete'] = '削除';
$PMF_LANG['msgYes'] = 'はい';
$PMF_LANG['msgNo'] = 'いいえ';
$PMF_LANG['msgTransToolSureDeleteFile'] = 'Are you sure this language file must be deleted?';
$PMF_LANG['msgTransToolFileRemoved'] = '言語ファイルの削除に成功しました';
$PMF_LANG['msgTransToolErrorRemovingFile'] = '言語ファイルの削除中にエラーです';
$PMF_LANG['msgVariable'] = 'Variable';
$PMF_LANG['msgCancel'] = '取り消し';
$PMF_LANG['msgSave'] = '保存';
$PMF_LANG['msgSaving3Dots'] = '保存しています ...';
$PMF_LANG['msgRemoving3Dots'] = '削除しています ...';
$PMF_LANG['msgTransToolFileSaved'] = '言語ファイルの保存に成功しました';
$PMF_LANG['msgTransToolErrorSavingFile'] = '言語ファイルの保存中にエラーです';
$PMF_LANG['msgLanguage'] = '言語';
$PMF_LANG['msgTransToolLanguageCharset'] = '言語の文字セット';
$PMF_LANG['msgTransToolLanguageDir'] = '言語の方向';
$PMF_LANG['msgTransToolLanguageDesc'] = '言語の説明';
$PMF_LANG['msgAuthor'] = '製作者';
$PMF_LANG['msgTransToolAddAuthor'] = '製作者の追加';
$PMF_LANG['msgTransToolCreateTranslation'] = '翻訳の作成';
$PMF_LANG['msgTransToolTransCreated'] = '新規翻訳の作成に成功しました';
$PMF_LANG['msgTransToolCouldntCreateTrans'] = '新規翻訳の作成ができません';
$PMF_LANG['msgAdding3Dots'] = '追加しています ...';
$PMF_LANG['msgTransToolSendToTeam'] = 'phpMyFAQ チームに送る';
$PMF_LANG['msgSending3Dots'] = '送信しています ...';
$PMF_LANG['msgTransToolFileSent'] = '言語ファイルを phpMyFAQ チームに送ることに成功しました。共有をありがとうございます。';
$PMF_LANG['msgTransToolErrorSendingFile'] = '翻訳ファイルの送信中にエラーです';
$PMF_LANG['msgTransToolPercent'] = '達成率';

// added 2.5.0-RC3 - 2009-06-23 by Anatoliy
$LANG_CONF['main.attachmentsPath'] = array(0 => "input", 1 => "Path where attachments will be saved.<br /><small>Relative path means a folder within web root</small>");

// added 2.5.0-RC3 - 2009-06-24 by Anatoliy
$PMF_LANG['msgAttachmentNotFound'] = "The file you're trying to download was not found on this server";
$PMF_LANG['ad_sess_noentry'] = "エントリーがありません";

// added 2.6.0-alpha - 2009-07-30 by Aurimas Fišeras
//P.S. "One User online" is also possible, since sprintf just ignores extra args
$PMF_LANG["plmsgUserOnline"][0] = "%d 人がオンライン";
$PMF_LANG["plmsgUserOnline"][1] = "%d 人がオンライン";

// added 2.6.0-alpha - 2009-08-02 by Anatoliy
$LANG_CONF['main.templateSet'] = array(0 => "select", 1 => "使用するテンプレート");

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras
$PMF_LANG['msgTransToolRemove'] = '削除';
$PMF_LANG["msgTransToolLanguageNumberOfPlurals"] = "Number of plural forms";
$PMF_LANG['msgTransToolLanguageOnePlural'] = 'This language has only one plural form';
$PMF_LANG['msgTransToolLanguagePluralNotSet'] = "Plural form support for language %s is disabled (nplurals not set)";

// added 2.6.0-alpha - 2009-08-16 by Aurimas Fišeras - Plural messages
$PMF_LANG["plmsgHomeArticlesOnline"][0] = "%d 件の FAQ があります";
$PMF_LANG["plmsgHomeArticlesOnline"][1] = "%d 件の FAQ があります";
$PMF_LANG["plmsgViews"][0] = "%d 回の閲覧";
$PMF_LANG["plmsgViews"][1] = "%d 回の閲覧";

// added 2.6.0-alpha - 2009-08-30 by Aurimas Fišeras - Plural messages
$PMF_LANG['plmsgGuestOnline'][0] = '%d 人のゲスト';
$PMF_LANG['plmsgGuestOnline'][1] = '%d 人のゲスト';
$PMF_LANG['plmsgRegisteredOnline'][0] = ' and %d Registered';
$PMF_LANG['plmsgRegisteredOnline'][1] = ' and %d Registered';
$PMF_LANG["plmsgSearchAmount"][0] = "%d 件の検索結果";
$PMF_LANG["plmsgSearchAmount"][1] = "%d 件の検索結果";
$PMF_LANG["plmsgPagesTotal"][0] = " %d ページ";
$PMF_LANG["plmsgPagesTotal"][1] = " %d ページ";
$PMF_LANG["plmsgVotes"][0] = "%d 件の投票";
$PMF_LANG["plmsgVotes"][1] = "%d 件の投票";
$PMF_LANG["plmsgEntries"][0] = "%d FAQ";
$PMF_LANG["plmsgEntries"][1] = "%d FAQ";

// added 2.6.0-alpha - 2009-09-06 by Aurimas Fišeras
$PMF_LANG["rightsLanguage"]['addtranslation'] = "翻訳の追加";
$PMF_LANG["rightsLanguage"]['edittranslation'] = "翻訳の編集";
$PMF_LANG["rightsLanguage"]['deltranslation'] = "翻訳の削除";
$PMF_LANG["rightsLanguage"]['approverec'] = "レコードの承認";

// added 2.6.0-alpha - 2009-09-9 by Anatoliy Belsky
$LANG_CONF["main.enableAttachmentEncryption"] = array(0 => "checkbox", 1 => "添付の暗号化を有効にする <br><small>添付が無効のときは無視</small>");
$LANG_CONF["main.defaultAttachmentEncKey"] = array(0 => "input", 1 => '標準の添付暗号化鍵 <br><small>添付の暗号化が無効の場合は無視</small><br><small><font color="red">警告: Do not change this once set and enabled file encryption!!!</font></small>');
//$LANG_CONF["main.attachmentsStorageType"] = array(0 => "select", 1 => "Attachment storage type");
//$PMF_LANG['att_storage_type'][0] = 'Filesystem';
//$PMF_LANG['att_storage_type'][1] = 'Database';

// added 2.6.0-alpha - 2009-09-06 by Thorsten
$PMF_LANG['ad_menu_upgrade'] = 'アップグレード';
$PMF_LANG['ad_you_shouldnt_update'] = 'phpMyFAQ のバージョンは最新です。アップグレードの必要はありません。';
$LANG_CONF['main.useSslForLogins'] = array(0 => 'checkbox', 1 => "セキュア接続のログインのみ許可しますか? (初期値: 無効)");
$PMF_LANG['msgSecureSwitch'] = "セキュア モードのログインに切り替えます!";

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
$LANG_CONF['search.numberSearchTerms']   = array(0 => 'input', 1 => '一覧に出す検索件数');
$LANG_CONF['main.orderingPopularFaqs'] = array(0 => "select", 1 => "トップ FAQ の並び替え方法");
$PMF_LANG['list_all_users']            = '全ユーザーの一覧';

$PMF_LANG['main.orderingPopularFaqs.visits'] = "最も訪問があるエントリーの一覧";
$PMF_LANG['main.orderingPopularFaqs.voting'] = "最も投票のあるエントリーの一覧";

// added 2.6.0-alpha - 2009-11-05 by Thorsten
$PMF_LANG['msgShowHelp'] = '単語をカンマで区切ってください。';

// added 2.6.0-RC - 2009-11-30 by Thorsten
$PMF_LANG['msgUpdateFaqDate'] = '更新';
$PMF_LANG['msgKeepFaqDate'] = '維持'; 
$PMF_LANG['msgEditFaqDat'] = '編集';
$LANG_CONF['main.optionalMailAddress'] = array(0 => 'checkbox', 1 => 'Mail address as mandatory field (default: deactivated)');
$LANG_CONF['search.useAjaxSearchOnStartpage'] = array(0 => 'checkbox', 1 => 'Instant Response on startpage (default: deactivated)');
