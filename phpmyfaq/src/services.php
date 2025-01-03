<?php

/**
 * phpMyFAQ service container configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-07
 */

use phpMyFAQ\Administration\Category;
use phpMyFAQ\Bookmark;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Instance;
use phpMyFAQ\Language;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Session;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Sitemap;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    // Parameters
    $container->parameters();

    // Services
    $services = $container->services();

    $services->set('phpmyfaq.admin.category', Category::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.bookmark', Bookmark::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('phpmyfaq.user.current_user')
        ]);

    $services->set('phpmyfaq.captcha', Captcha::class)
        ->factory([Captcha::class, 'getInstance'])
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.captcha.helper.captcha_helper', CaptchaHelper::class)
        ->factory([CaptchaHelper::class, 'getInstance'])
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.category.permission', Permission::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.configuration', Configuration::class)
        ->factory([Configuration::class, 'getConfigurationInstance']);

    $services->set('phpmyfaq.faq', Faq::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.faq.metadata', MetaData::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.faq.statistics', Statistics::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.instance', Instance::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.language', Language::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.session', Session::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.sitemap', Sitemap::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.services.gravatar', Gravatar::class);

    $services->set('phpmyfaq.setup.environment_configurator', EnvironmentConfigurator::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.setup.update', Update::class)
        ->args([
            new Reference('phpmyfaq.system'),
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.setup.upgrade', Upgrade::class)
        ->args([
            new Reference('phpmyfaq.system'),
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.system', System::class);

    $services->set('phpmyfaq.tags', Tags::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.user.current_user', CurrentUser::class)
        ->factory([CurrentUser::class, 'getCurrentUser'])
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);
};
