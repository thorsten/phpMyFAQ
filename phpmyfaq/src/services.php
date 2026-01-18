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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-07
 */

declare(strict_types=1);

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Category;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Administration\LatestUsers;
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
use phpMyFAQ\Command\CreateHashesCommand;
use phpMyFAQ\Comment\CommentsRepository;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\CustomPage;
use phpMyFAQ\CustomPage\CustomPageRepository;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Forms;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Mail;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Question;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Seo;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Service\McpServer\PhpMyFaqMcpServer;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Sitemap;
use phpMyFAQ\StopWords;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation\ContentTranslationService;
use phpMyFAQ\Translation\TranslationProviderFactory;
use phpMyFAQ\Translation\TranslationProviderInterface;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserSession;
use phpMyFAQ\Visits;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    // Parameters
    $container->parameters();

    // Services builder
    $services = $container->services();

    // Apply defaults to reduce args boilerplate
    $services->defaults()->autowire()->autoconfigure();

    // ========== Core Symfony framework services ==========
    $services->set('filesystem', Filesystem::class);
    $services->set('session', Session::class);
    $services->alias(SessionInterface::class, 'session');

    // ========== phpMyFAQ services ==========
    $services->set('phpmyfaq.admin.api', Api::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.system'),
    ]);

    $services->set('phpmyfaq.admin.admin-log', AdminLog::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.backup', Backup::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.database.helper'),
    ]);

    $services->set('phpmyfaq.admin.category', Category::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.changelog', Changelog::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.helper', Helper::class);

    $services->set('phpmyfaq.admin.faq', AdminFaq::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.latest-users', LatestUsers::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.rating-data', RatingData::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.admin.session', AdminSession::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.attachment-collection', AttachmentCollection::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.auth', Auth::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.backup', Backup::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.database.helper'),
    ]);

    $services->set('phpmyfaq.bookmark', Bookmark::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.user.current_user'),
    ]);

    $services
        ->set('phpmyfaq.captcha', Captcha::class)
        ->factory([Captcha::class, 'getInstance'])
        ->args([
            service('phpmyfaq.configuration'),
        ]);

    $services
        ->set('phpmyfaq.captcha.helper.captcha_helper', CaptchaHelper::class)
        ->factory([CaptchaHelper::class, 'getInstance'])
        ->args([
            service('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.category.image', Image::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.category.order', Order::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.category.permission', Permission::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.category', \phpMyFAQ\Category::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.comment.comments-repository', CommentsRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.comments', Comments::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.comment.comments-repository'),
    ]);

    $services->set('phpmyfaq.configuration', Configuration::class)->factory([
        Configuration::class,
        'getConfigurationInstance',
    ]);

    $services->set('phpmyfaq.date', Date::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.faq', Faq::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.faq.metadata', MetaData::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.faq.permission', Faq\Permission::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.faq.statistics', Statistics::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.database.helper', DatabaseHelper::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.forms', Forms::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.glossary', Glossary::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.helper.category-helper', CategoryHelper::class);

    $services->set('phpmyfaq.helper.faq', FaqHelper::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.helper.question', QuestionHelper::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.helper.search', SearchHelper::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.helper.statistics', StatisticsHelper::class)->args([
        service('phpmyfaq.admin.session'),
        service('phpmyfaq.visits'),
        service('phpmyfaq.date'),
    ]);

    $services->set('phpmyfaq.helper.user-helper', UserHelper::class)->args([
        service('phpmyfaq.user.current_user'),
    ]);

    $services->set('phpmyfaq.instance', Instance::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.instance.client', Instance\Client::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.instance.elasticsearch', Elasticsearch::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.instance.opensearch', OpenSearch::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.language', Language::class)->args([
        service('phpmyfaq.configuration'),
        service('session'),
    ]);

    $services->set('phpmyfaq.language.plurals', Plurals::class);

    $services->set('phpmyfaq.mail', Mail::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.news', News::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.custom-page-repository', CustomPageRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.custom-page', CustomPage::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.custom-page-repository'),
        service('phpmyfaq.seo-repository'),
    ]);

    $services->set('phpmyfaq.notification', Notification::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.plugin.plugin-manager', PluginManager::class);

    $services->set('phpmyfaq.question', Question::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.rating', Rating::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.search', Search::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.seo', Seo::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.seo-repository', SeoRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services
        ->set('phpmyfaq.session.token', Token::class)
        ->factory([Token::class, 'getInstance'])
        ->args([
            service('session'),
        ]);

    $services->set('phpmyfaq.session.wrapper', SessionWrapper::class)->args([
        service('session'),
    ]);

    $services->set('phpmyfaq.sitemap', Sitemap::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.services.gravatar', Gravatar::class);

    $services->set('phpmyfaq.setup.environment_configurator', EnvironmentConfigurator::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.setup.update', Update::class)->args([
        service('phpmyfaq.system'),
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.setup.upgrade', Upgrade::class)->args([
        service('phpmyfaq.system'),
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.stop-words', StopWords::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.system', System::class);

    $services->set('phpmyfaq.tags', Tags::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    // HTTP Client for Translation APIs
    $services
        ->set('phpmyfaq.http-client', HttpClientInterface::class)
        ->factory([HttpClient::class, 'create'])
        ->args([[
            'max_redirects' => 2,
            'timeout' => 30,
        ]]);

    // Translation Provider (factory-created)
    $services
        ->set('phpmyfaq.translation.provider', TranslationProviderInterface::class)
        ->factory([TranslationProviderFactory::class, 'create'])
        ->args([
            service('phpmyfaq.configuration'),
            service('phpmyfaq.http-client'),
        ]);

    // Content Translation Service
    $services->set('phpmyfaq.translation.content-translation-service', ContentTranslationService::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.user', User::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services
        ->set('phpmyfaq.user.current_user', CurrentUser::class)
        ->factory([CurrentUser::class, 'getCurrentUser'])
        ->args([
            service('phpmyfaq.configuration'),
        ]);

    $services->set('phpmyfaq.user.session', UserSession::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.user.two-factor', TwoFactor::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.user.current_user'),
    ]);

    $services->set('phpmyfaq.visits', Visits::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.service.mcp-server', PhpMyFaqMcpServer::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.language'),
        service('phpmyfaq.search'),
        service('phpmyfaq.faq'),
    ]);

    $services->set(CreateHashesCommand::class)->args([
        service('phpmyfaq.system'),
        service('filesystem'),
    ]);
};
