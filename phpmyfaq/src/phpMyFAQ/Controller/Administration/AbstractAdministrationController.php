<?php

/**
 * The abstract Administration controller
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
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

class AbstractAdministrationController extends AbstractController
{
    /**
     * @return string[]
     * @throws Exception
     */
    protected function getHeader(Request $request): array
    {
        $adminHelper = $this->container->get('phpmyfaq.admin.helper');
        $adminHelper->setUser($this->currentUser);

        $session = $this->container->get('session');

        $secLevelEntries = $this->getSecondLevelEntries($adminHelper);
        $pageFlags = $this->getPageFlags($request);
        $gravatarImage = $this->getGravatarImage();

        return [
            'metaLanguage' => Translation::get('metaLanguage'),
            'layoutMode' => 'light',
            'pageTitle' => $this->configuration->getTitle() . ' - ' . System::getPoweredByString(),
            'baseHref' => $this->configuration->getDefaultUrl() . 'admin/',
            'version' => System::getVersion(),
            'currentYear' => date('Y'),
            'metaRobots' => $this->configuration->get('seo.metaTagsAdmin'),
            'templateSetName' => TwigWrapper::getTemplateSetName(),
            'pageDirection' => Translation::get('direction'),
            'userHasAccessPermission' => $adminHelper->canAccessContent($this->currentUser),
            'msgSessionExpiration' => Translation::get('ad_session_expiration'),
            'pageAction' => $request->query->get('action') ? '?action=' . $request->query->get('action') : '',
            'renderedLanguageSelection' => LanguageHelper::renderSelectLanguage(
                $this->configuration->getLanguage()->getLanguage(),
                true
            ),
            'userName' => $this->currentUser->getUserData('display_name'),
            'hasGravatarSupport' => $this->configuration->get('main.enableGravatarSupport'),
            'gravatarImage' => $gravatarImage,
            'msgChangePassword' => Translation::get('ad_menu_passwd'),
            'csrfTokenLogout' => Token::getInstance($session)->getTokenString('admin-logout'),
            'msgLogout' => Translation::get('admin_mainmenu_logout'),
            'secondLevelEntries' => $secLevelEntries,
            'menuUsers' => Translation::get('admin_mainmenu_users'),
            'menuContent' => Translation::get('admin_mainmenu_content'),
            'menuStatistics' => Translation::get('admin_mainmenu_statistics'),
            'menuImportsExports' => Translation::get('admin_mainmenu_imports_exports'),
            'menuBackup' => Translation::get('admin_mainmenu_backup'),
            'menuConfiguration' => Translation::get('admin_mainmenu_configuration'),
            'isSessionTimeoutCounterEnabled' => $this->configuration->get('security.enableAdminSessionTimeoutCounter'),
        ] + $pageFlags;
    }

    private function getSecondLevelEntries(Helper $adminHelper): array
    {
        $secLevelEntries = [];

        $secLevelEntries['user'] = $adminHelper->addMenuEntry(
            'add_user+edit_user+delete_user',
            'ad_menu_user_administration',
            'user'
        );
        if ($this->configuration->get('security.permLevel') !== 'basic') {
            $secLevelEntries['user'] .= $adminHelper->addMenuEntry(
                'addgroup+editgroup+delgroup',
                'ad_menu_group_administration',
                'group'
            );
        }
        $secLevelEntries['user'] .= $adminHelper->addMenuEntry(
            PermissionType::PASSWORD_CHANGE->value,
            'ad_menu_passwd',
            'password/change'
        );

        $secLevelEntries['content'] = $adminHelper->addMenuEntry(
            'addcateg+editcateg+delcateg',
            'msgHeaderCategoryOverview',
            'category'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_ADD->value,
            'msgAddFAQ',
            'faq/add'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'edit_faq+delete_faq',
            'msgHeaderFAQOverview',
            'faqs'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_EDIT->value,
            'stickyRecordsHeader',
            'sticky-faqs'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_EDIT->value,
            'msgOrphanedFAQs',
            'orphaned-faqs'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::QUESTION_DELETE->value,
            'ad_menu_open',
            'questions'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::COMMENT_DELETE->value,
            'ad_menu_comments',
            'comments'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addattachment+editattachment+delattachment',
            'msgAttachments',
            'attachments'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_EDIT->value,
            'msgTags',
            'tags'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addglossary+editglossary+delglossary',
            'ad_menu_glossary',
            'glossary'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addnews+editnews+delnews',
            'ad_menu_news_edit',
            'news'
        );

        $secLevelEntries['statistics'] = $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'ad_menu_stat',
            'statistics/ratings'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'ad_menu_session',
            'statistics/sessions'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_ADMINLOG->value,
            'ad_menu_adminlog',
            'statistics/admin-log'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'msgAdminElasticsearchStats',
            'statistics/search'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::REPORTS->value,
            'ad_menu_reports',
            'statistics/report'
        );

        $secLevelEntries['imports_exports'] = $adminHelper->addMenuEntry(
            PermissionType::FAQ_ADD->value,
            'msgImportRecords',
            'import'
        );
        $secLevelEntries['imports_exports'] .= $adminHelper->addMenuEntry(
            PermissionType::EXPORT->value,
            'ad_menu_export',
            'export'
        );

        $secLevelEntries['backup'] = $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'ad_menu_backup',
            'backup'
        );

        $secLevelEntries['config'] = $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'ad_menu_editconfig',
            'configuration'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            'forms_edit',
            'msgEditForms',
            'forms'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            'editinstances+addinstances+delinstances',
            'ad_menu_instances',
            'instances'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'ad_menu_stopwordsconfig',
            'stopwords'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'msgAdminHeaderUpdate',
            'update'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'msgPlugins',
            'plugins'
        );
        if ($this->configuration->get('search.enableElasticsearch')) {
            $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
                PermissionType::CONFIGURATION_EDIT->value,
                'msgAdminHeaderElasticsearch',
                'elasticsearch'
            );
        }
        if ($this->configuration->get('search.enableOpenSearch')) {
            $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
                PermissionType::CONFIGURATION_EDIT->value,
                'msgAdminHeaderOpenSearch',
                'opensearch'
            );
        }
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'ad_system_info',
            'system'
        );

        return $secLevelEntries;
    }

    private function getPageFlags(Request $request): array
    {
        $userPage = false;
        $contentPage = false;
        $statisticsPage = false;
        $exportsPage = false;
        $backupPage = false;
        $configurationPage = false;

        switch ($request->attributes->get('_route')) {
            case 'admin.group':
            case 'admin.group.add':
            case 'admin.group.create':
            case 'admin.group.confirm':
            case 'admin.group.delete':
            case 'admin.group.update':
            case 'admin.group.update.members':
            case 'admin.group.update.permissions':
            case 'admin.password.change':
            case 'admin.password.update':
            case 'admin.user':
            case 'admin.user.list':
            case 'admin.user.edit':
                $userPage = true;
                break;
            case 'admin.attachments':
            case 'admin.category':
            case 'admin.category.add':
            case 'admin.category.add.child':
            case 'admin.category.create':
            case 'admin.category.edit':
            case 'admin.category.hierarchy':
            case 'admin.category.translate':
            case 'admin.category.update':
            case 'admin.content.orphaned-faqs':
            case 'admin.content.sticky-faqs':
            case 'admin.comments':
            case 'admin.faq.add':
            case 'admin.faq.answer':
            case 'admin.faq.copy':
            case 'admin.faq.edit':
            case 'admin.faq.translate':
            case 'admin.faqs':
            case 'admin.glossary':
            case 'admin.news':
            case 'admin.news.add':
            case 'admin.news.edit':
            case 'admin.questions':
            case 'admin.tags':
                $contentPage = true;
                break;
            case 'admin.statistics.admin-log':
            case 'admin.statistics.ratings':
            case 'admin.statistics.report':
            case 'admin.statistics.sessions':
            case 'admin.statistics.session.day':
            case 'admin.statistics.session.id':
            case 'admin.statistics.search':
                $statisticsPage = true;
                break;
            case 'admin.export':
            case 'admin.import':
                $exportsPage = true;
                break;
            case 'admin.backup':
            case 'admin.backup.export':
            case 'admin.backup.restore':
                $backupPage = true;
                break;
            case 'admin.configuration':
            case 'admin.elasticsearch':
            case 'admin.forms':
            case 'admin.instance.edit':
            case 'admin.instance.update':
            case 'admin.instances':
            case 'admin.stopwords':
            case 'admin.system':
            case 'admin.configuration.plugins':
            case 'admin.update':
                $configurationPage = true;
                break;
        }

        return [
            'userPage' => $userPage,
            'contentPage' => $contentPage,
            'statisticsPage' => $statisticsPage,
            'exportsPage' => $exportsPage,
            'backupPage' => $backupPage,
            'configurationPage' => $configurationPage,
        ];
    }

    private function getGravatarImage(): string
    {
        if ($this->currentUser->isLoggedIn() && $this->configuration->get('main.enableGravatarSupport')) {
            $avatar = new Gravatar();
            return $avatar->getImage(
                $this->currentUser->getUserData('email'),
                ['size' => '24', 'class' => 'img-profile rounded-circle']
            );
        }

        return '';
    }

    /**
     * @return string[]
     */
    protected function getFooter(): array
    {
        return [
            'msgModalSessionWarning' => sprintf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING),
            'msgPoweredBy' => System::getPoweredByString(),
            'documentationUrl' => System::getDocumentationUrl(),
            'phpMyFaqUrl' => System::PHPMYFAQ_URL,
            'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
            'currentLanguage' => $this->configuration->getLanguage()->getLanguage(),
            'currentTimeStamp' => time(),
            'currentYear' => date('Y'),
        ];
    }
}
