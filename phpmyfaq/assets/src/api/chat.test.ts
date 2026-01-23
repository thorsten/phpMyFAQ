import { beforeEach, describe, expect, test, vi } from 'vitest';
import { getConversations, getMessages, sendMessage, markAsRead, getUnreadCount, searchUsers } from './chat';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';
import {
  ChatConversationsResponse,
  ChatMessagesResponse,
  ChatSendResponse,
  ChatUnreadCountResponse,
  ChatUsersResponse,
} from '../interfaces';

const fetchMocker: FetchMock = createFetchMock(vi);

fetchMocker.enableMocks();

describe('Chat API', (): void => {
  beforeEach((): void => {
    fetchMocker.resetMocks();
  });

  test('getConversations should return conversations when successful', async (): Promise<void> => {
    const mockResponse: ChatConversationsResponse = {
      success: true,
      conversations: [
        {
          userId: 2,
          displayName: 'John Doe',
          lastMessage: 'Hello!',
          lastMessageTime: '2026-01-23T10:00:00Z',
          unreadCount: 1,
        },
      ],
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getConversations();

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/conversations', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('getConversations should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 401 });

    await expect(getConversations()).rejects.toThrow('HTTP 401');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('getMessages should return messages when successful', async (): Promise<void> => {
    const mockResponse: ChatMessagesResponse = {
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
          senderName: 'John Doe',
          recipientId: 1,
          message: 'Hi there!',
          isRead: false,
          createdAt: '2026-01-23T10:01:00Z',
        },
      ],
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getMessages(2, 50, 0);

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/messages/2?limit=50&offset=0', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('getMessages should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 400 });

    await expect(getMessages(0)).rejects.toThrow('HTTP 400');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('sendMessage should return response when successful', async (): Promise<void> => {
    const mockResponse: ChatSendResponse = {
      success: true,
      message: {
        id: 3,
        senderId: 1,
        senderName: 'Me',
        recipientId: 2,
        message: 'New message!',
        isRead: false,
        createdAt: '2026-01-23T10:02:00Z',
      },
      csrfToken: 'newToken123',
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await sendMessage(2, 'New message!', 'csrfToken');

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/send', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify({
        recipientId: 2,
        message: 'New message!',
        csrfToken: 'csrfToken',
      }),
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('sendMessage should handle validation error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 400 });

    await expect(sendMessage(0, '', 'csrfToken')).rejects.toThrow('HTTP 400');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('sendMessage should handle auth error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 401 });

    await expect(sendMessage(2, 'Hello', 'invalidToken')).rejects.toThrow('HTTP 401');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('markAsRead should return success when successful', async (): Promise<void> => {
    const mockResponse = { success: true };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await markAsRead(1, 'csrfToken');

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/read/1', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify({ csrfToken: 'csrfToken' }),
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('markAsRead should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 400 });

    await expect(markAsRead(0, 'csrfToken')).rejects.toThrow('HTTP 400');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('getUnreadCount should return count when successful', async (): Promise<void> => {
    const mockResponse: ChatUnreadCountResponse = {
      success: true,
      count: 5,
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getUnreadCount();

    expect(data).toEqual(mockResponse);
    expect(data.count).toBe(5);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/unread-count', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('getUnreadCount should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    await expect(getUnreadCount()).rejects.toThrow('HTTP 500');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('searchUsers should return users when successful', async (): Promise<void> => {
    const mockResponse: ChatUsersResponse = {
      success: true,
      users: [
        { userId: 2, displayName: 'John Doe', email: 'john@example.com' },
        { userId: 3, displayName: 'Jane Smith', email: 'jane@example.com' },
      ],
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await searchUsers('john');

    expect(data).toEqual(mockResponse);
    expect(data.users.length).toBe(2);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/chat/users?q=john', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('searchUsers should encode special characters in query', async (): Promise<void> => {
    const mockResponse: ChatUsersResponse = {
      success: true,
      users: [],
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    await searchUsers('john doe & jane');

    expect(fetch).toHaveBeenCalledWith('api/chat/users?q=john%20doe%20%26%20jane', expect.any(Object));
  });

  test('searchUsers should return empty array for short query', async (): Promise<void> => {
    const mockResponse: ChatUsersResponse = {
      success: true,
      users: [],
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await searchUsers('j');

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('searchUsers should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 401 });

    await expect(searchUsers('john')).rejects.toThrow('HTTP 401');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
