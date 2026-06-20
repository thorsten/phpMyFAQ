import { AutocompleteItem } from 'autocompleter';

export type SuggestionType = 'result' | 'recent' | 'popular' | 'empty';

export interface SuggestionItem {
  type?: SuggestionType;
  url: string;
  question?: string;
  category?: string;
  searchTerm?: string;
  count?: number;
}

export type Suggestion = SuggestionItem & AutocompleteItem;
