<?php
/**
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>
 *
 * @package     phpMyFAQ
 * @access      private
 * @author      Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Uwe Pries <uwe.pries@digartis.de>
 * @since       2006-05-08
 * @copyright   (c) 2006-2009 phpMyFAQ Team
 * @version     SVN: $Id$ 
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

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ_ADMIN', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

// Preload English strings
require_once(PMF_ROOT_DIR.'/lang/language_en.php');

//
// Get language (default: english)
//
if (isset($_GET['lang']) && PMF_Init::isASupportedLanguage($_GET['lang'])) {
    require_once(PMF_ROOT_DIR.'/lang/language_'.$_GET['lang'].'.php');
}

$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

$refreshTime = (PMF_SESSION_ID_EXPIRES - PMF_SESSION_ID_REFRESH) * 60;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
    <head>
        <title>phpMyFAQ - "Welcome to the real world."</title>
        <meta name="copyright" content="(c) 2001-2009 phpMyFAQ Team" />
        <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />
        <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
        <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
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
                if (expire.getFullYear() < 2007) {
                    parent.location.search = '?action=logout';
                    return;
                }

                // refresh clock in GUI
                if (topRef) {
                    topRef.innerHTML = ('' + expire).match(/\d\d:\d\d:\d\d/);
                }
            }

            window.onload = function() {
                var expire = new Date(2007, 0, 1);
                expire.setSeconds(<?php print PMF_SESSION_ID_EXPIRES; ?> * 60);
                var topRef = top.document.getElementById('sessioncounter');

                window.setTimeout(_PMFSessionTimeoutWarning, <?php print $refreshTime; ?> * 1000);
                window.setInterval(
                    function() {
                        _PMFSessionTimeoutClock(topRef, expire);
                    },
                    1000
                );
            }
        // --> /*]]>*/
        </script>
<?php
}
?>
    </head>
    <body>
    </body>
</html>
