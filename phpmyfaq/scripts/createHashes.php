<?php
/**
 * This scripts iterates recursively through the whole phpMyFAQ project and
 * creates SHA-1 keys for all files
 *
 * PHP Version 5.2.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Scripts
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-11
 */

require dirname(__FILE__) . '/../inc/System.php';

$system = new PMF_System();

print $system->createHashes();