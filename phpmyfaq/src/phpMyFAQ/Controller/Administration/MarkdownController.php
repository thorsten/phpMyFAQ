<?php

/**
 * The Admin Markdown Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-25
 */

namespace phpMyFAQ\Controller\Administration;

use ParsedownExtra;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MarkdownController extends AbstractController
{
    #[Route('admin/api/content/markdown')]
    public function render(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $data = json_decode($request->getContent());

        $answer = Filter::filterVar($data->text, FILTER_SANITIZE_SPECIAL_CHARS);

        $parseDown = new ParsedownExtra();

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => $parseDown->text($answer)]);

        return $response;
    }
}
