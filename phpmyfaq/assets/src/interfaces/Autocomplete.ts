interface AutocompleteSearchResult {
  category: string;
  question: string;
  url: string;
}

export type AutocompleteSearchResponse = AutocompleteSearchResult[];
