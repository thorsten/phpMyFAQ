<?php

/**
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @copyright 2006-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-05-08
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';
require PMF_ROOT_DIR . '/lang/language_en.php';

//
// Get language (default: english)
//
$language = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
if (!is_null($language) && Language::isASupportedLanguage($language)) {
    require PMF_ROOT_DIR . '/lang/language_' . $language . '.php';
}


//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($language);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($language);

$user = CurrentUser::getCurrentUser($faqConfig);

$refreshTime = (PMF_AUTH_TIMEOUT - PMF_AUTH_TIMEOUT_WARNING) * 60;
?>
<!DOCTYPE html>
<html lang="<?= Translation::get('metaLanguage'); ?>" class="no-js">
<head>
    <meta charset="utf-8">

    <title>phpMyFAQ - "Welcome to the real world."</title>

    <meta content="Only Chuck Norris can divide by zero." name="description">
    <meta content="phpMyFAQ Team" name="author" >
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="phpMyFAQ <?= System::getVersion(); ?>" name="application-name">
    <meta content="© 2001-<?= date('Y') ?> phpMyFAQ Team" name="copyright">
    <meta content="phpMyFAQ Team" name="publisher">
    <?php if ($user->isLoggedIn() && ($refreshTime > 0)) { ?>
    <script>
        const sessionTimeoutWarning = () => {
          if (window.confirm('<?php printf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING); ?>')) {
            location.href = location.href;
          }
        };

        const sessionTimeoutClock = (topRef, sessionStart, expire) => {
          expire.setSeconds(expire.getSeconds() - 1);
          const duration = expire - sessionStart;

          if (expire.getFullYear() < 2022) {
            parent.location.search = '?action=logout';
            return;
          }

          if (topRef) {
            topRef.innerHTML = new Date(duration).toISOString().substring(11, 19);
          }
        };

        window.onload = () => {
          const expire = new Date();
          const sessionStart = new Date();
          expire.setSeconds(<?= PMF_AUTH_TIMEOUT ?> * 60);

          const topRef = top.document.getElementById('sessioncounter');

          window.setTimeout(sessionTimeoutWarning, <?= $refreshTime ?> * 1000);
          window.setInterval(
            () => {
              sessionTimeoutClock(topRef, sessionStart, expire);
            },
            1000,
          );
        };
      </script>
    <?php } ?>
</head>
<body>

</body>
</html>
