<?php
/**
 * An error page that is displayed if the user has no admin permissions.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */
?>
    <header>
        <h2 class="page-header"><?php print $PMF_LANG['ad_pmf_info']; ?></h2>
    </header>

    <p class="error"><?php print $PMF_LANG['err_NotAuth'] ?></p>
