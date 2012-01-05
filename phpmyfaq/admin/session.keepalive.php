<?php
/**
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>
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
 * @package   Administration
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-05-08
 */

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

// Preload English strings
require PMF_ROOT_DIR.'/lang/language_en.php';

//
// Get language (default: english)
//
$_language = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
if (!is_null($_language) && PMF_Language::isASupportedLanguage($_language)) {
    require PMF_ROOT_DIR.'/lang/language_' . $_language . '.php';
}

//
// Initalizing static string wrapper
//
PMF_String::init($_language);

$user        = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));
$refreshTime = (PMF_SESSION_ID_EXPIRES - PMF_SESSION_ID_REFRESH) * 60;
?>
<!DOCTYPE html>
<html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>phpMyFAQ - "Welcome to the real world."</title>

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print $faqconfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2012 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
<?php
if (isset($user) && ($refreshTime > 0)) {
?>
    <script type="text/javascript">
    /*<![CDATA[*/ <!--
    function _PMFSessionTimeoutWarning()
    {
        if (window.confirm('<?php printf($PMF_LANG['ad_session_expiring'], PMF_SESSION_ID_REFRESH); ?>')) {
            // Reload this iframe: session refreshed!
            location.href = location.href;
        }
    }

    function _PMFSessionTimeoutClock(topRef, expire)
    {
        // decrease time
        expire.setSeconds(expire.getSeconds() - 1);
        // check if we're out of time and log out if needed
        if (expire.getFullYear() < 2009) {
            parent.location.search = '?action=logout';
            return;
        }

        // refresh clock in GUI
        if (topRef) {
            topRef.innerHTML = ('' + expire).match(/\d\d:\d\d:\d\d/);
        }
    }

    window.onload = function() {
        var expire = new Date(2009, 0, 1);
        expire.setSeconds(<?php print PMF_SESSION_ID_EXPIRES; ?> * 60);
        var topRef = top.document.getElementById('sessioncounter');

        window.setTimeout(_PMFSessionTimeoutWarning, <?php print $refreshTime; ?> * 1000);
        window.setInterval(function() {
            _PMFSessionTimeoutClock(topRef, expire);
            }, 1000);
    }
    // --> /*]]>*/
    </script>
<?php
}
?>
</head>
<body></body>
</html>
