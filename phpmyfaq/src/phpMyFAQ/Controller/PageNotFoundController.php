<?php

/**
 * Page Not Found Controller (404)
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PageNotFoundController extends AbstractFrontController
{
    /**
     * Handles the 404 Not Found page
     * @throws \Exception
     */
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking(SessionActionType::NOT_FOUND->value, 0);

        $response = $this->render('404.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgError404'), $this->configuration->getTitle()),
        ]);

        $response->setStatusCode(Response::HTTP_NOT_FOUND);

        return $response;
    }
}
