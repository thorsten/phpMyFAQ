/**
 * Admin LDAP configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-03-05
 */

import { fetchLdapConfiguration, fetchLdapHealthcheck, LdapServerHealth } from '../api';

const badge = (enabled: boolean): string => {
  const cls = enabled ? 'bg-success' : 'bg-secondary';
  const text = enabled ? 'Enabled' : 'Disabled';
  return `<span class="badge ${cls}">${text}</span>`;
};

const healthBadge = (serverHealth: LdapServerHealth | undefined): string => {
  if (!serverHealth) {
    return '<span class="badge bg-secondary">Not tested</span>';
  }
  if (serverHealth.available) {
    return '<span class="badge bg-success">Connected</span>';
  }
  const tooltip = serverHealth.error ? ` title="${serverHealth.error}"` : '';
  return `<span class="badge bg-danger"${tooltip}>Unreachable</span>`;
};

export const handleLdap = async (): Promise<void> => {
  const alertDiv = document.getElementById('pmf-ldap-healthcheck-alert') as HTMLElement;
  if (!alertDiv) {
    return;
  }

  const alertMessage = alertDiv.querySelector('.alert-message');

  // Health check
  alertDiv.style.display = 'block';
  alertDiv.className = 'alert alert-info';
  if (alertMessage) {
    alertMessage.textContent = 'Checking LDAP connection...';
  }

  let healthy = false;
  const serverHealthMap: Map<number, LdapServerHealth> = new Map();
  try {
    const healthResult = await fetchLdapHealthcheck(10000);
    healthy = healthResult.available;

    for (const serverHealth of healthResult.servers) {
      serverHealthMap.set(serverHealth.index, serverHealth);
    }

    if (healthy) {
      alertDiv.style.display = 'none';
    } else {
      alertDiv.style.display = 'block';
      alertDiv.className = 'alert alert-warning';
      if (alertMessage) {
        const failedCount = healthResult.servers.filter((s) => !s.available).length;
        alertMessage.textContent = `${failedCount} of ${healthResult.servers.length} LDAP server(s) unreachable.`;
      }
    }
  } catch (error) {
    alertDiv.style.display = 'block';
    alertDiv.className = 'alert alert-danger';
    if (alertMessage) {
      alertMessage.textContent = error instanceof Error ? error.message : 'LDAP is unavailable';
    }
  }

  // Fetch and render configuration
  try {
    const config = await fetchLdapConfiguration();

    // Servers
    const serversDiv = document.getElementById('pmf-ldap-servers') as HTMLElement;
    if (serversDiv) {
      let html = '';
      config.servers.forEach((server, index) => {
        if (index > 0) {
          html += '<hr>';
        }
        const serverHealth = serverHealthMap.get(index);
        html += `<h6>Server ${index + 1}</h6>`;
        html += '<dl class="row mb-0">';
        html += `<dt class="col-sm-4">Host</dt><dd class="col-sm-8">${server.ldap_server || '<em>Not set</em>'} ${healthBadge(serverHealth)}</dd>`;
        html += `<dt class="col-sm-4">Port</dt><dd class="col-sm-8">${server.ldap_port || 389}</dd>`;
        html += `<dt class="col-sm-4">Bind User</dt><dd class="col-sm-8">${server.ldap_user || '<em>Not set</em>'}</dd>`;
        html += `<dt class="col-sm-4">Base DN</dt><dd class="col-sm-8">${server.ldap_base || '<em>Not set</em>'}</dd>`;
        html += '</dl>';
      });
      serversDiv.innerHTML = html || '<p class="text-muted">No servers configured.</p>';
    }

    // Mapping
    const mappingDiv = document.getElementById('pmf-ldap-mapping') as HTMLElement;
    if (mappingDiv) {
      let html = '<dl class="row mb-0">';
      html += `<dt class="col-sm-4">Name</dt><dd class="col-sm-8"><code>${config.mapping.name || ''}</code></dd>`;
      html += `<dt class="col-sm-4">Username</dt><dd class="col-sm-8"><code>${config.mapping.username || ''}</code></dd>`;
      html += `<dt class="col-sm-4">Mail</dt><dd class="col-sm-8"><code>${config.mapping.mail || ''}</code></dd>`;
      html += `<dt class="col-sm-4">Member Of</dt><dd class="col-sm-8"><code>${config.mapping.memberOf || ''}</code></dd>`;
      html += '</dl>';
      mappingDiv.innerHTML = html;
    }

    // Options
    const optionsDiv = document.getElementById('pmf-ldap-options') as HTMLElement;
    if (optionsDiv) {
      let html = '<dl class="row mb-0">';
      html += `<dt class="col-sm-6">Protocol Version</dt><dd class="col-sm-6">${config.options.LDAP_OPT_PROTOCOL_VERSION ?? 3}</dd>`;
      html += `<dt class="col-sm-6">Referrals</dt><dd class="col-sm-6">${config.options.LDAP_OPT_REFERRALS ?? 0}</dd>`;
      html += '</dl>';
      optionsDiv.innerHTML = html;
    }

    // Group Settings
    const groupDiv = document.getElementById('pmf-ldap-group-settings') as HTMLElement;
    if (groupDiv) {
      let html = '<dl class="row mb-0">';
      html += `<dt class="col-sm-6">Group Restriction</dt><dd class="col-sm-6">${badge(!!config.groupConfig.use_group_restriction)}</dd>`;
      html += `<dt class="col-sm-6">Auto-Assign</dt><dd class="col-sm-6">${badge(!!config.groupConfig.auto_assign)}</dd>`;
      const groups = config.groupConfig.allowed_groups?.length
        ? config.groupConfig.allowed_groups.join(', ')
        : '<em>None</em>';
      html += `<dt class="col-sm-6">Allowed Groups</dt><dd class="col-sm-6">${groups}</dd>`;
      html += '</dl>';
      groupDiv.innerHTML = html;
    }

    // General Settings
    const generalDiv = document.getElementById('pmf-ldap-general-settings') as HTMLElement;
    if (generalDiv) {
      let html = '<dl class="row mb-0">';
      html += `<dt class="col-sm-6">Domain Prefix</dt><dd class="col-sm-6">${badge(config.generalSettings.domainPrefix)}</dd>`;
      html += `<dt class="col-sm-6">SASL</dt><dd class="col-sm-6">${badge(config.generalSettings.sasl)}</dd>`;
      html += `<dt class="col-sm-6">Anonymous Login</dt><dd class="col-sm-6">${badge(config.generalSettings.anonymousLogin)}</dd>`;
      html += `<dt class="col-sm-6">Dynamic Login</dt><dd class="col-sm-6">${badge(config.generalSettings.dynamicLogin)}</dd>`;
      if (config.generalSettings.dynamicLogin && config.generalSettings.dynamicLoginAttribute) {
        html += `<dt class="col-sm-6">Dynamic Login Attribute</dt><dd class="col-sm-6"><code>${config.generalSettings.dynamicLoginAttribute}</code></dd>`;
      }
      html += `<dt class="col-sm-6">Multiple Servers</dt><dd class="col-sm-6">${badge(config.generalSettings.multipleServers)}</dd>`;
      html += '</dl>';
      generalDiv.innerHTML = html;
    }
  } catch {
    // If we can't fetch config and health check also failed, that's expected
    if (healthy) {
      const serversDiv = document.getElementById('pmf-ldap-servers') as HTMLElement;
      if (serversDiv) {
        serversDiv.innerHTML = '<p class="text-danger">Failed to load LDAP configuration.</p>';
      }
    }
  }
};
