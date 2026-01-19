<?php

/**
 * The Chat class provides methods for private user-to-user messaging.
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

namespace phpMyFAQ;

use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use phpMyFAQ\Entity\ChatMessage;

readonly class Chat
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Sends a new message from one user to another.
     *
     * @throws Exception
     */
    public function sendMessage(int $senderId, int $recipientId, string $message): ?ChatMessage
    {
        if (trim($message) === '') {
            return null;
        }

        $query = sprintf(
            "INSERT INTO %sfaqchat_messages (sender_id, recipient_id, message, is_read, created_at)
             VALUES (%d, %d, '%s', 0, NOW())",
            Database::getTablePrefix(),
            $senderId,
            $recipientId,
            $this->configuration->getDb()->escape($message),
        );

        $result = $this->configuration->getDb()->query($query);

        if (!$result) {
            return null;
        }

        $lastId = $this->configuration->getDb()->lastInsertId();

        $chatMessage = new ChatMessage();
        $chatMessage
            ->setId((int) $lastId)
            ->setSenderId($senderId)
            ->setRecipientId($recipientId)
            ->setMessage($message)
            ->setIsRead(false)
            ->setCreatedAt(new DateTimeImmutable());

        return $chatMessage;
    }

    /**
     * Retrieves the conversation between two users.
     *
     * @return ChatMessage[]
     */
    public function getConversation(int $userId1, int $userId2, int $limit = 50, int $offset = 0): array
    {
        $query = sprintf(
            'SELECT id, sender_id, recipient_id, message, is_read, created_at
             FROM %sfaqchat_messages
             WHERE (sender_id = %d AND recipient_id = %d)
                OR (sender_id = %d AND recipient_id = %d)
             ORDER BY created_at ASC
             LIMIT %d OFFSET %d',
            Database::getTablePrefix(),
            $userId1,
            $userId2,
            $userId2,
            $userId1,
            $limit,
            $offset,
        );

        $result = $this->configuration->getDb()->query($query);
        $messages = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $messages[] = $this->mapRowToEntity($row);
        }

        return $messages;
    }

    /**
     * Gets the list of all users with whom the given user has conversations.
     *
     * @return array<int, array{userId: int, displayName: string, lastMessage: string, lastMessageTime: string, unreadCount: int}>
     */
    public function getConversationList(int $userId): array
    {
        $query = sprintf(
            'SELECT DISTINCT
                CASE WHEN sender_id = %d THEN recipient_id ELSE sender_id END as partner_id
             FROM %sfaqchat_messages
             WHERE sender_id = %d OR recipient_id = %d',
            $userId,
            Database::getTablePrefix(),
            $userId,
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        $conversations = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $partnerId = (int) $row->partner_id;
            $partnerInfo = $this->getUserInfo($partnerId);
            $lastMessage = $this->getLastMessage($userId, $partnerId);
            $unreadCount = $this->getUnreadCountFromUser($userId, $partnerId);

            $conversations[] = [
                'userId' => $partnerId,
                'displayName' => $partnerInfo['display_name'] ?? 'Unknown User',
                'lastMessage' => $lastMessage?->getMessage() ?? '',
                'lastMessageTime' => $lastMessage?->getCreatedAt()->format('c') ?? '',
                'unreadCount' => $unreadCount,
            ];
        }

        // Sort by last message time descending
        usort($conversations, fn($a, $b) => strcmp($b['lastMessageTime'], $a['lastMessageTime']));

        return $conversations;
    }

    /**
     * Marks a specific message as read.
     */
    public function markAsRead(int $messageId, int $userId): bool
    {
        $query = sprintf(
            'UPDATE %sfaqchat_messages SET is_read = 1 WHERE id = %d AND recipient_id = %d',
            Database::getTablePrefix(),
            $messageId,
            $userId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Marks all messages in a conversation as read.
     */
    public function markConversationAsRead(int $userId, int $partnerId): bool
    {
        $query = sprintf(
            'UPDATE %sfaqchat_messages SET is_read = 1 WHERE sender_id = %d AND recipient_id = %d AND is_read = 0',
            Database::getTablePrefix(),
            $partnerId,
            $userId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Gets the total count of unread messages for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $query = sprintf(
            'SELECT COUNT(*) as count FROM %sfaqchat_messages WHERE recipient_id = %d AND is_read = 0',
            Database::getTablePrefix(),
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) ($row->count ?? 0);
    }

    /**
     * Gets new messages since a given message ID (for SSE polling).
     *
     * @return ChatMessage[]
     */
    public function getNewMessages(int $userId, int $lastMessageId): array
    {
        $query = sprintf('SELECT id, sender_id, recipient_id, message, is_read, created_at
             FROM %sfaqchat_messages
             WHERE recipient_id = %d AND id > %d
             ORDER BY created_at ASC', Database::getTablePrefix(), $userId, $lastMessageId);

        $result = $this->configuration->getDb()->query($query);
        $messages = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $messages[] = $this->mapRowToEntity($row);
        }

        return $messages;
    }

    /**
     * Searches for users by display name (for starting new conversations).
     *
     * @return array<int, array{userId: int, displayName: string, email: string}>
     */
    public function searchUsers(string $searchTerm, int $excludeUserId, int $limit = 10): array
    {
        $query = sprintf(
            "SELECT u.user_id, ud.display_name, ud.email
             FROM %sfaquser u
             LEFT JOIN %sfaquserdata ud ON u.user_id = ud.user_id
             WHERE u.user_id != %d
               AND ud.display_name LIKE '%%%s%%'
               AND u.account_status = 'active'
               AND ud.is_visible = 1
             LIMIT %d",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $excludeUserId,
            $this->configuration->getDb()->escape($searchTerm),
            $limit,
        );

        $result = $this->configuration->getDb()->query($query);
        $users = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $users[] = [
                'userId' => (int) $row->user_id,
                'displayName' => $row->display_name ?? 'Unknown',
                'email' => $row->email ?? '',
            ];
        }

        return $users;
    }

    /**
     * Converts a message to an array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function messageToArray(ChatMessage $message): array
    {
        $senderInfo = $this->getUserInfo($message->getSenderId());

        return [
            'id' => $message->getId(),
            'senderId' => $message->getSenderId(),
            'senderName' => $senderInfo['display_name'] ?? 'Unknown User',
            'recipientId' => $message->getRecipientId(),
            'message' => $message->getMessage(),
            'isRead' => $message->isRead(),
            'createdAt' => $message->getCreatedAt()->format('c'),
        ];
    }

    /**
     * Converts multiple messages to an array for JSON serialization.
     *
     * @param ChatMessage[] $messages
     * @return array<int, array<string, mixed>>
     */
    public function messagesToArray(array $messages): array
    {
        return array_map(fn(ChatMessage $message) => $this->messageToArray($message), $messages);
    }

    /**
     * Gets the last message in a conversation.
     */
    private function getLastMessage(int $userId1, int $userId2): ?ChatMessage
    {
        $query = sprintf('SELECT id, sender_id, recipient_id, message, is_read, created_at
             FROM %sfaqchat_messages
             WHERE (sender_id = %d AND recipient_id = %d)
                OR (sender_id = %d AND recipient_id = %d)
             ORDER BY created_at DESC
             LIMIT 1', Database::getTablePrefix(), $userId1, $userId2, $userId2, $userId1);

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    /**
     * Gets the count of unread messages from a specific user.
     */
    private function getUnreadCountFromUser(int $userId, int $partnerId): int
    {
        $query = sprintf(
            'SELECT COUNT(*) as count FROM %sfaqchat_messages
             WHERE sender_id = %d AND recipient_id = %d AND is_read = 0',
            Database::getTablePrefix(),
            $partnerId,
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) ($row->count ?? 0);
    }

    /**
     * Gets user information by user ID.
     *
     * @return array{display_name: string|null, email: string|null}
     */
    private function getUserInfo(int $userId): array
    {
        $query = sprintf(
            'SELECT display_name, email FROM %sfaquserdata WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return [
            'display_name' => $row->display_name ?? null,
            'email' => $row->email ?? null,
        ];
    }

    /**
     * Maps a database row to a ChatMessage entity.
     * @throws DateMalformedStringException
     */
    private function mapRowToEntity(object $row): ChatMessage
    {
        $chatMessage = new ChatMessage();
        $chatMessage
            ->setId((int) $row->id)
            ->setSenderId((int) $row->sender_id)
            ->setRecipientId((int) $row->recipient_id)
            ->setMessage($row->message)
            ->setIsRead((bool) $row->is_read)
            ->setCreatedAt(new DateTimeImmutable($row->created_at));

        return $chatMessage;
    }
}
