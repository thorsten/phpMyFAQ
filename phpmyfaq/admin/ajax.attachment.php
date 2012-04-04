<?php
/**
 * AJAX: handles an attachment with the given id
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$attId      = PMF_Filter::filterInput(INPUT_GET, 'attId', FILTER_VALIDATE_INT);

$att = PMF_Attachment_Factory::create($attId);

if ($att) {
    switch ($ajaxAction) {
        case 'delete':
            if ($att->delete()) {
                print $PMF_LANG['ad_att_delsuc'];
            } else {
                print $PMF_LANG['ad_att_delfail'];
            }
            break;
    } 
}
