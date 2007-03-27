<?php
/**
 * $Id: search.php,v 1.20 2007/02/04 19:27:50 thorstenr Exp $
 *
 * The Ajax powered instant response page
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2007-03-18
 * @copyright   (c) 2007 phpMyFAQ Team
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
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

Tracking('instantresponse', 0);

$searchString           = '';
$printInstantResponse   = '';

$tpl->processTemplate(
    'writeContent', array(
        'msgInstantResponse'    => $PMF_LANG['msgInstantResponse'],
        'searchString'          => $searchString,
        'writeSendAdress'       => $_SERVER['PHP_SELF'].'?'.$sids.'action=instantresponse',
        'msgSearchWord'         => $PMF_LANG['msgSearchWord'],
        'msgInstanResponse'     => $PMF_LANG['msgSearch'],
        'printInstantResponse'  => $printInstantResponse));

$tpl->includeTemplate('writeContent', 'index');