/**
 * Chat API functionality
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

import {
  ChatConversationsResponse,
  ChatMessagesResponse,
  ChatSendResponse,
  ChatUnreadCountResponse,
  ChatUsersResponse,
} from '../interfaces';

export const getConversations = async (): Promise<ChatConversationsResponse> => {
  const response: Response = await fetch('api/chat/conversations', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const getMessages = async (userId: number, limit = 50, offset = 0): Promise<ChatMessagesResponse> => {
  const response: Response = await fetch(`api/chat/messages/${userId}?limit=${limit}&offset=${offset}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const sendMessage = async (
  recipientId: number,
  message: string,
  csrfToken: string
): Promise<ChatSendResponse> => {
  const response: Response = await fetch('api/chat/send', {
    method: 'POST',
    cache: 'no-cache',
    body: JSON.stringify({
      recipientId,
      message,
      csrfToken,
    }),
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const markAsRead = async (messageId: number, csrfToken: string): Promise<{ success: boolean }> => {
  const response: Response = await fetch(`api/chat/read/${messageId}`, {
    method: 'POST',
    cache: 'no-cache',
    body: JSON.stringify({
      csrfToken,
    }),
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const getUnreadCount = async (): Promise<ChatUnreadCountResponse> => {
  const response: Response = await fetch('api/chat/unread-count', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};

export const searchUsers = async (query: string): Promise<ChatUsersResponse> => {
  const response: Response = await fetch(`api/chat/users?q=${encodeURIComponent(query)}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return await response.json();
};
