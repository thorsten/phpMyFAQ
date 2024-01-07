<?php

/**
 * Footer of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-26
 */

use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
            </div>
        </main>

        <div class="toast-container position-fixed top-0 start-50 translate-middle-x mt-5 p-3">
            <div id="pmf-notification" class="toast align-items-center text-bg-primary shadow border-0" role="alert"
                 aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="pmf-notification-message">
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                            aria-label="Close">
                    </button>
                </div>
            </div>
            <div id="pmf-notification-error" class="toast align-items-center text-bg-danger shadow border-0"
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="pmf-notification-error-message">
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                            aria-label="Close">
                    </button>
                </div>
            </div>
        </div>

        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">
                        Proudly <?= System::getPoweredByString() ?> |
                        <a target="_blank" class="text-decoration-none"
                           href="https://en.isupportukraine.eu/trombinoscope">
                            #StandWithUkraine
                        </a>
                    </div>
                    <div>
                        <a target="_blank" rel="noopener" href="<?= System::getDocumentationUrl() ?>">
                            Documentation
                        </a>
                        &middot;
                        <a target="_blank" rel="noopener" href="https://www.buymeacoffee.com/thorsten">
                            Buy us a coffee
                        </a>
                        &middot;
                        <a target="_blank" rel="noopener" href="https://facebook.com/phpMyFAQ">
                            Facebook
                        </a>
                        &middot;
                        &copy; 2001 - <?= date('Y') ?>
                        <a target="_blank" rel="noopener" href="<?= System::PHPMYFAQ_URL ?>">
                            phpMyFAQ Team
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>


<?php

$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->isLoggedIn()) {
    ?>
  <iframe id="keepPMFSessionAlive" src="./session.keepalive.php?lang=<?= $faqLangCode ?>" width="0" height="0"
          style="display: none;" name="keep-phpmyfaq-session-alive"></iframe>
    <?php
}
?>
<script src="../assets/dist/backend.js?<?= time(); ?>"></script>
</body>
</html>
