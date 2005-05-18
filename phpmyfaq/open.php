<?php
/**
* $Id: open.php,v 1.3 2005-05-18 17:51:53 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-17
* @copyright    (c) 2001-2005 phpMyFAQ Team
* 
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the 'License'); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an 'AS IS'
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

Tracking('open_questions', 0);

$tpl->processTemplate ('writeContent', array(
				'msgOpenQuestions' => $PMF_LANG['msgOpenQuestions'],
				'msgQuestionText' => $PMF_LANG['msgQuestionText'],
				'msgDate_User' => $PMF_LANG['msgDate_User'],
				'msgQuestion2' => $PMF_LANG['msgQuestion2'],
				'printOpenQuestions' => printOpenQuestions()
				));

$tpl->includeTemplate('writeContent', 'index');
?>