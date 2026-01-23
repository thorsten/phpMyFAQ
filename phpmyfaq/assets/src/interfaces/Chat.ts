/**
 * Chat interfaces
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
 * @since     2026-01-23
 */

export interface ChatMessage {
  id: number;
  senderId: number;
  senderName: string;
  recipientId: number;
  message: string;
  isRead: boolean;
  createdAt: string;
}

export interface ChatConversation {
  userId: number;
  displayName: string;
  lastMessage: string;
  lastMessageTime: string;
  unreadCount: number;
}

export interface ChatUser {
  userId: number;
  displayName: string;
  email: string;
}

export interface ChatConversationsResponse {
  success: boolean;
  conversations: ChatConversation[];
  error?: string;
}

export interface ChatMessagesResponse {
  success: boolean;
  messages: ChatMessage[];
  error?: string;
}

export interface ChatSendResponse {
  success: boolean;
  message?: ChatMessage;
  csrfToken?: string;
  error?: string;
}

export interface ChatUnreadCountResponse {
  success: boolean;
  count: number;
  error?: string;
}

export interface ChatUsersResponse {
  success: boolean;
  users: ChatUser[];
  error?: string;
}

export interface ChatConfig {
  currentUserId: number;
  csrfTokenSendMessage: string;
  csrfTokenMarkRead: string;
}
