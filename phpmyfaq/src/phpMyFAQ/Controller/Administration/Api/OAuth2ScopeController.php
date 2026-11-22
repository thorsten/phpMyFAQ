<?php

/**
 * The Admin OAuth 2.0 Scope Controller
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

use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\ApiKeyScope;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuth2ScopeController extends AbstractAdministrationApiController
{
    private const int MAXIMUM_DESCRIPTION_LENGTH = 255;

    /**
     * Replaces the set of scopes clients may request by the submitted one. A scope has to be one
     * the REST API checks (ApiKeyScope) or one that is stored already: a scope created by hand
     * can be kept or removed, but no new unknown scope can be added.
     *
     * @throws \Exception
     */
    #[Route(path: 'oauth2/scopes', name: 'admin.api.oauth2.scopes.save', methods: ['PUT'])]
    public function save(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = $this->decodeJsonObject($request) ?? new \stdClass();
        if (!$this->verifySessionCsrfToken('oauth2-scopes-save', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $scopes = new ScopeRepository($this->configuration);
        $stored = $scopes->all();
        $manageable = array_unique([
            ...array_map(static fn(ApiKeyScope $scope): string => $scope->value, ApiKeyScope::cases()),
            ...array_map(strval(...), array_keys($stored)),
        ]);

        $entries = array_map(
            static fn(mixed $entry): array => [
                is_object($entry) && is_string($entry->scope ?? null) ? $entry->scope : '',
                is_object($entry)
                    ? Filter::filterVar($entry->description ?? '', FILTER_SANITIZE_SPECIAL_CHARS, default: '')
                    : '',
            ],
            is_array($data->scopes ?? null) ? array_values($data->scopes) : [],
        );

        $submitted = [];
        foreach ($entries as [$scope, $description]) {
            if (!in_array($scope, $manageable, strict: true)) {
                return $this->json([
                    'error' => sprintf(Translation::getString(key: 'msgOAuth2ScopeUnknown'), $scope),
                ], Response::HTTP_BAD_REQUEST);
            }

            $submitted[$scope] = mb_substr($description, start: 0, length: self::MAXIMUM_DESCRIPTION_LENGTH);
        }

        foreach ($manageable as $scope) {
            $saved = array_key_exists($scope, $submitted)
                ? $scopes->enable($scope, $submitted[$scope])
                : $scopes->disable($scope);

            if (!$saved) {
                return $this->json([
                    'error' => Translation::get(key: 'msgOAuth2ScopesSaveFailed'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $this->adminLog->log(
            $this->currentUser,
            AdminLogType::OAUTH2_SCOPES_UPDATED->value . ':' . implode(',', array_keys($submitted)),
        );

        return $this->json(['success' => Translation::get(key: 'msgOAuth2ScopesSaved')], Response::HTTP_OK);
    }
}
