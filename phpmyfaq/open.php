<?php
/**
 * Open questions frontend
 *
 * PHP Version 5.2
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
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('open_questions', 0);

$tpl->processTemplate ('writeContent', array(
    'msgOpenQuestions'   => $PMF_LANG['msgOpenQuestions'],
    'msgQuestionText'    => $PMF_LANG['msgQuestionText'],
    'msgDate_User'       => $PMF_LANG['msgDate_User'],
    'msgQuestion2'       => $PMF_LANG['msgQuestion2'],
    'printOpenQuestions' => $faq->printOpenQuestions()));

$tpl->includeTemplate('writeContent', 'index');