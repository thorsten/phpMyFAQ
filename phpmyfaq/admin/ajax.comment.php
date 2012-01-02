<?php
/**
 * AJAX: deletes comments with the given id
 * 
 * PHP Version 5.2.3
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
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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