<?php

/**
 * The Login Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use JsonException;
use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class LoginController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    /**
     * @throws JsonException|Exception
     */
    #[OA\Post(path: '/api/v3.2/login', operationId: 'login', tags: ['Public Endpoints'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\RequestBody(
        description: 'The username and password for the login.',
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
                type: 'object',
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'If "username" and "password" combination are correct.',
        content: new OA\JsonContent(example: '{ "loggedin": true }'),
    )]
    #[OA\Response(
        response: 400,
        description: 'If "username" and "password" combination are wrong.',
        content: new OA\JsonContent(example: '{ "loggedin": false, "error": "Wrong username or password." }'),
    )]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode(json: $request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $faqUsername = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);
        $faqPassword = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);

        $user = new CurrentUser($this->configuration);
        $userAuthentication = new UserAuthentication($this->configuration, $user);
        try {
            $user = $userAuthentication->authenticate($faqUsername, $faqPassword);
            $result = [
                'loggedin' => $user->isLoggedIn(),
            ];
            return $this->json($result, Response::HTTP_OK);
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error('Failed login: ' . $exception->getMessage());
            $result = [
                'loggedin' => $user->isLoggedIn(),
                'error' => Translation::get(key: 'ad_auth_fail'),
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }
    }
}
