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

$attachmentErrors = array();

if(headers_sent()) {
    die();
}

$recordId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$attachmentFilename  = PMF_Filter::filterInput(INPUT_GET, 'file', FILTER_SANITIZE_STRING);

$recordAttachmentsDir = PMF_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $recordId;
$attachmentFilepath = $recordAttachmentsDir . DIRECTORY_SEPARATOR . $attachmentFilename;  

$faq->getRecord($recordId);

if(0 !== strpos(realpath($attachmentFilepath), $recordAttachmentsDir)) {
    /**
     * Check that nobody is traversing
     * TODO much better would be to save attachment info
     *      into db and handle downloads by index
     */
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
} else if(empty($recordId) || empty($attachmentFilename) || !isAttachmentDirOk($recordId) ||
   !file_exists($attachmentFilepath) 
) {
    header('Status: 404 Not Found', true);
    $attachmentErrors[] = $PMF_LANG['msgAttachmentNotFound'];
}

if(empty($attachmentErrors)) {

    /**
     * overwriting text/html header previously sent in index.php
     * TODO read file mime type, decide either to send file as
     *       atachment or inline, send exact content type 
     *       
     */
    header('Content-Type: application/octet-stream', true);
    
    header('Content-Disposition: attachment; filename=' . $attachmentFilename , true);
    header('Status: 200 OK', true);
    
    readfile($attachmentFilepath);
    
    exit;
}

/**
 * If we're here, there was an error with file download
 */
$tpl->processBlock('writeContent', 'attachmentErrors', array('item' => $attachmentErrors));
$tpl->processTemplate('writeContent', array());
$tpl->includeTemplate('writeContent', 'index');

?>