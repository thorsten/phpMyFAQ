<?php
/**
 * AJAX: handling of Ajax record calls
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-31
 */

use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

// Expected is an array of the structure:
// array( 0 => array((int)id, (string)langugage, (int) checked)),
//        1 => .....
// )
$items = isset($_GET['items']) && is_array($_GET['items']) ? $_GET['items'] : array();

if (!isset($items[0][2])) {
    $items[0][2] = 0;
}

$response = new Response;

switch($ajax_action) {

    // save active FAQs
    case 'save_active_records':
        if ($permission['approverec']) {
            if (!empty($items)) {
                $faq = new PMF_Faq($faqConfig);

                $output = '';
                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && PMF_Language::isASupportedLanguage($item[1])) {
                        $output .= $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), $item[2], 'active');
                    }
                }
                $response->setContent($output);
            }
        } else {
            $response->setContent($PMF_LANG['err_NotAuth']);
        }
        break;

    // save sticky FAQs
    case 'save_sticky_records':
        if ($permission['editbt']) {
            if (!empty($items)) {
                $faq = new PMF_Faq($faqConfig);

                $output = '';
                foreach ($items as $item) {
                    if (is_array($item) && count($item) == 3 && PMF_Language::isASupportedLanguage($item[1])) {
                        $output .= $faq->updateRecordFlag((int)$item[0], addslashes($item[1]), (int)$item[2], 'sticky');
                    }
                }
                $response->setContent($output);
            }
        } else {
            $response->setContent($PMF_LANG['err_NotAuth']);
        }
        break;

    // search FAQs for suggestions
    case 'search_records':
        if ($permission['editbt']) {

            $faq             = new PMF_Faq($faqConfig);
            $faqSearch       = new PMF_Search($faqConfig);
            $faqSearchResult = new PMF_Search_Resultset($user, $faq, $faqConfig);
            $searchResult    = '';
            $searchString    = PMF_Filter::filterInput(INPUT_POST, 'search', FILTER_SANITIZE_STRIPPED);

            if (!is_null($searchString)) {
                $searchResult = $faqSearch->search($searchString, false);

                $faqSearchResult->reviewResultset($searchResult);

                $searchHelper = new PMF_Helper_Search($faqConfig);
                $searchHelper->setSearchterm($searchString);

                $response->setContent($searchHelper->renderAdminSuggestionResult($faqSearchResult));
            }
            
        } else {
            $response->setContent($PMF_LANG['err_NotAuth']);
        }
        break;

    // delete FAQs
    case 'delete_record':
        if ($permission['delbt']) {

            $recordId   = PMF_Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
            $recordLang = PMF_Filter::filterInput(INPUT_POST, 'record_lang', FILTER_SANITIZE_STRING);

            $logging = new PMF_Logging($faqConfig);
            $logging->logAdmin($user, 'Deleted FAQ ID ' . $recordId);

            $faq->deleteRecord($recordId, $recordLang);
            $response->setContent($PMF_LANG['ad_entry_delsuc']);
            
        } else {
            $response->setContent($PMF_LANG['err_NotAuth']);
        }
        break;

    // delete open questions
    case 'delete_question':
        if ($permission['delquestion']) {

            $checks  = array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY
            );
            $questionIds = PMF_Filter::filterInputArray(INPUT_POST, array('questions' => $checks));

            if (!is_null($questionIds['questions'])) {
                foreach ($questionIds['questions'] as $questionId) {
                    $faq->deleteQuestion((int)$questionId);
                }
            }
            $response->setContent($PMF_LANG['ad_entry_delsuc']);
        } else {
            $response->setContent($PMF_LANG['err_NotAuth']);
        }
        break;
}

$response->send();
