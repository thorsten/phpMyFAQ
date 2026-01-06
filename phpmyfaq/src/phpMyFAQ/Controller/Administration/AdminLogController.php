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
 * @copyright 2024-2026 phpMyFAQ Team
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
use phpMyFAQ\Pagination\UrlConfig;
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
    #[Route(path: '/statistics/admin-log')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::STATISTICS_ADMINLOG);

        $itemsPerPage = 15;
        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

        $pagination = new Pagination(
            baseUrl: $request->getUri(),
            total: $this->adminLog->getNumberOfEntries(),
            perPage: $itemsPerPage,
            urlConfig: new UrlConfig(pageParamName: 'page'),
        );

        $loggingData = $this->adminLog->getAll();

        $offset = ($page - 1) * $itemsPerPage;
        $currentItems = array_slice($loggingData, $offset, $itemsPerPage);

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        return $this->render('@admin/statistics/admin-log.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'headerAdminLog' => Translation::get(key: 'ad_menu_adminlog'),
            'buttonExportAdminLog' => Translation::get(key: 'msgAdminLogExportCsv'),
            'buttonDeleteAdminLog' => Translation::get(key: 'ad_adminlog_del_older_30d'),
            'csrfExportAdminLogToken' => Token::getInstance($this->session)->getTokenString('export-adminlog'),
            'csrfDeleteAdminLogToken' => Token::getInstance($this->session)->getTokenString('delete-adminlog'),
            'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
            'pagination' => $pagination->render(),
            'msgId' => Translation::get(key: 'ad_categ_id'),
            'msgDate' => Translation::get(key: 'ad_adminlog_date'),
            'msgUser' => Translation::get(key: 'ad_adminlog_user'),
            'msgIp' => Translation::get(key: 'ad_adminlog_ip'),
            'loggingData' => $currentItems,
        ]);
    }
}
