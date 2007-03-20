<?php
/**
 * $Id: save.php,v 1.40 2007-03-20 18:02:43 thorstenr Exp $
 *
 * Saves a user FAQ record and sends an email to the user
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author       Jürgen Kuza <kig@bluewin.ch>
 * @author       Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since        2002-09-16
 * @copyright    (c) 2002-2007 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (    isset($_POST['username']) && $_POST['username'] != ''
    && isset($_POST['usermail']) && checkEmail($_POST['usermail'])
    && ((!isset($_POST['faqid']) && isset($_POST['rubrik']) && is_array($_POST['rubrik']))
        || (isset($_POST['faqid']) && is_numeric($_POST['faqid']) && (intval($_POST['faqid']) > 0) && isset($_POST['faqlanguage']) && PMF_Init::isASupportedLanguage($_POST['faqlanguage'])))
    && isset($_POST['thema']) && $_POST['thema'] != ''
    && isset($_POST['content']) && $_POST['content'] != ''
    && IPCheck($_SERVER['REMOTE_ADDR'])
    && checkBannedWord(htmlspecialchars(strip_tags($_POST['thema'])))
    && checkBannedWord(htmlspecialchars(strip_tags($_POST['content'])))
    && checkCaptchaCode()) {

    $isNew = true;
    if (isset($_POST['faqid'])) {
        $isNew = false;
        Tracking('save_new_translation_entry', 0);
    } else {
        Tracking('save_new_entry', 0);
    }
    $content = $db->escape_string(safeHTML(nl2br($_POST['content'])));
    $contentlink = $db->escape_string(safeHTML($_POST['contentlink']));

    if (substr($contentlink,7) != "") {
        $content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".substr($contentlink,7)."\" target=\"_blank\">".$contentlink."</a>";
    }

    $userMail = $IDN->encode($db->escape_string($_POST['usermail']));
    $newData = array(
        'lang'          => $LANGCODE,
        'thema'         => $db->escape_string(safeHTML($_POST['thema'])),
        'active'        => FAQ_SQL_ACTIVE_NO,
        'content'       => $content,
        'keywords'      => $db->escape_string($_POST['keywords']),
        'author'        => $db->escape_string($_POST['username']),
        'email'         => $userMail,
        'comment'       => FAQ_SQL_YES,
        'date'          => date('YmdHis'),
        'dateStart'     => '00000000000000',
        'dateEnd'       => '99991231235959',
        'linkState'     => '',
        'linkDateCheck' => 0);

    if ($isNew) {
        $categories = $_POST['rubrik'];
    } else {
        // Fix data with correct values
        $newData['id'] = intval($_POST['faqid']);
        // Set categories equal to the faq source of the translation
        $category = new PMF_Category($_POST['faqlanguage']);
        $categories = $category->getCategoryIdsFromArticle($newData['id']);
    }

    $recordId = $faq->addRecord($newData, $isNew);
    $faq->addCategoryRelations($categories, $recordId, $newData['lang']);

    $sent = array();

    // Let the PMF Administrator and the Category Owner to be informed by email of this new entry
    foreach($categories as $_category) {

        $userId = $category->getCategoryUser($_category);

        // Avoid to send multiple emails to the same owner
        if (!isset($sent[$userId])) {
            // TODO: Move this code to Category.php and let the email contains the faq article both as plain text and as HTML
            $oUser = new PMF_User();
            $oUser->addDb($db);
            $oUser->getUserById($userId);
            $catOwnerEmail = $oUser->getUserData('email');

            $additional_header = array();
            $additional_header[] = 'MIME-Version: 1.0';
            $additional_header[] = 'Content-Type: text/plain; charset='. $PMF_LANG['metaCharset'];
            if (strtolower($PMF_LANG['metaCharset']) == 'utf-8') {
                $additional_header[] = 'Content-Transfer-Encoding: 8bit';
            }
            $additional_header[] = 'From: '.$userMail;
            // Let the category owner get a copy of the message
            if ($IDN->encode($faqconfig->get('main.administrationMail')) != $catOwnerEmail) {
                $additional_header[] = "Cc: ".$catOwnerEmail."\n";
            }
            $subject = $PMF_CONF['title'];
            if (function_exists('mb_encode_mimeheader')) {
                $subject = mb_encode_mimeheader($subject);
            }
            $body = unhtmlentities($PMF_LANG['msgMailCheck'])."\n".$PMF_CONF['title'].": ".PMF_Link::getSystemUri('/index.php').'/admin';
            if (ini_get('safe_mode')) {
                mail($IDN->encode($faqconfig->get('main.administrationMail')),
                $subject,
                $body,
                implode("\r\n", $additional_header));
            } else {
                mail($IDN->encode($faqconfig->get('main.administrationMail')),
                $subject, $body,
                implode("\r\n", $additional_header),
                "-f$userMail");
            }
            $sent[$userId] = $catOwnerEmail;
        }
    }

    $tpl->processTemplate ("writeContent", array(
    "msgNewContentHeader" => $PMF_LANG["msgNewContentHeader"],
    'Message' => ($isNew ? $PMF_LANG['msgNewContentThanks'] : $PMF_LANG['msgNewTranslationThanks'])
    ));
} else {
    if (IPCheck($_SERVER['REMOTE_ADDR']) == FALSE) {
        $tpl->processTemplate ("writeContent", array(
            'msgNewContentHeader'   => $PMF_LANG['msgNewContentHeader'],
            'Message'               => $PMF_LANG['err_bannedIP']));
    } else {
        if (!isset($_POST['faqid'])) {
            Tracking('error_save_entry', 0);
        } else {
            Tracking('error_save_translation_entry', 0);
        }
        $tpl->processTemplate ('writeContent', array(
            'msgNewContentHeader'   => $PMF_LANG['msgNewContentHeader'],
            'Message'               => $PMF_LANG['err_SaveEntries']));
    }
}

$tpl->includeTemplate("writeContent", "index");
