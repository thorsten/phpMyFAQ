<?php
/**
 * This is only a "static page" with thank you interaction after successful anonymous action
 *
 * @package   phpMyFAQ
 * @author    Elger Thiele <elger@phpmyfaq.de>
 * @since     2008-01-25
 * @copyright 2008 phpMyFAQ Team
 * @version   CVS: register.php,v 1.1 2008/01/26 15:10:11 thorstenr Exp
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

$tpl->processTemplate('writeContent', array(
'successMessage' => $PMF_LANG['successMessage'],
'msgRegThankYou' => $PMF_LANG['msgRegThankYou'],
));

$tpl->includeTemplate('writeContent', 'index');