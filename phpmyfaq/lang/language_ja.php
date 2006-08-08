<?php
/**
* $Id: language_ja.php,v 1.17 2006-08-08 18:58:19 thorstenr Exp $
*
* The Japanese language file -
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Tadashi Jokagi <elf2000@users.sourceforge.net>
* @since        2004-02-19
* @copyright    (c) 2004 - 2006 phpMyFAQ Team
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
//  EN-Revision: 1.6.2.9.2.22 (元になったlanguage_en.phpのリビジョン番号．cvs diff -r <REV> language_en.phpで差分を取る)

$PMF_LANG["metaCharset"] = "UTF-8";
$PMF_LANG["metaLanguage"] = "ja";
$PMF_LANG["language"] = "日本語";
$PMF_LANG["dir"] = "ltr"; // ltr: left to right (e.g. English language); rtl: right to left (e.g. Arabic language)

// Navigation
$PMF_LANG["msgCategory"] = "カテゴリ";
$PMF_LANG["msgShowAllCategories"] = "全カテゴリを表示する";
$PMF_LANG["msgSearch"] = "検索";
$PMF_LANG["msgAddContent"] = "FAQ の追加";
$PMF_LANG["msgQuestion"] = "質問する";
$PMF_LANG["msgOpenQuestions"] = "質問に答える";
$PMF_LANG["msgHelp"] = "ヘルプ";
$PMF_LANG["msgContact"] = "問い合わせ";
$PMF_LANG["msgHome"] = "ホーム";
$PMF_LANG["msgNews"] = "お知らせ";
$PMF_LANG["msgUserOnline"] = " ユーザーがオンライン";
$PMF_LANG["msgXMLExport"] = "XML ファイル";
$PMF_LANG["msgBack2Home"] = "ホームページに戻る";

// Contentpages
$PMF_LANG["msgFullCategories"] = "カテゴリ";
$PMF_LANG["msgFullCategoriesIn"] = "カテゴリ";
$PMF_LANG["msgSubCategories"] = "下位カテゴリ";
$PMF_LANG["msgEntries"] = "個のエントリ";
$PMF_LANG["msgEntriesIn"] = "カテゴリ名: ";
$PMF_LANG["msgViews"] = "回の閲覧";
$PMF_LANG["msgPage"] = "ページ ";
$PMF_LANG["msgPages"] = "ページ中";
$PMF_LANG["msgPrevious"] = "前へ";
$PMF_LANG["msgNext"] = "次へ";
$PMF_LANG["msgCategoryUp"] = "上位カテゴリへ";
$PMF_LANG["msgLastUpdateArticle"] = "最終更新: ";
$PMF_LANG["msgAuthor"] = "作成者: ";
$PMF_LANG["msgPrinterFriendly"] = "印刷用バージョン";
$PMF_LANG["msgPrintArticle"] = "このレコードを印刷する";
$PMF_LANG["msgMakeXMLExport"] = "XML ファイルエクスポート";
$PMF_LANG["msgAverageVote"] = "評価点数:";
$PMF_LANG["msgVoteUseability"] = "このエントリを評価してください:";
$PMF_LANG["msgVoteFrom"] = " - ";
$PMF_LANG["msgVoteBad"] = "まったく役に立たない";
$PMF_LANG["msgVoteGood"] = "とても役に立った";
$PMF_LANG["msgVotings"] = "個の投票 ";
$PMF_LANG["msgVoteSubmit"] = "投票";
$PMF_LANG["msgVoteThanks"] = "投票ありがとうございます!";
$PMF_LANG["msgYouCan"] = "";
$PMF_LANG["msgWriteComment"] = "このエントリにコメントする";
$PMF_LANG["msgShowCategory"] = "内容の概要: ";
$PMF_LANG["msgCommentBy"] = "コメント作成は";
$PMF_LANG["msgCommentHeader"] = "このエントリにコメント";
$PMF_LANG["msgYourComment"] = "あなたのコメント:";
$PMF_LANG["msgCommentThanks"] = "コメントありがとうございます!";
$PMF_LANG["msgSeeXMLFile"] = "XML ファイルを開く";
$PMF_LANG["msgSend2Friend"] = "友達に教える";
$PMF_LANG["msgS2FName"] = "名前:";
$PMF_LANG["msgS2FEMail"] = "メールアドレス:";
$PMF_LANG["msgS2FFriends"] = "あなたの友達:";
$PMF_LANG["msgS2FEMails"] = ". 電子メールアドレス:";
$PMF_LANG["msgS2FText"] = "追加して送るテキストを入力してください:";
$PMF_LANG["msgS2FText2"] = "次の URL からこの内容が確認できます:";
$PMF_LANG["msgS2FMessage"] = "友達への補足メッセージ:";
$PMF_LANG["msgS2FButton"] = "メール送信";
$PMF_LANG["msgS2FThx"] = "推薦してくれてありがとうございます!";
$PMF_LANG["msgS2FMailSubject"] = "Recommendation from ";

// Search
$PMF_LANG["msgSearchWord"] = "キーワード";
$PMF_LANG["msgSearchFind"] = "検索結果 ";
$PMF_LANG["msgSearchAmount"] = " 検索結果";
$PMF_LANG["msgSearchAmounts"] = " 検索結果";
$PMF_LANG["msgSearchCategory"] = "カテゴリ: ";
$PMF_LANG["msgSearchContent"] = "内容: ";

// new Content
$PMF_LANG["msgNewContentHeader"] = "FAQ を提案する";
$PMF_LANG["msgNewContentAddon"] = "提案する内容はすぐに追加はされません。管理者の承認後、追加されます。<strong>名前</strong>、<strong>メールアドレス</strong>、<strong>カテゴリ</strong>、<strong>件名</strong>、<strong>FAQ 内容</strong>は必須項目です。キーワードには半角空白で分割して入力してください。";
$PMF_LANG["msgNewContentName"] = "名前:";
$PMF_LANG["msgNewContentMail"] = "電子メールアドレス:";
$PMF_LANG["msgNewContentCategory"] = "カテゴリ選択";
$PMF_LANG["msgNewContentTheme"] = "件名:";
$PMF_LANG["msgNewContentArticle"] = "FAQ の内容:";
$PMF_LANG["msgNewContentKeywords"] = "キーワード:";
$PMF_LANG["msgNewContentLink"] = "関連リンク先";
$PMF_LANG["msgNewContentSubmit"] = "送信";
$PMF_LANG["msgInfo"] = "追加情報: ";
$PMF_LANG["msgNewContentThanks"] = "ご提案ありがとうございます!";
$PMF_LANG["msgNoQuestionsAvailable"] = "現在処理すべき質問がありません。";

// ask Question
$PMF_LANG["msgNewQuestion"] = "質問したい内容を入力してください:";
$PMF_LANG["msgAskCategory"] = "カテゴリ選択";
$PMF_LANG["msgAskYourQuestion"] = "質問内容:";
$PMF_LANG["msgAskThx4Mail"] = "<h2>ご質問、ありがとうございます!</h2>";
$PMF_LANG["msgDate_User"] = "日付 / ユーザー";
$PMF_LANG["msgQuestion2"] = "質問";
$PMF_LANG["msg2answer"] = "回答";
$PMF_LANG["msgQuestionText"] = "他のユーザーが質問した内容を確認することができます。質問に答えた場合、管理者の確認後、FAQ に追加されます。";

// Help
$PMF_LANG["msgHelpText"] = "<p>このFAQ (<strong>F</strong>requently <strong>A</strong>sked <strong>Q</strong>uestions) の利用方法は簡単です。<strong><a href=\"".$_SERVER["PHP_SELF"]."?action=show\">カテゴリ</a></strong> から関連内容を項目別に探すか <strong><a href=\"".$_SERVER["PHP_SELF"]."?action=search\">検索</a></strong> からキーワードを入力して探すことができます。</p>";

// Contact
$PMF_LANG["msgContactEMail"] = "管理者に電子メール:";
$PMF_LANG["msgMessage"] = "メッセージ:";

// Startseite
$PMF_LANG["msgNews"] = " お知らせ";
$PMF_LANG["msgTopTen"] = "トップ 10";
$PMF_LANG["msgHomeThereAre"] = "合計 ";
$PMF_LANG["msgHomeArticlesOnline"] = " 個の FAQ があります。";
$PMF_LANG["msgNoNews"] = "新しいお知らせはありません。";
$PMF_LANG["msgLatestArticles"] = "最近投稿された 5 つの質問:";

// E-Mailbenachrichtigung
$PMF_LANG["msgMailThanks"] = "FAQ に提案してくれてありがとうございます。";
$PMF_LANG["msgMailCheck"] = "新しい質問があります。\n管理者ページを確認してください。";
$PMF_LANG["msgMailContact"] = "メッセージは管理者に送信されました。";

// Fehlermeldungen
$PMF_LANG["err_noDatabase"] = "データベース接続が有効ではありません。";
$PMF_LANG["err_noHeaders"] = "カテゴリが見つかりません。";
$PMF_LANG["err_noArticles"] = "<p>登録されているエントリがありません。</p>";
$PMF_LANG["err_badID"] = "<p>間違った ID です。</p>";
$PMF_LANG["err_noTopTen"] = "<p>トップ 10 が利用できません。</p>";
$PMF_LANG["err_nothingFound"] = "<p>エントリが見つかりません。</p>";
$PMF_LANG["err_SaveEntries"] = "必須項目は、<strong>名前</strong>、<strong>電子メールアドレス</strong>、<strong>カテゴリ</strong>、<strong>件名</strong>、<strong>ヘッドライン</strong>、<strong>FAQ 内容</strong>、要求された場合、<strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"WikiPedia で Captcha についてもっと読む\" target=\"_blank\">Captcha</a> コード</strong>です!<br /><br />\n<a href=\"javascript:history.back();\">戻る</a><br /><br />\n";
$PMF_LANG["err_SaveComment"] = "必須項目は、<strong>名前</strong>、<strong>電子メールアドレス</strong>、<strong>コメント</strong>、要求された場合、<strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"WikiPedia で Captcha についてもっと読む\" target=\"_blank\">Captcha</a> コード</strong>です!<br /><br />\n<a href=\"javascript:history.back();\">戻る</a><br /><br />\n";
$PMF_LANG["err_VoteTooMuch"] = "<p>複数回の評価はできません。<a href=\"javascript:history.back();\">ここ</a>をクリックすると戻ります。</p>";
$PMF_LANG["err_noVote"] = "<p><strong>評価点数を選択してください。</strong> 評価をするためには<a href=\"javascript:history.back();\">ここ</a>をクリックしてください。</p>";
$PMF_LANG["err_noMailAdress"] = "電子メールアドレスが正しくありません。<br /><a href=\"javascript:history.back();\">戻る</a>";
$PMF_LANG["err_sendMail"] = "必須項目は、<strong>名前</strong>、<strong>電子メールアドレス</strong>、<strong>質問</strong>、要求された場合、<strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"WikiPedia で Captcha についてもっと読む\" target=\"_blank\">Captcha</a> コード</strong>です!<br /><br />\n<a href=\"javascript:history.back();\">戻る</a><br /><br />\n";

// Hilfe zur Suche
$PMF_LANG["help_search"] = "<p><strong>内容検索：</strong><br /><strong style=\"color: Red;\">言葉1 言葉2</strong>のように検索すると、2個以上の検索結果が関連度が高い順番で表示されます。</p><p><strong>注意：</strong> 英文を検索する際には、少なくとも4文字以上を入力してください。</p>";

// Menu
$PMF_LANG["ad"] = "管理者ページ";
$PMF_LANG["ad_menu_user_administration"] = "ユーザーの管理";
$PMF_LANG["ad_menu_entry_aprove"] = "レコードの承認";
$PMF_LANG["ad_menu_entry_edit"] = "レコードの編集";
$PMF_LANG["ad_menu_categ_add"] = "カテゴリの追加";
$PMF_LANG["ad_menu_categ_edit"] = "カテゴリの変更";
$PMF_LANG["ad_menu_news_add"] = "お知らせの追加";
$PMF_LANG["ad_menu_news_edit"] = "お知らせの編集";
$PMF_LANG["ad_menu_open"] = "質問の編集";
$PMF_LANG["ad_menu_stat"] = "統計";
$PMF_LANG["ad_menu_cookie"] = "Cookie の設定";
$PMF_LANG["ad_menu_session"] = "セッションの閲覧";
$PMF_LANG["ad_menu_adminlog"] = "管理ログの閲覧";
$PMF_LANG["ad_menu_passwd"] = "パスワードの変更";
$PMF_LANG["ad_menu_logout"] = "ログアウト";
$PMF_LANG["ad_menu_startpage"] = "開始ページ";

// Nachrichten
$PMF_LANG["ad_msg_identify"] = "ログインをしてください。";
$PMF_LANG["ad_msg_passmatch"] = "両方のパスワードは<strong>一致</strong>しなければなりません。";
$PMF_LANG["ad_msg_savedsuc_1"] = "";
$PMF_LANG["ad_msg_savedsuc_2"] = "　のプロファイルの保存に成功しました。";
$PMF_LANG["ad_msg_mysqlerr"] = "<strong>データベースのエラー</strong>のため、プロファイルが保存できません。";
$PMF_LANG["ad_msg_noauth"] = "認証していません。";

// Allgemein
$PMF_LANG["ad_gen_page"] = "ページ";
$PMF_LANG["ad_gen_of"] = "of";
$PMF_LANG["ad_gen_lastpage"] = "前のページ";
$PMF_LANG["ad_gen_nextpage"] = "次のページ";
$PMF_LANG["ad_gen_save"] = "保存する";
$PMF_LANG["ad_gen_reset"] = "リセットする";
$PMF_LANG["ad_gen_yes"] = "はい";
$PMF_LANG["ad_gen_no"] = "いいえ";
$PMF_LANG["ad_gen_top"] = "ページの先頭";
$PMF_LANG["ad_gen_ncf"] = "カテゴリが見つかりません!";
$PMF_LANG["ad_gen_delete"] = "削除する";

// Benutzerverwaltung
$PMF_LANG["ad_user"] = "ユーザー管理";
$PMF_LANG["ad_user_username"] = "登録済ユーザー一覧";
$PMF_LANG["ad_user_rights"] = "ユーザー権限一覧";
$PMF_LANG["ad_user_edit"] = "変更する";
$PMF_LANG["ad_user_delete"] = "削除する";
$PMF_LANG["ad_user_add"] = "ユーザー追加";
$PMF_LANG["ad_user_profou"] = "ユーザーのプロフィール";
$PMF_LANG["ad_user_name"] = "ID";
$PMF_LANG["ad_user_password"] = "パスワード";
$PMF_LANG["ad_user_confirm"] = "パスワード確認";
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
$PMF_LANG["ad_entry_edit_1"] = "レコードの編集";
$PMF_LANG["ad_entry_edit_2"] = "";
$PMF_LANG["ad_entry_theme"] = "件名:";
$PMF_LANG["ad_entry_content"] = "内容:";
$PMF_LANG["ad_entry_keywords"] = "キーワード:";
$PMF_LANG["ad_entry_author"] = "作成者:";
$PMF_LANG["ad_entry_category"] = "カテゴリ:";
$PMF_LANG["ad_entry_active"] = "有効にする";
$PMF_LANG["ad_entry_date"] = "日付:";
$PMF_LANG["ad_entry_changed"] = "変更内容は?";
$PMF_LANG["ad_entry_changelog"] = "変更履歴:";
$PMF_LANG["ad_entry_commentby 	"] = "コメント作成者";
$PMF_LANG["ad_entry_comment"] = "コメント:";
$PMF_LANG["ad_entry_save"] = "保存する";
$PMF_LANG["ad_entry_delete"] = "削除する";
$PMF_LANG["ad_entry_delcom_1"] = " ";
$PMF_LANG["ad_entry_delcom_2"] = "　さんのコメントを削除しますか？";
$PMF_LANG["ad_entry_commentdelsuc"] = "コメントの<strong>削除に成功</strong>しました。";
$PMF_LANG["ad_entry_back"] = "戻る";
$PMF_LANG["ad_entry_commentdelfail"] = "コメントの<strong>削除に失敗</strong>しました。";
$PMF_LANG["ad_entry_savedsuc"] = "変更の保存に<strong>成功</strong>しました。";
$PMF_LANG["ad_entry_savedfail 	"] = "<strong>データベースのエラー</strong> が発生しました。";
$PMF_LANG["ad_entry_del_1"] = " ";
$PMF_LANG["ad_entry_del_2"] = "に関する";
$PMF_LANG["ad_entry_del_3"] = " さんのレコードを削除しますか？";
$PMF_LANG["ad_entry_delsuc"] = "削除に<strong>成功</strong> しました。";
$PMF_LANG["ad_entry_delfail"] = "削除に<strong>失敗</strong> しました。";
$PMF_LANG["ad_entry_back"] = "戻る";


// Newsverwaltung
$PMF_LANG["ad_news_header"] = "お知らせの件名";
$PMF_LANG["ad_news_text"] = "内容";
$PMF_LANG["ad_news_link_url"] = "関連リンク: (<strong>http:// は不要です</strong>)!";
$PMF_LANG["ad_news_link_title"] = "リンクのタイトル:";
$PMF_LANG["ad_news_link_target"] = "リンクのターゲット";
$PMF_LANG["ad_news_link_window"] = "新規ウィンドウでリンクを開く";
$PMF_LANG["ad_news_link_faq"] = "FAQ 内のリンク";
$PMF_LANG["ad_news_add"] = "お知らせの追加";
$PMF_LANG["ad_news_id"] = "#";
$PMF_LANG["ad_news_headline"] = "件名";
$PMF_LANG["ad_news_date"] = "日付";
$PMF_LANG["ad_news_action"] = "操作";
$PMF_LANG["ad_news_update"] = "更新する";
$PMF_LANG["ad_news_delete"] = "削除する";
$PMF_LANG["ad_news_nodata"] = "データベースにデータが見つかりませんでした。";
$PMF_LANG["ad_news_updatesuc"] = "更新しました。";
$PMF_LANG["ad_news_del"] = "これを削除しますか?";
$PMF_LANG["ad_news_yesdelete"] = "はい, 削除します!";
$PMF_LANG["ad_news_nodelete"] = "いいえ!";
$PMF_LANG["ad_news_delsuc"] = "削除しました。";
$PMF_LANG["ad_news_updatenews"] = "お知らせの変更";

// Kategorieverwaltung
$PMF_LANG["ad_categ_new"] = "カテゴリの追加";
$PMF_LANG["ad_categ_catnum"] = "カテゴリ番号:";
$PMF_LANG["ad_categ_subcatnum"] = "サブカテゴリ番号:";
$PMF_LANG["ad_categ_nya"] = "<em>利用できません!</em>";
$PMF_LANG["ad_categ_titel"] = "カテゴリ名:";
$PMF_LANG["ad_categ_add"] = "カテゴリの追加";
$PMF_LANG["ad_categ_existing"] = "存在するカテゴリ一覧";
$PMF_LANG["ad_categ_id"] = "#";
$PMF_LANG["ad_categ_categ"] = "カテゴリ";
$PMF_LANG["ad_categ_subcateg"] = "下位カテゴリ";
$PMF_LANG["ad_categ_titel"] = "カテゴリ名";
$PMF_LANG["ad_categ_action"] = "操作";
$PMF_LANG["ad_categ_update"] = "更新する";
$PMF_LANG["ad_categ_delete"] = "削除する";
$PMF_LANG["ad_categ_updatecateg"] = "カテゴリの変更";
$PMF_LANG["ad_categ_nodata"] = "データベースにデータが見つかりません。";
$PMF_LANG["ad_categ_remark"] = "カテゴリを削除すると、該当カテゴリのレコード(FAQ)も削除されます。カテゴリを削除する前に、レコード(FAQ)を他のカテゴリに指定してください。";
$PMF_LANG["ad_categ_edit_1"] = "変更する";
$PMF_LANG["ad_categ_edit_2"] = "カテゴリ";
$PMF_LANG["ad_categ_add"] = "カテゴリの追加";
$PMF_LANG["ad_categ_added"] = "カテゴリを追加しました。";
$PMF_LANG["ad_categ_updated"] = "カテゴリを変更しました。";
$PMF_LANG["ad_categ_del_yes"] = "はい、削除します!";
$PMF_LANG["ad_categ_del_no"] = "いいえ!";
$PMF_LANG["ad_categ_deletesure"] = "本当にこのカテゴリを削除しますか?";
$PMF_LANG["ad_categ_deleted"] = "カテゴリを削除しました。";

// Cookies
$PMF_LANG["ad_cookie_cookiesuc 	"] = "Cookie の設定に<strong>成功</strong>しました。";
$PMF_LANG["ad_cookie_already"] = "Cookie は既に設定されています。現在次のオプションがあります:";
$PMF_LANG["ad_cookie_again"] = "もう一度 Cookie の設定";
$PMF_LANG["ad_cookie_delete"] = "Cookie の削除";
$PMF_LANG["ad_cookie_no"] = "保存されてい Cookie がありません。Cookie にてログインスクリプトを保存します。再びあなたのログイン詳細を覚えることはありません。次のようなオプションがあります:";
$PMF_LANG["ad_cookie_set"] = "Cookie の設定";
$PMF_LANG["ad_cookie_deleted"] = "Cookie の削除に成功しました。";

// Adminlog
$PMF_LANG["ad_adminlog"] = "管理ログ";

// Passwd
$PMF_LANG["ad_passwd_cop"] = "パスワードの変更";
$PMF_LANG["ad_passwd_old"] = "現在のパスワード:";
$PMF_LANG["ad_passwd_new"] = "新しいパスワード:";
$PMF_LANG["ad_passwd_con"] = "新しいパスワードの再確認:";
$PMF_LANG["ad_passwd_change"] = "パスワードの変更";
$PMF_LANG["ad_passwd_suc"] = "パスワードの変更に成功しました。";
$PMF_LANG["ad_passwd_remark"] = "<strong>注意:</strong><br />もう一度クッキーを設定してください。";
$PMF_LANG["ad_passwd_fail"] = "'現在のパスワード'を <strong>正しく</strong> 入力し, '新しいパスワード' と '新しいパスワードの再確認' は必ず <strong>一致</strong>するように入力してください。";

// Adduser
$PMF_LANG["ad_adus_adduser"] = "ユーザーの追加";
$PMF_LANG["ad_adus_name"] = "ID:";
$PMF_LANG["ad_adus_password"] = "パスワード:";
$PMF_LANG["ad_adus_add"] = "ユーザーの追加";
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
$PMF_LANG["ad_sess_search"] = "検索する";
$PMF_LANG["ad_sess_sfs"] = "セッションから検索";
$PMF_LANG["ad_sess_s_ip"] = "IP:";
$PMF_LANG["ad_sess_s_minct"] = "min. actions:";
$PMF_LANG["ad_sess_s_date"] = "日付";
$PMF_LANG["ad_sess_s_after"] = "以後";
$PMF_LANG["ad_sess_s_before"] = "以前";
$PMF_LANG["ad_sess_s_search"] = "検索する";
$PMF_LANG["ad_sess_session"] = "セッション";
$PMF_LANG["ad_sess_r"] = "検索結果 - ";
$PMF_LANG["ad_sess_referer"] = "リファラー:";
$PMF_LANG["ad_sess_browser"] = "ブラウザー:";
$PMF_LANG["ad_sess_ai_rubrik"] = "カテゴリ:";
$PMF_LANG["ad_sess_ai_artikel"] = "レコード:";
$PMF_LANG["ad_sess_ai_sb"] = "検索文字列:";
$PMF_LANG["ad_sess_ai_sid"] = "セッション ID:";
$PMF_LANG["ad_sess_back"] = "戻る";

// Statistik
$PMF_LANG["ad_rs"] = "評価の統計";
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
$PMF_LANG["ad_auth_reset"] = "リセットする";
$PMF_LANG["ad_auth_fail"] = "ID かパスワードが正しくありません。";
$PMF_LANG["ad_auth_sess"] = "セッション ID が終了されました。";

// Added v0.8 - 24.05.2001 - Bastian - Admin
$PMF_LANG["ad_config_edit"] = "環境設定";
$PMF_LANG["ad_config_save"] = "保存する";
$PMF_LANG["ad_config_reset"] = "リセットする";
$PMF_LANG["ad_config_saved"] = "環境設定の保存に成功しました。";
$PMF_LANG["ad_menu_editconfig"] = "環境設定";
$PMF_LANG["ad_att_none"] = "ファイルの添付ができません。";
$PMF_LANG["ad_att_att"] = "添付:";
$PMF_LANG["ad_att_add"] = "添付ファイル";
$PMF_LANG["ad_entryins_suc"] = "保存に成功しました。";
$PMF_LANG["ad_entryins_fail"] = "エラーが生じました。";
$PMF_LANG["ad_att_del"] = "削除する";
$PMF_LANG["ad_att_nope"] = "添付ファイルは内容の変更中にのみ追加できます。";
$PMF_LANG["ad_att_delsuc"] = "添付ファイルの削除に成功しました。";
$PMF_LANG["ad_att_delfail"] = "添付ファイルの削除中にエラーが発生しました。";
$PMF_LANG["ad_entry_add"] = "レコードの追加";

// Added v0.85 - 08.06.2001 - Bastian - Admin
$PMF_LANG["ad_csv_make"] = "データベースの内容をそのままバックアップします。少なくとも月 1 回のバックアップをするようにしてください。バックアップファイルは MySQL のファイル形式で、phpMyAdmin または、MySQL クライアントから読み込むことも可能です。";
$PMF_LANG["ad_csv_link"] = "バックアップのダウンロード";
$PMF_LANG["ad_csv_head"] = "バックアップの作成";
$PMF_LANG["ad_att_addto"] = "ファイルの添付";
$PMF_LANG["ad_att_addto_2"] = "";
$PMF_LANG["ad_att_att"] = "ファイル:";
$PMF_LANG["ad_att_butt"] = "OK";
$PMF_LANG["ad_att_suc"] = "ファイルの添付に成功しました。";
$PMF_LANG["ad_att_fail"] = "ファイルの添付中にエラーが発生しました。";
$PMF_LANG["ad_att_close"] = "このウィンドウを閉じる";

// Added v0.85 - 08.07.2001 - Bastian - Admin
$PMF_LANG["ad_csv_restore"] = "phpMyFAQ でバックアップしたデータを復元します。復元する場合、既存のデータは上書きされることに注意してください。";
$PMF_LANG["ad_csv_file"] = "ファイル";
$PMF_LANG["ad_csv_ok"] = "OK";
$PMF_LANG["ad_csv_linklog"] = "ログのバックアップ";
$PMF_LANG["ad_csv_linkdat"] = "データのバックアップ";
$PMF_LANG["ad_csv_head2"] = "復元する";
$PMF_LANG["ad_csv_no"] = "phpMyFAQ のバックアップファイルの形式ではありません。";
$PMF_LANG["ad_csv_prepare"] = "データーベース照会の準備中...";
$PMF_LANG["ad_csv_process"] = "照会中...";
$PMF_LANG["ad_csv_of"] = "";
$PMF_LANG["ad_csv_suc"] = " が成功しました。";
$PMF_LANG["ad_csv_backup"] = "バックアップ";
$PMF_LANG["ad_csv_rest"] = "バックアップの復元";

// Added v0.8 - 25.05.2001 - Bastian - Admin
$PMF_LANG["ad_menu_backup"] = "バックアップ";
$PMF_LANG["ad_logout"] = "セッションが終了されました。";
$PMF_LANG["ad_news_add"] = "お知らせの追加";
$PMF_LANG["ad_news_edit"] = "お知らせの変更";
$PMF_LANG["ad_cookie"] = "Cookie";
$PMF_LANG["ad_sess_head"] = "セッションの閲覧";

// Added v1.1 - 06.01.2002 - Bastian
$PMF_LANG["ad_menu_categ_edit"] = "カテゴリの管理";
$PMF_LANG["ad_menu_stat"] = "評価統計";
$PMF_LANG["ad_kateg_add"] = "カテゴリの追加";
$PMF_LANG["ad_kateg_rename"] = "名称の変更";
$PMF_LANG["ad_adminlog_date"] = "日付";
$PMF_LANG["ad_adminlog_user"] = "ユーザー";
$PMF_LANG["ad_adminlog_ip"] = "IP アドレス";

$PMF_LANG["ad_stat_sess"] = "セッション";
$PMF_LANG["ad_stat_days"] = "日数";
$PMF_LANG["ad_stat_vis"] = "セッション (訪問数)";
$PMF_LANG["ad_stat_vpd"] = "1 日中の訪問数";
$PMF_LANG["ad_stat_fien"] = "最初のログ";
$PMF_LANG["ad_stat_laen"] = "最後のログ";
$PMF_LANG["ad_stat_browse"] = "セッション情報の表示";
$PMF_LANG["ad_stat_ok"] = "OK";

$PMF_LANG["ad_sess_time"] = "時間";
$PMF_LANG["ad_sess_sid"] = "セッション ID";
$PMF_LANG["ad_sess_ip"] = "IP アドレス";

$PMF_LANG["ad_ques_take"] = "質問への回答を作成する";
$PMF_LANG["no_cats"] = "カテゴリが見つかりません。";

// Added v1.1 - 17.01.2002 - Bastian
$PMF_LANG["ad_log_lger"] = "IDまたはパスワードが正しくありません。";
$PMF_LANG["ad_log_sess"] = "セッションは終了しました。";
$PMF_LANG["ad_log_edit"] = "次のユーザーの「ユーザー編集」フォーム: ";
$PMF_LANG["ad_log_crea"] = "「新規記事」フォーム";
$PMF_LANG["ad_log_crsa"] = "新規エントリを作成しました。";
$PMF_LANG["ad_log_ussa"] = "次のユーザーのデータを更新しました: ";
$PMF_LANG["ad_log_usde"] = "次のユーザーを削除しました: ";
$PMF_LANG["ad_log_beed"] = "次のユーザーの変更フォーム: ";
$PMF_LANG["ad_log_bede"] = "次のエントリの削除: ";

$PMF_LANG["ad_start_visits"] = "訪問数";
$PMF_LANG["ad_start_articles"] = "記事数";
$PMF_LANG["ad_start_comments"] = "コメント数";

$PMF_LANG["ad_user_chpw"] = "現在のユーザーのパスワード変更は「".$PMF_LANG["ad_menu_passwd"]."」でできます。";

// Added v1.1 - 30.01.2002 - Bastian
$PMF_LANG["ad_categ_paste"] = "貼り付け";
$PMF_LANG["ad_categ_cut"] = "切り取り";
$PMF_LANG["ad_categ_copy"] = "コピー";
$PMF_LANG["ad_categ_process"] = "カテゴリ処理中...";

// Added v1.1.4 - 07.05.2002 - Thorsten
$PMF_LANG["err_NotAuth"] = "<strong>使用権限がありません。</strong>";

// Added v1.2.3 - 29.11.2002 - Thorsten
$PMF_LANG["msgPreviusPage"] = "前のページ";
$PMF_LANG["msgNextPage"] = "次のページ";
$PMF_LANG["msgPageDoublePoint"] = "ページ: ";
$PMF_LANG["msgMainCategory"] = "メインカテゴリ";

// Added v1.2.4 - 30.01.2003 - Thorsten
$PMF_LANG["ad_passwdsuc"] = "パスワードを変更しました。";

// Added v1.3.0 - 04.03.2003 - Thorsten
$PMF_LANG["msgPDF"] = "PDF ファイルで表示する";
$PMF_LANG["ad_xml_head"] = "XML にバックアップする";
$PMF_LANG["ad_xml_hint"] = "FAQ の全レコードを 1 つの XML ファイルに保存する";
$PMF_LANG["ad_xml_gen"] = "XML ファイルの生成";
$PMF_LANG["ad_entry_locale"] = "言語";
$PMF_LANG["msgLangaugeSubmit"] = "言語の変更";

// Added v1.3.1 - 29.04.2003 - Thorsten
$PMF_LANG["ad_entry_preview"] = "プレビュー";
$PMF_LANG["ad_attach_1"] = "環境設定から添付ファイルを保存するディレクトリを先に設定してください。";
$PMF_LANG["ad_attach_2"] = "環境設定から添付ファイルのリンクを先に設定してください。";
$PMF_LANG["ad_attach_3"] = "attachment.php ファイルを権限なしではオープンできません。";
$PMF_LANG["ad_attach_4"] = "添付ファイルのサイズは ".$PMF_CONF["attmax"]." バイトより大きくてはいけません。";
$PMF_LANG["ad_menu_export"] = "FAQ のエクスポート";
$PMF_LANG["ad_export_1"] = "Built RSS-Feed on";
$PMF_LANG["ad_export_2"] = ".";
$PMF_LANG["ad_export_file"] = "エラー: ファイルの書き込みができません。";
$PMF_LANG["ad_export_news"] = "お知らせ RSS フィード";
$PMF_LANG["ad_export_topten"] = "トップ 10 RSS フィード";
$PMF_LANG["ad_export_latest"] = "最新 5 件の RSS フィード";
$PMF_LANG["ad_export_pdf"] = "全レコードの PDF エクスポート";
$PMF_LANG["ad_export_generate"] = "RSS フィードを構築";

$PMF_LANG["rightsLanguage"][0] = "ユーザーの追加";
$PMF_LANG["rightsLanguage"][1] = "ユーザーの編集";
$PMF_LANG["rightsLanguage"][2] = "ユーザーの削除";
$PMF_LANG["rightsLanguage"][3] = "レコードの追加";
$PMF_LANG["rightsLanguage"][4] = "レコードの編集";
$PMF_LANG["rightsLanguage"][5] = "レコードの削除";
$PMF_LANG["rightsLanguage"][6] = "ログの閲覧";
$PMF_LANG["rightsLanguage"][7] = "管理ログの閲覧";
$PMF_LANG["rightsLanguage"][8] = "コメントの削除";
$PMF_LANG["rightsLanguage"][9] = "ニュースの追加";
$PMF_LANG["rightsLanguage"][10] = "ニュースの編集";
$PMF_LANG["rightsLanguage"][11] = "ニュースの削除";
$PMF_LANG["rightsLanguage"][12] = "カテゴリの追加";
$PMF_LANG["rightsLanguage"][13] = "カテゴリの編集";
$PMF_LANG["rightsLanguage"][14] = "カテゴリの削除";
$PMF_LANG["rightsLanguage"][15] = "パスワードの編集";
$PMF_LANG["rightsLanguage"][16] = "構成の編集";
$PMF_LANG["rightsLanguage"][17] = "添付の追加";
$PMF_LANG["rightsLanguage"][18] = "添付の削除";
$PMF_LANG["rightsLanguage"][19] = "バックアップの作成";
$PMF_LANG["rightsLanguage"][20] = "バックアップの復元";
$PMF_LANG["rightsLanguage"][21] = "開いた質問の削除";
$PMF_LANG["rightsLanguage"][22] = "改定の編集";

$PMF_LANG["msgAttachedFiles"] = "添付ファイル:";

// Added v1.3.3 - 27.05.2003 - Thorsten
$PMF_LANG["ad_user_action"] = "操作";
$PMF_LANG["ad_entry_email"] = "メールアドレス:";
$PMF_LANG["ad_entry_allowComments"] = "コメントの許可";
$PMF_LANG["msgWriteNoComment"] = "このエントリにコメントできません。";
$PMF_LANG["ad_user_realname"] = "本名:";
$PMF_LANG["ad_export_generate_pdf"] = "PDF ファイル生成";
$PMF_LANG["ad_export_full_faq"] = "FAQ を PDF ファイルにする: ";
$PMF_LANG["err_bannedIP"] = "あなたのIPアドレスからのアクセスは遮断されています。";
$PMF_LANG["err_SaveQuestion"] = "必須項目は、<strong>名前</strong>、<strong>電子メールアドレス</strong>、<strong>質問</strong>、要求された場合、<strong><a href=\"http://ja.wikipedia.org/wiki/Captcha\" title=\"WikiPedia で Captcha についてもっと読む\" target=\"_blank\">Captcha</a> コード</strong>です。<br /><br /><a href=\"javascript:history.back();\">戻る</a><br /><br />\n";

// added v1.3.4 - 23.07.2003 - Thorsten
$PMF_LANG["ad_entry_fontcolor"] = "フォント色: ";
$PMF_LANG["ad_entry_fontsize"] = "フォントサイズ: ";

// added v1.4.0 - 2003-12-04 by Thorsten / Mathias
$LANG_CONF["language"] = array(0 => "select", 1 => "言語");
$LANG_CONF["detection"] = array(0 => "checkbox", 1 => "言語の自動認識を有効にする");
$LANG_CONF["title"] = array(0 => "input", 1 => "FAQ の題名");
$LANG_CONF["version"] = array(0 => "print", 1 => "FAQ バージョン");
$LANG_CONF["metaDescription"] = array(0 => "input", 1 => "ページの説明");
$LANG_CONF["metaKeywords"] = array(0 => "input", 1 => "検索ロボット用キーワード");
$LANG_CONF["metaPublisher"] = array(0 => "input", 1 => "管理者名");
$LANG_CONF["adminmail"] = array(0 => "input", 1 => "管理者の電子メールアドレス");
$LANG_CONF["msgContactOwnText"] = array(0 => "area", 1 => "問い合わせ情報");
$LANG_CONF["copyright_eintrag"] = array(0 => "area", 1 => "スタートページのコピーライト文");
$LANG_CONF["send2friend_text"] = array(0 => "area", 1 => "友達に送信ページのテキスト");
$LANG_CONF["attmax"] = array(0 => "input", 1 => "添付ファイルの最大サイズ (最大 ".ini_get("upload_max_filesize")." バイト)");
$LANG_CONF["disatt"] = array(0 => "checkbox", 1 => "エントリの下に添付のリンクを表示する");
$LANG_CONF["tracking"] = array(0 => "checkbox", 1 => "追跡機能を使用する");
$LANG_CONF["enableadminlog"] = array(0 => "checkbox", 1 => "管理ログを使用する");
$LANG_CONF["ipcheck"] = array(0 => "checkbox", 1 => "admin.php で UIN のチェック時に IP アドレスを確認するか");
$LANG_CONF["numRecordsPage"] = array(0 => "input", 1 => "ページ毎に表示するトピック数");
$LANG_CONF["numNewsArticles"] = array(0 => "input", 1 => "お知らせの表示数");
$LANG_CONF["bannedIP"] = array(0 => "area", 1 => "拒否する IP アドレス");
$LANG_CONF["parse_php"] = array(0 => "checkbox", 1 => "テンプレートエンジンで PHP コードの解析を可能にしますか? (デフォルト: 無効)");
$LANG_CONF["mod_rewrite"] = array(0 => "checkbox", 1 => "mod_rewrite のサポートを使用しますか? (デフォルト: 無効)");
$LANG_CONF["ldap_support"] = array(0 => "checkbox", 1 => "LDAP サポートを使用にしますか? (デフォルト: 無効)");

$PMF_LANG["ad_categ_new_main_cat"] = "ROOT の下位カテゴリへ";
$PMF_LANG["ad_categ_paste_error"] = "このカテゴリは移動できません。";
$PMF_LANG["ad_categ_move"] = "カテゴリの移動";
$PMF_LANG["ad_categ_lang"] = "言語";
$PMF_LANG["ad_categ_desc"] = "説明";
$PMF_LANG["ad_categ_change"] = "選択したカテゴリと入れ替え";

$PMF_LANG["lostPassword"] = "パスワードを忘れましたか? その時はここをクリックしてください。";
$PMF_LANG["lostpwd_err_1"] = "エラー: ユーザー名と電子メールアドレスが見つかりません。";
$PMF_LANG["lostpwd_err_2"] = "エラー: 不正な入力です!";
$PMF_LANG["lostpwd_text_1"] = "アカウント情報を要求してくれてありがとうございます。";
$PMF_LANG["lostpwd_text_2"] = "FAQ の管理セクションの中で新しい個人のパスワードを設定してください。";
$PMF_LANG["lostpwd_mail_okay"] = "電子メールを送信しました。";

$PMF_LANG["ad_xmlrpc_button"] = "最新の phpMyFAQ バージョンをウェブで確認する";
$PMF_LANG["ad_xmlrpc_latest"] = "最新バージョンを次のサイトから利用することができます:";

// added v1.5.0 - 2005-07-31 by Thorsten
$PMF_LANG['ad_categ_select'] = 'カテゴリ言語の選択';

// added v1.5.1 - 2005-09-06 by Thorsten
$PMF_LANG['msgSitemap'] = 'サイトマップ';

// added v1.5.2 - 2005-09-23 by Lars
$PMF_LANG['err_inactiveArticle'] = 'このエントリは改訂中で、表示することができません。';
$PMF_LANG['msgArticleCategories'] = 'このエントリのカテゴリ';

// added v1.5.3 - 2005-10-04 by Thorsten and Periklis
$PMF_LANG['ad_menu_searchplugin'] = '検索プラグイン';
$PMF_LANG['ad_search_plugin_install'] = 'Firefox 検索プラグインのインストール';
$PMF_LANG['ad_search_plugin_title'] = '検索プラグインの作成';
$PMF_LANG['ad_search_plugin_ttitle'] = 'Firefox 検索ボックスの題名:';
$PMF_LANG['ad_search_plugin_tdesc'] = '説明:';
$PMF_LANG['ad_search_plugin_create'] = 'Firefox 検索プラグインを作成する';
$PMF_LANG['ad_search_plugin_success'] = 'Mozilla Firefox 検索プラグインの作成は成功しました!';

// added v1.6.0 - 2006-02-02 by Thorsten
$PMF_LANG['ad_entry_solution_id'] = '一意的回答 ID';
$PMF_LANG['ad_entry_faq_record'] = 'FAQ の内容';
$PMF_LANG['ad_entry_new_revision'] = '新規改定を作成しますか?';
$PMF_LANG['ad_entry_record_administration'] = 'レコード管理';
$PMF_LANG['ad_entry_changelog'] = '変更履歴';
$PMF_LANG['ad_entry_revision'] = '改定';
$PMF_LANG['ad_changerev'] = '改定の選択';
$PMF_LANG['msgCaptcha'] = "画像を読んで文字を入力してください";
$PMF_LANG['msgSelectCategories'] = '検索対象 ...';
$PMF_LANG['msgAllCategories'] = '... すべてのカテゴリ';
$PMF_LANG['ad_you_should_update'] = 'インストールしている phpMyFAQ は旧式です。最新の利用可能なバージョンに更新するべきです。';
$PMF_LANG['msgAdvancedSearch'] = '高度な検索';

// added v1.6.1 - 2006-04-25 by Matteo and Thorsten
$PMF_LANG['spamControlCenter'] = 'スパム制御センター';
$LANG_CONF["spamEnableSafeEmail"] = array(0 => "checkbox", 1 => "安全にユーザーの電子メールを表示する (デフォルト: 有効)");
$LANG_CONF["spamCheckBannedWords"] = array(0 => "checkbox", 1 => "公開フォームの内容に対して禁止された単語を確認する (デフォルト: 有効)");
$LANG_CONF["spamEnableCatpchaCode"] = array(0 => "checkbox", 1 => "公開フォームの送信の許可に Catpcha コードを使用する (デフォルト: 有効)");
$PMF_LANG['ad_firefoxsearch_plugin_title'] = 'Firefox 検索プラグインの作成';
$PMF_LANG['ad_msiesearch_plugin_install'] = 'Microsoft Internet Explorer 7 検索プラグインのインストール';
$PMF_LANG['ad_msiesearch_plugin_title'] = 'Microsoft Internet Explorer 7 検索プラグインの作成';
$PMF_LANG['ad_msiesearch_plugin_ttitle'] = 'MSIE 7 検索ボックスの題名:';
$PMF_LANG['ad_msiesearch_plugin_create'] = 'Microsoft Internet Explorer 7 検索プラグインを作成します。';
$PMF_LANG['ad_msiesearch_plugin_success'] = 'Microsoft Internet Explorer 7 検索プラグインの作成に成功しました!';
$PMF_LANG['ad_session_expiring'] = 'セッションは %d d 期限切れになるでしょう。作業をし続けますか?';

// added v1.6.2 - 2006-06-13 by Matteo
$PMF_LANG['ad_stat_management'] = 'セッション管理';
$PMF_LANG['ad_stat_choose'] = '月を選択する';
$PMF_LANG['ad_stat_delete'] = '選択したセッションをすぐに削除する';
