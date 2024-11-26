<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\UserNameTwigExtension;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extra\Intl\IntlExtension;

class AdminLogController extends AbstractAdministrationController
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

        $adminLog = $this->container->get('phpmyfaq.admin.admin-log');
        $session = $this->container->get('session');

        $itemsPerPage = 15;
        $page = Filter::filterVar($request->get('page'), FILTER_VALIDATE_INT, 1);

        $baseUrl = sprintf('./statistics/admin-log?page=%d', $page);

        // Pagination options
        $options = [
            'baseUrl' => $baseUrl,
            'total' => $adminLog->getNumberOfEntries(),
            'perPage' => $itemsPerPage,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($options);

        $loggingData = $adminLog->getAll();

        $totalItems = count($loggingData);
        $totalPages = ceil($totalItems / $itemsPerPage);

        $offset = ($page - 1) * $itemsPerPage;
        $currentItems = array_slice($loggingData, $offset, $itemsPerPage);

        $this->addExtension(new IntlExtension());
        $this->addExtension(new UserNameTwigExtension());
        return $this->render(
            '@admin/statistics/admin-log.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'headerAdminLog' => Translation::get('ad_menu_adminlog'),
                'buttonDeleteAdminLog' => Translation::get('ad_adminlog_del_older_30d'),
                'csrfDeleteAdminLogToken' => Token::getInstance($session)->getTokenString('delete-adminlog'),
                'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
                'pagination' => $pagination->render(),
                'msgId' => Translation::get('ad_categ_id'),
                'msgDate' => Translation::get('ad_adminlog_date'),
                'msgUser' => Translation::get('ad_adminlog_user'),
                'msgIp' => Translation::get('ad_adminlog_ip'),
                'loggingData' => $currentItems,
            ]
        );
    }
}
