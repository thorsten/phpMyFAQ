<?php
/**
* $Id: main.php,v 1.11 2006-06-18 06:59:27 matteo Exp $
*
* The main start page with the Top10 and the latest messages
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License""); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$tpl->processTemplate ('writeContent', array(
    'writeNewsHeader'       => $PMF_CONF['title'].$PMF_LANG['msgNews'],
    'writeNews'             => generateNews($LANGCODE),
    'writeNumberOfArticles' => $PMF_LANG['msgHomeThereAre'].$faq->getNumberOfRecords($LANGCODE).$PMF_LANG['msgHomeArticlesOnline'],
    'writeTopTenHeader'     => $PMF_LANG['msgTopTen'],
    'writeTopTenRow'        => $faq->getTopTen(),
    'writeNewestHeader'     => $PMF_LANG['msgLatestArticles'],
    'writeNewestRow'        => $faq->getLatest()));

$tpl->includeTemplate('writeContent', 'index');
