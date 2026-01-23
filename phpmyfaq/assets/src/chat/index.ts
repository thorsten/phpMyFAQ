/**
 * Chat UI functionality
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

import { getMessages, sendMessage, searchUsers, getConversations } from '../api/chat';
import { ChatConfig, ChatMessage, ChatUser } from '../interfaces';

declare global {
  interface Window {
    pmfChatConfig?: ChatConfig;
  }
}

let eventSource: EventSource | null = null;
let lastMessageId = 0;
let currentPartnerId: number | null = null;
let csrfToken: string;

const escapeHtml = (text: string): string => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

const formatTime = (isoString: string): string => {
  const date = new Date(isoString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const renderMessage = (message: ChatMessage, currentUserId: number): string => {
  const isOwn = message.senderId === currentUserId;
  const alignClass = isOwn ? 'justify-content-end' : 'justify-content-start';
  const bgClass = isOwn ? 'bg-primary text-white' : 'bg-body-secondary';

  return `
    <div class="d-flex ${alignClass} mb-2" data-message-id="${message.id}">
      <div class="p-2 rounded ${bgClass}" style="max-width: 75%;">
        <div>${escapeHtml(message.message)}</div>
        <small class="d-block text-end ${isOwn ? 'text-white-50' : 'text-body-tertiary'}">${formatTime(message.createdAt)}</small>
      </div>
    </div>
  `;
};

const scrollToBottom = (): void => {
  const messagesContainer = document.getElementById('pmf-chat-messages');
  if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
};

const connectSSE = (userId: number): void => {
  if (eventSource) {
    eventSource.close();
  }

  eventSource = new EventSource(`api/chat/stream?lastId=${lastMessageId}`);

  eventSource.onmessage = (event) => {
    const messages: ChatMessage[] = JSON.parse(event.data);
    const messagesContainer = document.getElementById('pmf-chat-messages');
    const config = window.pmfChatConfig;

    if (!messagesContainer || !config) return;

    messages.forEach((message) => {
      // Only append if this message is part of the current conversation
      if (
        (message.senderId === currentPartnerId && message.recipientId === config.currentUserId) ||
        (message.senderId === config.currentUserId && message.recipientId === currentPartnerId)
      ) {
        messagesContainer.insertAdjacentHTML('beforeend', renderMessage(message, config.currentUserId));
      }

      // Update lastMessageId
      if (message.id > lastMessageId) {
        lastMessageId = message.id;
      }
    });

    scrollToBottom();
  };

  eventSource.addEventListener('reconnect', (event: Event) => {
    const messageEvent = event as MessageEvent;
    const data = JSON.parse(messageEvent.data);
    lastMessageId = data.lastId;
    // Reconnect after a short delay
    setTimeout(() => connectSSE(userId), 1000);
  });

  eventSource.onerror = () => {
    eventSource?.close();
    // Attempt to reconnect after 5 seconds
    setTimeout(() => connectSSE(userId), 5000);
  };
};

const loadConversation = async (partnerId: number, partnerName: string): Promise<void> => {
  const config = window.pmfChatConfig;
  if (!config) return;

  currentPartnerId = partnerId;

  // Update UI elements
  const header = document.getElementById('pmf-chat-header');
  const placeholder = document.getElementById('pmf-chat-placeholder');
  const inputArea = document.getElementById('pmf-chat-input-area');
  const partnerNameEl = document.getElementById('pmf-chat-partner-name');
  const partnerAvatar = document.getElementById('pmf-chat-partner-avatar');
  const recipientInput = document.getElementById('pmf-chat-recipient-id') as HTMLInputElement;
  const messagesContainer = document.getElementById('pmf-chat-messages');

  if (header) header.classList.remove('d-none');
  if (placeholder) placeholder.classList.add('d-none');
  if (inputArea) inputArea.classList.remove('d-none');
  if (partnerNameEl) partnerNameEl.textContent = partnerName;
  if (partnerAvatar) partnerAvatar.textContent = partnerName.charAt(0).toUpperCase();
  if (recipientInput) recipientInput.value = String(partnerId);

  // Clear messages container
  if (messagesContainer) {
    messagesContainer.innerHTML =
      '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
  }

  try {
    const response = await getMessages(partnerId);

    if (response.success && messagesContainer) {
      messagesContainer.innerHTML = '';

      if (response.messages.length === 0) {
        messagesContainer.innerHTML =
          '<div class="text-center text-muted py-4">No messages yet. Start the conversation!</div>';
      } else {
        response.messages.forEach((message) => {
          messagesContainer.insertAdjacentHTML('beforeend', renderMessage(message, config.currentUserId));
          if (message.id > lastMessageId) {
            lastMessageId = message.id;
          }
        });
        scrollToBottom();
      }
    }
  } catch (error) {
    console.error('Failed to load conversation:', error);
    if (messagesContainer) {
      messagesContainer.innerHTML = '<div class="alert alert-danger">Failed to load messages</div>';
    }
  }

  // Mark conversation as active in the sidebar
  document.querySelectorAll('.pmf-chat-conversation').forEach((el) => {
    el.classList.remove('active');
    if (el.getAttribute('data-user-id') === String(partnerId)) {
      el.classList.add('active');
      // Remove unread badge
      const badge = el.querySelector('.badge');
      if (badge) badge.remove();
    }
  });
};

const handleSendMessage = async (event: Event): Promise<void> => {
  event.preventDefault();

  const config = window.pmfChatConfig;
  if (!config || !currentPartnerId) return;

  const messageInput = document.getElementById('pmf-chat-message-input') as HTMLInputElement;
  const message = messageInput?.value.trim();

  if (!message) return;

  try {
    const response = await sendMessage(currentPartnerId, message, csrfToken);

    if (response.success && response.message) {
      const messagesContainer = document.getElementById('pmf-chat-messages');
      const placeholder = messagesContainer?.querySelector('.text-muted.py-4');
      if (placeholder) placeholder.remove();

      if (messagesContainer) {
        messagesContainer.insertAdjacentHTML('beforeend', renderMessage(response.message, config.currentUserId));
        scrollToBottom();
      }

      // Update CSRF token for next request
      if (response.csrfToken) {
        csrfToken = response.csrfToken;
        const csrfInput = document.getElementById('pmf-chat-csrf-token') as HTMLInputElement;
        if (csrfInput) csrfInput.value = csrfToken;
      }

      // Clear input
      messageInput.value = '';
    }
  } catch (error) {
    console.error('Failed to send message:', error);
  }
};

const handleUserSearch = async (query: string): Promise<void> => {
  const resultsContainer = document.getElementById('pmf-chat-user-search-results');
  if (!resultsContainer) return;

  if (query.length < 2) {
    resultsContainer.innerHTML = '';
    return;
  }

  try {
    const response = await searchUsers(query);

    if (response.success) {
      if (response.users.length === 0) {
        resultsContainer.innerHTML =
          '<div class="position-absolute bg-white border rounded p-2 shadow-sm w-100" style="z-index: 1000;">No users found</div>';
      } else {
        const userList = response.users
          .map(
            (user: ChatUser) => `
          <a href="#" class="list-group-item list-group-item-action pmf-chat-start-conversation"
             data-user-id="${user.userId}" data-display-name="${escapeHtml(user.displayName)}">
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                   style="width: 32px; height: 32px;">
                ${user.displayName.charAt(0).toUpperCase()}
              </div>
              <div>
                <strong>${escapeHtml(user.displayName)}</strong>
              </div>
            </div>
          </a>
        `
          )
          .join('');

        resultsContainer.innerHTML = `<div class="position-absolute bg-white border rounded shadow-sm w-100 list-group list-group-flush" style="z-index: 1000;">${userList}</div>`;

        // Add click handlers
        resultsContainer.querySelectorAll('.pmf-chat-start-conversation').forEach((el) => {
          el.addEventListener('click', (e) => {
            e.preventDefault();
            const userId = parseInt(el.getAttribute('data-user-id') || '0', 10);
            const displayName = el.getAttribute('data-display-name') || 'User';
            if (userId) {
              loadConversation(userId, displayName);
              resultsContainer.innerHTML = '';
              const searchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;
              if (searchInput) searchInput.value = '';
            }
          });
        });
      }
    }
  } catch (error) {
    console.error('Failed to search users:', error);
  }
};

const updateConversationList = async (): Promise<void> => {
  try {
    const response = await getConversations();
    const conversationList = document.getElementById('pmf-chat-conversation-list');

    if (!response.success || !conversationList) return;

    if (response.conversations.length === 0) {
      conversationList.innerHTML = `
        <div class="p-3 text-muted text-center">
          <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
          No messages yet
        </div>
      `;
      return;
    }

    conversationList.innerHTML = response.conversations
      .map(
        (conv) => `
        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center pmf-chat-conversation${currentPartnerId === conv.userId ? ' active' : ''}"
           data-user-id="${conv.userId}">
          <div class="flex-shrink-0">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                 style="width: 40px; height: 40px;">
              ${conv.displayName.charAt(0).toUpperCase()}
            </div>
          </div>
          <div class="flex-grow-1 ms-3 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center">
              <strong class="text-truncate">${escapeHtml(conv.displayName)}</strong>
              ${conv.unreadCount > 0 ? `<span class="badge bg-primary rounded-pill">${conv.unreadCount}</span>` : ''}
            </div>
            <small class="text-muted text-truncate d-block">${escapeHtml(conv.lastMessage.slice(0, 30))}${conv.lastMessage.length > 30 ? '...' : ''}</small>
          </div>
        </a>
      `
      )
      .join('');

    // Re-attach click handlers
    attachConversationClickHandlers();
  } catch (error) {
    console.error('Failed to update conversation list:', error);
  }
};

const attachConversationClickHandlers = (): void => {
  document.querySelectorAll('.pmf-chat-conversation').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const userId = parseInt(el.getAttribute('data-user-id') || '0', 10);
      const displayName = el.querySelector('strong')?.textContent || 'User';
      if (userId) {
        loadConversation(userId, displayName);
      }
    });
  });
};

export const handleChat = (): void => {
  const config = window.pmfChatConfig;
  const chatForm = document.getElementById('pmf-chat-form');
  const userSearchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;

  if (!config || !chatForm) return;

  csrfToken = config.csrfTokenSendMessage;

  // Initialize SSE connection
  connectSSE(config.currentUserId);

  // Handle form submission
  chatForm.addEventListener('submit', handleSendMessage);

  // Handle conversation clicks
  attachConversationClickHandlers();

  // Handle user search
  if (userSearchInput) {
    let searchTimeout: ReturnType<typeof setTimeout>;
    userSearchInput.addEventListener('input', async () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        handleUserSearch(userSearchInput.value);
      }, 300);
    });

    // Close search results when clicking outside
    document.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;
      const resultsContainer = document.getElementById('pmf-chat-user-search-results');
      if (resultsContainer && !resultsContainer.contains(target) && target !== userSearchInput) {
        resultsContainer.innerHTML = '';
      }
    });
  }

  // Periodically update a conversation list (every 30 seconds)
  setInterval(updateConversationList, 30000);

  // Cleanup on page unloaded
  window.addEventListener('beforeunload', () => {
    if (eventSource) {
      eventSource.close();
    }
  });
};
