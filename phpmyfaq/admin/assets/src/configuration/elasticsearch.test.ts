import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleElasticsearch } from './elasticsearch';
import {
  fetchElasticsearchAction,
  fetchElasticsearchStatistics,
  fetchElasticsearchHealthcheck,
} from '../api/elasticsearch';

vi.mock('../api/elasticsearch');
vi.mock('../../../../assets/src/utils');

describe('Elasticsearch Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleElasticsearch', () => {
    it('should handle Elasticsearch button clicks and fetch action', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
        <div id="pmf-elasticsearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchElasticsearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchElasticsearchAction as Mock).mockResolvedValue({ success: 'Reindexing started' });
      (fetchElasticsearchStatistics as Mock).mockResolvedValue({
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

      await handleElasticsearch();

      const button = document.querySelector('button.pmf-elasticsearch') as HTMLButtonElement;
      button.click();

      expect(fetchElasticsearchAction).toHaveBeenCalledWith('reindex');
    });

    it('should handle Elasticsearch statistics update when healthy', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
        <div id="pmf-elasticsearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchElasticsearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchElasticsearchStatistics as Mock).mockResolvedValue({
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

      await handleElasticsearch();

      // Wait for health check promise to resolve and stats to be fetched
      await new Promise((resolve) => setTimeout(resolve, 10));

      // Verify health check was called
      expect(fetchElasticsearchHealthcheck).toHaveBeenCalled();

      // Stats should be populated after health check completes
      await vi.waitFor(
        () => {
          const statsDiv = document.getElementById('pmf-elasticsearch-stats') as HTMLElement;
          expect(statsDiv.innerHTML).toContain('Documents');
        },
        { timeout: 1000 }
      );
    });

    it('should display health check alert when Elasticsearch is unavailable', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
        <div id="pmf-elasticsearch-healthcheck-alert" style="display: none;"><span class="alert-message"></span></div>
      `;

      (fetchElasticsearchHealthcheck as Mock).mockRejectedValue(new Error('Elasticsearch is unavailable'));

      await handleElasticsearch();

      const alertDiv = document.getElementById('pmf-elasticsearch-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('block');
      expect(alertDiv.querySelector('.alert-message')?.textContent).toBe('Elasticsearch is unavailable');
    });

    it('should hide health check alert when Elasticsearch is available', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
        <div id="pmf-elasticsearch-healthcheck-alert" style="display: block;"><span class="alert-message"></span></div>
      `;

      (fetchElasticsearchHealthcheck as Mock).mockResolvedValue({ available: true, status: 'healthy' });
      (fetchElasticsearchStatistics as Mock).mockResolvedValue({
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

      await handleElasticsearch();

      const alertDiv = document.getElementById('pmf-elasticsearch-healthcheck-alert') as HTMLElement;
      expect(alertDiv.style.display).toBe('none');
    });

    it('should not fetch statistics when Elasticsearch is unhealthy', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
        <div id="pmf-elasticsearch-healthcheck-alert"><span class="alert-message"></span></div>
      `;

      (fetchElasticsearchHealthcheck as Mock).mockRejectedValue(new Error('Service unavailable'));

      await handleElasticsearch();

      expect(fetchElasticsearchStatistics).not.toHaveBeenCalled();
    });
  });
});
