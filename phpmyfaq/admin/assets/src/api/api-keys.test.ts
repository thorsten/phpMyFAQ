import { describe, it, expect, vi, afterEach } from 'vitest';
import { createApiKey, deleteApiKey, fetchApiKeys, updateApiKey } from './api-keys';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

const payload = { name: 'CI', scopes: ['faq.read'], expiresAt: '2027-01-31 23:59:59' };

describe('API keys API', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('fetchApiKeys should load the keys of the logged-in user', async () => {
    const keys = [{ id: 1, name: 'CI', scopes: [], last_used_at: null, expires_at: null, created: '2026-11-22' }];
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(keys);

    expect(await fetchApiKeys()).toEqual(keys);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith(
      './api/user/api-keys',
      expect.objectContaining({ method: 'GET' })
    );
  });

  it('createApiKey should post the key together with the CSRF token', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ id: 2, apiKey: 'pmf_abc' });

    expect(await createApiKey(payload, 'csrf-create')).toEqual({ id: 2, apiKey: 'pmf_abc' });
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/user/api-keys', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...payload, csrf: 'csrf-create' }),
    });
  });

  it('updateApiKey should put the key to its own URL', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ id: 2 });

    await updateApiKey(2, payload, 'csrf-update');

    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/user/api-keys/2', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...payload, csrf: 'csrf-update' }),
    });
  });

  it('deleteApiKey should send the CSRF token in the body', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: true });

    await deleteApiKey('2', 'csrf-delete');

    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/user/api-keys/2', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: 'csrf-delete' }),
    });
  });
});
