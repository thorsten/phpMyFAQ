<?php

/**
 * The Administration admin log Controller
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
 * @since     2024-11-26
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\UserNameTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;
use Twig\Extra\Intl\IntlExtension;

final class AdminLogController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/statistics/admin-log')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_ADMINLOG);

        $adminLog = $this->container->get(id: 'phpmyfaq.admin.admin-log');
        $session = $this->container->get(id: 'session');

        $itemsPerPage = 15;
        $page = Filter::filterVar($request->get('page'), FILTER_VALIDATE_INT, 1);

        // Pagination options
        $options = [
            'baseUrl' => $request->getUri(),
            'total' => $adminLog->getNumberOfEntries(),
            'perPage' => $itemsPerPage,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($options);

        $loggingData = $adminLog->getAll();

        $offset = ($page - 1) * $itemsPerPage;
        $currentItems = array_slice($loggingData, $offset, $itemsPerPage);

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        return $this->render('@admin/statistics/admin-log.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'headerAdminLog' => Translation::get(languageKey: 'ad_menu_adminlog'),
            'buttonDeleteAdminLog' => Translation::get(languageKey: 'ad_adminlog_del_older_30d'),
            'csrfDeleteAdminLogToken' => Token::getInstance($session)->getTokenString('delete-adminlog'),
            'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
            'pagination' => $pagination->render(),
            'msgId' => Translation::get(languageKey: 'ad_categ_id'),
            'msgDate' => Translation::get(languageKey: 'ad_adminlog_date'),
            'msgUser' => Translation::get(languageKey: 'ad_adminlog_user'),
            'msgIp' => Translation::get(languageKey: 'ad_adminlog_ip'),
            'loggingData' => $currentItems,
        ]);
    }
}
