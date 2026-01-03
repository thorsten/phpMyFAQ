<?php

/**
 * The Glossary Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class GlossaryController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    /**
     * @throws Exception
     */
    #[OA\Get(path: '/api/v3.2/glossary', operationId: 'getGlossary', tags: ['Public Endpoints'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the glossary items.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the glossary items for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '[
            {"id": 1, "language": "en", "item": "API", "definition": "Application Programming Interface" },
            {"id": 2, "language": "en", "item": "FAQ", "definition": "Frequently Asked Questions" }
        ]'),
    )]
    #[OA\Response(
        response: 404,
        description: 'If no glossary items are stored.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(Request $request): JsonResponse
    {
        $glossary = $this->container->get(id: 'phpmyfaq.glossary');
        $language = $this->container->get(id: 'phpmyfaq.language');
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        if ($currentLanguage !== false) {
            $glossary->setLanguage($currentLanguage);
        }

        $result = $glossary->fetchAll();

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
