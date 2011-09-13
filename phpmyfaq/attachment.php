<?php
/**
 * Handle attachment diwnloads
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
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-06-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

set_time_limit(0);

if (headers_sent()) {
    die();
}

$attachmentErrors = array();

/**
 * TODO check if user is allowed to download this file
 */

$id  = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$att = PMF_Attachment_Factory::create($id);


if ($att) {
    try {
        $att->rawOut();
        exit(0);
    } catch (Exception $e) {
        $attachmentErrors[] = $PMF_LANG['msgAttachmentInvalid'];
    }
}

/**
 * If we're here, there was an error with file download
 */
$tpl->processBlock('writeContent', 'attachmentErrors', array('item' => implode('<br>', $attachmentErrors)));
$tpl->processTemplate('writeContent', array());
$tpl->includeTemplate('writeContent', 'index');