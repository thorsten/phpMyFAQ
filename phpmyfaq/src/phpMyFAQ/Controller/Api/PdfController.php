<?php

/**
 * The PDF Controller for the REST API
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
 * @since     2024-03-02
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Services;
use phpMyFAQ\User\CurrentUser;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PdfController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.0/pdf/{categoryId}/{faqId}',
        operationId: 'getPdfById',
        description: 'This endpoint returns the URL to the PDF of FAQ for the given FAQ ID and the language provided ' .
            'by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'categoryId',
        description: 'The category ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'faqId',
        description: 'The FAQ ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]

    #[OA\Response(
        response: 200,
        description: 'If the PDF of the FAQ exists.',
        content: new OA\JsonContent(example: '"https://www.example.org/pdf.php?cat=3&id=142&artlang=de"')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there\'s no FAQ and PDF for the given FAQ ID.',
        content: new OA\JsonContent(example: []),
    )]
    public function getById(Request $request): JsonResponse
    {
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faq->getFaq($faqId);
        $result = $faq->faqRecord;

        if ((is_countable($result) ? count($result) : 0) === 0 || $result['solution_id'] === 42) {
            $result = new stdClass();
            return $this->json($result, Response::HTTP_NOT_FOUND);
        } else {
            $service = new Services($this->configuration);
            $service->setFaqId($faqId);
            $service->setLanguage($this->configuration->getLanguage()->getLanguage());
            $service->setCategoryId($categoryId);

            $result = $service->getPdfApiLink();
            return $this->json($result, Response::HTTP_OK);
        }
    }
}
