<?php

/**
 * Process-wide registry for the Kernel's shared DI container
 *
 * The Kernel registers its container here on boot so that controllers can reuse it
 * during construction instead of parsing services.php into a private fallback
 * container — which used to happen once per controller instantiation.
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
 * @since     2026-07-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Container;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class ContainerRegistry
{
    private static ?ContainerInterface $container = null;

    public static function set(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function get(): ?ContainerInterface
    {
        return self::$container;
    }

    public static function reset(): void
    {
        self::$container = null;
    }
}
