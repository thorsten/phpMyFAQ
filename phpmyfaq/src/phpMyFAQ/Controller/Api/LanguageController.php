<?php

declare(strict_types=1);

/**
 * The Language Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LanguageController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    #[OA\Get(path: '/api/v3.1/language', operationId: 'getLanguage', tags: ['Public Endpoints'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the default language as language code.',
        content: new OA\JsonContent(example: '"en"'),
    )]
    public function index(): JsonResponse
    {
        return new JsonResponse($this->configuration->getLanguage()->getLanguage());
    }
}
