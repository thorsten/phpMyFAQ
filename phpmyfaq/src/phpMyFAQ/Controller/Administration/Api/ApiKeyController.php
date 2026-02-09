<?php

/**
 * The Admin API Key Controller
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use JsonException;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiKeyController extends AbstractAdministrationApiController
{
    /**
     * @throws \Exception
     */
    #[Route(path: 'user/api-keys', name: 'admin.api.user.api-keys.list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $db = $this->configuration->getDb();
        $sql = sprintf('SELECT id, user_id, name, scopes, last_used_at, expires_at, created
             FROM %sfaqapi_keys
             WHERE user_id = %d
             ORDER BY id DESC', Database::getTablePrefix(), $this->currentUser->getUserId());

        $result = $db->query($sql);
        $rows = $result === false ? [] : $db->fetchAll($result) ?? [];

        foreach ($rows as &$row) {
            $decoded = is_string($row['scopes'] ?? null) ? json_decode($row['scopes'], true) : null;
            $row['scopes'] = is_array($decoded) ? $decoded : [];
        }

        unset($row);

        return $this->json($rows, Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     * @throws JsonException
     */
    #[Route(path: 'user/api-keys', name: 'admin.api.user.api-keys.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $csrf = Filter::filterVar($data->csrf ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$this->verifySessionCsrfToken('api-key-create', $csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $name = Filter::filterVar($data->name ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($name === '') {
            return $this->json(['error' => 'API key name is required.'], Response::HTTP_BAD_REQUEST);
        }

        $scopes = is_array($data->scopes ?? null) ? array_values($data->scopes) : [];
        $scopes = array_values(array_filter($scopes, static fn(mixed $scope): bool => is_string($scope)));
        $expiresAt = Filter::filterVar($data->expiresAt ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($expiresAt !== '' && strtotime($expiresAt) === false) {
            return $this->json(['error' => 'Invalid expiresAt value.'], Response::HTTP_BAD_REQUEST);
        }

        $db = $this->configuration->getDb();
        $id = $db->nextId(Database::getTablePrefix() . 'faqapi_keys', 'id');
        $apiKey = 'pmf_' . bin2hex(random_bytes(20));
        $apiKeyHash = hash('sha256', $apiKey);

        $insert = sprintf(
            "INSERT INTO %sfaqapi_keys
                (id, user_id, api_key, name, scopes, last_used_at, expires_at, created)
             VALUES
                (%d, %d, '%s', '%s', '%s', NULL, %s, %s)",
            Database::getTablePrefix(),
            $id,
            $this->currentUser->getUserId(),
            $db->escape($apiKeyHash),
            $db->escape($name),
            $db->escape((string) json_encode($scopes)),
            $expiresAt === '' ? 'NULL' : "'" . $db->escape($expiresAt) . "'",
            $db->now(),
        );

        if (!$db->query($insert)) {
            return $this->json(['error' => $db->error()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'id' => $id,
            'apiKey' => $apiKey,
            'name' => $name,
            'scopes' => $scopes,
            'expiresAt' => $expiresAt !== '' ? $expiresAt : null,
        ], Response::HTTP_CREATED);
    }

    /**
     * @throws \Exception
     * @throws JsonException
     */
    #[Route(path: 'user/api-keys/{id}', name: 'admin.api.user.api-keys.update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $csrf = Filter::filterVar($data->csrf ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$this->verifySessionCsrfToken('api-key-update', $csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->attributes->get('id') === null) {
            return $this->json(['error' => 'API key ID is required.'], Response::HTTP_BAD_REQUEST);
        }

        $id = (int) $request->attributes->get('id');
        $name = Filter::filterVar($data->name ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($name === '') {
            return $this->json(['error' => 'API key name is required.'], Response::HTTP_BAD_REQUEST);
        }

        $scopes = is_array($data->scopes ?? null) ? array_values($data->scopes) : [];
        $scopes = array_values(array_filter($scopes, static fn(mixed $scope): bool => is_string($scope)));
        $expiresAt = Filter::filterVar($data->expiresAt ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($expiresAt !== '' && strtotime($expiresAt) === false) {
            return $this->json(['error' => 'Invalid expiresAt value.'], Response::HTTP_BAD_REQUEST);
        }

        $db = $this->configuration->getDb();

        $check = sprintf(
            'SELECT id FROM %sfaqapi_keys WHERE id = %d AND user_id = %d',
            Database::getTablePrefix(),
            $id,
            $this->currentUser->getUserId(),
        );
        $checkResult = $db->query($check);
        if ($checkResult === false || $db->numRows($checkResult) === 0) {
            return $this->json(['error' => 'API key not found.'], Response::HTTP_NOT_FOUND);
        }

        $update = sprintf(
            "UPDATE %sfaqapi_keys
             SET name = '%s', scopes = '%s', expires_at = %s
             WHERE id = %d AND user_id = %d",
            Database::getTablePrefix(),
            $db->escape($name),
            $db->escape((string) json_encode($scopes)),
            $expiresAt === '' ? 'NULL' : "'" . $db->escape($expiresAt) . "'",
            $id,
            $this->currentUser->getUserId(),
        );

        if (!$db->query($update)) {
            return $this->json(['error' => $db->error()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'id' => $id,
            'name' => $name,
            'scopes' => $scopes,
            'expiresAt' => $expiresAt !== '' ? $expiresAt : null,
        ], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'user/api-keys/{id}', name: 'admin.api.user.api-keys.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $csrf = $request->headers->get('X-CSRF-Token') ?? $request->query->get('csrf');

        if ($csrf === null) {
            $body = json_decode($request->getContent(), false);
            $csrf = $body->csrf ?? null;
        }

        $csrf = Filter::filterVar((string) ($csrf ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$this->verifySessionCsrfToken('api-key-delete', $csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->attributes->get('id') === null) {
            return $this->json(['error' => 'API key ID is required.'], Response::HTTP_BAD_REQUEST);
        }

        $id = (int) $request->attributes->get('id');
        $db = $this->configuration->getDb();
        $delete = sprintf(
            'DELETE FROM %sfaqapi_keys WHERE id = %d AND user_id = %d',
            Database::getTablePrefix(),
            $id,
            $this->currentUser->getUserId(),
        );

        if (!$db->query($delete)) {
            return $this->json(['error' => $db->error()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }
}
