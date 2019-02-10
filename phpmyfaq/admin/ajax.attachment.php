<?php

/**
 * AJAX: handles an attachment with the given id.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-20
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
$attId = PMF_Filter::filterInput(INPUT_GET, 'attId', FILTER_VALIDATE_INT);
$csrfToken = PMF_Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

$att = PMF_Attachment_Factory::create($attId);

if ($att) {
    switch ($ajaxAction) {
        case 'delete':

            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                echo $PMF_LANG['err_NotAuth'];
                exit(1);
            }

            if ($att->delete()) {
                echo $PMF_LANG['msgAttachmentsDeleted'];
            } else {
                echo $PMF_LANG['ad_att_delfail'];
            }
            break;
    }
}
