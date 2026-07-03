<?php

/**
 * The Admin Comment Controller
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
 * @since     2023-10-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use phpMyFAQ\Comments;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractAdministrationApiController
{
    public function __construct(
        private readonly Comments $comments,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'content/comments', name: 'admin.api.content.comments', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::COMMENT_DELETE);

        $data = $this->getJsonObject($request);
        $payload = $data->data instanceof \stdClass ? $data->data : null;

        $csrfToken = $payload !== null && isset($payload->{'pmf-csrf-token'})
            ? (string) $payload->{'pmf-csrf-token'}
            : null;
        if (!Token::getInstance($this->session)->verifyToken('delete-comment', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $rawIds = $payload !== null && isset($payload->{'comments[]'}) ? $payload->{'comments[]'} : [];
        $commentIds = is_array($rawIds) ? $rawIds : [$rawIds];

        $type = (string) ($data->type ?? '');
        $result = false;
        foreach ($commentIds as $commentId) {
            $commentId = filter_var($commentId, FILTER_VALIDATE_INT);
            if ($commentId === false || $commentId === 0) {
                continue;
            }

            $result = $this->comments->delete($type, $commentId);
        }

        if ($result) {
            $this->adminLog?->log(
                $this->currentUser,
                AdminLogType::COMMENT_DELETE->value . ':'
                    . implode(',', array_map(static fn(mixed $id): string => (string) $id, $commentIds)),
            );
        }

        return $this->json(['success' => $result], Response::HTTP_OK);
    }
}
