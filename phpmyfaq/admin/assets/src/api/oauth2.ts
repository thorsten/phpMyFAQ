/**
 * Fetch data for OAuth 2.0 client and scope management
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
 * @since     2026-11-22
 */

import { fetchJson } from './fetch-wrapper';
import { OAuth2ClientPayload, OAuth2ClientResponse, OAuth2Scope } from '../interfaces';

export const createOAuth2Client = async (payload: OAuth2ClientPayload, csrf: string): Promise<OAuth2ClientResponse> => {
  return await fetchJson<OAuth2ClientResponse>('./api/oauth2/clients', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ ...payload, csrf }),
  });
};

export const updateOAuth2Client = async (
  clientId: string,
  payload: OAuth2ClientPayload,
  csrf: string
): Promise<OAuth2ClientResponse> => {
  return await fetchJson<OAuth2ClientResponse>(`./api/oauth2/clients/${encodeURIComponent(clientId)}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ ...payload, csrf }),
  });
};

export const deleteOAuth2Client = async (clientId: string, csrf: string): Promise<OAuth2ClientResponse> => {
  return await fetchJson<OAuth2ClientResponse>(`./api/oauth2/clients/${encodeURIComponent(clientId)}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ csrf }),
  });
};

export const saveOAuth2Scopes = async (scopes: OAuth2Scope[], csrf: string): Promise<OAuth2ClientResponse> => {
  return await fetchJson<OAuth2ClientResponse>('./api/oauth2/scopes', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ scopes, csrf }),
  });
};
