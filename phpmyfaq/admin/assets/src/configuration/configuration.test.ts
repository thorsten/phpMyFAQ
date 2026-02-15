import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import {
  handleConfiguration,
  handleConfigurationTabFiltering,
  handleSaveConfiguration,
  handleSMTPPasswordToggle,
  handleTranslation,
  handleTemplates,
  handleFaqsSortingKeys,
  handleFaqsSortingOrder,
  handleFaqsSortingPopular,
  handlePermLevel,
  handleReleaseEnvironment,
  handleSearchRelevance,
  handleSeoMetaTags,
  handleMailProvider,
} from './configuration';
import {
  fetchConfiguration,
  fetchFaqsSortingKeys,
  fetchFaqsSortingOrder,
  fetchFaqsSortingPopular,
  fetchPermLevel,
  fetchReleaseEnvironment,
  fetchSearchRelevance,
  fetchSeoMetaTags,
  fetchMailProvider,
  fetchTemplates,
  fetchTranslations,
  saveConfiguration,
} from '../api';

vi.mock('../api');

describe('Configuration Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('handleSaveConfiguration', () => {
    it('should save configuration and show notification', async () => {
      document.body.innerHTML = `
        <form id="configuration-list"></form>
        <button id="save-configuration"></button>
      `;

      (saveConfiguration as Mock).mockResolvedValue({ success: 'Configuration saved' });

      await handleSaveConfiguration();

      const event = new Event('click');
      document.getElementById('save-configuration')?.dispatchEvent(event);

      expect(saveConfiguration).toHaveBeenCalled();
    });
  });

  describe('handleSMTPPasswordToggle', () => {
    it('should toggle SMTP password visibility', async () => {
      document.body.innerHTML = `
        <input name="edit[mail.remoteSMTPPassword]" type="password" />
      `;

      await handleSMTPPasswordToggle();

      const toggle = document.getElementById('SMTPtogglePassword');
      toggle?.click();

      const passwordField = document.querySelector('input[name="edit[mail.remoteSMTPPassword]"]');
      expect(passwordField?.getAttribute('type')).toBe('text');
    });
  });

  describe('handleTranslation', () => {
    it('should fetch and insert translations', async () => {
      document.body.innerHTML = `
        <select name="edit[main.language]"></select>
      `;

      (fetchTranslations as Mock).mockResolvedValue('<option value="en">English</option>');

      await handleTranslation();

      const selectBox = document.querySelector('select[name="edit[main.language]"]');
      expect(selectBox?.innerHTML).toContain('<option value="en">English</option>');
    });
  });

  describe('handleTemplates', () => {
    it('should fetch and insert templates', async () => {
      document.body.innerHTML = `
        <select name="edit[layout.templateSet]"></select>
      `;

      (fetchTemplates as Mock).mockResolvedValue('<option value="default">Default</option>');

      await handleTemplates();

      const selectBox = document.querySelector('select[name="edit[layout.templateSet]"]');
      expect(selectBox?.innerHTML).toContain('<option value="default">Default</option>');
    });
  });

  describe('handleFaqsSortingKeys', () => {
    it('should fetch and insert FAQs sorting keys', async () => {
      document.body.innerHTML = `
        <select name="edit[records.orderby]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchFaqsSortingKeys as Mock).mockResolvedValue('<option value="key">Key</option>');

      await handleFaqsSortingKeys();

      const selectBox = document.querySelector('select[name="edit[records.orderby]"]');
      expect(selectBox?.innerHTML).toContain('<option value="key">Key</option>');
    });
  });

  describe('handleFaqsSortingOrder', () => {
    it('should fetch and insert FAQs sorting order', async () => {
      document.body.innerHTML = `
        <select name="edit[records.sortby]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchFaqsSortingOrder as Mock).mockResolvedValue('<option value="order">Order</option>');

      await handleFaqsSortingOrder();

      const selectBox = document.querySelector('select[name="edit[records.sortby]"]');
      expect(selectBox?.innerHTML).toContain('<option value="order">Order</option>');
    });
  });

  describe('handleFaqsSortingPopular', () => {
    it('should fetch and insert FAQs sorting popular', async () => {
      document.body.innerHTML = `
        <select name="edit[records.orderingPopularFaqs]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchFaqsSortingPopular as Mock).mockResolvedValue('<option value="popular">Popular</option>');

      await handleFaqsSortingPopular();

      const selectBox = document.querySelector('select[name="edit[records.orderingPopularFaqs]"]');
      expect(selectBox?.innerHTML).toContain('<option value="popular">Popular</option>');
    });
  });

  describe('handlePermLevel', () => {
    it('should fetch and insert permission levels', async () => {
      document.body.innerHTML = `
        <select name="edit[security.permLevel]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchPermLevel as Mock).mockResolvedValue('<option value="level">Level</option>');

      await handlePermLevel();

      const selectBox = document.querySelector('select[name="edit[security.permLevel]"]');
      expect(selectBox?.innerHTML).toContain('<option value="level">Level</option>');
    });
  });

  describe('handleReleaseEnvironment', () => {
    it('should fetch and insert release environments', async () => {
      document.body.innerHTML = `
        <select name="edit[upgrade.releaseEnvironment]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchReleaseEnvironment as Mock).mockResolvedValue('<option value="env">Environment</option>');

      await handleReleaseEnvironment();

      const selectBox = document.querySelector('select[name="edit[upgrade.releaseEnvironment]"]');
      expect(selectBox?.innerHTML).toContain('<option value="env">Environment</option>');
    });
  });

  describe('handleSearchRelevance', () => {
    it('should fetch and insert search relevance options', async () => {
      document.body.innerHTML = `
        <select name="edit[search.relevance]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchSearchRelevance as Mock).mockResolvedValue('<option value="relevance">Relevance</option>');

      await handleSearchRelevance();

      const selectBox = document.querySelector('select[name="edit[search.relevance]"]');
      expect(selectBox?.innerHTML).toContain('<option value="relevance">Relevance</option>');
    });
  });

  describe('handleSeoMetaTags', () => {
    it('should fetch and insert SEO meta tags', async () => {
      document.body.innerHTML = `
        <select name="edit[seo.metaTags]" data-pmf-configuration-current-value="someValue"></select>
      `;

      (fetchSeoMetaTags as Mock).mockResolvedValue('<option value="meta">Meta</option>');

      await handleSeoMetaTags();

      const selectBox = document.querySelector('select[name="edit[seo.metaTags]"]');
      expect(selectBox?.innerHTML).toContain('<option value="meta">Meta</option>');
    });
  });

  describe('handleMailProvider', () => {
    it('should fetch and insert mail provider options', async () => {
      document.body.innerHTML = `
        <select name="edit[mail.provider]" data-pmf-configuration-current-value="smtp"></select>
      `;

      (fetchMailProvider as Mock).mockResolvedValue('<option value="smtp">SMTP</option>');

      await handleMailProvider();

      const selectBox = document.querySelector('select[name="edit[mail.provider]"]');
      expect(selectBox?.innerHTML).toContain('<option value="smtp">SMTP</option>');
    });
  });

  describe('handleConfiguration', () => {
    it('should handle configuration tabs and load data', async () => {
      document.body.innerHTML = `
        <form id="configuration-list">
          <ul class="pmf-configuration-tabs">
            <li class="nav-item" data-config-group="core" data-config-label="Main">
              <a href="#main" data-bs-toggle="tab"></a>
            </li>
            <li class="nav-item" data-config-group="appearance" data-config-label="Layout">
              <a href="#layout" data-bs-toggle="tab"></a>
            </li>
          </ul>
        </form>
        <div id="pmf-configuration-result"></div>
        <input id="pmf-language" value="en">
        <div id="main"></div>
      `;

      (fetchConfiguration as Mock).mockResolvedValue('Configuration content');

      await handleConfiguration();

      const event = new Event('shown.bs.tab');
      document.querySelector('#configuration-list a')?.dispatchEvent(event);

      expect(fetchConfiguration).toHaveBeenCalled();
    });
  });

  describe('handleConfigurationTabFiltering', () => {
    const buildFilterDOM = (): void => {
      document.body.innerHTML = `
        <input id="pmf-language" value="en" />
        <input id="pmf-configuration-tab-filter" value="" />
        <ul class="pmf-configuration-tabs">
          <li class="pmf-configuration-group" data-config-group="core"><span>Core</span></li>
          <li class="nav-item" data-config-group="core" data-config-label="Main">
            <a class="nav-link" href="#main"></a>
          </li>
          <li class="pmf-configuration-group" data-config-group="maintenance"><span>Maintenance</span></li>
          <li class="nav-item" data-config-group="maintenance" data-config-label="Upgrade">
            <a class="nav-link active" href="#upgrade"></a>
          </li>
        </ul>
        <div id="main">
          <div class="pmf-config-item" data-config-key="main.language">Default language</div>
          <div class="pmf-config-item" data-config-key="main.titleFAQ">FAQ title</div>
        </div>
        <div id="records"></div>
        <div id="search"></div>
        <div id="security"></div>
        <div id="spam"></div>
        <div id="seo"></div>
        <div id="layout"></div>
        <div id="mail">
          <div class="pmf-config-item" data-config-key="mail.remoteSMTP">SMTP server address</div>
          <div class="pmf-config-item" data-config-key="mail.remoteSMTPPassword">SMTP password</div>
        </div>
        <div id="api"></div>
        <div id="upgrade">
          <div class="pmf-config-item" data-config-key="upgrade.releaseEnvironment">Release environment</div>
        </div>
        <div id="translation"></div>
        <div id="push"></div>
        <div id="ldap"></div>
      `;
    };

    const triggerFilterAndFlush = async (query: string): Promise<void> => {
      const filterInput = document.getElementById('pmf-configuration-tab-filter') as HTMLInputElement;
      filterInput.value = query;
      filterInput.dispatchEvent(new Event('input'));
      vi.advanceTimersByTime(300);
      await vi.runAllTimersAsync();
    };

    it('should filter tabs by tab label and hide non-matching groups', async () => {
      vi.useFakeTimers();
      buildFilterDOM();

      (fetchConfiguration as Mock).mockResolvedValue('');

      handleConfigurationTabFiltering();

      await triggerFilterAndFlush('main');

      const mainItem = document.querySelector('li.nav-item[data-config-label="Main"]');
      const upgradeItem = document.querySelector('li.nav-item[data-config-label="Upgrade"]');
      const coreGroup = document.querySelector('li.pmf-configuration-group[data-config-group="core"]');
      const maintenanceGroup = document.querySelector('li.pmf-configuration-group[data-config-group="maintenance"]');

      expect(mainItem?.classList.contains('d-none')).toBe(false);
      expect(upgradeItem?.classList.contains('d-none')).toBe(true);
      expect(coreGroup?.classList.contains('d-none')).toBe(false);
      expect(maintenanceGroup?.classList.contains('d-none')).toBe(true);

      vi.useRealTimers();
    });

    it('should show matching config items and hide non-matching ones', async () => {
      vi.useFakeTimers();
      buildFilterDOM();

      (fetchConfiguration as Mock).mockResolvedValue('');

      handleConfigurationTabFiltering();

      await triggerFilterAndFlush('smtp');

      const smtpItem = document.querySelector('.pmf-config-item[data-config-key="mail.remoteSMTP"]');
      const smtpPasswordItem = document.querySelector('.pmf-config-item[data-config-key="mail.remoteSMTPPassword"]');
      const languageItem = document.querySelector('.pmf-config-item[data-config-key="main.language"]');

      expect(smtpItem?.classList.contains('d-none')).toBe(false);
      expect(smtpPasswordItem?.classList.contains('d-none')).toBe(false);
      expect(languageItem?.classList.contains('d-none')).toBe(true);

      vi.useRealTimers();
    });

    it('should hide tabs with zero matching items', async () => {
      vi.useFakeTimers();
      buildFilterDOM();

      (fetchConfiguration as Mock).mockResolvedValue('');

      handleConfigurationTabFiltering();

      await triggerFilterAndFlush('smtp');

      const mainItem = document.querySelector('li.nav-item[data-config-label="Main"]');
      const upgradeItem = document.querySelector('li.nav-item[data-config-label="Upgrade"]');

      expect(mainItem?.classList.contains('d-none')).toBe(true);
      expect(upgradeItem?.classList.contains('d-none')).toBe(true);

      vi.useRealTimers();
    });

    it('should restore all items and tabs when filter is cleared', async () => {
      vi.useFakeTimers();
      buildFilterDOM();

      (fetchConfiguration as Mock).mockResolvedValue('');

      handleConfigurationTabFiltering();

      await triggerFilterAndFlush('smtp');

      await triggerFilterAndFlush('');

      const allItems = document.querySelectorAll('.pmf-config-item');
      allItems.forEach((item) => {
        expect(item.classList.contains('d-none')).toBe(false);
      });

      const allNavItems = document.querySelectorAll('li.nav-item[data-config-group]');
      allNavItems.forEach((item) => {
        expect(item.classList.contains('d-none')).toBe(false);
      });

      const allGroupHeaders = document.querySelectorAll('li.pmf-configuration-group');
      allGroupHeaders.forEach((header) => {
        expect(header.classList.contains('d-none')).toBe(false);
      });

      vi.useRealTimers();
    });
  });
});
