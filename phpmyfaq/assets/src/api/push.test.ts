import { beforeEach, describe, expect, test, vi } from 'vitest';
import { getVapidPublicKey, unsubscribePush, getPushStatus } from './push';
import createFetchMock, { FetchMock } from 'vitest-fetch-mock';
import type { VapidPublicKeyResponse, PushSubscribeResponse, PushStatusResponse } from './push';

const fetchMocker: FetchMock = createFetchMock(vi);

fetchMocker.enableMocks();

describe('Push API', (): void => {
  beforeEach((): void => {
    fetchMocker.resetMocks();
  });

  test('getVapidPublicKey should return key and enabled status when successful', async (): Promise<void> => {
    const mockResponse: VapidPublicKeyResponse = {
      enabled: true,
      vapidPublicKey: 'BNcR...testPublicKey',
    };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getVapidPublicKey();

    expect(data).toEqual(mockResponse);
    expect(data.enabled).toBe(true);
    expect(data.vapidPublicKey).toBe('BNcR...testPublicKey');
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/push/vapid-public-key', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('getVapidPublicKey should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 500 });

    await expect(getVapidPublicKey()).rejects.toThrow('HTTP 500');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('unsubscribePush should send endpoint and return success', async (): Promise<void> => {
    const mockResponse: PushSubscribeResponse = { success: true };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await unsubscribePush('https://fcm.googleapis.com/fcm/send/abc123');

    expect(data).toEqual(mockResponse);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/push/unsubscribe', {
      method: 'POST',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ endpoint: 'https://fcm.googleapis.com/fcm/send/abc123' }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('unsubscribePush should handle error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 401 });

    await expect(unsubscribePush('https://example.com/push')).rejects.toThrow('HTTP 401');
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  test('getPushStatus should return subscription status', async (): Promise<void> => {
    const mockResponse: PushStatusResponse = { subscribed: true };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getPushStatus();

    expect(data).toEqual(mockResponse);
    expect(data.subscribed).toBe(true);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('api/push/status', {
      method: 'GET',
      cache: 'no-cache',
      headers: { 'Content-Type': 'application/json' },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  test('getPushStatus should return false when not subscribed', async (): Promise<void> => {
    const mockResponse: PushStatusResponse = { subscribed: false };
    fetchMocker.mockResponseOnce(JSON.stringify(mockResponse));

    const data = await getPushStatus();

    expect(data.subscribed).toBe(false);
  });

  test('getPushStatus should handle auth error', async (): Promise<void> => {
    fetchMocker.mockResponseOnce(null, { status: 401 });

    await expect(getPushStatus()).rejects.toThrow('HTTP 401');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
