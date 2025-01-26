export interface ElasticsearchStats {
  indices: {
    [indexName: string]: {
      total: {
        docs: {
          count: number;
        };
        store: {
          size_in_bytes: number;
        };
      };
    };
  };
}

export interface ElasticsearchResponse {
  index: string;
  stats: ElasticsearchStats;
}
