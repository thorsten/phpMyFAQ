<?php

/**
 * The Registration Controller for the REST API
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
 * @since     2024-02-27
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    #[OA\Post(
        path: '/api/v3.0/register',
        operationId: 'createUser',
        tags: ['Endpoints with Authentication'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the question.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(
                required: [
                    'username',
                    'fullname',
                    'email',
                    'is-visible'
                ],
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'fullname', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'is-visible', type: 'boolean'),
                ],
                type: 'object'
            ),
            example: '{
                "username": "ada",
                "fullname": "Ada Lovelace",
                "email": "ada.lovelace@example.org",
                "is-visible": false
            }'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'If "username", "fullname", "email", and "is-visible" combination is correct.',
        content: new OA\JsonContent(example: '{ "registered": true, "success": "User created."}')
    )]
    #[OA\Response(
        response: 400,
        description: 'If "username", "fullname", "email", and "is-visible" combination is not correct.',
        content: new OA\JsonContent(example: '{ "registered": false, "error": "Error message"}')
    )]
    #[OA\Response(
        response: 409,
        description: 'If the domain of the email address is not allowed.',
        content: new OA\JsonContent(example: '{ "registered": false, "error": "The domain is not allowed."}')
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.',
        content: new OA\JsonContent(example: '{ "registered": false }')
    )]
    public function create(Request $request): JsonResponse
    {
        $this->hasValidToken();

        $configuration = Configuration::getConfigurationInstance();
        $registration = new RegistrationHelper($configuration);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $userName = trim((string) Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS));
        $fullName = trim((string) Filter::filterVar($data->fullname, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_SANITIZE_EMAIL));
        $isVisible = Filter::filterVar($data->{'is-visible'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $isVisible = $isVisible === 'true';

        if (!$registration->isDomainAllowed($email)) {
            $result = [
                'registered' => false,
                'error' => 'The domain is not allowed.'
            ];
            return $this->json($result, Response::HTTP_CONFLICT);
        }

        if (!empty($userName) && !empty($fullName) && !empty($email)) {
            $result = $registration->createUser($userName, $fullName, $email, $isVisible);
            return $this->json($result, Response::HTTP_CREATED);
        } else {
            $result = [
                'registered' => false,
                'error' => Translation::get('err_sendMail')
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }
    }
}
