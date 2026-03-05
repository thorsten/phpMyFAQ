/**
 * Fetch data for LDAP configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-03-05
 */

import { fetchWrapper, fetchJson } from './fetch-wrapper';

export interface LdapServer {
  ldap_server: string;
  ldap_port: number;
  ldap_user: string;
  ldap_password: string;
  ldap_base: string;
}

export interface LdapMapping {
  name: string;
  username: string;
  mail: string;
  memberOf: string;
}

export interface LdapOptions {
  LDAP_OPT_PROTOCOL_VERSION: number;
  LDAP_OPT_REFERRALS: number;
}

export interface LdapGroupConfig {
  use_group_restriction: boolean;
  allowed_groups: string[];
  auto_assign: boolean;
  group_mapping: Record<string, string>;
}

export interface LdapGeneralSettings {
  domainPrefix: boolean;
  sasl: boolean;
  anonymousLogin: boolean;
  dynamicLogin: boolean;
  dynamicLoginAttribute: string;
  multipleServers: boolean;
}

export interface LdapConfigurationResponse {
  servers: LdapServer[];
  mapping: LdapMapping;
  options: LdapOptions;
  groupConfig: LdapGroupConfig;
  generalSettings: LdapGeneralSettings;
}

export interface LdapServerHealth {
  index: number;
  host: string;
  available: boolean;
  error?: string | null;
}

export interface LdapHealthcheckResponse {
  available: boolean;
  status: string;
  error?: string;
  servers: LdapServerHealth[];
}

export const fetchLdapConfiguration = async (): Promise<LdapConfigurationResponse> => {
  return (await fetchJson('./api/ldap/configuration', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as LdapConfigurationResponse;
};

export const fetchLdapHealthcheck = async (timeoutMs: number = 5000): Promise<LdapHealthcheckResponse> => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetchWrapper('./api/ldap/healthcheck', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'LDAP is unavailable');
    }

    return await response.json();
  } catch (error) {
    clearTimeout(timeoutId);
    if (error instanceof Error && error.name === 'AbortError') {
      throw new Error('LDAP health check timed out. Service may be down.');
    }
    throw error;
  }
};
