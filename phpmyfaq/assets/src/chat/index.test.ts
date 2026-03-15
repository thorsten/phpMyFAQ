import { beforeEach, afterEach, describe, expect, test, vi } from 'vitest';

// Mock the chat API
vi.mock('../api/chat', () => ({
  getMessages: vi.fn(),
  sendMessage: vi.fn(),
  searchUsers: vi.fn(),
  getConversations: vi.fn(),
}));

import { handleChat } from './index';
import { getMessages, sendMessage, searchUsers, getConversations } from '../api/chat';
import { ChatConfig, ChatMessagesResponse, ChatSendResponse, ChatUsersResponse } from '../interfaces';

// Mock EventSource
class MockEventSource {
  onmessage: ((event: MessageEvent) => void) | null = null;
  onerror: (() => void) | null = null;
  close = vi.fn();
  addEventListener = vi.fn();
}

const mockConfig: ChatConfig = {
  currentUserId: 1,
  csrfTokenSendMessage: 'csrf-token-123',
  csrfTokenMarkRead: 'csrf-mark-read-123',
};

describe('Chat UI', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    document.body.innerHTML = '';
    window.pmfChatConfig = undefined;

    // Mock EventSource globally
    (global as Record<string, unknown>).EventSource = MockEventSource;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('handleChat', () => {
    test('should return early when config is missing', () => {
      document.body.innerHTML = '<form id="pmf-chat-form"></form>';
      window.pmfChatConfig = undefined;

      handleChat();

      // No errors thrown, no event listeners attached
      expect(getConversations).not.toHaveBeenCalled();
    });

    test('should return early when chat form is missing', () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = '<div></div>';

      handleChat();

      expect(getConversations).not.toHaveBeenCalled();
    });

    test('should initialize when config and form are present', () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      // EventSource should have been created
      expect(MockEventSource.prototype).toBeDefined();
    });

    test('should set up user search with debounce when search input exists', () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <input id="pmf-chat-user-search" type="text" />
        <div id="pmf-chat-user-search-results"></div>
      `;

      handleChat();

      const searchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;
      expect(searchInput).not.toBeNull();
    });
  });

  describe('loadConversation', () => {
    test('should load and display messages for a conversation', async () => {
      const mockMessages: ChatMessagesResponse = {
        success: true,
        messages: [
          {
            id: 1,
            senderId: 1,
            senderName: 'Me',
            recipientId: 2,
            message: 'Hello!',
            isRead: true,
            createdAt: '2026-01-23T10:00:00Z',
          },
          {
            id: 2,
            senderId: 2,
            senderName: 'John',
            recipientId: 1,
            message: 'Hi there!',
            isRead: false,
            createdAt: '2026-01-23T10:01:00Z',
          },
        ],
      };
      vi.mocked(getMessages).mockResolvedValue(mockMessages);

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-messages"></div>
        <div id="pmf-chat-header" class="d-none"></div>
        <div id="pmf-chat-placeholder"></div>
        <div id="pmf-chat-input-area" class="d-none"></div>
        <span id="pmf-chat-partner-name"></span>
        <span id="pmf-chat-partner-avatar"></span>
        <input id="pmf-chat-recipient-id" value="" />
        <a href="#" class="pmf-chat-conversation" data-user-id="2">
          <strong>John</strong>
          <span class="badge">1</span>
        </a>
      `;

      handleChat();

      // Click the conversation to load it
      const conversationEl = document.querySelector('.pmf-chat-conversation') as HTMLElement;
      conversationEl.click();

      // Wait for async operations
      await vi.waitFor(() => {
        expect(getMessages).toHaveBeenCalledWith(2);
      });

      const messagesContainer = document.getElementById('pmf-chat-messages');
      expect(messagesContainer?.innerHTML).toContain('Hello!');
      expect(messagesContainer?.innerHTML).toContain('Hi there!');

      // Header should be visible
      expect(document.getElementById('pmf-chat-header')?.classList.contains('d-none')).toBe(false);
      // Placeholder should be hidden
      expect(document.getElementById('pmf-chat-placeholder')?.classList.contains('d-none')).toBe(true);
      // Partner name should be set
      expect(document.getElementById('pmf-chat-partner-name')?.textContent).toBe('John');
      // Avatar should show first letter
      expect(document.getElementById('pmf-chat-partner-avatar')?.textContent).toBe('J');
      // Recipient input should be set
      expect((document.getElementById('pmf-chat-recipient-id') as HTMLInputElement).value).toBe('2');
      // Conversation should be active
      expect(conversationEl.classList.contains('active')).toBe(true);
      // Badge should be removed
      expect(conversationEl.querySelector('.badge')).toBeNull();
    });

    test('should show empty state when no messages exist', async () => {
      const mockMessages: ChatMessagesResponse = {
        success: true,
        messages: [],
      };
      vi.mocked(getMessages).mockResolvedValue(mockMessages);

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-messages"></div>
        <div id="pmf-chat-header" class="d-none"></div>
        <div id="pmf-chat-input-area" class="d-none"></div>
        <span id="pmf-chat-partner-name"></span>
        <input id="pmf-chat-recipient-id" value="" />
        <a href="#" class="pmf-chat-conversation" data-user-id="3">
          <strong>Jane</strong>
        </a>
      `;

      handleChat();

      const conversationEl = document.querySelector('.pmf-chat-conversation') as HTMLElement;
      conversationEl.click();

      await vi.waitFor(() => {
        expect(getMessages).toHaveBeenCalledWith(3);
      });

      const messagesContainer = document.getElementById('pmf-chat-messages');
      expect(messagesContainer?.innerHTML).toContain('No messages yet');
    });

    test('should show error state when loading fails', async () => {
      vi.mocked(getMessages).mockRejectedValue(new Error('Network error'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-messages"></div>
        <div id="pmf-chat-header" class="d-none"></div>
        <div id="pmf-chat-input-area" class="d-none"></div>
        <span id="pmf-chat-partner-name"></span>
        <input id="pmf-chat-recipient-id" value="" />
        <a href="#" class="pmf-chat-conversation" data-user-id="4">
          <strong>Bob</strong>
        </a>
      `;

      handleChat();

      const conversationEl = document.querySelector('.pmf-chat-conversation') as HTMLElement;
      conversationEl.click();

      await vi.waitFor(() => {
        expect(consoleSpy).toHaveBeenCalled();
      });

      const messagesContainer = document.getElementById('pmf-chat-messages');
      expect(messagesContainer?.innerHTML).toContain('Failed to load messages');

      consoleSpy.mockRestore();
    });
  });

  describe('handleSendMessage', () => {
    test('should send a message and display it', async () => {
      const mockResponse: ChatSendResponse = {
        success: true,
        message: {
          id: 10,
          senderId: 1,
          senderName: 'Me',
          recipientId: 2,
          message: 'Test message',
          isRead: false,
          createdAt: '2026-01-23T10:05:00Z',
        },
        csrfToken: 'new-csrf-token',
      };
      vi.mocked(sendMessage).mockResolvedValue(mockResponse);
      vi.mocked(getMessages).mockResolvedValue({ success: true, messages: [] });

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form">
          <input id="pmf-chat-message-input" value="Test message" />
          <input id="pmf-chat-csrf-token" value="csrf-token-123" />
        </form>
        <div id="pmf-chat-messages"></div>
        <div id="pmf-chat-header" class="d-none"></div>
        <div id="pmf-chat-input-area" class="d-none"></div>
        <span id="pmf-chat-partner-name"></span>
        <input id="pmf-chat-recipient-id" value="" />
        <a href="#" class="pmf-chat-conversation" data-user-id="2">
          <strong>John</strong>
        </a>
      `;

      handleChat();

      // First load a conversation
      const conversationEl = document.querySelector('.pmf-chat-conversation') as HTMLElement;
      conversationEl.click();

      await vi.waitFor(() => {
        expect(getMessages).toHaveBeenCalled();
      });

      // Submit the form
      const form = document.getElementById('pmf-chat-form') as HTMLFormElement;
      form.dispatchEvent(new Event('submit'));

      await vi.waitFor(() => {
        expect(sendMessage).toHaveBeenCalledWith(2, 'Test message', 'csrf-token-123');
      });

      // Message should be displayed
      const messagesContainer = document.getElementById('pmf-chat-messages');
      expect(messagesContainer?.innerHTML).toContain('Test message');

      // Input should be cleared
      const messageInput = document.getElementById('pmf-chat-message-input') as HTMLInputElement;
      expect(messageInput.value).toBe('');

      // CSRF token should be updated
      const csrfInput = document.getElementById('pmf-chat-csrf-token') as HTMLInputElement;
      expect(csrfInput.value).toBe('new-csrf-token');
    });

    test('should not send when message is empty', async () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form">
          <input id="pmf-chat-message-input" value="   " />
        </form>
        <div id="pmf-chat-messages"></div>
        <a href="#" class="pmf-chat-conversation" data-user-id="2">
          <strong>John</strong>
        </a>
      `;

      handleChat();

      // Load conversation first
      vi.mocked(getMessages).mockResolvedValue({ success: true, messages: [] });
      const conversationEl = document.querySelector('.pmf-chat-conversation') as HTMLElement;
      conversationEl.click();

      await vi.waitFor(() => {
        expect(getMessages).toHaveBeenCalled();
      });

      // Submit with empty message
      const form = document.getElementById('pmf-chat-form') as HTMLFormElement;
      form.dispatchEvent(new Event('submit'));

      expect(sendMessage).not.toHaveBeenCalled();
    });
  });

  describe('handleUserSearch', () => {
    test('should display search results for valid query', async () => {
      const mockResponse: ChatUsersResponse = {
        success: true,
        users: [
          { userId: 5, displayName: 'Alice', email: 'alice@example.com' },
          { userId: 6, displayName: 'Alex', email: 'alex@example.com' },
        ],
      };
      vi.mocked(searchUsers).mockResolvedValue(mockResponse);

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <input id="pmf-chat-user-search" type="text" />
        <div id="pmf-chat-user-search-results"></div>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      const searchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;
      searchInput.value = 'ali';
      searchInput.dispatchEvent(new Event('input'));

      // Advance past debounce
      vi.advanceTimersByTime(300);

      await vi.waitFor(() => {
        expect(searchUsers).toHaveBeenCalledWith('ali');
      });

      const resultsContainer = document.getElementById('pmf-chat-user-search-results');
      expect(resultsContainer?.innerHTML).toContain('Alice');
      expect(resultsContainer?.innerHTML).toContain('Alex');
    });

    test('should clear results for short query', async () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <input id="pmf-chat-user-search" type="text" />
        <div id="pmf-chat-user-search-results"><div>old results</div></div>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      const searchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;
      searchInput.value = 'a';
      searchInput.dispatchEvent(new Event('input'));

      vi.advanceTimersByTime(300);

      // Wait for the timeout callback to execute
      await vi.advanceTimersByTimeAsync(0);

      const resultsContainer = document.getElementById('pmf-chat-user-search-results');
      expect(resultsContainer?.innerHTML).toBe('');
    });

    test('should show no users found message', async () => {
      const mockResponse: ChatUsersResponse = {
        success: true,
        users: [],
      };
      vi.mocked(searchUsers).mockResolvedValue(mockResponse);

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <input id="pmf-chat-user-search" type="text" />
        <div id="pmf-chat-user-search-results"></div>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      const searchInput = document.getElementById('pmf-chat-user-search') as HTMLInputElement;
      searchInput.value = 'nonexistent';
      searchInput.dispatchEvent(new Event('input'));

      vi.advanceTimersByTime(300);

      await vi.waitFor(() => {
        expect(searchUsers).toHaveBeenCalledWith('nonexistent');
      });

      const resultsContainer = document.getElementById('pmf-chat-user-search-results');
      expect(resultsContainer?.innerHTML).toContain('No users found');
    });
  });

  describe('updateConversationList', () => {
    test('should update conversation list periodically', async () => {
      const mockResponse = {
        success: true,
        conversations: [
          {
            userId: 2,
            displayName: 'John',
            lastMessage: 'Hello!',
            lastMessageTime: '2026-01-23T10:00:00Z',
            unreadCount: 3,
          },
        ],
      };
      vi.mocked(getConversations).mockResolvedValue(mockResponse);

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-conversation-list"></div>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      // Advance to trigger the setInterval (30 seconds)
      vi.advanceTimersByTime(30000);

      await vi.waitFor(() => {
        expect(getConversations).toHaveBeenCalled();
      });

      const conversationList = document.getElementById('pmf-chat-conversation-list');
      expect(conversationList?.innerHTML).toContain('John');
      expect(conversationList?.innerHTML).toContain('Hello!');
      expect(conversationList?.innerHTML).toContain('badge');
    });

    test('should show empty state when no conversations', async () => {
      vi.mocked(getConversations).mockResolvedValue({
        success: true,
        conversations: [],
      });

      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <div id="pmf-chat-conversation-list"></div>
        <div id="pmf-chat-messages"></div>
      `;

      handleChat();

      vi.advanceTimersByTime(30000);

      await vi.waitFor(() => {
        expect(getConversations).toHaveBeenCalled();
      });

      const conversationList = document.getElementById('pmf-chat-conversation-list');
      expect(conversationList?.innerHTML).toContain('No messages yet');
    });
  });

  describe('close search results on outside click', () => {
    test('should clear search results when clicking outside', () => {
      window.pmfChatConfig = mockConfig;
      document.body.innerHTML = `
        <form id="pmf-chat-form"></form>
        <input id="pmf-chat-user-search" type="text" />
        <div id="pmf-chat-user-search-results"><div>some results</div></div>
        <div id="pmf-chat-messages"></div>
        <div id="outside-element">Click me</div>
      `;

      handleChat();

      const outsideElement = document.getElementById('outside-element') as HTMLElement;
      document.dispatchEvent(
        new MouseEvent('click', {
          bubbles: true,
          target: outsideElement,
        } as MouseEventInit)
      );

      // Results should be cleared since click target is not the search input or results container
      const resultsContainer = document.getElementById('pmf-chat-user-search-results');
      expect(resultsContainer?.innerHTML).toBe('');
    });
  });
});
