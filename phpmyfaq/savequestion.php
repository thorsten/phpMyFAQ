<?php
/**
 * Saves the question of a user
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
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Jürgen Kuza <kig@bluewin.ch>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

$username = PMF_Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$usermail = PMF_Filter::filterInput(INPUT_POST, 'usermail', FILTER_VALIDATE_EMAIL);
$usercat  = PMF_Filter::filterInput(INPUT_POST, 'rubrik', FILTER_VALIDATE_INT);
$content  = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_STRIPPED);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$code     = (is_null($code) ? PMF_Filter::filterInput(INPUT_GET, 'code', FILTER_SANITIZE_STRING, 42) : $code);
$domail   = PMF_Filter::filterInput(INPUT_GET, 'domail', FILTER_VALIDATE_INT);
$thankyou = PMF_Filter::filterInput(INPUT_GET, 'thankyou', FILTER_VALIDATE_INT);

// If e-mail address is set to optional
if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($usermail)) {
    $usermail = PMF_Configuration::getInstance()->get('main.administrationMail');
}

function sendAskedQuestion($username, $usermail, $usercat, $content)
{
    global $category, $PMF_LANG, $faq;
    
    $retval     = false;
    $faqconfig  = PMF_Configuration::getInstance();
    $cat        = new PMF_Category();
    $categories = $cat->getAllCategories();

    if ($faqconfig->get('records.enableVisibilityQuestions')) {
        $visibility = 'N';
    } else {
        $visibility = 'Y';
    }

    $questionData = array(
        'ask_username' => $username,
        'ask_usermail' => $usermail,
        'ask_category' => $usercat,
        'ask_content'  => $content,
        'ask_date'     => date('YmdHis'),
        'is_visible'   => $visibility);

    if (PMF_Filter::filterVar($questionData['ask_usermail'], FILTER_VALIDATE_EMAIL) != false) {

        $faq->addQuestion($questionData);

        $questionMail = "User: ".$questionData['ask_username'].", mailto:".htmlentities($questionData['ask_usermail'], ENT_QUOTES)."\n"
                        .$PMF_LANG["msgCategory"].": ".$categories[$questionData['ask_category']]["name"]."\n\n"
                        .wordwrap($content, 72);

        $userId = $category->getCategoryUser($questionData['ask_category']);
        $oUser  = new PMF_User();
        $oUser->getUserById($userId);

        $userEmail      = $oUser->getUserData('email');
        $mainAdminEmail = $faqconfig->get('main.administrationMail');
        
        $mail = new PMF_Mail();
        $mail->unsetFrom();
        $mail->setFrom($questionData['ask_usermail'], html_entity_decode($questionData['ask_username'], ENT_QUOTES));
        $mail->addTo($mainAdminEmail);
        // Let the category owner get a copy of the message
        if ($userEmail && $mainAdminEmail != $userEmail) {
            $mail->addCc($userEmail);
        }
        $mail->subject = '%sitename%';
        $mail->message = html_entity_decode($questionMail, ENT_QUOTES);
        $retval = $mail->send();
    }
    
    return $retval;
}

if (!is_null($username) && !empty($usermail) && !empty($content) && IPCheck($_SERVER['REMOTE_ADDR']) && 
    checkBannedWord(PMF_String::htmlspecialchars($content)) && $captcha->checkCaptchaCode($code)) {
    	
    $pmf_sw       = PMF_Stopwords::getInstance();
    $search_stuff = $pmf_sw->clean($content);       

    $search        = new PMF_Search($db, $Language);
    $search_result = array();
    $counter = 0;
    foreach ($search_stuff as $word) {
        $tmp = $search->search($word);
        foreach ($tmp as $foundItem) {
            if (!isset($foundItem->id, $search_result[$foundItem->category_id])) {
                $counter++;
                $foundItem->searchterm = PMF_String::htmlspecialchars(stripslashes($word), ENT_QUOTES, 'utf-8');
                $search_result[$foundItem->category_id][$foundItem->id] = $foundItem; 
            }
        }
    }
    
    if ($search_result) {
        $search_result_html = '<p>'.$plr->GetMsg('plmsgSearchAmount', count($search_result))."</p>\n";
        $counter            = 0;
        foreach ($search_result as $cat_id => $cat_contents) {
            $tmp_result_html = '';
            foreach ($cat_contents as $cat_content_item) {
                $b_permission = false;
                //Groups Permission Check
                if ($faqconfig->get('main.permLevel') == 'medium') {
                    $perm_group = $faq->getPermission('group', $cat_content_item->id);
                    foreach ($current_groups as $index => $value){
                        if (in_array($value, $perm_group)) {
                            $b_permission = true;
                        }
                    }
                }
                if ($faqconfig->get('main.permLevel') == 'basic' || $b_permission) {
                    $perm_user = $faq->getPermission('user', $cat_content_item->id);
                    foreach ($perm_user as $index => $value) {
                        if ($value == -1) {
                            $b_permission = true;
                            break;
                        } elseif (((int)$value == $current_user)) {
                            $b_permission = true;
                            break;
                        } else {
                            $b_permission = false;
                        }
                    }
                }
                
                if (!$b_permission) {
                    continue;
                }
                
                $url = sprintf(
                    '?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    $sids,
                    $cat_content_item->category_id,
                    $cat_content_item->id,
                    $cat_content_item->lang,
                    urlencode($cat_content_item->searchterm));
                
                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri() . $url);
                $oLink->text      = $cat_content_item->question;
                $tmp_result_html .= '<li>' . $oLink->toHtmlAnchor() . '<br /></li>' . "\n";
            }
            
            if ($tmp_result_html) {
                $search_result_html .= '<strong>'.$category->getPath($cat_id).'</strong>: ';
                $search_result_html .= '<ul class="phpmyfaq_ul">' . "\n";
                $search_result_html .= $tmp_result_html;
                $search_result_html .= '</ul>';
            }
        }
        
        $search_result_html .= '<div class="searchpreview"><strong>'.$PMF_LANG['msgSearchContent'].'</strong> '.$content.'...</div>';
        
        $tpl->processBlock('writeContent', 'adequateAnswers', array('answers' => $search_result_html));
        $tpl->processBlock('writeContent', 
                           'messageQuestionFound', 
                           array('BtnText' => $PMF_LANG['msgSendMailDespiteEverything'],
                                 'Message' => $PMF_LANG['msgSendMailIfNothingIsFound'],
                                 'Code'    => $code));
        
        $_SESSION['asked_questions'][$code] = array('username' => $username, 
                                                    'usermail' => $usermail,
                                                    'usercat'  => $usercat,
                                                    'content'  => $content);
    } else {
        if (sendAskedQuestion($username, $usermail, $usercat, $content)) {
            header('Location: index.php?action=savequestion&thankyou=1');
            exit;
        }
        
        $tpl->processBlock('writeContent', 'messageSaveQuestion', array('Message' => $PMF_LANG['err_noMailAdress']));
    }

} elseif (null != $domail && null != $code && isset($_SESSION['asked_questions'][$code])) {
    
    extract($_SESSION['asked_questions'][$code]);
    sendAskedQuestion($username, $usermail, $usercat, $content);
    
    unset($_SESSION['asked_questions'][$code]);
    header('Location: index.php?action=savequestion&thankyou=1');
    exit;
    
} elseif (null != $thankyou) {
	
    $tpl->processBlock('writeContent', 'messageSaveQuestion', array('Message' => $PMF_LANG['msgAskThx4Mail']));
    
} else {
    if (false === IPCheck($_SERVER['REMOTE_ADDR'])) {
        $message = $PMF_LANG['err_bannedIP'];
    } else {
        $message = $PMF_LANG['err_SaveQuestion'];
    }
    
    $tpl->processBlock('writeContent', 'messageSaveQuestion', array('Message' => $message));
}

$tpl->processTemplate('writeContent', array('msgQuestion' => $PMF_LANG['msgQuestion']));
$tpl->includeTemplate('writeContent', 'index');
