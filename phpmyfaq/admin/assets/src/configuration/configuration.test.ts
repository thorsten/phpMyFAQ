import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import {
  handleConfiguration,
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

  describe('handleConfiguration', () => {
    it('should handle configuration tabs and load data', async () => {
      document.body.innerHTML = `
        <div id="configuration-list">
          <a href="#main"></a>
          <a href="#layout"></a>
        </div>
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
});
