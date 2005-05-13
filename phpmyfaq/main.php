<?php
/**
* $Id: main.php,v 1.3 2005-05-13 17:35:47 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-23
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

$tpl->processTemplate ('writeContent', array(
                       'writeNewsHeader' => $PMF_CONF['title'].$PMF_LANG['msgNews'],
                       'writeNews' => generateNews(),
                       'writeNumberOfArticles' => $PMF_LANG['msgHomeThereAre'].generateNumberOfArticles().$PMF_LANG['msgHomeArticlesOnline']));

$tpl->includeTemplate('writeContent', 'index');
?>