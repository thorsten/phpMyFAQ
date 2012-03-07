<?php
/**
 * AJAX: deletes comments with the given id
 *
 * PHP Version 5.2.3
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_POST, 'ajaxaction', FILTER_SANITIZE_STRING);

if ('delete' == $ajax_action && $permission['delcomment']) {

    $comment    = new PMF_Comment();
    $checkFaqs  = array(
        'filter'  => FILTER_VALIDATE_INT,
        'flags'   => FILTER_REQUIRE_ARRAY
    );
    $checkNews  = array(
        'filter'  => FILTER_VALIDATE_INT,
        'flags'   => FILTER_REQUIRE_ARRAY
    );
    $ret     = false;
    
    $faqComments  = PMF_Filter::filterInputArray(INPUT_POST, array('faq_comments' => $checkFaqs));
    $newsComments = PMF_Filter::filterInputArray(INPUT_POST, array('news_comments' => $checkNews));

    if (!is_null($faqComments['faq_comments'])) {
        foreach ($faqComments['faq_comments'] as $commentId => $recordId) {
            $ret = $comment->deleteComment($recordId, $commentId);
        }
    }
    
    if (!is_null($newsComments['news_comments'])) {
        foreach ($newsComments['news_comments'] as $commentId => $recordId) {
            $ret = $comment->deleteComment($recordId, $commentId);
        }
    }
    
    print $ret;
} else {
    print 0;
}