<?php
/**
 * $Id: session.keepalive.php,v 1.3 2006-08-19 15:22:11 matteo Exp $
 *
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>
 *
 * @package     phpMyFAQ
 * @access      private
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2006-05-08
 * @copyright   (c) 2006 phpMyFAQ Team
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

require_once('../inc/Init.php');
require_once('../inc/PMF_User/CurrentUser.php');
require_once('../lang/language_en.php');
if (isset($_GET['lang']) && PMF_Init::isASupportedLanguage($_GET['lang'])) {
    require_once('../lang/language_'.$_GET['lang'].'.php');
}

$user = PMF_CurrentUser::getFromSession($faqconfig->get('ipcheck'));

$refreshTime = (PMF_SESSION_ID_EXPIRES - PMF_SESSION_ID_REFRESH) * 60;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
    <head>
        <title>phpMyFAQ - "Welcome to the real world."</title>
        <meta name="copyright" content="(c) 2001-2006 phpMyFAQ Team" />
        <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />
        <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
        <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
<?php
if (isset($user) && ($refreshTime > 0)) {
?>
        <script type="text/javascript">
        <!--
            function _PMFSessionTimeoutWarning()
            {
                if (window.confirm('<?php printf($PMF_LANG['ad_session_expiring'], PMF_SESSION_ID_REFRESH); ?>')) {
                    // Reload this iframe: session refreshed!
                    window.location.reload();
                }
            }
            function _PMFClockPad(number)
            {
                return (new String(number).length < 2 ? '0' + number : '' + number);
            }
            function _PMFSessionTimeoutClock()
            {
                _PMFSessionTimeoutSeconds--;
                if (top.document.getElementById('sessioncounter')) {
                    var nClockHours   = parseInt(_PMFSessionTimeoutSeconds/3600, 10);
                    var nClockMinutes = parseInt((_PMFSessionTimeoutSeconds - (3600*nClockHours))/60, 10);
                    var nClockSeconds = parseInt((_PMFSessionTimeoutSeconds - (3600*nClockHours) - (60*nClockMinutes)), 10);
                    var sClock = _PMFClockPad(nClockHours) + ':' + _PMFClockPad(nClockMinutes) + ':' + _PMFClockPad(nClockSeconds);
                    top.document.getElementById('sessioncounter').innerHTML = sClock;
                }
                if ('00:00:00' != sClock) {
                    window.setTimeout("_PMFSessionTimeoutClock()", 1000);
                }
            }
            var _PMFSessionTimeoutSeconds = <?php print PMF_SESSION_ID_EXPIRES?> * 60;
            window.setTimeout("_PMFSessionTimeoutWarning()", <?php print $refreshTime; ?> * 1000);
            window.setTimeout("_PMFSessionTimeoutClock()", 500);
        //-->
        </script>
<?php
}
?>
    </head>
    <body>
    </body>
</html>
