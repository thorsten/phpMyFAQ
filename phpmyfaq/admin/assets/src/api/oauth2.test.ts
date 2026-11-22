import { describe, it, expect, vi, afterEach } from 'vitest';
import { createOAuth2Client, deleteOAuth2Client, saveOAuth2Scopes, updateOAuth2Client } from './oauth2';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

const payload = {
  name: 'Intranet',
  redirectUri: 'https://intranet.example.org/callback',
  grants: ['authorization_code'],
  confidential: true,
};

describe('OAuth 2.0 API', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('createOAuth2Client should post the client together with the CSRF token', async () => {
    const created = { clientId: 'pmf_1', clientSecret: 'secret' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(created);

    expect(await createOAuth2Client(payload, 'csrf-save')).toEqual(created);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/oauth2/clients', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...payload, csrf: 'csrf-save' }),
    });
  });

  it('updateOAuth2Client should put the client to its URL-encoded ID', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'Client saved.' });

    await updateOAuth2Client('legacy/client', payload, 'csrf-save');

    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/oauth2/clients/legacy%2Fclient', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...payload, csrf: 'csrf-save' }),
    });
  });

  it('deleteOAuth2Client should send the CSRF token in the body', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'Client deleted.' });

    await deleteOAuth2Client('pmf_1', 'csrf-delete');

    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/oauth2/clients/pmf_1', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: 'csrf-delete' }),
    });
  });

  it('saveOAuth2Scopes should put the enabled scopes', async () => {
    const scopes = [{ scope: 'faq.read', description: 'Read FAQs' }];
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue({ success: 'Scopes saved.' });

    await saveOAuth2Scopes(scopes, 'csrf-scopes');

    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/oauth2/scopes', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ scopes, csrf: 'csrf-scopes' }),
    });
  });
});
