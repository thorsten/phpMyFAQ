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
 * @copyright 2003-2023 phpMyFAQ Team
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

        <!-- Session expired modal - not used yet -->
        <div class="modal fade phpmyfaq-session-expired-modal" id="sessionExpiredModal" tabindex="-1"
             aria-labelledby="sessionExpiredModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="sessionExpiredModalLabel">Session Warning</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= sprintf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary"
                                onClick="window.location.href=window.location.href">
                            Refresh page
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">
                        Proudly <?= System::getPoweredByString() ?>
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
                        <a target="_blank" rel="noopener" href="https://twitter.com/phpMyFAQ">
                            Twitter
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
if (DEBUG) {
    printf('<hr><div class="container">DEBUG INFORMATION:<br>%s</div>', $faqConfig->getDb()->log());
}

$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->isLoggedIn()) {
    ?>
  <iframe id="keepPMFSessionAlive" src="./session.keepalive.php?lang=<?= $faqLangCode ?>" width="0" height="0"
          style="display: none;"></iframe>
    <?php
}
?>
</body>
</html>
