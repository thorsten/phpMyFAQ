import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleElasticsearch } from './elasticsearch';
import { fetchElasticsearchAction, fetchElasticsearchStatistics } from '../api/elasticsearch';

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
      `;

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

    it('should handle Elasticsearch statistics update', async () => {
      document.body.innerHTML = `
        <button class="pmf-elasticsearch" data-action="reindex">Reindex</button>
        <div id="pmf-elasticsearch-stats"></div>
      `;

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

      const statsDiv = document.getElementById('pmf-elasticsearch-stats') as HTMLElement;
      expect(statsDiv.innerHTML).toContain('Documents');
      expect(statsDiv.innerHTML).toContain('Storage size');
    });
  });
});
