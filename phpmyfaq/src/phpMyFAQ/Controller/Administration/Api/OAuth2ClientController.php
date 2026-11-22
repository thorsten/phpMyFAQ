<?php

/**
 * The Admin OAuth 2.0 Client Controller
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use InvalidArgumentException;
use phpMyFAQ\Auth\OAuth2\ClientRegistration;
use phpMyFAQ\Auth\OAuth2\Entity\ClientEntity;
use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\OAuth2GrantType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuth2ClientController extends AbstractAdministrationApiController
{
    /**
     * Registers a client. The secret of a confidential client is part of this response only:
     * its hash is stored, so it cannot be shown again.
     *
     * @throws \Exception
     */
    #[Route(path: 'oauth2/clients', name: 'admin.api.oauth2.clients.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = $this->decodeJsonObject($request) ?? new \stdClass();
        if (!$this->verifySessionCsrfToken('oauth2-client-save', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $registration = $this->registrationFrom($data, confidential: ($data->confidential ?? true) !== false);
        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'error' => Translation::get(key: $exception->getMessage()),
            ], Response::HTTP_BAD_REQUEST);
        }

        $clientId = 'pmf_' . bin2hex(random_bytes(16));
        $clientSecret = $registration->confidential ? bin2hex(random_bytes(32)) : null;
        $secretHash = $clientSecret === null ? null : password_hash($clientSecret, PASSWORD_BCRYPT);

        $clients = new ClientRepository($this->configuration);
        if (!$clients->register($clientId, $registration, $secretHash, $this->currentUser->getUserId())) {
            return $this->json([
                'error' => Translation::get(key: 'msgOAuth2ClientSaveFailed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::OAUTH2_CLIENT_CREATED->value . ':' . $clientId);

        return $this->json([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'name' => $registration->name,
        ], Response::HTTP_CREATED);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'oauth2/clients/{clientId}', name: 'admin.api.oauth2.clients.update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = $this->decodeJsonObject($request) ?? new \stdClass();
        if (!$this->verifySessionCsrfToken('oauth2-client-save', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $clients = new ClientRepository($this->configuration);
        $clientId = (string) $request->attributes->get('clientId');
        $client = $clients->getClientEntity($clientId);
        if (!$client instanceof ClientEntity) {
            return $this->json(['error' => Translation::get(key: 'msgOAuth2ClientNotFound')], Response::HTTP_NOT_FOUND);
        }

        try {
            $registration = $this->registrationFrom($data, $client->isConfidential());
        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'error' => Translation::get(key: $exception->getMessage()),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$clients->update($clientId, $registration)) {
            return $this->json([
                'error' => Translation::get(key: 'msgOAuth2ClientSaveFailed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::OAUTH2_CLIENT_UPDATED->value . ':' . $clientId);

        return $this->json(['success' => Translation::get(key: 'msgOAuth2ClientSaved')], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'oauth2/clients/{clientId}', name: 'admin.api.oauth2.clients.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = $this->decodeJsonObject($request) ?? new \stdClass();
        if (!$this->verifySessionCsrfToken('oauth2-client-delete', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $clients = new ClientRepository($this->configuration);
        $clientId = (string) $request->attributes->get('clientId');
        if (!$clients->getClientEntity($clientId) instanceof ClientEntity) {
            return $this->json(['error' => Translation::get(key: 'msgOAuth2ClientNotFound')], Response::HTTP_NOT_FOUND);
        }

        if (!$clients->delete($clientId)) {
            return $this->json([
                'error' => Translation::get(key: 'msgOAuth2ClientDeleteFailed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::OAUTH2_CLIENT_DELETED->value . ':' . $clientId);

        return $this->json(['success' => Translation::get(key: 'msgOAuth2ClientDeleted')], Response::HTTP_OK);
    }

    /**
     * @throws InvalidArgumentException with a translation key as message
     */
    private function registrationFrom(\stdClass $data, bool $confidential): ClientRegistration
    {
        $grants = array_map(
            static function (mixed $grantValue): OAuth2GrantType {
                $grant = is_string($grantValue) ? OAuth2GrantType::tryFrom($grantValue) : null;

                return $grant ?? throw new InvalidArgumentException('msgOAuth2ClientGrantUnknown');
            },
            is_array($data->grants ?? null) ? array_values($data->grants) : [],
        );

        return new ClientRegistration(
            Filter::filterVar($data->name ?? '', FILTER_SANITIZE_SPECIAL_CHARS, default: ''),
            is_string($data->redirectUri ?? null) ? $data->redirectUri : '',
            $grants,
            $confidential,
        );
    }
}
