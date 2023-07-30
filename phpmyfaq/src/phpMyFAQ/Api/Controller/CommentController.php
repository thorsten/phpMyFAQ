<?php

/**
 * The Comemnt Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentController
{
    public function list(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);

        $comment = new Comments($faqConfig);
        $result = $comment->getCommentsData($recordId, CommentType::FAQ);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $response->setData($result);

        return $response;
    }
}
