<?php

/**
 * The Reports Controller
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
 * @since     2024-11-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class ReportController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/statistics/report', name: 'admin.statistics.report', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::REPORTS);

        $session = $this->container->get('session');

        return $this->render(
            '@admin/statistics/report.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'csrfTokenInput' => Token::getInstance($session)->getTokenInput('create-report'),
            ]
        );
    }
}
