<?php
/**
* $Id: savevoting.php,v 1.20 2006-09-19 21:39:38 matteo Exp $
*
* Saves a user voting
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-16
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

$record_id   = (isset($_POST['artikel'])) ? intval($_POST['artikel']) : '';
$voting      = (isset($_POST['vote'])) ? intval($_POST['vote']) : 0;
$user_ip     = (isset($_POST['userip'])) ? strip_tags($_POST['userip']) : '';

if (isset($voting) && $faq->votingCheck($record_id, $user_ip) && $voting > 0 && $voting < 6) {
    Tracking('save_voting', $record_id);

    $votingData = array(
        'record_id'  => $record_id,
        'vote'       => $voting,
        'user_ip'    => $user_ip);

    if ($faq->getNumberOfVotings($record_id)) {
        $faq->addVoting($votingData);
    }  else {
        $faq->updateVoting($votingData);
    }

    $tpl->processTemplate ('writeContent', array(
        'msgVoteThanks' => $PMF_LANG['msgVoteThanks']));

} elseif (isset($voting) && !$faq->votingCheck($record_id, $user_ip)) {
    Tracking('error_save_voting', $record_id);
    $tpl->processTemplate('writeContent', array(
        'msgVoteThanks' => $PMF_LANG['err_VoteTooMuch']));

} else {
    Tracking('error_save_voting', $record_id);
    $tpl->processTemplate ('writeContent', array(
        'msgVoteThanks' => $PMF_LANG['err_noVote']));

}

$tpl->includeTemplate('writeContent', 'index');
