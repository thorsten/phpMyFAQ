import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { fetchWrapper, fetchJson } from './fetch-wrapper';

describe('fetchWrapper', () => {
  let originalFetch: typeof globalThis.fetch;
  let originalLocation: Location;
  let sessionStorageMock: { [key: string]: string };

  beforeEach(() => {
    // Save original fetch
    originalFetch = globalThis.fetch;

    // Save the original location
    originalLocation = window.location;

    // Mock sessionStorage
    sessionStorageMock = {};
    const sessionStorageMockImpl = {
      getItem: vi.fn((key: string) => sessionStorageMock[key] || null),
      setItem: vi.fn((key: string, value: string) => {
        sessionStorageMock[key] = value;
      }),
      removeItem: vi.fn((key: string) => {
        Reflect.deleteProperty(sessionStorageMock, key);
      }),
      clear: vi.fn(() => {
        sessionStorageMock = {};
      }),
    };

    Object.defineProperty(window, 'sessionStorage', {
      value: sessionStorageMockImpl,
      writable: true,
    });

    // Mock window.location.href
    Object.defineProperty(window, 'location', {
      value: { ...originalLocation, href: '' },
      writable: true,
      configurable: true,
    });
  });

  afterEach(() => {
    // Restore original fetch
    globalThis.fetch = originalFetch;

    // Restore original location
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true,
      configurable: true,
    });

    vi.clearAllMocks();
  });

  describe('successful responses', () => {
    it('should return response for 200 status', async () => {
      const mockResponse = new Response(JSON.stringify({ data: 'test' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'GET' });

      expect(response.status).toBe(200);
      expect(await response.json()).toEqual({ data: 'test' });
    });

    it('should return response for 201 status', async () => {
      const mockResponse = new Response(JSON.stringify({ created: true }), {
        status: 201,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'POST' });

      expect(response.status).toBe(201);
      expect(await response.json()).toEqual({ created: true });
    });

    it('should pass through all fetch options', async () => {
      const mockResponse = new Response('OK', { status: 200 });
      const fetchSpy = vi.fn().mockResolvedValue(mockResponse);
      globalThis.fetch = fetchSpy;

      const options = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ test: 'data' }),
      };

      await fetchWrapper('/api/test', options);

      expect(fetchSpy).toHaveBeenCalledWith('/api/test', options);
    });
  });

  describe('401 Unauthorized handling', () => {
    it('should store session timeout message in sessionStorage on 401', async () => {
      const mockResponse = new Response('Unauthorized', { status: 401 });
      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      try {
        await fetchWrapper('/test', { method: 'GET' });
        expect.fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(Error);
        expect((error as Error).message).toBe('Session expired');
      }

      expect(sessionStorageMock['loginMessage']).toBe('Your session has expired. Please log in again.');
    });

    it('should redirect to login page on 401', async () => {
      const mockResponse = new Response('Unauthorized', { status: 401 });
      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      try {
        await fetchWrapper('/test', { method: 'GET' });
        expect.fail('Should have thrown an error');
      } catch {
        expect(window.location.href).toBe('./admin/login');
      }
    });

    it('should throw error to stop further processing on 401', async () => {
      const mockResponse = new Response('Unauthorized', { status: 401 });
      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      await expect(fetchWrapper('/test', { method: 'GET' })).rejects.toThrow('Session expired');
    });

    it('should handle 401 from POST request', async () => {
      const mockResponse = new Response('Unauthorized', { status: 401 });
      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const options = {
        method: 'POST',
        body: JSON.stringify({ data: 'test' }),
      };

      try {
        await fetchWrapper('/api/save', options);
        expect.fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(Error);
        expect((error as Error).message).toBe('Session expired');
        expect(sessionStorageMock['loginMessage']).toBe('Your session has expired. Please log in again.');
        expect(window.location.href).toBe('./admin/login');
      }
    });
  });

  describe('other error responses', () => {
    it('should return response for 400 Bad Request', async () => {
      const mockResponse = new Response(JSON.stringify({ error: 'Bad request' }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'GET' });

      expect(response.status).toBe(400);
      expect(await response.json()).toEqual({ error: 'Bad request' });
    });

    it('should return response for 403 Forbidden', async () => {
      const mockResponse = new Response(JSON.stringify({ error: 'Forbidden' }), {
        status: 403,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'GET' });

      expect(response.status).toBe(403);
      expect(await response.json()).toEqual({ error: 'Forbidden' });
    });

    it('should return response for 404 Not Found', async () => {
      const mockResponse = new Response(JSON.stringify({ error: 'Not found' }), {
        status: 404,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'GET' });

      expect(response.status).toBe(404);
      expect(await response.json()).toEqual({ error: 'Not found' });
    });

    it('should return response for 500 Internal Server Error', async () => {
      const mockResponse = new Response(JSON.stringify({ error: 'Server error' }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' },
      });

      globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

      const response = await fetchWrapper('/test', { method: 'GET' });

      expect(response.status).toBe(500);
      expect(await response.json()).toEqual({ error: 'Server error' });
    });
  });
});

describe('fetchJson', () => {
  let originalFetch: typeof globalThis.fetch;

  beforeEach(() => {
    originalFetch = globalThis.fetch;
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
    vi.clearAllMocks();
  });

  it('should fetch and parse JSON for successful response', async () => {
    const mockData = { message: 'Success', data: [1, 2, 3] };
    const mockResponse = new Response(JSON.stringify(mockData), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    });

    globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

    const result = await fetchJson('/api/test', { method: 'GET' });

    expect(result).toEqual(mockData);
  });

  it('should handle POST request with JSON body', async () => {
    const mockData = { id: 123 };
    const mockResponse = new Response(JSON.stringify(mockData), {
      status: 201,
      headers: { 'Content-Type': 'application/json' },
    });

    globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

    const requestBody = { name: 'Test' };
    const result = await fetchJson('/api/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(requestBody),
    });

    expect(result).toEqual(mockData);
  });

  it('should parse empty response as null', async () => {
    const mockResponse = new Response('null', {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    });

    globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

    const result = await fetchJson('/api/test');

    expect(result).toBeNull();
  });

  it('should parse array response', async () => {
    const mockData = [{ id: 1 }, { id: 2 }, { id: 3 }];
    const mockResponse = new Response(JSON.stringify(mockData), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    });

    globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

    const result = await fetchJson('/api/list');

    expect(result).toEqual(mockData);
  });

  it('should handle error responses and still parse JSON', async () => {
    const mockError = { error: 'Validation failed', fields: ['name', 'email'] };
    const mockResponse = new Response(JSON.stringify(mockError), {
      status: 400,
      headers: { 'Content-Type': 'application/json' },
    });

    globalThis.fetch = vi.fn().mockResolvedValue(mockResponse);

    const result = await fetchJson('/api/validate');

    expect(result).toEqual(mockError);
  });
});
