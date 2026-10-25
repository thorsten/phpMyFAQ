<?php

/**
 * Resets the request-scoped static state between two requests of a long-running worker.
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
 * @since     2026-10-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use phpMyFAQ\Configuration;
use phpMyFAQ\Container\ContainerRegistry;
use phpMyFAQ\Database;
use phpMyFAQ\Instance\Database as InstanceDatabase;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\Tracking;

/**
 * phpMyFAQ keeps the configuration, the database connection, the translation, the CSRF token and a few other
 * objects in static singletons that are created once per PHP process. In FrankenPHP worker mode one process
 * serves many requests, so these singletons must be dropped before every request; otherwise the language, the
 * tenant database or the session of one visitor would leak into the next request.
 */
final readonly class WorkerState
{
    public static function reset(): void
    {
        Configuration::resetInstance();
        Database::reset();
        InstanceDatabase::reset();
        Translation::resetInstance();
        Strings::reset();
        Token::resetInstance();
        Tracking::resetInstance();
        ContainerRegistry::reset();
        TwigWrapper::setTemplateSetName();
    }
}
