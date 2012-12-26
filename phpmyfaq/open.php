<?php
/**
 * Open questions frontend
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('open_questions', 0);

$tpl->parse ('writeContent', array(
    'msgOpenQuestions'   => $PMF_LANG['msgOpenQuestions'],
    'msgQuestionText'    => $PMF_LANG['msgQuestionText'],
    'msgDate_User'       => $PMF_LANG['msgDate_User'],
    'msgQuestion2'       => $PMF_LANG['msgQuestion2'],
    'printOpenQuestions' => $faq->printOpenQuestions()));

$tpl->merge('writeContent', 'index');
