<?php

/**
 * The Session Keepalive Controller
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
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class SessionKeepAliveController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/session-keep-alive', name: 'admin.session.keepalive', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        $language = Filter::filterVar($request->query->get('lang', 'en'), FILTER_SANITIZE_SPECIAL_CHARS);
        $refreshTime = (PMF_AUTH_TIMEOUT - PMF_AUTH_TIMEOUT_WARNING) * 60;

        return $this->render('@admin/session-keepalive.twig', [
            'metaLanguage' => $language,
            'phpMyFAQVersion' => System::getVersion(),
            'currentYear' => date(format: 'Y'),
            'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString('admin-logout'),
            'msgConfirm' => sprintf(Translation::get(languageKey: 'ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING),
            'sessionTimeout' => PMF_AUTH_TIMEOUT,
            'refreshTime' => $refreshTime,
        ]);
    }
}
