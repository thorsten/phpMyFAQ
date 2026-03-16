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
use phpMyFAQ\Auth as LegacyAuth;
use phpMyFAQ\Auth\ApiKeyAuthenticator;
use phpMyFAQ\Auth\AuthChain;
use phpMyFAQ\Auth\OAuth2\Repository\AccessTokenRepository;
use phpMyFAQ\Auth\OAuth2\Repository\AuthCodeRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
use phpMyFAQ\Auth\OAuth2\Repository\RefreshTokenRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Auth\OAuth2\Repository\UserRepository;
use phpMyFAQ\Auth\OAuth2\AuthorizationServer as OAuth2AuthorizationServer;
use phpMyFAQ\Auth\OAuth2\ResourceServer as OAuth2ResourceServer;
use phpMyFAQ\Bookmark;
use phpMyFAQ\Chat;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Command\CreateHashesCommand;
use phpMyFAQ\Controller\Administration\Api\CategoryController as AdminApiCategoryController;
use phpMyFAQ\Controller\Administration\Api\CommentController as AdminApiCommentController;
use phpMyFAQ\Controller\Administration\Api\ConfigurationController as AdminApiConfigurationController;
use phpMyFAQ\Controller\Administration\Api\ConfigurationTabController as AdminApiConfigurationTabController;
use phpMyFAQ\Controller\Administration\Api\DashboardController as AdminApiDashboardController;
use phpMyFAQ\Controller\Administration\Api\ElasticsearchController as AdminApiElasticsearchController;
use phpMyFAQ\Controller\Administration\Api\ExportController as AdminApiExportController;
use phpMyFAQ\Controller\Administration\Api\FaqController as AdminApiFaqController;
use phpMyFAQ\Controller\Administration\Api\GlossaryController as AdminApiGlossaryController;
use phpMyFAQ\Controller\Administration\Api\InstanceController as AdminApiInstanceController;
use phpMyFAQ\Controller\Administration\Api\LdapController as AdminApiLdapController;
use phpMyFAQ\Controller\Administration\Api\OpenSearchController as AdminApiOpenSearchController;
use phpMyFAQ\Controller\Administration\Api\PageController as AdminApiPageController;
use phpMyFAQ\Controller\Administration\Api\QuestionController as AdminApiQuestionController;
use phpMyFAQ\Controller\Administration\Api\SessionController as AdminApiSessionController;
use phpMyFAQ\Controller\Administration\Api\StatisticsController as AdminApiStatisticsController;
use phpMyFAQ\Controller\Administration\Api\TagController as AdminApiTagController;
use phpMyFAQ\Controller\Administration\Api\TranslationController as AdminApiTranslationController;
use phpMyFAQ\Controller\Administration\Api\UpdateController as AdminApiUpdateController;
use phpMyFAQ\Controller\Administration\Api\UserController as AdminApiUserController;
use phpMyFAQ\Controller\Administration\AttachmentsController as AdminAttachmentsController;
use phpMyFAQ\Controller\Administration\AuthenticationController as AdminAuthenticationController;
use phpMyFAQ\Controller\Administration\BackupController as AdminBackupController;
use phpMyFAQ\Controller\Administration\CategoryController as AdminCategoryController;
use phpMyFAQ\Controller\Administration\CommentsController as AdminCommentsController;
use phpMyFAQ\Controller\Administration\DashboardController as AdminDashboardController;
use phpMyFAQ\Controller\Administration\ExportController as AdminExportController;
use phpMyFAQ\Controller\Administration\FaqController as AdminFaqController;
use phpMyFAQ\Controller\Administration\FormsController as AdminFormsController;
use phpMyFAQ\Controller\Administration\GlossaryController as AdminGlossaryController;
use phpMyFAQ\Controller\Administration\GroupController as AdminGroupController;
use phpMyFAQ\Controller\Administration\InstanceController as AdminInstanceController;
use phpMyFAQ\Controller\Administration\NewsController as AdminNewsController;
use phpMyFAQ\Controller\Administration\OpenQuestionsController as AdminOpenQuestionsController;
use phpMyFAQ\Controller\Administration\OrphanedFaqsController as AdminOrphanedFaqsController;
use phpMyFAQ\Controller\Administration\PageController as AdminPageController;
use phpMyFAQ\Controller\Administration\PasswordChangeController as AdminPasswordChangeController;
use phpMyFAQ\Controller\Administration\PluginController as AdminPluginController;
use phpMyFAQ\Controller\Administration\RatingController as AdminRatingController;
use phpMyFAQ\Controller\Administration\StatisticsSearchController as AdminStatisticsSearchController;
use phpMyFAQ\Controller\Administration\StatisticsSessionsController as AdminStatisticsSessionsController;
use phpMyFAQ\Controller\Administration\StickyFaqsController as AdminStickyFaqsController;
use phpMyFAQ\Controller\Administration\SystemInformationController as AdminSystemInformationController;
use phpMyFAQ\Controller\Administration\TagController as AdminTagController;
use phpMyFAQ\Controller\Administration\UserController as AdminUserController;
use phpMyFAQ\Controller\Api\CategoryController as ApiCategoryController;
use phpMyFAQ\Controller\Api\OAuth2Controller;
use phpMyFAQ\Controller\Api\CommentController as ApiCommentController;
use phpMyFAQ\Controller\Api\FaqController as ApiFaqController;
use phpMyFAQ\Controller\Api\GlossaryController as ApiGlossaryController;
use phpMyFAQ\Controller\Api\OpenQuestionController as ApiOpenQuestionController;
use phpMyFAQ\Controller\Api\QuestionController as ApiQuestionController;
use phpMyFAQ\Controller\Api\SearchController as ApiSearchController;
use phpMyFAQ\Controller\Api\TagController as ApiTagController;
use phpMyFAQ\Controller\Frontend\AttachmentController;
use phpMyFAQ\Controller\Frontend\AuthenticationController as FrontendAuthenticationController;
use phpMyFAQ\Controller\Frontend\AzureAuthenticationController as FrontendAzureAuthenticationController;
use phpMyFAQ\Controller\Frontend\CategoryController as FrontendCategoryController;
use phpMyFAQ\Controller\Frontend\ChatController as FrontendChatController;
use phpMyFAQ\Controller\Frontend\ContactController as FrontendContactController;
use phpMyFAQ\Controller\Frontend\CustomPageController as FrontendCustomPageController;
use phpMyFAQ\Controller\Frontend\FaqController as FrontendFaqController;
use phpMyFAQ\Controller\Frontend\GlossaryController as FrontendGlossaryController;
use phpMyFAQ\Controller\Frontend\NewsController as FrontendNewsController;
use phpMyFAQ\Controller\Frontend\OverviewController as FrontendOverviewController;
use phpMyFAQ\Controller\Frontend\PageNotFoundController;
use phpMyFAQ\Controller\Frontend\PdfController;
use phpMyFAQ\Controller\Frontend\QuestionsController as FrontendQuestionsController;
use phpMyFAQ\Controller\Frontend\SearchController as FrontendSearchController;
use phpMyFAQ\Controller\Frontend\SitemapController as FrontendSitemapController;
use phpMyFAQ\Controller\Frontend\StartpageController;
use phpMyFAQ\Controller\Frontend\UserController as FrontendUserController;
use phpMyFAQ\Controller\Frontend\Api\AutoCompleteController as FrontendApiAutoCompleteController;
use phpMyFAQ\Controller\Frontend\Api\CaptchaController as FrontendApiCaptchaController;
use phpMyFAQ\Controller\Frontend\Api\CommentController as FrontendApiCommentController;
use phpMyFAQ\Controller\Frontend\Api\ContactController as FrontendApiContactController;
use phpMyFAQ\Controller\Frontend\Api\FaqController as FrontendApiFaqController;
use phpMyFAQ\Controller\Frontend\Api\PushController as FrontendApiPushController;
use phpMyFAQ\Controller\Frontend\Api\QuestionController as FrontendApiQuestionController;
use phpMyFAQ\Controller\Frontend\Api\UserController as FrontendApiUserController;
use phpMyFAQ\Controller\Frontend\Api\VotingController as FrontendApiVotingController;
use phpMyFAQ\Controller\SitemapController as RootSitemapController;
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
use phpMyFAQ\Cache\CacheFactory;
use phpMyFAQ\Forms;
use phpMyFAQ\Glossary;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Ldap;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Mail;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Queue\DatabaseMessageBus;
use phpMyFAQ\Queue\Handler\ExportHandler;
use phpMyFAQ\Queue\Handler\IndexFaqHandler;
use phpMyFAQ\Queue\Handler\SendMailHandler;
use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\MessageBusFactory;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use phpMyFAQ\Queue\Worker;
use phpMyFAQ\Push\PushSubscriptionRepository;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Question;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Scheduler\TaskScheduler;
use phpMyFAQ\Seo;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\Seo\SitemapXmlService;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Service\McpServer\PhpMyFaqMcpServer;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Sitemap;
use phpMyFAQ\Storage\StorageFactory;
use phpMyFAQ\Storage\StorageInterface;
use phpMyFAQ\StopWords;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Template\ThemeManager;
use phpMyFAQ\Tenant\TenantContext;
use phpMyFAQ\Tenant\TenantContextResolver;
use phpMyFAQ\Tenant\TenantEventDispatcher;
use phpMyFAQ\Translation\ContentTranslationService;
use phpMyFAQ\Translation\TranslationProviderFactory;
use phpMyFAQ\Translation\TranslationProviderInterface;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserSession;
use phpMyFAQ\Visits;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
    $services->set('phpmyfaq.event_dispatcher', EventDispatcher::class);
    $services->alias(EventDispatcherInterface::class, 'phpmyfaq.event_dispatcher');
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

    $services->set('phpmyfaq.auth', LegacyAuth::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.api-key-authenticator', ApiKeyAuthenticator::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.chain', AuthChain::class)->args([
        service('phpmyfaq.user.current_user'),
        service('phpmyfaq.auth.api-key-authenticator'),
    ])->call('setOAuth2Authenticator', [[service('phpmyfaq.auth.oauth2.resource-server'), 'authenticate']]);
    $services->set('phpmyfaq.auth.oauth2.authorization-server', OAuth2AuthorizationServer::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.resource-server', OAuth2ResourceServer::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.client', ClientRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.scope', ScopeRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.access-token', AccessTokenRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.refresh-token', RefreshTokenRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.auth-code', AuthCodeRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.auth.oauth2.repository.user', UserRepository::class)->args([
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

    $services->set('phpmyfaq.chat', Chat::class)->args([
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

    $services->set('phpmyfaq.storage.factory', StorageFactory::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.tenant.context'),
    ]);
    $services->set('phpmyfaq.storage', StorageInterface::class)->factory([
        service('phpmyfaq.storage.factory'),
        'create',
    ]);

    $services->set('phpmyfaq.tenant.context-resolver', TenantContextResolver::class);
    $services->set('phpmyfaq.tenant.context', TenantContext::class)->factory([
        service('phpmyfaq.tenant.context-resolver'),
        'resolve',
    ]);
    $services->set('phpmyfaq.tenant.event-dispatcher', TenantEventDispatcher::class)->args([
        service('phpmyfaq.event_dispatcher'),
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

    $services->set('phpmyfaq.cache.factory', CacheFactory::class)->args([
        service('phpmyfaq.configuration'),
        PMF_ROOT_DIR . '/cache/app',
    ]);
    $services->set('phpmyfaq.cache', \Psr\Cache\CacheItemPoolInterface::class)->factory([
        service('phpmyfaq.cache.factory'),
        'create',
    ]);

    $services->set('phpmyfaq.http.rate-limiter', RateLimiter::class)->args([
        service('phpmyfaq.cache'),
    ]);

    $services->set('phpmyfaq.queue.transport.database', DatabaseTransport::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.queue.message-bus-factory', MessageBusFactory::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.queue.transport.database'),
    ]);

    $services->set('phpmyfaq.queue.message-bus', DatabaseMessageBus::class)->factory([
        service('phpmyfaq.queue.message-bus-factory'),
        'create',
    ]);

    $services->set('phpmyfaq.queue.handler.send-mail', SendMailHandler::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.queue.handler.index-faq', IndexFaqHandler::class)->args([
        service('phpmyfaq.configuration'),
    ]);
    $services->set('phpmyfaq.queue.handler.export', ExportHandler::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.queue.worker', Worker::class)->args([
        service('phpmyfaq.queue.transport.database'),
    ])->call('registerHandler', [
        SendMailMessage::class,
        service('phpmyfaq.queue.handler.send-mail'),
    ])->call('registerHandler', [
        IndexFaqMessage::class,
        service('phpmyfaq.queue.handler.index-faq'),
    ])->call('registerHandler', [
        ExportMessage::class,
        service('phpmyfaq.queue.handler.export'),
    ]);

    $services->set('phpmyfaq.scheduler.task-scheduler', TaskScheduler::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.admin.session'),
        service('phpmyfaq.admin.backup'),
        service('phpmyfaq.faq.statistics'),
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

    $services->set('phpmyfaq.ldap', Ldap::class)->args([
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

    $services->set('phpmyfaq.push.subscription-repository', PushSubscriptionRepository::class)->args([
        service('phpmyfaq.configuration'),
    ]);

    $services->set('phpmyfaq.push.web-push-service', WebPushService::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.push.subscription-repository'),
    ]);

    $services->set('phpmyfaq.notification', Notification::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.push.web-push-service'),
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

    $services->set('phpmyfaq.seo.sitemap-xml', SitemapXmlService::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.faq.statistics'),
        service('phpmyfaq.custom-page'),
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

    $services->set('phpmyfaq.template.theme-manager', ThemeManager::class)->args([
        service('phpmyfaq.configuration'),
        service('phpmyfaq.storage'),
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

    $services->set(CreateHashesCommand::class, CreateHashesCommand::class)->args([
        service('phpmyfaq.system'),
        service('filesystem'),
    ]);

    // ========== Controller services (constructor injection) ==========

    // Batch 1: Controller/Api/
    $services->set(ApiCategoryController::class, ApiCategoryController::class)->args([
        service('phpmyfaq.language'),
    ]);
    $services->set(ApiCommentController::class, ApiCommentController::class)->args([
        service('phpmyfaq.comments'),
    ]);
    $services->set(ApiFaqController::class, ApiFaqController::class)->args([
        service('phpmyfaq.faq'),
        service('phpmyfaq.tags'),
        service('phpmyfaq.faq.statistics'),
        service('phpmyfaq.faq.metadata'),
    ]);
    $services->set(ApiGlossaryController::class, ApiGlossaryController::class)->args([
        service('phpmyfaq.glossary'),
        service('phpmyfaq.language'),
    ]);
    $services->set(ApiOpenQuestionController::class, ApiOpenQuestionController::class)->args([
        service('phpmyfaq.question'),
    ]);
    $services->set(ApiSearchController::class, ApiSearchController::class)->args([
        service('phpmyfaq.search'),
    ]);
    $services->set(ApiTagController::class, ApiTagController::class)->args([
        service('phpmyfaq.tags'),
    ]);
    $services->set(ApiQuestionController::class, ApiQuestionController::class)->args([
        service('phpmyfaq.notification'),
    ]);

    // Batch 2: Controller/Frontend/Api/
    $services->set(FrontendApiAutoCompleteController::class, FrontendApiAutoCompleteController::class)->args([
        service('phpmyfaq.faq.permission'),
        service('phpmyfaq.search'),
        service('phpmyfaq.helper.search'),
        service('phpmyfaq.language.plurals'),
    ]);
    $services->set(FrontendApiCaptchaController::class, FrontendApiCaptchaController::class)->args([
        service('phpmyfaq.captcha'),
    ]);
    $services->set(FrontendApiCommentController::class, FrontendApiCommentController::class)->args([
        service('phpmyfaq.faq'),
        service('phpmyfaq.comments'),
        service('phpmyfaq.stop-words'),
        service('phpmyfaq.user.session'),
        service('phpmyfaq.language'),
        service('phpmyfaq.user'),
        service('phpmyfaq.notification'),
        service('phpmyfaq.news'),
        service('phpmyfaq.services.gravatar'),
    ]);
    $services->set(FrontendApiContactController::class, FrontendApiContactController::class)->args([
        service('phpmyfaq.stop-words'),
        service('phpmyfaq.mail'),
    ]);
    $services->set(FrontendApiFaqController::class, FrontendApiFaqController::class)->args([
        service('phpmyfaq.faq'),
        service('phpmyfaq.helper.faq'),
        service('phpmyfaq.question'),
        service('phpmyfaq.stop-words'),
        service('phpmyfaq.user.session'),
        service('phpmyfaq.language'),
        service('phpmyfaq.helper.category-helper'),
        service('phpmyfaq.notification'),
    ]);
    $services->set(FrontendApiPushController::class, FrontendApiPushController::class)->args([
        service('phpmyfaq.push.web-push-service'),
        service('phpmyfaq.push.subscription-repository'),
    ]);
    $services->set(FrontendApiQuestionController::class, FrontendApiQuestionController::class)->args([
        service('phpmyfaq.stop-words'),
        service('phpmyfaq.helper.question'),
        service('phpmyfaq.search'),
        service('phpmyfaq.question'),
        service('phpmyfaq.notification'),
    ]);
    $services->set(FrontendApiUserController::class, FrontendApiUserController::class)->args([
        service('phpmyfaq.stop-words'),
        service('phpmyfaq.mail'),
    ]);
    $services->set(FrontendApiVotingController::class, FrontendApiVotingController::class)->args([
        service('phpmyfaq.rating'),
        service('phpmyfaq.user.session'),
    ]);

    // Batch 3: Controller/Administration/Api/
    $services->set(AdminApiCategoryController::class, AdminApiCategoryController::class)->args([
        service('phpmyfaq.category.image'),
        service('phpmyfaq.category.order'),
        service('phpmyfaq.category.permission'),
    ]);
    $services->set(AdminApiElasticsearchController::class, AdminApiElasticsearchController::class)->args([
        service('phpmyfaq.instance.elasticsearch'),
        service('phpmyfaq.faq'),
        service('phpmyfaq.custom-page'),
    ]);
    $services->set(AdminApiCommentController::class, AdminApiCommentController::class)->args([
        service('phpmyfaq.comments'),
    ]);
    $services->set(AdminApiConfigurationController::class, AdminApiConfigurationController::class)->args([
        service('phpmyfaq.mail'),
    ]);
    $services->set(AdminApiConfigurationTabController::class, AdminApiConfigurationTabController::class)->args([
        service('phpmyfaq.language'),
        service('phpmyfaq.system'),
        service('phpmyfaq.template.theme-manager'),
    ]);
    $services->set(AdminApiDashboardController::class, AdminApiDashboardController::class)->args([
        service('phpmyfaq.admin.session'),
    ]);
    $services->set(AdminApiExportController::class, AdminApiExportController::class)->args([
        service('phpmyfaq.faq'),
    ]);
    $services->set(AdminApiFaqController::class, AdminApiFaqController::class)->args([
        service('phpmyfaq.faq'),
        service('phpmyfaq.admin.faq'),
        service('phpmyfaq.tags'),
        service('phpmyfaq.notification'),
        service('phpmyfaq.admin.changelog'),
        service('phpmyfaq.visits'),
        service('phpmyfaq.seo'),
        service('phpmyfaq.question'),
        service('phpmyfaq.admin.admin-log'),
        service('phpmyfaq.push.web-push-service'),
    ]);
    $services->set(AdminApiGlossaryController::class, AdminApiGlossaryController::class)->args([
        service('phpmyfaq.glossary'),
    ]);
    $services->set(AdminApiInstanceController::class, AdminApiInstanceController::class)->args([
        service('phpmyfaq.instance'),
    ]);
    $services->set(AdminApiLdapController::class, AdminApiLdapController::class)->args([
        service('phpmyfaq.ldap'),
    ]);
    $services->set(AdminApiOpenSearchController::class, AdminApiOpenSearchController::class)->args([
        service('phpmyfaq.instance.opensearch'),
        service('phpmyfaq.faq'),
        service('phpmyfaq.custom-page'),
    ]);
    $services->set(AdminApiPageController::class, AdminApiPageController::class)->args([
        service('phpmyfaq.instance.elasticsearch'),
        service('phpmyfaq.instance.opensearch'),
    ]);
    $services->set(AdminApiQuestionController::class, AdminApiQuestionController::class)->args([
        service('phpmyfaq.question'),
    ]);
    $services->set(AdminApiSessionController::class, AdminApiSessionController::class)->args([
        service('phpmyfaq.admin.session'),
    ]);
    $services->set(AdminApiStatisticsController::class, AdminApiStatisticsController::class)->args([
        service('phpmyfaq.helper.statistics'),
        service('phpmyfaq.search'),
        service('phpmyfaq.rating'),
    ]);
    $services->set(AdminApiTagController::class, AdminApiTagController::class)->args([
        service('phpmyfaq.tags'),
    ]);
    $services->set(AdminApiTranslationController::class, AdminApiTranslationController::class)->args([
        service('phpmyfaq.translation.content-translation-service'),
    ]);
    $services->set(AdminApiUpdateController::class, AdminApiUpdateController::class)->args([
        service('phpmyfaq.setup.upgrade'),
        service('phpmyfaq.admin.api'),
        service('phpmyfaq.setup.update'),
        service('phpmyfaq.setup.environment_configurator'),
    ]);
    $services->set(AdminApiUserController::class, AdminApiUserController::class)->args([
        service('phpmyfaq.user.current_user'),
    ]);

    // Batch 4: Controller/Frontend/
    $services->set(AttachmentController::class, AttachmentController::class)->args([
        service('phpmyfaq.faq.permission'),
    ]);
    $services->set(FrontendAuthenticationController::class, FrontendAuthenticationController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.user.current_user'),
        service('phpmyfaq.user.two-factor'),
    ]);
    $services->set(FrontendAzureAuthenticationController::class, FrontendAzureAuthenticationController::class);
    $services->set(FrontendCategoryController::class, FrontendCategoryController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.category'),
        service('phpmyfaq.faq'),
    ]);
    $services->set(FrontendChatController::class, FrontendChatController::class)->args([
        service('phpmyfaq.user.session'),
    ]);
    $services->set(FrontendContactController::class, FrontendContactController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.captcha'),
        service('phpmyfaq.captcha.helper.captcha_helper'),
    ]);
    $services->set(FrontendCustomPageController::class, FrontendCustomPageController::class)->args([
        service('phpmyfaq.custom-page'),
    ]);
    $services->set(FrontendFaqController::class, FrontendFaqController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.captcha'),
        service('phpmyfaq.captcha.helper.captcha_helper'),
        service('phpmyfaq.faq'),
        service('phpmyfaq.category'),
        service('phpmyfaq.bookmark'),
        service('phpmyfaq.date'),
        service('phpmyfaq.mail'),
        service('phpmyfaq.services.gravatar'),
    ]);
    $services->set(FrontendGlossaryController::class, FrontendGlossaryController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.glossary'),
    ]);
    $services->set(FrontendNewsController::class, FrontendNewsController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.captcha'),
        service('phpmyfaq.date'),
        service('phpmyfaq.mail'),
        service('phpmyfaq.services.gravatar'),
    ]);
    $services->set(FrontendOverviewController::class, FrontendOverviewController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.helper.faq'),
        service('phpmyfaq.faq'),
    ]);
    $services->set(PageNotFoundController::class, PageNotFoundController::class)->args([
        service('phpmyfaq.user.session'),
    ]);
    $services->set(PdfController::class, PdfController::class)->args([
        service('phpmyfaq.faq'),
        service('phpmyfaq.tags'),
    ]);
    $services->set(FrontendQuestionsController::class, FrontendQuestionsController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.captcha'),
        service('phpmyfaq.captcha.helper.captcha_helper'),
    ]);
    $services->set(FrontendSearchController::class, FrontendSearchController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.language.plurals'),
    ]);
    $services->set(FrontendSitemapController::class, FrontendSitemapController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.sitemap'),
    ]);
    $services->set(StartpageController::class, StartpageController::class)->args([
        service('phpmyfaq.language.plurals'),
        service('phpmyfaq.faq'),
        service('phpmyfaq.tags'),
    ]);
    $services->set(FrontendUserController::class, FrontendUserController::class)->args([
        service('phpmyfaq.user.session'),
        service('phpmyfaq.captcha'),
        service('phpmyfaq.captcha.helper.captcha_helper'),
        service('phpmyfaq.services.gravatar'),
    ]);

    // Batch 5: Controller/Administration/
    $services->set(AdminAttachmentsController::class, AdminAttachmentsController::class)->args([
        service('phpmyfaq.attachment-collection'),
    ]);
    $services->set(AdminAuthenticationController::class, AdminAuthenticationController::class)->args([
        service('phpmyfaq.user.current_user'),
        service('phpmyfaq.user.two-factor'),
    ]);
    $services->set(AdminBackupController::class, AdminBackupController::class)->args([
        service('phpmyfaq.backup'),
    ]);
    $services->set(AdminCategoryController::class, AdminCategoryController::class)->args([
        service('phpmyfaq.admin.category'),
        service('phpmyfaq.category.order'),
        service('phpmyfaq.category.permission'),
        service('phpmyfaq.category.image'),
        service('phpmyfaq.seo'),
        service('phpmyfaq.helper.user-helper'),
    ]);
    $services->set(AdminCommentsController::class, AdminCommentsController::class)->args([
        service('phpmyfaq.comments'),
        service('phpmyfaq.news'),
    ]);
    $services->set(AdminDashboardController::class, AdminDashboardController::class)->args([
        service('phpmyfaq.admin.session'),
        service('phpmyfaq.admin.faq'),
        service('phpmyfaq.admin.backup'),
        service('phpmyfaq.admin.latest-users'),
        service('phpmyfaq.admin.api'),
    ]);
    $services->set(AdminExportController::class, AdminExportController::class)->args([
        service('phpmyfaq.helper.category-helper'),
    ]);
    $services->set(AdminFaqController::class, AdminFaqController::class)->args([
        service('phpmyfaq.comments'),
        service('phpmyfaq.faq'),
        service('phpmyfaq.tags'),
        service('phpmyfaq.seo'),
        service('phpmyfaq.helper.category-helper'),
        service('phpmyfaq.helper.user-helper'),
        service('phpmyfaq.faq.permission'),
        service('phpmyfaq.admin.changelog'),
        service('phpmyfaq.question'),
    ]);
    $services->set(AdminFormsController::class, AdminFormsController::class)->args([
        service('phpmyfaq.forms'),
    ]);
    $services->set(AdminGlossaryController::class, AdminGlossaryController::class)->args([
        service('phpmyfaq.glossary'),
    ]);
    $services->set(AdminGroupController::class, AdminGroupController::class)->args([
        service('phpmyfaq.user'),
    ]);
    $services->set(AdminInstanceController::class, AdminInstanceController::class)->args([
        service('phpmyfaq.instance'),
        service('phpmyfaq.instance.client'),
    ]);
    $services->set(AdminNewsController::class, AdminNewsController::class)->args([
        service('phpmyfaq.news'),
        service('phpmyfaq.comments'),
    ]);
    $services->set(AdminOpenQuestionsController::class, AdminOpenQuestionsController::class)->args([
        service('phpmyfaq.question'),
    ]);
    $services->set(AdminOrphanedFaqsController::class, AdminOrphanedFaqsController::class)->args([
        service('phpmyfaq.admin.faq'),
    ]);
    $services->set(AdminPageController::class, AdminPageController::class)->args([
        service('phpmyfaq.custom-page'),
    ]);
    $services->set(AdminPasswordChangeController::class, AdminPasswordChangeController::class)->args([
        service('phpmyfaq.auth'),
    ]);
    $services->set(AdminPluginController::class, AdminPluginController::class)->args([
        service('phpmyfaq.plugin.plugin-manager'),
    ]);
    $services->set(AdminRatingController::class, AdminRatingController::class)->args([
        service('phpmyfaq.admin.rating-data'),
    ]);
    $services->set(AdminStatisticsSearchController::class, AdminStatisticsSearchController::class)->args([
        service('phpmyfaq.search'),
    ]);
    $services->set(AdminStatisticsSessionsController::class, AdminStatisticsSessionsController::class)->args([
        service('phpmyfaq.admin.session'),
        service('phpmyfaq.date'),
        service('phpmyfaq.visits'),
    ]);
    $services->set(AdminStickyFaqsController::class, AdminStickyFaqsController::class)->args([
        service('phpmyfaq.faq'),
    ]);
    $services->set(AdminSystemInformationController::class, AdminSystemInformationController::class)->args([
        service('phpmyfaq.system'),
    ]);
    $services->set(AdminTagController::class, AdminTagController::class)->args([
        service('phpmyfaq.tags'),
    ]);
    $services->set(AdminUserController::class, AdminUserController::class)->args([
        service('phpmyfaq.user'),
    ]);

    // Api/OAuth2Controller
    $services->set(OAuth2Controller::class, OAuth2Controller::class)->args([
        service('phpmyfaq.auth.oauth2.authorization-server'),
    ]);

    // Batch 6: Root controllers
    $services->set(RootSitemapController::class, RootSitemapController::class)->args([
        service('phpmyfaq.seo.sitemap-xml'),
    ]);
};
