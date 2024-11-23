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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\AdministrationHelper;
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
        $contentPage = false;
        $userPage = false;
        $statisticsPage = false;
        $exportsPage = false;
        $backupPage = false;
        $configurationPage = false;

        $adminHelper = new AdministrationHelper();
        $adminHelper->setUser($this->currentUser);

        $action = $request->query->get('action');

        $secLevelEntries['user'] = $adminHelper->addMenuEntry(
            'add_user+edit_user+delete_user',
            'user',
            'ad_menu_user_administration'
        );
        if ($this->configuration->get('security.permLevel') !== 'basic') {
            $secLevelEntries['user'] .= $adminHelper->addMenuEntry(
                'addgroup+editgroup+delgroup',
                'group',
                'ad_menu_group_administration'
            );
        }
        $secLevelEntries['content'] = $adminHelper->addMenuEntry(
            'addcateg+editcateg+delcateg',
            'category-overview',
            'msgHeaderCategoryOverview'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_ADD->value,
            'editentry',
            'ad_entry_add'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'edit_faq+delete_faq',
            'faqs-overview',
            'msgHeaderFAQOverview'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_EDIT->value,
            'stickyfaqs',
            'stickyRecordsHeader'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'delcomment',
            'comments',
            'ad_menu_comments'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addattachment+editattachment+delattachment',
            'attachments',
            'ad_menu_attachments'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            PermissionType::FAQ_EDIT->value,
            'tags',
            'ad_entry_tags'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addglossary+editglossary+delglossary',
            'glossary',
            'ad_menu_glossary'
        );
        $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
            'addnews+editnews+delnews',
            'news',
            'ad_menu_news_edit'
        );

        $secLevelEntries['statistics'] = $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'statistics',
            'ad_menu_stat'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'viewsessions',
            'ad_menu_session'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_ADMINLOG->value,
            'adminlog',
            'ad_menu_adminlog'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
            PermissionType::STATISTICS_VIEWLOGS->value,
            'searchstats',
            'msgAdminElasticsearchStats'
        );
        $secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports');

        $secLevelEntries['imports_exports'] = $adminHelper->addMenuEntry(
            PermissionType::FAQ_ADD->value,
            'importcsv',
            'msgImportRecords',
            'import'
        );
        $secLevelEntries['imports_exports'] .= $adminHelper->addMenuEntry(
            PermissionType::EXPORT->value,
            'export',
            'ad_menu_export',
            'export'
        );

        $secLevelEntries['backup'] = $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'backup',
            'ad_menu_backup',
            'backup'
        );

        $secLevelEntries['config'] = $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'config',
            'ad_menu_editconfig',
            'configuration'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry('forms_edit', 'forms', 'msgEditForms');
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            'editinstances+addinstances+delinstances',
            'instances',
            'ad_menu_instances',
            'instances'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'stopwordsconfig',
            'ad_menu_stopwordsconfig',
            'stopwords'
        );
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'upgrade',
            'msgAdminHeaderUpdate',
            'update'
        );
        if ($this->configuration->get('search.enableElasticsearch')) {
            $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
                PermissionType::CONFIGURATION_EDIT->value,
                'elasticsearch',
                'msgAdminHeaderElasticsearch',
                'elasticsearch'
            );
        }
        $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
            PermissionType::CONFIGURATION_EDIT->value,
            'system',
            'ad_system_info',
            'system'
        );

        switch ($action) {
            case 'user':
            case 'group':
            case 'passwd':
            case 'cookies':
                $userPage = true;
                break;
            case 'category-overview':
            case 'addcategory':
            case 'savecategory':
            case 'editcategory':
            case 'translatecategory':
            case 'updatecategory':
            case 'showcategory':
            case 'faqs-overview':
            case 'editentry':
            case 'copyentry':
            case 'glossary':
            case 'saveglossary':
            case 'updateglossary':
            case 'deleteglossary':
            case 'addglossary':
            case 'editglossary':
            case 'news':
            case 'add-news':
            case 'edit-news':
            case 'save-news':
            case 'update-news':
            case 'delete-news':
            case 'question':
            case 'takequestion':
            case 'comments':
            case 'tags':
            case 'delete-tag':
            case 'stickyfaqs':
                $contentPage = true;
                break;
            case 'statistics':
            case 'viewsessions':
            case 'sessionbrowse':
            case 'sessionsuche':
            case 'adminlog':
            case 'searchstats':
            case 'reports':
            case 'reportview':
                $statisticsPage = true;
                break;
            case 'forms':
                $configurationPage = true;
                break;
            default:
                break;
        }

        switch ($request->attributes->get('_route')) {
            case 'admin.attachments':
                $contentPage = true;
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
            case 'admin.instance.edit':
            case 'admin.instance.update':
            case 'admin.instances':
            case 'admin.stopwords':
            case 'admin.system':
            case 'admin.update':
                $configurationPage = true;
                break;
        }

        if ($this->configuration->get('main.enableGravatarSupport')) {
            $avatar = new Gravatar();
            $gravatarImage = $avatar->getImage(
                $this->currentUser->getUserData('email'),
                ['size' => 24, 'class' => 'img-profile rounded-circle']
            );
        }

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
            'pageAction' => isset($action) ? '?action=' . $action : '',
            'renderedLanguageSelection' => LanguageHelper::renderSelectLanguage(
                $this->configuration->getLanguage()->getLanguage(),
                true
            ),
            'userName' => $this->currentUser->getUserData('display_name'),
            'hasGravatarSupport' => $this->configuration->get('main.enableGravatarSupport'),
            'gravatarImage' => $gravatarImage ?? '',
            'msgChangePassword' => Translation::get('ad_menu_passwd'),
            'csrfTokenLogout' => Token::getInstance($this->container->get('session'))->getTokenString('admin-logout'),
            'msgLogout' => Translation::get('admin_mainmenu_logout'),
            'secondLevelEntries' => $secLevelEntries,
            'menuUsers' => Translation::get('admin_mainmenu_users'),
            'menuContent' => Translation::get('admin_mainmenu_content'),
            'menuStatistics' => Translation::get('admin_mainmenu_statistics'),
            'menuImportsExports' => Translation::get('admin_mainmenu_imports_exports'),
            'menuBackup' => Translation::get('admin_mainmenu_backup'),
            'menuConfiguration' => Translation::get('admin_mainmenu_configuration'),
            'userPage' => $userPage,
            'contentPage' => $contentPage,
            'statisticsPage' => $statisticsPage,
            'exportsPage' => $exportsPage,
            'backupPage' => $backupPage,
            'configurationPage' => $configurationPage,
        ];
    }

    /**
     * @return string[]
     */
    protected function getFooter(): array
    {
        return [
            'msgSessionExpiringSoon' => Translation::get('msgSessionExpiringSoon'),
            'msgModalSessionWarning' => sprintf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING),
            'msgNoLogMeOut' => Translation::get('msgNoLogMeOut'),
            'msgYesKeepMeLoggedIn' => Translation::get('msgYesKeepMeLoggedIn'),
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
