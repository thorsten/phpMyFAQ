<?php
/**
 * $Id: thankyou.php,v 1.1 2008-01-26 15:10:11 thorstenr Exp $
 *
 * Thank You!
 *
 * @author      Elger Thiele <elger@phpmyfaq.de>
 * @since       2008-01-25
 * @copyright   (c) 2002-2008 phpMyFAQ Team
 *
 * This is only a "static page" with thank you interaction after successful anonymous action
 *
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