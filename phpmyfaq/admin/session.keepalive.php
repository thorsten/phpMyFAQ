<?php
/**
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Uwe Pries <uwe.pries@digartis.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-05-08
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/src/Bootstrap.php';
require PMF_ROOT_DIR.'/lang/language_en.php';

//
// Get language (default: english)
//
$language = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
if (!is_null($language) && Language::isASupportedLanguage($language)) {
    require PMF_ROOT_DIR.'/lang/language_'.$language.'.php';
}

//
// Initializing static string wrapper
//
Strings::init($language);

$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
$refreshTime = (PMF_AUTH_TIMEOUT - PMF_AUTH_TIMEOUT_WARNING)*60;
?>
<!DOCTYPE html>
<html lang="<?= $PMF_LANG['metaLanguage']; ?>" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>phpMyFAQ - "Welcome to the real world."</title>

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2019 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
<?php if (isset($user) && ($refreshTime > 0)) {
    ?>
    <script>

    function _PMFSessionTimeoutWarning() {
        if (window.confirm('<?php printf($PMF_LANG['ad_session_expiring'], PMF_AUTH_TIMEOUT_WARNING);
    ?>')) {
            location.href = location.href;
        }
    }

    function _PMFSessionTimeoutClock(topRef, expire) {
        expire.setSeconds(expire.getSeconds() - 1);
        if (expire.getFullYear() < 2009) {
            parent.location.search = '?action=logout';
            return;
        }

        if (topRef) {
            topRef.innerHTML = ('' + expire).match(/\d\d:\d\d:\d\d/);
        }
    }

    window.onload = function() {
        var expire = new Date();
        expire.setSeconds(<?= PMF_AUTH_TIMEOUT ?> * 60);
        var topRef = top.document.getElementById('sessioncounter');

        window.setTimeout(_PMFSessionTimeoutWarning, <?= $refreshTime ?> * 1000);
        window.setInterval(
            function() {
                _PMFSessionTimeoutClock(topRef, expire);
            },
            1000
        );
    }
    </script>
<?php } ?>
</head>
<body>

</body>
</html>
