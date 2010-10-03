<?php
/**
 * Saves a user FAQ record and sends an email to the user
 *
 * PHP Version 5.2.0
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
 * 
 * @category  phpMyFAQ 
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jürgen Kuza <kig@bluewin.ch>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

$username    = PMF_Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$usermail    = PMF_Filter::filterInput(INPUT_POST, 'usermail', FILTER_VALIDATE_EMAIL);

$faqid       = PMF_Filter::filterInput(INPUT_POST, 'faqid', FILTER_VALIDATE_INT);
$faqlanguage = PMF_Filter::filterInput(INPUT_POST, 'faqlanguage', FILTER_SANITIZE_STRING);
$thema       = PMF_Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_STRIPPED);
$content     = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_STRIPPED);
$tr_content  = PMF_Filter::filterInput(INPUT_POST, 'translated_content', FILTER_SANITIZE_STRING);
$contentlink = PMF_Filter::filterInput(INPUT_POST, 'contentlink', FILTER_VALIDATE_URL);
$keywords    = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRIPPED);
$code        = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$categories  = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                'flags'  => FILTER_REQUIRE_ARRAY)));

// If e-mail address is set to optional
if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($usermail)) {
    $usermail = PMF_Configuration::getInstance()->get('main.administrationMail');
}

// Check on translation
if (is_null($content) && !is_null($tr_content)) {
    $content = $tr_content;
}

if (!is_null($username) && !is_null($usermail) && !is_null($thema) && !is_null($content) && 
    IPCheck($_SERVER['REMOTE_ADDR']) && checkBannedWord(PMF_String::htmlspecialchars($thema)) && 
    checkBannedWord(PMF_String::htmlspecialchars($content)) && $captcha->checkCaptchaCode($code) && 
    ((is_null($faqid) && !is_null($categories['rubrik'])) || (!is_null($faqid) && !is_null($faqlanguage) && 
    PMF_Language::isASupportedLanguage($faqlanguage)))) {

    $isNew = true;
    if (!is_null($faqid)) {
        $isNew = false;
        $faqsession->userTracking('save_new_translation_entry', 0);
    } else {
        $faqsession->userTracking('save_new_entry', 0);
    }

    $isTranslation = false;
    if (!is_null($faqlanguage)) {
        $isTranslation = true;
        $newLanguage   = $faqlanguage;
    }

    if (PMF_String::substr($contentlink,7) != "") {
        $content = $content."<br />".$PMF_LANG["msgInfo"]."<a href=\"http://".PMF_String::substr($contentlink,7)."\" target=\"_blank\">".$contentlink."</a>";
    }

    $newData = array(
        'lang'          => ($isTranslation == true ? $newLanguage : $LANGCODE),
        'thema'         => $thema,
        'active'        => FAQ_SQL_ACTIVE_NO,
        'sticky'        => 0,
        'content'       => nl2br($content),
        'keywords'      => $keywords,
        'author'        => $username,
        'email'         => $usermail,
        'comment'       => FAQ_SQL_YES,
        'date'          => date('YmdHis'),
        'dateStart'     => '00000000000000',
        'dateEnd'       => '99991231235959',
        'linkState'     => '',
        'linkDateCheck' => 0);

    if ($isNew) {
        $categories = $categories['rubrik'];
    } else {
        $newData['id'] = $faqid;
        $category      = new PMF_Category();
        $categories    = $category->getCategoryIdsFromArticle($newData['id']);
    }
    
    $recordId = $faq->addRecord($newData, $isNew);
    $faq->addCategoryRelations($categories, $recordId, $newData['lang']);

    $sent = array();

    // Let the PMF Administrator and the Category Owner to be informed by email of this new entry
    foreach ($categories as $_category) {

        $userId = $category->getCategoryUser($_category);

        // Avoid to send multiple emails to the same owner
        if (!isset($sent[$userId])) {
            // TODO: Move this code to Category.php
            $oUser = new PMF_User();
            $oUser->getUserById($userId);
            $catOwnerEmail = $oUser->getUserData('email');

            $mail = new PMF_Mail();
            $mail->unsetFrom();
            $mail->setFrom($usermail);
            $mail->addTo($faqconfig->get('main.administrationMail'));
            // Let the category owner get a copy of the message
            if ($faqconfig->get('main.administrationMail') != $catOwnerEmail) {
                $mail->addCc($catOwnerEmail);
            }
            $mail->subject = '%sitename%';
            // TODO: let the email contains the faq article both as plain text and as HTML
            $mail->message = html_entity_decode($PMF_LANG['msgMailCheck']) .
                "\n\n" . $faqconfig->get('main.titleFAQ') . ": " .
                PMF_Link::getSystemUri('/index.php').'/admin';
            $result = $mail->send();
            unset($mail);

            $sent[$userId] = $catOwnerEmail;
        }
    }

    $tpl->processTemplate('writeContent',
        array('msgNewContentHeader' => $PMF_LANG["msgNewContentHeader"],
              'Message'             => ($isNew ? $PMF_LANG['msgNewContentThanks'] : $PMF_LANG['msgNewTranslationThanks'])));
} else {
    if (false === IPCheck($_SERVER['REMOTE_ADDR'])) {
        $tpl->processTemplate('writeContent',
            array(
                'msgNewContentHeader' => $PMF_LANG['msgNewContentHeader'],
                'Message'             => $PMF_LANG['err_bannedIP']));
    } else {
        if (is_null($faqid)) {
            $faqsession->userTracking('error_save_entry', 0);
        } else {
            $faqsession->userTracking('error_save_translation_entry', 0);
        }
        $tpl->processTemplate('writeContent',
            array(
                'msgNewContentHeader' => $PMF_LANG['msgNewContentHeader'],
                'Message'             => $PMF_LANG['err_SaveEntries']));
    }
}

$tpl->includeTemplate('writeContent', 'index');
