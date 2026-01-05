<?php

/**
 * Minimal service provider for Forms/Repository wiring.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Forms;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FormsServiceProvider
{
    public static function register(ContainerBuilder $containerBuilder): void
    {
        // Repository Definition (concrete)
        if (!$containerBuilder->has(FormsRepository::class)) {
            $repoDef = new Definition(FormsRepository::class);
            $repoDef->setArgument(0, new Reference('phpmyfaq.configuration'));
            $repoDef->setPublic(true);
            $containerBuilder->setDefinition(FormsRepository::class, $repoDef);
        }

        // Interface Alias -> concrete Repository
        if (!$containerBuilder->has(FormsRepositoryInterface::class)) {
            $containerBuilder->setAlias(FormsRepositoryInterface::class, new Alias(FormsRepository::class, true));
        }

        // Forms service
        if (!$containerBuilder->has(Forms::class)) {
            $formsDef = new Definition(Forms::class);
            $formsDef->setArgument(0, new Reference('phpmyfaq.configuration'));
            $formsDef->setArgument(1, new Reference(FormsRepositoryInterface::class));
            $formsDef->setPublic(true);
            $containerBuilder->setDefinition(Forms::class, $formsDef);
        }
    }
}
