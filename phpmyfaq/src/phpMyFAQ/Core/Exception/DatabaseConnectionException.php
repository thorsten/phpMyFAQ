<?php

/**
 * Database connection exception class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Core\Exception;

use phpMyFAQ\Core\Exception;

/**
 * Class DatabaseConnectionException
 *
 * Thrown when the application cannot establish a connection to the database.
 *
 * @package phpMyFAQ\Core\Exception
 */
class DatabaseConnectionException extends Exception {}
