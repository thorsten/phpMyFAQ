<?php

/**
 * AJAX: handles an image upload from TinyMCE.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-10-10
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$upload = PMF_Filter::filterInput(INPUT_GET, 'image', FILTER_VALIDATE_INT);
$uploadedFile = isset($_FILES['upload']) ? $_FILES['upload'] : '';

switch ($ajaxAction) {

    case 'upload':

        $uploadDir = PMF_ROOT_DIR.'/images/';
        $uploadFile = basename($_FILES['upload']['name']);
        $isUploaded = false;
        $height = $width = 0;

        if (is_uploaded_file($uploadedFile['tmp_name']) &&
            $uploadedFile['size'] < $faqConfig->get('records.maxAttachmentSize')) {

            $info = getimagesize($uploadedFile['tmp_name']);

            if (false === $info) {
                $isUploaded = false;
            } else {
                list($width, $height) = $info;
                if (move_uploaded_file($uploadedFile['tmp_name'], $uploadDir.$uploadFile)) {
                    $isUploaded = true;
                } else {
                    $isUploaded = false;
                }
            }
            ?>
            <script>
                window.parent.window.pmfImageUpload.uploadFinished({
                    filename: '<?php echo $faqConfig->getDefaultUrl().'images/'.$uploadFile ?>',
                    result: '<?php echo $isUploaded ? 'file_uploaded' : 'error' ?>',
                    resultCode: '<?php echo $isUploaded ? 'success' : 'failed' ?>',
                    height: <?php echo $height ?>,
                    width: <?php echo $width ?>
                });
            </script>
            <?php
        } else {
            ?>
            <script>
                window.parent.window.pmfImageUpload.uploadFinished({
                    filename: '',
                    result: 'Image too big',
                    resultCode: 'failed',
                    height: 0,
                    width: 0
                });
            </script>
            <?php
        }
        break;
}
