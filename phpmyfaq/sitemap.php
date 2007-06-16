<?php
/**
 * $Id: sitemap.php,v 1.16.2.1 2007-06-16 14:08:31 thorstenr Exp $
 *
 * Shows the whole FAQ articles
 *
 * @author      Thomas Zeithaml <seo@annatom.de>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2005-08-21
 * @copyright   (c) 2005-2007 phpMyFAQ Team
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

Tracking('sitemap', 0);

require_once('inc/Sitemap.php');

if (isset($_GET['letter']) && is_string($_GET['letter']) && (1 == strlen($_GET['letter']))) {
    $currentLetter = strtoupper($db->escape_string(substr($_GET['letter'], 0, 1)));
} else {
    $currentLetter = 'A';
}

$sitemap = new PMF_Sitemap($db, $LANGCODE, $current_user, $current_groups);

$tpl->processTemplate (
    'writeContent', array(
        'writeLetters'          => $sitemap->getAllFirstLetters(),
        'writeMap'              => $sitemap->getRecordsFromLetter($currentLetter),
        'writeCuttentLetter'    => $currentLetter));

$tpl->includeTemplate('writeContent', 'index');