<?php
/**
* $Id: ask.php,v 1.2 2004-11-22 21:01:10 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-17
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

Tracking('searchpage', 0);

$tree->buildTree();

$tpl->processTemplate('writeContent', array(
				      'msgQuestion' => $PMF_LANG['msgQuestion'],
				      'msgNewQuestion' => $PMF_LANG['msgNewQuestion'],
                      'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=savequestion',
                      'msgNewContentName' => $PMF_LANG['msgNewContentName'],
                      'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
                      'msgAskCategory' => $PMF_LANG['msgAskCategory'],
                      'printCategoryOptions' => $tree->printCategoryOptions(),
                      'msgAskYourQuestion' => $PMF_LANG['msgAskYourQuestion'],
                      'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit']));

$tpl->includeTemplate('writeContent', 'index');
?>