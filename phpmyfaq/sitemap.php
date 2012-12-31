<?php
/**
 * Shows the whole FAQ articles
 *
 * @param      phpMyFAQ
 * @subpackage Frontend
 * @author     Thomas Zeithaml <seo@annatom.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2005-08-21
 * @copyright  2005-2013 phpMyFAQ Team
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

$faqsession->userTracking('sitemap', 0);

$letter = PMF_Filter::filterInput(INPUT_GET, 'letter', FILTER_SANITIZE_STRIPPED);
if (!is_null($letter) && (1 == PMF_String::strlen($letter))) {
    $currentLetter = strtoupper($db->escape_string(PMF_String::substr($letter, 0, 1)));
} else {
    $currentLetter = 'A';
}

$sitemap = new PMF_Sitemap($current_user, $current_groups);

$tpl->processTemplate (
    'writeContent', array(
        'writeLetters'       => $sitemap->getAllFirstLetters(),
        'writeMap'           => $sitemap->getRecordsFromLetter($currentLetter),
        'writeCurrentLetter' => $currentLetter));

$tpl->includeTemplate('writeContent', 'index');