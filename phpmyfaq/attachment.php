<?php
/**
 * Handle attachment diwnloads
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @since     2009-06-23
 * @version   SVN: $Id: attachment.php 4236 2009-05-01 19:13:33Z anatoliy $
 * @copyright 2002-2009 phpMyFAQ Team
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
set_time_limit(0);

if(headers_sent()) {
    die();
}

/**
 * TODO check if user is allowed to download this file
 */

$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$att = PMF_Attachment_Factory::create($id);

if($att) {
    $att->rawOut();
    exit;
}

/**
 * If we're here, there was an error with file download
 */
$tpl->processBlock('writeContent', 'attachmentErrors', array('item' => 'Error'));
$tpl->processTemplate('writeContent', array());
$tpl->includeTemplate('writeContent', 'index');

?>