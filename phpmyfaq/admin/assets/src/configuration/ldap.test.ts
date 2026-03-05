import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleLdap } from './ldap';
import { fetchLdapConfiguration, fetchLdapHealthcheck } from '../api/ldap';

vi.mock('../api/ldap');

const mockHealthyResponse = {
  available: true,
  status: 'healthy',
  servers: [{ index: 0, host: 'ldap.example.com', available: true, error: null }],
};

const mockConfigResponse = {
  servers: [
    {
      ldap_server: 'ldap.example.com',
      ldap_port: 389,
      ldap_user: 'cn=admin,dc=example,dc=com',
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

const ldapPageHtml = `
  <div id="pmf-ldap-healthcheck-alert" style="display: none;">
    <span class="alert-message"></span>
  </div>
  <div id="pmf-ldap-servers"></div>
  <div id="pmf-ldap-mapping"></div>
  <div id="pmf-ldap-options"></div>
  <div id="pmf-ldap-group-settings"></div>
  <div id="pmf-ldap-general-settings"></div>
`;

describe('LDAP Configuration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleLdap', () => {
    it('should return early when alert element is missing', async () => {
      document.body.innerHTML = '';

      await handleLdap();

      expect(fetchLdapHealthcheck).not.toHaveBeenCalled();
      expect(fetchLdapConfiguration).not.toHaveBeenCalled();
    });

    it('should hide health check alert when all servers are healthy', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const alertDiv = document.getElementById('pmf-ldap-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('none');
    });

    it('should show warning alert when some servers are unreachable', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue({
        available: false,
        status: 'degraded',
        servers: [
          { index: 0, host: 'ldap1.example.com', available: true, error: null },
          { index: 1, host: 'ldap2.example.com', available: false, error: 'Connection refused' },
        ],
      });
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const alertDiv = document.getElementById('pmf-ldap-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('block');
      expect(alertDiv.className).toBe('alert alert-warning');
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('1 of 2 LDAP server(s) unreachable.');
    });

    it('should show error alert when healthcheck throws', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockRejectedValue(new Error('LDAP is unavailable'));
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const alertDiv = document.getElementById('pmf-ldap-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('block');
      expect(alertDiv.className).toBe('alert alert-danger');
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('LDAP is unavailable');
    });

    it('should show generic message for non-Error healthcheck failures', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockRejectedValue('string error');
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const alertDiv = document.getElementById('pmf-ldap-healthcheck-alert') as HTMLElement;
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('LDAP is unavailable');
    });

    it('should render server configuration with Connected badge when healthy', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const serversDiv = document.getElementById('pmf-ldap-servers') as HTMLElement;
      expect(serversDiv.innerHTML).toContain('ldap.example.com');
      expect(serversDiv.innerHTML).toContain('Connected');
      expect(serversDiv.innerHTML).toContain('bg-success');
      expect(serversDiv.innerHTML).toContain('389');
      expect(serversDiv.innerHTML).toContain('cn=admin,dc=example,dc=com');
      expect(serversDiv.innerHTML).toContain('dc=example,dc=com');
    });

    it('should render server configuration with Unreachable badge when unhealthy', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue({
        available: false,
        status: 'degraded',
        servers: [{ index: 0, host: 'ldap.example.com', available: false, error: 'Connection refused' }],
      });
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const serversDiv = document.getElementById('pmf-ldap-servers') as HTMLElement;
      expect(serversDiv.innerHTML).toContain('Unreachable');
      expect(serversDiv.innerHTML).toContain('bg-danger');
      expect(serversDiv.innerHTML).toContain('Connection refused');
    });

    it('should render attribute mapping', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const mappingDiv = document.getElementById('pmf-ldap-mapping') as HTMLElement;
      expect(mappingDiv.innerHTML).toContain('cn');
      expect(mappingDiv.innerHTML).toContain('uid');
      expect(mappingDiv.innerHTML).toContain('mail');
      expect(mappingDiv.innerHTML).toContain('memberOf');
    });

    it('should render LDAP options', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const optionsDiv = document.getElementById('pmf-ldap-options') as HTMLElement;
      expect(optionsDiv.innerHTML).toContain('3');
      expect(optionsDiv.innerHTML).toContain('0');
    });

    it('should render group settings with Enabled/Disabled badges', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue({
        ...mockConfigResponse,
        groupConfig: {
          use_group_restriction: true,
          allowed_groups: ['admins', 'editors'],
          auto_assign: false,
          group_mapping: {},
        },
      });

      await handleLdap();

      const groupDiv = document.getElementById('pmf-ldap-group-settings') as HTMLElement;
      expect(groupDiv.innerHTML).toContain('Enabled');
      expect(groupDiv.innerHTML).toContain('Disabled');
      expect(groupDiv.innerHTML).toContain('admins, editors');
    });

    it('should render general settings with badges', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue({
        ...mockConfigResponse,
        generalSettings: {
          domainPrefix: true,
          sasl: false,
          anonymousLogin: false,
          dynamicLogin: true,
          dynamicLoginAttribute: 'uid',
          multipleServers: false,
        },
      });

      await handleLdap();

      const generalDiv = document.getElementById('pmf-ldap-general-settings') as HTMLElement;
      expect(generalDiv.innerHTML).toContain('Domain Prefix');
      expect(generalDiv.innerHTML).toContain('Dynamic Login');
      expect(generalDiv.innerHTML).toContain('uid');
      // domainPrefix and dynamicLogin are enabled
      const enabledBadges = generalDiv.querySelectorAll('.badge.bg-success');
      expect(enabledBadges.length).toBe(2);
    });

    it('should not show dynamic login attribute when dynamic login is disabled', async () => {
      document.body.innerHTML = ldapPageHtml;

      (fetchLdapHealthcheck as Mock).mockResolvedValue(mockHealthyResponse);
      (fetchLdapConfiguration as Mock).mockResolvedValue(mockConfigResponse);

      await handleLdap();

      const generalDiv = document.getElementById('pmf-ldap-general-settings') as HTMLElement;
      expect(generalDiv.innerHTML).not.toContain('Dynamic Login Attribute');
    });
  });
});
