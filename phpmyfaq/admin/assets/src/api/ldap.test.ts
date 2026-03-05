import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchLdapConfiguration, fetchLdapHealthcheck } from './ldap';

describe('LDAP API', () => {
  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  describe('fetchLdapConfiguration', () => {
    it('should fetch LDAP configuration and return JSON response if successful', async () => {
      const mockResponse = {
        servers: [
          {
            ldap_server: 'ldap.example.com',
            ldap_port: 389,
            ldap_user: 'cn=admin',
            ldap_password: '********',
            ldap_base: 'dc=example,dc=com',
          },
        ],
        mapping: { name: 'cn', username: 'uid', mail: 'mail', memberOf: 'memberOf' },
        options: { LDAP_OPT_PROTOCOL_VERSION: 3, LDAP_OPT_REFERRALS: 0 },
        groupConfig: { use_group_restriction: false, allowed_groups: [], auto_assign: false, group_mapping: {} },
        generalSettings: {
          domainPrefix: false,
          sasl: false,
          anonymousLogin: false,
          dynamicLogin: false,
          dynamicLoginAttribute: '',
          multipleServers: false,
        },
      };
      globalThis.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchLdapConfiguration();

      expect(result).toEqual(mockResponse);
      expect(globalThis.fetch).toHaveBeenCalledWith('./api/ldap/configuration', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      globalThis.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchLdapConfiguration()).rejects.toThrow(mockError);
    });
  });

  describe('fetchLdapHealthcheck', () => {
    it('should fetch LDAP healthcheck and return JSON response when available', async () => {
      const mockResponse = {
        available: true,
        status: 'healthy',
        servers: [{ index: 0, host: 'ldap.example.com', available: true, error: null }],
      };
      globalThis.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const result = await fetchLdapHealthcheck();

      expect(result).toEqual(mockResponse);
      expect(globalThis.fetch).toHaveBeenCalledWith(
        './api/ldap/healthcheck',
        expect.objectContaining({
          method: 'GET',
          cache: 'no-cache',
          headers: {
            'Content-Type': 'application/json',
          },
          redirect: 'follow',
          referrerPolicy: 'no-referrer',
          signal: expect.any(AbortSignal),
        })
      );
    });

    it('should throw an error when LDAP returns 503 Service Unavailable', async () => {
      const errorResponse = { available: false, status: 'unavailable', servers: [] };
      globalThis.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 503,
          json: () => Promise.resolve(errorResponse),
        } as Response)
      );

      await expect(fetchLdapHealthcheck()).rejects.toThrow('LDAP is unavailable');
    });

    it('should throw an error with custom message when error data is provided', async () => {
      const errorResponse = { error: 'PHP LDAP extension is not loaded.' };
      globalThis.fetch = vi.fn(() =>
        Promise.resolve({
          ok: false,
          status: 503,
          json: () => Promise.resolve(errorResponse),
        } as Response)
      );

      await expect(fetchLdapHealthcheck()).rejects.toThrow('PHP LDAP extension is not loaded.');
    });

    it('should throw a timeout error when request takes too long', async () => {
      vi.useFakeTimers();

      globalThis.fetch = vi.fn(
        () =>
          new Promise<Response>((_, reject) => {
            // The AbortController signal will trigger this
            const signal = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls[0][1]?.signal;
            if (signal) {
              signal.addEventListener('abort', () => {
                const error = new Error('The operation was aborted.');
                error.name = 'AbortError';
                reject(error);
              });
            }
          })
      );

      const promise = fetchLdapHealthcheck(1000);
      vi.advanceTimersByTime(1000);

      await expect(promise).rejects.toThrow('LDAP health check timed out. Service may be down.');
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Network error');
      globalThis.fetch = vi.fn(() => Promise.reject(mockError));

      await expect(fetchLdapHealthcheck()).rejects.toThrow(mockError);
    });
  });
});
