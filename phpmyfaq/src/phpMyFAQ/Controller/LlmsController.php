<?php

/**
 * The llms.txt Controller
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
 * @since     2025-01-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LlmsController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/llms.txt', name: 'public.llms.txt', methods: ['GET'])]
    public function index(): Response
    {
        $response = new Response();

        $response->headers->set(key: 'Content-Type', values: 'text/plain');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($this->configuration->get(item: 'seo.contentLlmsText'));

        return $response;
    }
}
