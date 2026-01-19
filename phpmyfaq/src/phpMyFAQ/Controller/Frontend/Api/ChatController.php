<?php

/**
 * The Chat API Controller for private user-to-user messaging.
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
 * @since     2026-01-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use Exception;
use JsonException;
use phpMyFAQ\Chat;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    /**
     * Gets the list of all conversations for the current user.
     *
     * @throws Exception
     */
    #[Route(path: 'api/chat/conversations', name: 'api.private.chat.conversations', methods: ['GET'])]
    public function getConversations(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $chat = new Chat($this->configuration);
        $conversations = $chat->getConversationList($this->currentUser->getUserId());

        return $this->json([
            'success' => true,
            'conversations' => $conversations,
        ], Response::HTTP_OK);
    }

    /**
     * Gets messages with a specific user.
     *
     * @throws Exception
     */
    #[Route(path: 'api/chat/messages/{userId}', name: 'api.private.chat.messages', methods: ['GET'])]
    public function getMessages(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $partnerId = Filter::filterVar($request->attributes->get('userId'), FILTER_VALIDATE_INT);
        $limit = Filter::filterVar($request->query->get('limit', 50), FILTER_VALIDATE_INT) ?: 50;
        $offset = Filter::filterVar($request->query->get('offset', 0), FILTER_VALIDATE_INT) ?: 0;

        if (!$partnerId) {
            return $this->json(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
        }

        $chat = new Chat($this->configuration);

        // Mark messages as read when viewing conversation
        $chat->markConversationAsRead($this->currentUser->getUserId(), $partnerId);

        $messages = $chat->getConversation($this->currentUser->getUserId(), $partnerId, $limit, $offset);

        return $this->json([
            'success' => true,
            'messages' => $chat->messagesToArray($messages),
        ], Response::HTTP_OK);
    }

    /**
     * Sends a new message.
     *
     * @throws JsonException
     * @throws Exception
     */
    #[Route(path: 'api/chat/send', name: 'api.private.chat.send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $recipientId = Filter::filterVar($data->recipientId ?? 0, FILTER_VALIDATE_INT);
        $message = Filter::filterVar($data->message ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken('send-chat-message', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if (!$recipientId || trim($message) === '') {
            return $this->json(['error' => 'Invalid recipient or empty message'], Response::HTTP_BAD_REQUEST);
        }

        $chat = new Chat($this->configuration);
        $chatMessage = $chat->sendMessage($this->currentUser->getUserId(), $recipientId, $message);

        if ($chatMessage === null) {
            return $this->json(['error' => Translation::get(key: 'msgError')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'message' => $chat->messageToArray($chatMessage),
            'csrfToken' => Token::getInstance($this->session)->getTokenString('send-chat-message'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Marks a message as read.
     *
     * @throws JsonException
     * @throws Exception
     */
    #[Route(path: 'api/chat/read/{messageId}', name: 'api.private.chat.read', methods: ['POST'])]
    public function markAsRead(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $messageId = Filter::filterVar($request->attributes->get('messageId'), FILTER_VALIDATE_INT);

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken('mark-chat-read', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if (!$messageId) {
            return $this->json(['error' => 'Invalid message ID'], Response::HTTP_BAD_REQUEST);
        }

        $chat = new Chat($this->configuration);
        $result = $chat->markAsRead($messageId, $this->currentUser->getUserId());

        if (!$result) {
            return $this->json(['error' => Translation::get(key: 'msgError')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    /**
     * Gets the unread message count for the current user.
     *
     * @throws Exception
     */
    #[Route(path: 'api/chat/unread-count', name: 'api.private.chat.unread-count', methods: ['GET'])]
    public function getUnreadCount(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $chat = new Chat($this->configuration);
        $count = $chat->getUnreadCount($this->currentUser->getUserId());

        return $this->json([
            'success' => true,
            'count' => $count,
        ], Response::HTTP_OK);
    }

    /**
     * Searches for users to start a new conversation.
     *
     * @throws Exception
     */
    #[Route(path: 'api/chat/users', name: 'api.private.chat.users', methods: ['GET'])]
    public function searchUsers(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $query = Filter::filterVar($request->query->get('q', ''), FILTER_SANITIZE_SPECIAL_CHARS);

        if (strlen($query) < 2) {
            return $this->json([
                'success' => true,
                'users' => [],
            ], Response::HTTP_OK);
        }

        $chat = new Chat($this->configuration);
        $users = $chat->searchUsers($query, $this->currentUser->getUserId());

        return $this->json([
            'success' => true,
            'users' => $users,
        ], Response::HTTP_OK);
    }
}
