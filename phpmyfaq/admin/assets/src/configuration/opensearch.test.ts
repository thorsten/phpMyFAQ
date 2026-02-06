import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleOpenSearch } from './opensearch';
import { fetchOpenSearchAction, fetchOpenSearchStatistics, fetchOpenSearchHealthcheck } from '../api/opensearch';

vi.mock('../api/opensearch');
vi.mock('../../../../assets/src/utils');

describe('OpenSearch Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleOpenSearch', () => {
    it('should handle OpenSearch button clicks and fetch action', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchOpenSearchAction as Mock).mockResolvedValue({ success: 'Reindexing started' });
      (fetchOpenSearchStatistics as Mock).mockResolvedValue({
        index: 'test-index',
        stats: {
          indices: {
            'test-index': {
              total: {
                docs: { count: 1000 },
                store: { size_in_bytes: 1024 },
              },
            },
          },
        },
      });

      await handleOpenSearch();

      const button = document.querySelector('button.pmf-opensearch') as HTMLButtonElement;
      button.click();

      expect(fetchOpenSearchAction).toHaveBeenCalledWith('reindex');
    });

    it('should handle OpenSearch statistics update when healthy', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchOpenSearchStatistics as Mock).mockResolvedValue({
        index: 'test-index',
        stats: {
          indices: {
            'test-index': {
              total: {
                docs: { count: 1000 },
                store: { size_in_bytes: 1024 },
              },
            },
          },
        },
      });

      await handleOpenSearch();

      // Wait for health check promise to resolve and stats to be fetched
      await new Promise((resolve) => setTimeout(resolve, 10));

      // Verify health check was called
      expect(fetchOpenSearchHealthcheck).toHaveBeenCalled();

      // Stats should be populated after health check completes
      await vi.waitFor(
        () => {
          const statsDiv = document.getElementById('pmf-opensearch-stats') as HTMLElement;
          expect(statsDiv.innerHTML).toContain('Documents');
        },
        { timeout: 1000 }
      );
    });

    it('should display health check alert when OpenSearch is unavailable', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert" style="display: none;"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockRejectedValue(new Error('OpenSearch is unavailable'));

      await handleOpenSearch();

      // Wait for health check promise to resolve
      await new Promise((resolve) => setTimeout(resolve, 10));

      const alertDiv = document.getElementById('pmf-opensearch-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('block');
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('OpenSearch is unavailable');
    });

    it('should hide health check alert when OpenSearch is available', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert" style="display: block;"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchOpenSearchStatistics as Mock).mockResolvedValue({
        index: 'test-index',
        stats: {
          indices: {
            'test-index': {
              total: {
                docs: { count: 1000 },
                store: { size_in_bytes: 1024 },
              },
            },
          },
        },
      });

      await handleOpenSearch();

      // Wait for health check promise to resolve
      await new Promise((resolve) => setTimeout(resolve, 10));

      const alertDiv = document.getElementById('pmf-opensearch-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('none');
    });

    it('should not fetch statistics when OpenSearch is unhealthy', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockRejectedValue(new Error('Service unavailable'));

      await handleOpenSearch();

      // Wait for health check promise to settle
      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchOpenSearchStatistics).not.toHaveBeenCalled();
    });

    it('should show non-Error healthcheck failures as generic message', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert" style="display: none;"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockRejectedValue('string error');

      await handleOpenSearch();

      // Wait for health check promise to resolve
      await new Promise((resolve) => setTimeout(resolve, 10));

      const alertDiv = document.getElementById('pmf-opensearch-healthcheck-alert') as HTMLElement;
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('OpenSearch is unavailable');
    });

    it('should handle error response from OpenSearch action', async () => {
      const { pushErrorNotification } = await import('../../../../assets/src/utils');

      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchOpenSearchAction as Mock).mockResolvedValue({ error: 'Reindex failed' });

      await handleOpenSearch();

      const button = document.querySelector('button.pmf-opensearch') as HTMLButtonElement;
      button.click();

      // Wait for async click handler
      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Reindex failed');
    });

    it('should handle rejected promise from OpenSearch action', async () => {
      const { pushErrorNotification } = await import('../../../../assets/src/utils');

      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
        <div id="pmf-opensearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchOpenSearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchOpenSearchAction as Mock).mockRejectedValue('Network error');

      await handleOpenSearch();

      const button = document.querySelector('button.pmf-opensearch') as HTMLButtonElement;
      button.click();

      // Wait for async click handler
      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(pushErrorNotification).toHaveBeenCalledWith('Network error');
    });

    it('should return false from healthCheckAlert when alert element is missing', async () => {
      document.body.innerHTML = `
        <button class="pmf-opensearch" data-action="reindex">Reindex</button>
        <div id="pmf-opensearch-stats"></div>
      `;

      await handleOpenSearch();

      // Wait for health check promise to settle
      await new Promise((resolve) => setTimeout(resolve, 10));

      // No health check should be called since the alert element is missing
      expect(fetchOpenSearchHealthcheck).not.toHaveBeenCalled();
      expect(fetchOpenSearchStatistics).not.toHaveBeenCalled();
    });
  });
});
