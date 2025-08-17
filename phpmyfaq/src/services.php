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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-07
 */

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Category;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\RatingData;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Attachment\AttachmentCollection;
use phpMyFAQ\Auth;
use phpMyFAQ\Bookmark;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Forms;
use phpMyFAQ\Glossary;
use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Mail;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Question;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Seo;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Service\McpServer\PhpMyFaqMcpServer;
use phpMyFAQ\Command\McpServerCommand;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Sitemap;
use phpMyFAQ\StopWords;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserSession;
use phpMyFAQ\Visits;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Session;

return static function (ContainerConfigurator $container): void {
    // Parameters
    $container->parameters();

    // Services
    $services = $container->services();

    $services->set('session', Session::class);

    $services->set('phpmyfaq.admin.api', Api::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('phpmyfaq.system')
        ]);

    $services->set('phpmyfaq.admin.admin-log', AdminLog::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.admin.category', Category::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.admin.changelog', Changelog::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.admin.helper', Helper::class);

    $services->set('phpmyfaq.admin.faq', AdminFaq::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.admin.rating-data', RatingData::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.admin.session', AdminSession::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);


    $services->set('phpmyfaq.attachment-collection', AttachmentCollection::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.auth', Auth::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.backup', Backup::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('phpmyfaq.database.helper')
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

    $services->set('phpmyfaq.category.image', Image::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.category.order', Order::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.category.permission', Permission::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.comments', Comments::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.configuration', Configuration::class)
        ->factory([Configuration::class, 'getConfigurationInstance']);

    $services->set('phpmyfaq.date', Date::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.faq', Faq::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.faq.metadata', MetaData::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.faq.permission', Faq\Permission::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.faq.statistics', Statistics::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.database.helper', DatabaseHelper::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.forms', Forms::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.glossary', Glossary::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.helper.category-helper', CategoryHelper::class);

    $services->set('phpmyfaq.helper.faq', FaqHelper::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.helper.search', SearchHelper::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.helper.statistics', StatisticsHelper::class)
        ->args([
            new Reference('phpmyfaq.admin.session'),
            new Reference('phpmyfaq.visits'),
            new Reference('phpmyfaq.date')
        ]);

    $services->set('phpmyfaq.helper.user-helper', UserHelper::class)
        ->args([
            new Reference('phpmyfaq.user.current_user')
        ]);

    $services->set('phpmyfaq.instance', Instance::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.instance.client', Instance\Client::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.instance.elasticsearch', Elasticsearch::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.language', Language::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('session')
        ]);

    $services->set('phpmyfaq.language.plurals', Plurals::class);

    $services->set('phpmyfaq.mail', Mail::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.news', News::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.notification', Notification::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.plugin.plugin-manager', PluginManager::class);

    $services->set('phpmyfaq.question', Question::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.rating', Rating::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.search', Search::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.seo', Seo::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.session.token', Token::class)
        ->factory([Token::class, 'getInstance'])
        ->args([
            new Reference('session')
        ]);

    $services->set('phpmyfaq.session.wrapper', SessionWrapper::class)
        ->args([
            new Reference('session')
        ]);

    $services->set('phpmyfaq.sitemap', Sitemap::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.services.gravatar', Gravatar::class);

    $services->set('phpmyfaq.seo', Seo::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

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

    $services->set('phpmyfaq.stop-words', StopWords::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.system', System::class);

    $services->set('phpmyfaq.tags', Tags::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.user', User::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.user.current_user', CurrentUser::class)
        ->factory([CurrentUser::class, 'getCurrentUser'])
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.user.session', UserSession::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.user.two-factor', TwoFactor::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('phpmyfaq.user.current_user')
        ]);

    $services->set('phpmyfaq.visits', Visits::class)
        ->args([
            new Reference('phpmyfaq.configuration')
        ]);

    $services->set('phpmyfaq.service.mcp-server', PhpMyFaqMcpServer::class)
        ->args([
            new Reference('phpmyfaq.configuration'),
            new Reference('phpmyfaq.language'),
            new Reference('phpmyfaq.search'),
            new Reference('phpmyfaq.faq')
        ]);
};
