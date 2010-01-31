<?php
/**
 * Saves a user voting
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
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$record_id = PMF_Filter::filterInput(INPUT_POST, 'artikel', FILTER_VALIDATE_INT, 0);
$vote      = PMF_Filter::filterInput(INPUT_POST, 'vote', FILTER_VALIDATE_INT);
$user_ip   = PMF_Filter::filterVar($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

if (isset($vote) && $faq->votingCheck($record_id, $user_ip) && $vote > 0 && $vote < 6) {
    $faqsession->userTracking('save_voting', $record_id);

    $voting     = new PMF_Rating();
    $votingData = array(
        'id'        => null,
        'record_id' => $record_id,
        'vote'      => $vote,
        'date'      => $_SERVER['REQUEST_TIME'],
        'user_ip'   => $user_ip);

    if (!$faq->getNumberOfVotings($record_id)) {
        $voting->create($votingData);
    }  else {
        $faq->updateVoting($votingData);
    }

    $tpl->processTemplate ('writeContent', array(
                           'msgVoteThanks' => $PMF_LANG['msgVoteThanks']));

} elseif (isset($voting) && !$faq->votingCheck($record_id, $user_ip)) {
    $faqsession->userTracking('error_save_voting', $record_id);
    $tpl->processTemplate('writeContent', array(
                          'msgVoteThanks' => $PMF_LANG['err_VoteTooMuch']));

} else {
    $faqsession->userTracking('error_save_voting', $record_id);
    $tpl->processTemplate('writeContent', array(
                           'msgVoteThanks' => $PMF_LANG['err_noVote']));

}

$tpl->includeTemplate('writeContent', 'index');