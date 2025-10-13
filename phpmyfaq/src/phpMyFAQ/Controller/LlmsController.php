<?php

declare(strict_types=1);

/**
 * The llms.txt Controller
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
 * @since     2025-01-07
 */

namespace phpMyFAQ\Controller;

use Symfony\Component\HttpFoundation\Response;

final class LlmsController extends AbstractController
{
    /**
     * @throws \Exception
     */
    public function index(): Response
    {
        $response = new Response();

        $response->headers->set(
            key: 'Content-Type',
            values: 'text/plain',
        );
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($this->configuration->get('seo.contentLlmsText'));

        return $response;
    }
}
